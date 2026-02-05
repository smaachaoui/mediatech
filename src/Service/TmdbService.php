<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Je gere les appels a l'API TMDB avec mise en cache.
 */
final class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    private const TTL_SEARCH = 1800;
    private const TTL_NOW    = 3600;
    private const TTL_BY_ID  = 86400;
    private const TTL_GENRES = 2592000;

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $tmdbApiKey,
        private readonly CacheItemPoolInterface $cache,
    ) {}

    /**
     * Je recherche des films avec pagination.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    public function search(string $query, int $limit = 12, int $page = 1): array
    {
        $query = trim($query);
        $limit = max(1, min($limit, 20));
        $page = max(1, $page);

        if ($query === '') {
            return ['items' => [], 'total' => 0, 'totalPages' => 0];
        }

        $cacheKey = 'tmdb.search.v2.' . md5($query . '|' . $limit . '|' . $page);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();
            return is_array($cached) ? $cached : ['items' => [], 'total' => 0, 'totalPages' => 0];
        }

        try {
            $result = $this->fetchListWithTotal('/search/movie', [
                'query' => $query,
                'page' => $page,
            ], $limit);

            $item->set($result);
            $item->expiresAfter(self::TTL_SEARCH);
            $this->cache->save($item);

            return $result;
        } catch (TransportExceptionInterface|\RuntimeException) {
            $result = ['items' => [], 'total' => 0, 'totalPages' => 0];
            $item->set($result);
            $item->expiresAfter(300);
            $this->cache->save($item);

            return $result;
        }
    }

    /**
     * Je recupere les films actuellement a l'affiche avec pagination.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    public function nowPlaying(int $limit = 12, int $page = 1): array
    {
        $limit = max(1, min($limit, 20));
        $page = max(1, $page);

        $cacheKey = 'tmdb.now_playing.v2.' . $limit . '|' . $page;
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();
            return is_array($cached) ? $cached : ['items' => [], 'total' => 0, 'totalPages' => 0];
        }

        try {
            $result = $this->fetchListWithTotal('/movie/now_playing', [
                'region' => 'FR',
                'page' => $page,
            ], $limit);

            $item->set($result);
            $item->expiresAfter(self::TTL_NOW);
            $this->cache->save($item);

            return $result;
        } catch (TransportExceptionInterface|\RuntimeException) {
            $result = ['items' => [], 'total' => 0, 'totalPages' => 0];
            $item->set($result);
            $item->expiresAfter(300);
            $this->cache->save($item);

            return $result;
        }
    }

    /**
     * Je recupere les details d'un film.
     *
     * @return array<string, mixed>
     */
    public function getById(int $tmdbId): array
    {
        if ($tmdbId <= 0) {
            throw new \InvalidArgumentException('ID TMDB invalide.');
        }

        $cacheKey = 'tmdb.by_id.v2.' . $tmdbId;
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();
            return is_array($cached) ? $cached : [];
        }

        try {
            $res = $this->http->request('GET', self::BASE_URL . '/movie/' . $tmdbId, [
                'query' => [
                    'api_key' => $this->tmdbApiKey,
                    'language' => 'fr-FR',
                ],
                'timeout' => 5.0,
                'max_duration' => 8.0,
            ]);

            $m = $res->toArray(false);

            if (isset($m['status_code']) && $m['status_code'] !== 200) {
                throw new \RuntimeException('Erreur TMDB');
            }

            $genreMap = $this->getMovieGenreMap();
            $mapped = $this->mapMovie($m, $genreMap);

            $item->set($mapped);
            $item->expiresAfter(self::TTL_BY_ID);
            $this->cache->save($item);

            return $mapped;
        } catch (TransportExceptionInterface|\RuntimeException $e) {
            $item->set([]);
            $item->expiresAfter(300);
            $this->cache->save($item);

            throw $e;
        }
    }

    /**
     * Je recupere une liste avec le total pour la pagination.
     *
     * @param array<string, mixed> $query
     * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    private function fetchListWithTotal(string $endpoint, array $query, int $limit): array
    {
        $res = $this->http->request('GET', self::BASE_URL . $endpoint, [
            'query' => array_merge($query, [
                'api_key' => $this->tmdbApiKey,
                'language' => 'fr-FR',
            ]),
            'timeout' => 5.0,
            'max_duration' => 8.0,
        ]);

        $data = $res->toArray(false);

        $total = isset($data['total_results']) ? (int) $data['total_results'] : 0;
        $totalPages = isset($data['total_pages']) ? (int) $data['total_pages'] : 0;

        if (!isset($data['results']) || !is_array($data['results'])) {
            return ['items' => [], 'total' => $total, 'totalPages' => $totalPages];
        }

        $genreMap = $this->getMovieGenreMap();
        $mapped = [];

        foreach ($data['results'] as $m) {
            if (!is_array($m)) {
                continue;
            }
            $mapped[] = $this->mapMovie($m, $genreMap);
        }

        $mapped = array_slice($mapped, 0, $limit);

        return ['items' => $mapped, 'total' => $total, 'totalPages' => $totalPages];
    }

    /**
     * Je recupere la liste des genres films TMDB et je la mets en cache.
     *
     * @return array<int, string>
     */
    private function getMovieGenreMap(): array
    {
        $cacheKey = 'tmdb.movie_genres.fr.v1';
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();
            return is_array($cached) ? $cached : [];
        }

        try {
            $res = $this->http->request('GET', self::BASE_URL . '/genre/movie/list', [
                'query' => [
                    'api_key' => $this->tmdbApiKey,
                    'language' => 'fr-FR',
                ],
                'timeout' => 5.0,
                'max_duration' => 8.0,
            ]);

            $data = $res->toArray(false);

            $genres = $data['genres'] ?? [];
            if (!is_array($genres)) {
                $genres = [];
            }

            $map = [];

            foreach ($genres as $g) {
                if (!is_array($g)) {
                    continue;
                }
                $id = isset($g['id']) ? (int) $g['id'] : 0;
                $name = isset($g['name']) ? (string) $g['name'] : '';
                if ($id > 0 && $name !== '') {
                    $map[$id] = $name;
                }
            }

            $item->set($map);
            $item->expiresAfter(self::TTL_GENRES);
            $this->cache->save($item);

            return $map;
        } catch (TransportExceptionInterface|\RuntimeException) {
            $item->set([]);
            $item->expiresAfter(600);
            $this->cache->save($item);

            return [];
        }
    }

    /**
     * Je mappe un film TMDB vers un format stable.
     *
     * @param array<string, mixed> $m
     * @param array<int, string> $genreMap
     * @return array<string, mixed>
     */
    private function mapMovie(array $m, array $genreMap): array
    {
        $poster = null;
        if (!empty($m['poster_path'])) {
            $poster = 'https://image.tmdb.org/t/p/w342' . $m['poster_path'];
        }

        $genres = [];

        if (isset($m['genres']) && is_array($m['genres'])) {
            foreach ($m['genres'] as $g) {
                if (!is_array($g)) {
                    continue;
                }
                $name = $g['name'] ?? null;
                if (is_string($name) && $name !== '') {
                    $genres[] = $name;
                }
            }
        } elseif (isset($m['genre_ids']) && is_array($m['genre_ids'])) {
            foreach ($m['genre_ids'] as $id) {
                $gid = is_int($id) ? $id : (int) $id;
                if ($gid > 0 && isset($genreMap[$gid])) {
                    $genres[] = $genreMap[$gid];
                }
            }
        }

        $genres = array_values(array_unique($genres));

        return [
            'id' => (int) ($m['id'] ?? 0),
            'title' => (string) ($m['title'] ?? 'Sans titre'),
            'releaseDate' => $m['release_date'] ?? null,
            'overview' => $m['overview'] ?? null,
            'poster' => $poster,
            'genres' => $genres,
        ];
    }
}

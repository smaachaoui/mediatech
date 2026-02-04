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

        $cacheKey = 'tmdb.search.' . md5($query . '|' . $limit . '|' . $page);
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

        $cacheKey = 'tmdb.now_playing.' . $limit . '|' . $page;
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

        $cacheKey = 'tmdb.by_id.' . $tmdbId;
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

            $mapped = $this->mapMovie($m);

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

        $mapped = [];

        foreach ($data['results'] as $m) {
            if (!is_array($m)) {
                continue;
            }
            $mapped[] = $this->mapMovie($m);
        }

        $mapped = array_slice($mapped, 0, $limit);

        return ['items' => $mapped, 'total' => $total, 'totalPages' => $totalPages];
    }

    /**
     * Je mappe un film TMDB vers un format stable.
     *
     * @param array<string, mixed> $m
     * @return array<string, mixed>
     */
    private function mapMovie(array $m): array
    {
        $poster = null;
        if (!empty($m['poster_path'])) {
            $poster = 'https://image.tmdb.org/t/p/w342' . $m['poster_path'];
        }

        return [
            'id' => (int) ($m['id'] ?? 0),
            'title' => (string) ($m['title'] ?? 'Sans titre'),
            'releaseDate' => $m['release_date'] ?? null,
            'overview' => $m['overview'] ?? null,
            'poster' => $poster,
        ];
    }
}
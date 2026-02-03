<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    // TTLs (secondes)
    private const TTL_SEARCH = 1800;   // 30 min
    private const TTL_NOW    = 3600;   // 1 h
    private const TTL_BY_ID  = 86400;  // 24 h

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $tmdbApiKey,
        private readonly CacheItemPoolInterface $cache,
    ) {}

    /**
     * Recherche de films.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 12): array
    {
        $query = trim($query);
        $limit = max(1, min($limit, 20));

        if ($query === '') {
            return [];
        }

        $cacheKey = 'tmdb.search.' . md5($query . '|' . $limit);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();
            return is_array($cached) ? $cached : [];
        }

        try {
            $data = $this->fetchList('/search/movie', [
                'query' => $query,
                'page' => 1,
            ]);

            $mapped = array_slice($data, 0, $limit);

            $item->set($mapped);
            $item->expiresAfter(self::TTL_SEARCH);
            $this->cache->save($item);

            return $mapped;
        } catch (TransportExceptionInterface|\RuntimeException) {
            $item->set([]);
            $item->expiresAfter(300); // 5 min anti-spam API
            $this->cache->save($item);

            return [];
        }
    }

    /**
     * Films actuellement à l'affiche (France).
     *
     * @return array<int, array<string, mixed>>
     */
    public function nowPlaying(int $limit = 12): array
    {
        $limit = max(1, min($limit, 20));

        $cacheKey = 'tmdb.now_playing.' . $limit;
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();
            return is_array($cached) ? $cached : [];
        }

        try {
            $data = $this->fetchList('/movie/now_playing', [
                'region' => 'FR',
                'page' => 1,
            ]);

            $mapped = array_slice($data, 0, $limit);

            $item->set($mapped);
            $item->expiresAfter(self::TTL_NOW);
            $this->cache->save($item);

            return $mapped;
        } catch (TransportExceptionInterface|\RuntimeException) {
            $item->set([]);
            $item->expiresAfter(300);
            $this->cache->save($item);

            return [];
        }
    }

    /**
     * Détails d'un film.
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
     * @param array<string, mixed> $query
     * @return array<int, array<string, mixed>>
     */
    private function fetchList(string $endpoint, array $query): array
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

        if (!isset($data['results']) || !is_array($data['results'])) {
            return [];
        }

        $mapped = [];

        foreach ($data['results'] as $m) {
            if (!is_array($m)) {
                continue;
            }
            $mapped[] = $this->mapMovie($m);
        }

        return $mapped;
    }

    /**
     * Mapping stable d'un film TMDB.
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

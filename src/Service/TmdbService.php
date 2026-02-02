<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TmdbService
{
    public function __construct(
        private HttpClientInterface $http,
        private string $tmdbApiKey,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function search(string $query, int $limit = 12): array
    {
        $res = $this->http->request('GET', 'https://api.themoviedb.org/3/search/movie', [
            'query' => [
                'api_key' => $this->tmdbApiKey,
                'language' => 'fr-FR',
                'query' => $query,
                'page' => 1,
            ],
        ]);

        $data = $res->toArray(false);
        $results = $data['results'] ?? [];

        $mapped = array_map(static fn(array $m) => [
            'id' => $m['id'] ?? null,
            'title' => $m['title'] ?? 'Sans titre',
            'releaseDate' => $m['release_date'] ?? null,
            'overview' => $m['overview'] ?? null,
            'poster' => isset($m['poster_path']) ? 'https://image.tmdb.org/t/p/w342'.$m['poster_path'] : null,
        ], $results);

        return array_slice($mapped, 0, $limit);
    }

    /** @return array<string, mixed> */
    public function getById(int $tmdbId): array
    {
        $res = $this->http->request('GET', sprintf('https://api.themoviedb.org/3/movie/%d', $tmdbId), [
            'query' => [
                'api_key' => $this->tmdbApiKey,
                'language' => 'fr-FR',
            ],
        ]);

        $m = $res->toArray(false);

        return [
            'id' => $m['id'] ?? $tmdbId,
            'title' => $m['title'] ?? 'Sans titre',
            'releaseDate' => $m['release_date'] ?? null,
            'overview' => $m['overview'] ?? null,
            'poster' => isset($m['poster_path']) ? 'https://image.tmdb.org/t/p/w500'.$m['poster_path'] : null,
            'director' => null, // (option: fetch credits endpoint)
        ];
    }

    /**
     * Je récupère une liste de films actuellement à l'affiche en France.
     *
     * @return array<int, array<string, mixed>>
     */
    public function nowPlaying(int $limit = 12): array
    {
        $res = $this->http->request('GET', 'https://api.themoviedb.org/3/movie/now_playing', [
            'query' => [
                'api_key' => $this->tmdbApiKey,
                'language' => 'fr-FR',
                'region' => 'FR',
                'page' => 1,
            ],
        ]);

        $data = $res->toArray(false);
        $results = $data['results'] ?? [];

        $mapped = array_map(static fn(array $m) => [
            'id' => $m['id'] ?? null,
            'title' => $m['title'] ?? 'Sans titre',
            'releaseDate' => $m['release_date'] ?? null,
            'overview' => $m['overview'] ?? null,
            'poster' => isset($m['poster_path']) ? 'https://image.tmdb.org/t/p/w342' . $m['poster_path'] : null,
        ], $results);

        return array_slice($mapped, 0, $limit);
    }

}

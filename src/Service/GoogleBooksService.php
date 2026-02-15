<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Je gère les appels à l'API Google Books avec mise en cache.
 * J'ai normalisé les données retournées afin de fournir un format stable au reste de l'application.
 */
final class GoogleBooksService
{
    private const BASE_URL = 'https://www.googleapis.com/books/v1/volumes';

    private const TTL_SEARCH = 1800;
    private const TTL_NEWEST = 21600;
    private const TTL_BY_ID = 86400;

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $googleBooksApiKey,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * Je recherche des livres via Google Books avec pagination.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function search(string $query, int $limit = 12, int $page = 1): array
    {
        $query = trim($query);
        $limit = max(1, min($limit, 40));
        $page = max(1, $page);
        $startIndex = ($page - 1) * $limit;

        if ($query === '') {
            return ['items' => [], 'total' => 0];
        }

        $cacheKey = 'gbooks.search.v2.' . md5($query . '|' . $limit . '|' . $page);

        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();

            return is_array($cached) ? $cached : ['items' => [], 'total' => 0];
        }

        try {
            $result = $this->fetchListWithTotal([
                'q' => $query,
                'printType' => 'books',
                'maxResults' => $limit,
                'startIndex' => $startIndex,
            ]);
        } catch (TransportExceptionInterface|\RuntimeException) {
            $result = ['items' => [], 'total' => 0];
        }

        $isEmpty = empty($result['items']);

        $item->set($result);
        $item->expiresAfter($isEmpty ? 120 : self::TTL_SEARCH);
        $this->cache->save($item);

        return $result;
    }

    /**
     * Je récupère les livres les plus récents avec pagination.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function newest(string $subject = 'fiction', int $limit = 12, int $page = 1): array
    {
        $subject = trim($subject) !== '' ? trim($subject) : 'fiction';
        $limit = max(1, min($limit, 40));
        $page = max(1, $page);
        $startIndex = ($page - 1) * $limit;

        $cacheKey = 'gbooks.newest.v2.' . md5($subject . '|' . $limit . '|' . $page);

        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();

            return is_array($cached) ? $cached : ['items' => [], 'total' => 0];
        }

        try {
            $primary = $this->fetchListWithTotal([
                'q' => sprintf('subject:%s', $subject),
                'orderBy' => 'newest',
                'printType' => 'books',
                'langRestrict' => 'fr',
                'maxResults' => $limit,
                'startIndex' => $startIndex,
            ]);

            if (!empty($primary['items'])) {
                $item->set($primary);
                $item->expiresAfter(self::TTL_NEWEST);
                $this->cache->save($item);

                return $primary;
            }

            $fallback = $this->fetchListWithTotal([
                'q' => sprintf('subject:%s', $subject),
                'orderBy' => 'newest',
                'printType' => 'books',
                'maxResults' => $limit,
                'startIndex' => $startIndex,
            ]);

            $isEmpty = empty($fallback['items']);

            $item->set($fallback);
            $item->expiresAfter($isEmpty ? 300 : self::TTL_NEWEST);
            $this->cache->save($item);

            return $fallback;
        } catch (TransportExceptionInterface|\RuntimeException) {
            $result = ['items' => [], 'total' => 0];

            $item->set($result);
            $item->expiresAfter(300);
            $this->cache->save($item);

            return $result;
        }
    }

    /**
     * Je recherche des livres par sujet, en essayant plusieurs variantes pour maximiser les résultats.
     * J'utilise cette méthode quand un filtre par genre est activé côté interface.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function searchBySubject(
        string $subject,
        int $limit = 12,
        int $page = 1,
        bool $newestFirst = false
    ): array {
        $subject = trim($subject);
        $limit = max(1, min($limit, 40));
        $page = max(1, $page);
        $startIndex = ($page - 1) * $limit;

        if ($subject === '') {
            return $this->newest('fiction', $limit, $page);
        }

        $variants = $this->createSubjectVariants($subject);

        $cacheKey = 'gbooks.subject.v3.' . md5(
            implode('|', $variants) . '|' . $limit . '|' . $page . '|' . ($newestFirst ? '1' : '0')
        );

        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();

            return is_array($cached) ? $cached : ['items' => [], 'total' => 0];
        }

        try {
            $mainVariant = $variants[0];

            $queryParams = [
                'q' => sprintf('subject:%s', $mainVariant),
                'printType' => 'books',
                'langRestrict' => 'fr',
                'maxResults' => $limit,
                'startIndex' => $startIndex,
            ];

            if ($newestFirst) {
                $queryParams['orderBy'] = 'newest';
            }

            $result = $this->fetchListWithTotal($queryParams);

            if (count($result['items']) >= $limit || count($variants) === 1) {
                $item->set($result);
                $item->expiresAfter(self::TTL_SEARCH);
                $this->cache->save($item);

                return $result;
            }

            $allItems = $result['items'];
            $seenIds = array_column($allItems, 'id');

            foreach (array_slice($variants, 1) as $variant) {
                if (count($allItems) >= $limit) {
                    break;
                }

                $queryParams['q'] = sprintf('subject:%s', $variant);
                $queryParams['maxResults'] = $limit - count($allItems);
                $queryParams['startIndex'] = 0;

                $additional = $this->fetchListWithTotal($queryParams);

                foreach ($additional['items'] as $book) {
                    $bookId = $book['id'] ?? null;

                    if (!is_string($bookId) || $bookId === '') {
                        continue;
                    }

                    if (!in_array($bookId, $seenIds, true)) {
                        $allItems[] = $book;
                        $seenIds[] = $bookId;

                        if (count($allItems) >= $limit) {
                            break;
                        }
                    }
                }
            }

            $finalResult = [
                'items' => array_slice($allItems, 0, $limit),
                'total' => $result['total'],
            ];

            $item->set($finalResult);
            $item->expiresAfter(self::TTL_SEARCH);
            $this->cache->save($item);

            return $finalResult;
        } catch (TransportExceptionInterface|\RuntimeException) {
            $result = ['items' => [], 'total' => 0];

            $item->set($result);
            $item->expiresAfter(300);
            $this->cache->save($item);

            return $result;
        }
    }

    /**
     * Je crée des variantes d'un sujet pour améliorer les correspondances.
     * J'ai ajouté quelques mappings simples afin d'aider pour les genres courants.
     *
     * @return array<int, string>
     */
    private function createSubjectVariants(string $subject): array
    {
        $variants = [$subject];

        $normalized = str_replace(['-', '_'], ' ', $subject);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim((string) $normalized);

        if ($normalized !== '' && $normalized !== $subject) {
            $variants[] = $normalized;
        }

        $mappings = [
            'science-fiction' => 'science fiction',
            'science fiction' => 'fiction / science fiction',
            'fantastique' => 'fantasy',
            'policier' => 'mystery',
            'thriller' => 'thriller',
            'romance' => 'romance',
            'horreur' => 'horror',
            'aventure' => 'adventure',
            'biographie' => 'biography',
            'histoire' => 'history',
            'poésie' => 'poetry',
            'theatre' => 'drama',
            'essai' => 'essays',
            'jeunesse' => 'juvenile fiction',
        ];

        $lowerSubject = strtolower($subject);

        if (isset($mappings[$lowerSubject])) {
            $variants[] = $mappings[$lowerSubject];
        }

        return array_values(array_unique($variants));
    }

    /**
     * Je récupère les détails d'un livre via son ID Google Books.
     * J'ai mis en cache le résultat afin d'éviter de solliciter l'API pour chaque affichage de fiche.
     *
     * @return array<string, mixed>
     */
    public function getById(string $googleBooksId): array
    {
        $googleBooksId = trim($googleBooksId);

        if ($googleBooksId === '') {
            throw new \InvalidArgumentException('Google Books ID invalide.');
        }

        $cacheKey = 'gbooks.by_id.v2.' . md5($googleBooksId);

        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();

            return is_array($cached) ? $cached : [];
        }

        $url = sprintf('%s/%s', self::BASE_URL, urlencode($googleBooksId));

        try {
            $res = $this->http->request('GET', $url, [
                'query' => [
                    'key' => $this->googleBooksApiKey,
                ],
                'timeout' => 5.0,
                'max_duration' => 8.0,
            ]);

            $row = $res->toArray(false);

            if (isset($row['error'])) {
                $message = (string) ($row['error']['message'] ?? 'Google Books error');
                throw new \RuntimeException($message);
            }

            $info = is_array($row['volumeInfo'] ?? null) ? $row['volumeInfo'] : [];
            $mapped = $this->mapItem($row, $info, $googleBooksId);

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
     * Je récupère une liste avec son total pour la pagination.
     *
     * @param array<string, mixed> $query
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    private function fetchListWithTotal(array $query): array
    {
        $query['key'] = $this->googleBooksApiKey;

        $res = $this->http->request('GET', self::BASE_URL, [
            'query' => $query,
            'timeout' => 5.0,
            'max_duration' => 8.0,
        ]);

        $data = $res->toArray(false);

        if (isset($data['error'])) {
            $message = (string) ($data['error']['message'] ?? 'Google Books error');
            throw new \RuntimeException($message);
        }

        $total = isset($data['totalItems']) ? (int) $data['totalItems'] : 0;

        $items = $data['items'] ?? [];

        if (!is_array($items)) {
            return ['items' => [], 'total' => $total];
        }

        $mapped = [];

        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }

            $info = $row['volumeInfo'] ?? [];

            if (!is_array($info)) {
                $info = [];
            }

            $mapped[] = $this->mapItem($row, $info);
        }

        return ['items' => $mapped, 'total' => $total];
    }

    /**
     * Je mappe un item Google Books vers un format stable.
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $info
     *
     * @return array<string, mixed>
     */
    private function mapItem(array $row, array $info, ?string $fallbackId = null): array
    {
        $id = (string) ($row['id'] ?? $fallbackId ?? '');

        $thumbnail = null;

        if (isset($info['imageLinks']) && is_array($info['imageLinks'])) {
            $thumb = $info['imageLinks']['thumbnail'] ?? null;
            $small = $info['imageLinks']['smallThumbnail'] ?? null;

            if (is_string($thumb) && $thumb !== '') {
                $thumbnail = $this->normalizeBookCoverUrl($id, $thumb);
            } elseif (is_string($small) && $small !== '') {
                $thumbnail = $this->normalizeBookCoverUrl($id, $small);
            }
        }

        if ($thumbnail === null && $id !== '') {
            $thumbnail = $this->buildStableGoogleBooksCoverUrl($id);
        }

        $authors = $info['authors'] ?? [];

        if (!is_array($authors)) {
            $authors = [];
        }

        $categories = $info['categories'] ?? [];

        if (!is_array($categories)) {
            $categories = [];
        }

        return [
            'id' => $id,
            'title' => (string) ($info['title'] ?? 'Sans titre'),
            'authors' => $authors,
            'publisher' => isset($info['publisher']) ? (string) $info['publisher'] : null,
            'publishedDate' => isset($info['publishedDate']) ? (string) $info['publishedDate'] : null,
            'description' => isset($info['description']) ? (string) $info['description'] : null,
            'thumbnail' => $thumbnail,
            'pageCount' => isset($info['pageCount']) ? (int) $info['pageCount'] : null,
            'isbn' => self::extractIsbn($info['industryIdentifiers'] ?? []),
            'categories' => $categories,
        ];
    }

    private function normalizeBookCoverUrl(string $googleBooksId, string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $url = str_replace('http://', 'https://', $url);

        if ($googleBooksId !== '' && str_contains($url, 'books.google.') && str_contains($url, '/books/content')) {
            return $this->buildStableGoogleBooksCoverUrl($googleBooksId);
        }

        if ($googleBooksId !== '' && str_contains($url, 'imgtk=')) {
            return $this->buildStableGoogleBooksCoverUrl($googleBooksId);
        }

        return $url;
    }

    private function buildStableGoogleBooksCoverUrl(string $googleBooksId): string
    {
        return sprintf(
            'https://books.google.com/books/content?id=%s&printsec=frontcover&img=1&zoom=1&source=gbs_api',
            urlencode($googleBooksId)
        );
    }

    private static function extractIsbn(mixed $ids): ?string
    {
        if (!is_array($ids)) {
            return null;
        }

        foreach ($ids as $id) {
            if (!is_array($id)) {
                continue;
            }

            if (($id['type'] ?? '') === 'ISBN_13') {
                $val = $id['identifier'] ?? null;

                return is_string($val) ? $val : null;
            }
        }

        foreach ($ids as $id) {
            if (!is_array($id)) {
                continue;
            }

            if (($id['type'] ?? '') === 'ISBN_10') {
                $val = $id['identifier'] ?? null;

                return is_string($val) ? $val : null;
            }
        }

        return null;
    }
}

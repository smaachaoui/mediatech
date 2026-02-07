<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Je gere les appels a l'API Google Books avec mise en cache.
 */
final class GoogleBooksService
{
    private const BASE_URL = 'https://www.googleapis.com/books/v1/volumes';

    private const TTL_SEARCH = 1800;
    private const TTL_NEWEST = 21600;
    private const TTL_BY_ID  = 86400;

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $googleBooksApiKey,
        private readonly CacheItemPoolInterface $cache,
    ) {}

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
     * Je recupere les livres les plus recents avec pagination.
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
     * Je recupere les details d'un livre via son ID Google Books.
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
     * Je recupere une liste avec le total pour la pagination.
     *
     * @param array<string, mixed> $query
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

        // Si c’est déjà une URL Google Books "content", je la remplace par une version stable.
        if (str_contains($url, 'books.google.') && str_contains($url, '/books/content')) {
            if ($googleBooksId !== '') {
                return $this->buildStableGoogleBooksCoverUrl($googleBooksId);
            }
        }

        // Si c’est une URL tokenisée (imgtk), je préfère aussi une URL stable.
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



    /**
     * @param mixed $ids
     */
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

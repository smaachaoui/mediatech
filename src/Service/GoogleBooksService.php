<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleBooksService
{
    private const BASE_URL = 'https://www.googleapis.com/books/v1/volumes';

    // TTLs (en secondes)
    private const TTL_SEARCH = 1800;  // 30 min
    private const TTL_NEWEST = 21600; // 6 h
    private const TTL_BY_ID  = 86400; // 24 h

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $googleBooksApiKey,
        private readonly CacheItemPoolInterface $cache,
    ) {}

    /**
     * Recherche de livres via Google Books.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 12): array
    {
        $query = trim($query);
        $limit = max(1, min($limit, 40)); // Google maxResults <= 40

        if ($query === '') {
            return [];
        }

        $cacheKey = 'gbooks.search.' . md5($query . '|' . $limit);

        $item = $this->cache->getItem($cacheKey);
        if ($item->isHit()) {
            $cached = $item->get();
            return is_array($cached) ? $cached : [];
        }

        try {
            $data = $this->fetchList([
                'q' => $query,
                'printType' => 'books',
                'maxResults' => $limit,
            ]);
        } catch (TransportExceptionInterface|\RuntimeException) {
            $data = [];
        }

        $item->set($data);
        $item->expiresAfter(self::TTL_SEARCH);
        $this->cache->save($item);

        return $data;
    }

    /**
     * Flux "newest" par sujet. On tente FR, puis fallback sans restriction.
     *
     * @return array<int, array<string, mixed>>
     */
    public function newest(string $subject = 'fiction', int $limit = 12): array
    {
        $subject = trim($subject) !== '' ? trim($subject) : 'fiction';
        $limit = max(1, min($limit, 40));

        $cacheKey = 'gbooks.newest.' . md5($subject . '|' . $limit);

        $item = $this->cache->getItem($cacheKey);
        if ($item->isHit()) {
            $cached = $item->get();
            return is_array($cached) ? $cached : [];
        }

        try {
            $primary = $this->fetchList([
                'q' => sprintf('subject:%s', $subject),
                'orderBy' => 'newest',
                'printType' => 'books',
                'langRestrict' => 'fr',
                'maxResults' => $limit,
            ]);

            if (!empty($primary)) {
                $item->set($primary);
                $item->expiresAfter(self::TTL_NEWEST);
                $this->cache->save($item);

                return $primary;
            }

            $fallback = $this->fetchList([
                'q' => sprintf('subject:%s', $subject),
                'orderBy' => 'newest',
                'printType' => 'books',
                'maxResults' => $limit,
            ]);

            $item->set($fallback);
            $item->expiresAfter(self::TTL_NEWEST);
            $this->cache->save($item);

            return $fallback;
        } catch (TransportExceptionInterface|\RuntimeException) {
            // Si erreur réseau/quota : renvoyer vide (le controller décidera du message)
            $item->set([]);
            $item->expiresAfter(300); // 5 min (évite de spammer l’API en boucle)
            $this->cache->save($item);

            return [];
        }
    }

    /**
     * Détails d'un livre via son ID Google Books.
     *
     * @return array<string, mixed>
     */
    public function getById(string $googleBooksId): array
    {
        $googleBooksId = trim($googleBooksId);
        if ($googleBooksId === '') {
            throw new \InvalidArgumentException('Google Books ID invalide.');
        }

        $cacheKey = 'gbooks.by_id.' . md5($googleBooksId);

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
                // robustesse réseau
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
            // Pas de cache long terme sur erreur, mais évite de marteler l’API
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
    private function fetchList(array $query): array
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

        $items = $data['items'] ?? [];
        if (!is_array($items)) {
            return [];
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

        return $mapped;
    }

    /**
     * Mappe un item Google Books vers un format stable utilisé par les templates.
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $info
     * @return array<string, mixed>
     */
    private function mapItem(array $row, array $info, ?string $fallbackId = null): array
    {
        $thumbnail = null;

        if (isset($info['imageLinks']) && is_array($info['imageLinks'])) {
            $thumb = $info['imageLinks']['thumbnail'] ?? null;
            if (is_string($thumb) && $thumb !== '') {
                // Force https
                $thumbnail = str_replace('http://', 'https://', $thumb);
            }
        }

        $authors = $info['authors'] ?? [];
        if (!is_array($authors)) {
            $authors = [];
        }

        return [
            'id' => (string) ($row['id'] ?? $fallbackId ?? ''),
            'title' => (string) ($info['title'] ?? 'Sans titre'),
            'authors' => $authors,
            'publisher' => isset($info['publisher']) ? (string) $info['publisher'] : null,
            'publishedDate' => isset($info['publishedDate']) ? (string) $info['publishedDate'] : null,
            'description' => isset($info['description']) ? (string) $info['description'] : null,
            'thumbnail' => $thumbnail,
            'pageCount' => isset($info['pageCount']) ? (int) $info['pageCount'] : null,
            'isbn' => self::extractIsbn($info['industryIdentifiers'] ?? []),
        ];
    }

    /**
     * @param mixed $ids
     */
    private static function extractIsbn(mixed $ids): ?string
    {
        if (!is_array($ids)) {
            return null;
        }

        // Recherche ISBN_13
        foreach ($ids as $id) {
            if (!is_array($id)) {
                continue;
            }
            if (($id['type'] ?? '') === 'ISBN_13') {
                $val = $id['identifier'] ?? null;
                return is_string($val) ? $val : null;
            }
        }

        // Fallback ISBN_10
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

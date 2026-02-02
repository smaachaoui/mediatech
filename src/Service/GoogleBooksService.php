<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleBooksService
{
    public function __construct(
        private HttpClientInterface $http,
        private string $googleBooksApiKey,
    ) {}

    /**
     * Je recherche des livres via Google Books.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 12): array
    {
        return $this->fetchList([
            'q' => $query,
            'maxResults' => $limit,
        ]);
    }

    /**
     * Je récupère un flux de livres récents via Google Books.
     *
     * Je tente d'abord une requête en français. Si je n'ai aucun résultat, je fais un fallback sans restriction.
     *
     * @return array<int, array<string, mixed>>
     */
    public function newest(string $subject = 'fiction', int $limit = 12): array
    {
        $primary = $this->fetchList([
            'q' => sprintf('subject:%s', $subject),
            'orderBy' => 'newest',
            'printType' => 'books',
            'langRestrict' => 'fr',
            'maxResults' => $limit,
        ]);

        if (!empty($primary)) {
            return $primary;
        }

        return $this->fetchList([
            'q' => sprintf('subject:%s', $subject),
            'orderBy' => 'newest',
            'printType' => 'books',
            'maxResults' => $limit,
        ]);
    }

    /**
     * Je récupère les détails d'un livre via son ID Google Books.
     *
     * @return array<string, mixed>
     */
    public function getById(string $googleBooksId): array
    {
        $url = sprintf('https://www.googleapis.com/books/v1/volumes/%s', urlencode($googleBooksId));
        $res = $this->http->request('GET', $url, [
            'query' => [
                'key' => $this->googleBooksApiKey,
            ],
        ]);

        $row = $res->toArray(false);

        if (isset($row['error'])) {
            $message = (string) ($row['error']['message'] ?? 'Google Books error');
            throw new \RuntimeException($message);
        }

        $info = $row['volumeInfo'] ?? [];

        return $this->mapItem($row, $info, $googleBooksId);
    }

    /**
     * Je récupère une liste de volumes et je les mappe dans un format homogène.
     *
     * @param array<string, mixed> $query
     * @return array<int, array<string, mixed>>
     */
    private function fetchList(array $query): array
    {
        $query['key'] = $this->googleBooksApiKey;

        $res = $this->http->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
            'query' => $query,
        ]);

        $data = $res->toArray(false);

        if (isset($data['error'])) {
            $message = (string) ($data['error']['message'] ?? 'Google Books error');
            throw new \RuntimeException($message);
        }

        $items = $data['items'] ?? [];

        return array_map(function (array $row): array {
            $info = $row['volumeInfo'] ?? [];
            return $this->mapItem($row, $info);
        }, $items);
    }

    /**
     * Je mappe un item Google Books vers un format stable utilisé par les templates.
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $info
     * @return array<string, mixed>
     */
    private function mapItem(array $row, array $info, ?string $fallbackId = null): array
    {
        $thumbnail = $info['imageLinks']['thumbnail'] ?? null;
        if (is_string($thumbnail)) {
            $thumbnail = str_replace('http://', 'https://', $thumbnail);
        }

        return [
            'id' => $row['id'] ?? $fallbackId,
            'title' => $info['title'] ?? 'Sans titre',
            'authors' => $info['authors'] ?? [],
            'publisher' => $info['publisher'] ?? null,
            'publishedDate' => $info['publishedDate'] ?? null,
            'description' => $info['description'] ?? null,
            'thumbnail' => $thumbnail,
            'pageCount' => $info['pageCount'] ?? null,
            'isbn' => self::extractIsbn($info['industryIdentifiers'] ?? []),
        ];
    }

    /**
     * @param array<int, array<string, string>> $ids
     */
    private static function extractIsbn(array $ids): ?string
    {
        foreach ($ids as $id) {
            if (($id['type'] ?? '') === 'ISBN_13') {
                return $id['identifier'] ?? null;
            }
        }

        foreach ($ids as $id) {
            if (($id['type'] ?? '') === 'ISBN_10') {
                return $id['identifier'] ?? null;
            }
        }

        return null;
    }
}

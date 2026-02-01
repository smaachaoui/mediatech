<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleBooksService
{
    public function __construct(
        private HttpClientInterface $http,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function search(string $query, int $limit = 12): array
    {
        $url = 'https://www.googleapis.com/books/v1/volumes';
        $res = $this->http->request('GET', $url, [
            'query' => [
                'q' => $query,
                'maxResults' => $limit,
            ],
        ]);

        $data = $res->toArray(false);
        $items = $data['items'] ?? [];

        return array_map(static function (array $row): array {
            $info = $row['volumeInfo'] ?? [];
            return [
                'id' => $row['id'] ?? null,
                'title' => $info['title'] ?? 'Sans titre',
                'authors' => $info['authors'] ?? [],
                'publisher' => $info['publisher'] ?? null,
                'publishedDate' => $info['publishedDate'] ?? null,
                'description' => $info['description'] ?? null,
                'thumbnail' => $info['imageLinks']['thumbnail'] ?? null,
                'pageCount' => $info['pageCount'] ?? null,
                'isbn' => self::extractIsbn($info['industryIdentifiers'] ?? []),
            ];
        }, $items);
    }

    /** @return array<string, mixed> */
    public function getById(string $googleBooksId): array
    {
        $url = sprintf('https://www.googleapis.com/books/v1/volumes/%s', urlencode($googleBooksId));
        $res = $this->http->request('GET', $url);

        $row = $res->toArray(false);
        $info = $row['volumeInfo'] ?? [];

        return [
            'id' => $row['id'] ?? $googleBooksId,
            'title' => $info['title'] ?? 'Sans titre',
            'authors' => $info['authors'] ?? [],
            'publisher' => $info['publisher'] ?? null,
            'publishedDate' => $info['publishedDate'] ?? null,
            'description' => $info['description'] ?? null,
            'thumbnail' => $info['imageLinks']['thumbnail'] ?? null,
            'pageCount' => $info['pageCount'] ?? null,
            'isbn' => self::extractIsbn($info['industryIdentifiers'] ?? []),
        ];
    }

    /** @param array<int, array<string, string>> $ids */
    private static function extractIsbn(array $ids): ?string
    {
        foreach ($ids as $id) {
            if (($id['type'] ?? '') === 'ISBN_13') return $id['identifier'] ?? null;
        }
        foreach ($ids as $id) {
            if (($id['type'] ?? '') === 'ISBN_10') return $id['identifier'] ?? null;
        }
        return null;
    }
}

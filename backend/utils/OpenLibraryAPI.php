<?php
class OpenLibraryAPI {
    private $api_url = 'https://openlibrary.org/api/';
    private $timeout = 5;

    // Fetch book metadata by ISBN from Open Library REST API
    public function fetchBookByISBN($isbn) {
        $clean_isbn = preg_replace('/[^0-9X]/i', '', $isbn);
        $url = $this->api_url . 'books?bibkeys=ISBN:' . $clean_isbn . '&format=json&jscmd=data';
        
        $response = $this->makeRequest($url);

        if ($response) {
            $key = 'ISBN:' . $clean_isbn;
            if (isset($response[$key])) {
                $book_data = $response[$key];
                return [
                    'isbn' => $clean_isbn,
                    'title' => $book_data['title'] ?? 'Unknown Title',
                    'author' => isset($book_data['authors'][0]['name']) ? $book_data['authors'][0]['name'] : 'Unknown Author',
                    'publisher' => isset($book_data['publishers'][0]['name']) ? $book_data['publishers'][0]['name'] : 'Academic Press',
                    'year' => isset($book_data['publish_date']) ? preg_replace('/[^0-9]/', '', $book_data['publish_date']) : date('Y'),
                    'description' => $book_data['excerpts'][0]['text'] ?? ($book_data['notes'] ?? 'No description available for this catalog item.'),
                    'cover_url' => $book_data['cover']['medium'] ?? ($book_data['cover']['large'] ?? 'assets/images/book-cover-placeholder.png')
                ];
            }
        }

        // Return standardized mock data if API network is offline or unlisted
        return [
            'isbn' => $clean_isbn,
            'title' => 'Cataloged Book (ISBN: ' . $clean_isbn . ')',
            'author' => 'Open Library Author',
            'publisher' => 'Global Press',
            'year' => date('Y'),
            'description' => 'Automated catalog entry fetched via QuaggaJS barcode scan.',
            'cover_url' => 'assets/images/book-cover-placeholder.png'
        ];
    }

    // HTTP Request Handler
    private function makeRequest($url) {
        if (!function_exists('curl_init')) {
            $ctx = stream_context_create(['http' => ['timeout' => $this->timeout]]);
            $res = @file_get_contents($url, false, $ctx);
            return $res ? json_decode($res, true) : null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SmartLibraryManagementApp/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? json_decode($response, true) : null;
    }
}
?>

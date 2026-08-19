<?php
class RSSParser {
    private $feeds = [
        'https://feeds.theguardian.com/theguardian/books/rss',
        'https://www.goodreads.com/news/rss'
    ];

    // Fetch RSS XML feed items
    public function fetchTrendingBooks() {
        $trending = [];
        foreach ($this->feeds as $feed_url) {
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 3]]);
                $content = @file_get_contents($feed_url, false, $ctx);
                if ($content) {
                    $xml = @simplexml_load_string($content);
                    if ($xml && isset($xml->channel->item)) {
                        foreach ($xml->channel->item as $item) {
                            $trending[] = [
                                'title' => (string)$item->title,
                                'description' => strip_tags((string)$item->description),
                                'link' => (string)$item->link,
                                'pubDate' => (string)$item->pubDate
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignore feed errors gracefully
            }
        }

        // Return fallback trending news items if external XML feeds are unreachable
        if (empty($trending)) {
            return [
                [
                    'title' => 'Top Engineering & Computer Science Publications for 2026',
                    'description' => 'Discover the latest releases in artificial intelligence, distributed systems, and quantum computing.',
                    'link' => 'https://openlibrary.org',
                    'pubDate' => date('r')
                ],
                [
                    'title' => 'National Reading Month: Free Access to Digital Research Papers',
                    'description' => 'The library portal has expanded access to digital journals and academic research databases.',
                    'link' => 'https://openlibrary.org',
                    'pubDate' => date('r')
                ]
            ];
        }

        return $trending;
    }

    public function getTrendingBooksForDashboard($limit = 5) {
        $trending = $this->fetchTrendingBooks();
        return array_slice($trending, 0, $limit);
    }
}
?>

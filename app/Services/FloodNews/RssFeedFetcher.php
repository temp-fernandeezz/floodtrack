<?php

namespace App\Services\FloodNews;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Busca e faz parse de um feed RSS, devolvendo uma lista de NewsItem já normalizados.
 */
class RssFeedFetcher
{
    /**
     * @return NewsItem[]
     */
    public function fetch(string $rssUrl): array
    {
        try {
            $response = Http::timeout(15)->retry(2, 500)->get($rssUrl);
        } catch (\Throwable $e) {
            Log::warning("Falha de rede ao buscar RSS: {$rssUrl}", ['error' => $e->getMessage()]);

            return [];
        }

        if (! $response->ok()) {
            Log::warning("Erro ao buscar RSS: {$rssUrl}", ['status' => $response->status()]);

            return [];
        }

        $xml = @simplexml_load_string($response->body());

        if (! $xml || ! isset($xml->channel->item)) {
            Log::warning("RSS inválido ou sem itens: {$rssUrl}");

            return [];
        }

        $items = [];

        foreach ($xml->channel->item as $item) {
            $title = trim((string) $item->title);
            $link  = trim((string) $item->link);

            if ($title === '' || $link === '') {
                continue;
            }

            $summary = strip_tags((string) $item->description);
            $pubDate = isset($item->pubDate) ? $this->parseDate((string) $item->pubDate) : null;

            $items[] = new NewsItem($title, $link, $summary, $pubDate, json_encode($item));
        }

        return $items;
    }

    private function parseDate(string $value): ?\DateTimeInterface
    {
        try {
            return new \DateTime($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

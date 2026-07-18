<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Services\FloodNews\FloodLocationParser;
use App\Services\FloodNews\FloodNewsRelevanceFilter;
use App\Services\FloodNews\FloodPointImporter;
use App\Services\FloodNews\NewsItem;
use App\Services\FloodNews\RssFeedFetcher;
use App\Services\ScraperHealthRecorder;
use App\Settings\FloodTrackScraperSettings;
use Illuminate\Console\Command;

class FetchFloodNews extends Command
{
    protected $signature = 'flood:fetch-news';

    protected $description = 'Busca noticias RSS sobre alagamentos e cria pontos sugeridos';

    private const DEFAULT_RSS_URLS = [
        'https://g1.globo.com/rss/g1/',
    ];

    public function handle(
        FloodTrackScraperSettings $scraperSettings,
        RssFeedFetcher $rssFetcher,
        FloodNewsRelevanceFilter $relevanceFilter,
        FloodLocationParser $locationParser,
        FloodPointImporter $importer,
        ScraperHealthRecorder $healthRecorder,
    ): int {
        $rssUrls = $scraperSettings->rss_urls ?: self::DEFAULT_RSS_URLS;

        $imported = 0;
        $totalItemsSeen = 0;

        foreach ($rssUrls as $rssUrl) {
            $this->info("Buscando RSS: {$rssUrl}");

            $items = $rssFetcher->fetch($rssUrl);

            if (empty($items)) {
                $this->warn("Nenhum item aproveitável em: {$rssUrl}");
                continue;
            }

            foreach ($items as $item) {
                $totalItemsSeen++;

                if (! $relevanceFilter->isAllowedBySettings($item->link, $scraperSettings)) {
                    continue;
                }

                if (! $relevanceFilter->isRelevant($item)) {
                    continue;
                }

                // Deduplicação — salva o artigo ANTES de tentar geocoding.
                // Isso evita que o mesmo item seja retentado infinitamente quando geocoding falha.
                if (NewsArticle::where('url', $item->link)->exists()) {
                    continue;
                }

                $article = $this->storeArticle($item, $relevanceFilter->detectSource($item->link));

                $location = $locationParser->extract($item);

                if (! $location->isRelevantOccurrence()) {
                    $article->update([
                        'discard_reason' => 'A IA julgou que a notícia não relata uma ocorrência real de alagamento (menciona a palavra de passagem, mas trata de outro assunto).',
                        'discarded_at'   => now(),
                    ]);
                    $this->warn("Descartado (IA julgou não ser uma ocorrência real): {$item->title}");
                    continue;
                }

                if (! $location->hasIdentifiableCity()) {
                    $this->warn("Sem cidade identificável: {$item->title}");
                }

                $floodPoint = $importer->import(
                    item: $item,
                    location: $location,
                    sourceType: 'news',
                    sourceUrl: $item->link,
                    publishedAt: $article->published_at,
                    dedupWindowHours: $scraperSettings->dedup_window_hours,
                );

                if (! $floodPoint->wasRecentlyCreated) {
                    $this->info("Consolidado em ponto existente #{$floodPoint->id} ({$floodPoint->merged_sources_count} fontes): {$item->title}");
                } elseif ($floodPoint->review_status === 'approved') {
                    $imported++;
                    $this->info("Importado: {$item->title}");
                } else {
                    $this->warn("Sem coordenadas (ponto pendente): {$item->title}");
                }
            }
        }

        $healthRecorder->recordFetch($imported, $totalItemsSeen);

        $this->info("Concluído! {$imported} notícia(s) com coordenadas importadas.");

        return self::SUCCESS;
    }

    private function storeArticle(NewsItem $item, string $source): NewsArticle
    {
        return NewsArticle::create([
            'source'       => $source,
            'url'          => $item->link,
            'title'        => $item->title,
            'summary'      => $item->summary,
            'published_at' => $item->publishedAt?->format('Y-m-d H:i:s'),
            'raw'          => $item->rawJson,
        ]);
    }
}

<?php

namespace App\Services\FloodNews;

/**
 * Representa um item de RSS já normalizado (título, link, resumo em texto puro e data).
 */
final class NewsItem
{
    public function __construct(
        public readonly string $title,
        public readonly string $link,
        public readonly string $summary,
        public readonly ?\DateTimeInterface $publishedAt,
        /** JSON do item de RSS original, para depuração/auditoria. */
        public readonly ?string $rawJson = null,
    ) {
    }

    public function fullText(): string
    {
        return trim($this->title . ' ' . $this->summary);
    }
}

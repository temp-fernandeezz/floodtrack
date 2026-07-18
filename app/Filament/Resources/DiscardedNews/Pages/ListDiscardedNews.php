<?php

namespace App\Filament\Resources\DiscardedNews\Pages;

use App\Filament\Resources\DiscardedNews\DiscardedNewsResource;
use Filament\Resources\Pages\ListRecords;

class ListDiscardedNews extends ListRecords
{
    protected static string $resource = DiscardedNewsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

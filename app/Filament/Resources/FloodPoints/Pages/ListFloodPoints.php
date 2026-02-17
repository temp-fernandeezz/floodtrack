<?php

namespace App\Filament\Resources\FloodPoints\Pages;

use App\Filament\Resources\FloodPoints\FloodPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFloodPoints extends ListRecords
{
    protected static string $resource = FloodPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

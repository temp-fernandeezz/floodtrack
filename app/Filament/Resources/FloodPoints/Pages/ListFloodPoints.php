<?php

namespace App\Filament\Resources\FloodPoints\Pages;

use App\Filament\Resources\FloodPoints\FloodPointResource;
use App\Filament\Widgets\FloodPointsMiniMapWidget;
use App\Filament\Widgets\PointsByNivelWidget;
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

    protected function getHeaderWidgets(): array
    {
        return [
            // PointsByNivelWidget::class,
            // FloodPointsMiniMapWidget::class,
        ];
    }
}

<?php

namespace App\Filament\Resources\FloodPoints\Pages;

use App\Filament\Resources\FloodPoints\FloodPointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFloodPoint extends EditRecord
{
    protected static string $resource = FloodPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

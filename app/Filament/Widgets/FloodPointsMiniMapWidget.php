<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Preview rápido dos pontos ativos direto na listagem — sem precisar abrir o mapa público
 * pra ter uma noção visual de onde as ocorrências estão concentradas.
 */
class FloodPointsMiniMapWidget extends Widget
{
    protected string $view = 'filament.widgets.flood-points-mini-map';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';
}

<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class InfoSesionWidget extends Widget
{
    protected string $view = 'filament.widgets.info-sesion-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getViewData(): array
    {
        return [
            'usuario' => auth()->user(),
            'fecha' => now()->format('d/m/Y'),
            'hora' => now()->format('H:i:s'),
        ];
    }
}
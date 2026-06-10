<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVenta extends ViewRecord
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
{
    return [
        EditAction::make()
            ->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
    ];
}
}

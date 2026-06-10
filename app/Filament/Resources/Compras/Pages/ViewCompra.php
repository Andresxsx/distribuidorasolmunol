<?php

namespace App\Filament\Resources\Compras\Pages;

use App\Filament\Resources\Compras\CompraResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompra extends ViewRecord
{
    protected static string $resource = CompraResource::class;

    protected function getHeaderActions(): array
{
    return [
        EditAction::make()
            ->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
    ];
}
}

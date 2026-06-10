<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVentas extends ListRecords
{
    protected static string $resource = VentaResource::class;

   protected function getHeaderActions(): array
{
    return [
        CreateAction::make()
            ->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
    ];
}
}
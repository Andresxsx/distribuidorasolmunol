<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

   protected function getHeaderActions(): array
{
    return [
        ViewAction::make(),

        DeleteAction::make()
            ->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
    ];
}
}

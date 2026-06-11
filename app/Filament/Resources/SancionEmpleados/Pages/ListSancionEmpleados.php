<?php

namespace App\Filament\Resources\SancionEmpleados\Pages;

use App\Filament\Resources\SancionEmpleados\SancionEmpleadoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSancionEmpleados extends ListRecords
{
    protected static string $resource = SancionEmpleadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Registrar sanción')->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
        ];
    }
}

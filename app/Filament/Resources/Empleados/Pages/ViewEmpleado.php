<?php

namespace App\Filament\Resources\Empleados\Pages;

use App\Filament\Resources\Empleados\EmpleadoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmpleado extends ViewRecord
{
    protected static string $resource = EmpleadoResource::class;

    protected function getHeaderActions(): array
{
    return [
        EditAction::make()
            ->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
    ];
}
}

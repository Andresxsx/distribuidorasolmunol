<?php

namespace App\Filament\Resources\Empleados\Pages;

use App\Filament\Resources\Empleados\EmpleadoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmpleado extends EditRecord
{
    protected static string $resource = EmpleadoResource::class;

   protected function getHeaderActions(): array
{
    return [
        ViewAction::make(),

        DeleteAction::make()
            ->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
    ];
}
}

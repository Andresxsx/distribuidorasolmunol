<?php

namespace App\Filament\Resources\Empleados\Pages;

use App\Filament\Resources\Empleados\EmpleadoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEmpleado extends CreateRecord
{
    protected static string $resource = EmpleadoResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Empleado registrado correctamente')
            ->body(
                'El empleado ' . $this->record->nombres . ' ' . $this->record->apellidos .
                ' fue registrado con el código ' . $this->record->codigo_empleado . '.'
            );
    }
}
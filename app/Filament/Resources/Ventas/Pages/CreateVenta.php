<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Venta registrada correctamente')
            ->body(
                'La venta ' . $this->record->numero_venta .
                ' fue guardada y el stock del producto se descontó automáticamente.'
            );
    }
}
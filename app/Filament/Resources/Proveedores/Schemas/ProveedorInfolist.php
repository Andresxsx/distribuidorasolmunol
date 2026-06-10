<?php

namespace App\Filament\Resources\Proveedores\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProveedorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del proveedor')
                    ->description('Datos principales del proveedor registrado.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('ruc')
                            ->label('RUC')
                            ->copyable(),

                        TextEntry::make('nombre')
                            ->label('Razón social'),

                        TextEntry::make('telefono')
                            ->label('Teléfono de contacto')
                            ->placeholder('No registrado')
                            ->copyable(),

                        TextEntry::make('correo')
                            ->label('Correo electrónico')
                            ->placeholder('No registrado')
                            ->copyable(),

                        TextEntry::make('direccion')
                            ->label('Dirección')
                            ->placeholder('No registrada')
                            ->columnSpanFull(),

                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Activo' => 'success',
                                'Inactivo' => 'danger',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Información del registro')
                    ->description('Fechas de creación y actualización del proveedor.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Registrado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('No disponible'),

                        TextEntry::make('updated_at')
                            ->label('Última actualización')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('No disponible'),
                    ]),
            ]);
    }
}
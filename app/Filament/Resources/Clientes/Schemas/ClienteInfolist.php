<?php

namespace App\Filament\Resources\Clientes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClienteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del cliente')
                    ->description('Datos principales registrados del cliente.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('cedula_ruc')
                            ->label('Cédula / RUC')
                            ->copyable(),

                        TextEntry::make('nombre')
                            ->label('Cliente'),

                        TextEntry::make('telefono')
                            ->label('Teléfono')
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
                    ->description('Fechas de creación y última actualización.')
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
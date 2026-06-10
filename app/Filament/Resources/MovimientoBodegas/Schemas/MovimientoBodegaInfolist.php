<?php

namespace App\Filament\Resources\MovimientoBodegas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MovimientoBodegaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del movimiento')
                    ->description('Detalle del movimiento de inventario registrado automáticamente.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('codigo_movimiento')
                            ->label('Código movimiento')
                            ->copyable(),

                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('producto.nombre')
                            ->label('Producto'),

                        TextEntry::make('tipo_movimiento')
                            ->label('Tipo de movimiento')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Entrada' => 'success',
                                'Salida' => 'danger',
                                'Ajuste' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('origen')
                            ->label('Origen')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Compra' => 'success',
                                'Venta' => 'info',
                                'Corrección' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('documento_referencia')
                            ->label('Documento referencia')
                            ->placeholder('Sin documento')
                            ->copyable(),

                        TextEntry::make('cantidad')
                            ->label('Cantidad'),

                        TextEntry::make('stock_anterior')
                            ->label('Stock anterior'),

                        TextEntry::make('stock_nuevo')
                            ->label('Stock nuevo'),

                        TextEntry::make('observacion')
                            ->label('Observación')
                            ->placeholder('Sin observación')
                            ->columnSpanFull(),
                    ]),

                Section::make('Control del sistema')
                    ->description('Datos automáticos del registro.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Usuario')
                            ->placeholder('Sistema'),

                        TextEntry::make('created_at')
                            ->label('Registrado')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Última actualización')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
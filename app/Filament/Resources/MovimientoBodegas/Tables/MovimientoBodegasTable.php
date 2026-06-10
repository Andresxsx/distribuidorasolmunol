<?php

namespace App\Filament\Resources\MovimientoBodegas\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MovimientoBodegasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo_movimiento')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tipo_movimiento')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Entrada' => 'success',
                        'Salida' => 'danger',
                        'Ajuste' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('origen')
                    ->label('Origen')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Compra' => 'success',
                        'Venta' => 'info',
                        'Corrección' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('documento_referencia')
                    ->label('Documento')
                    ->searchable()
                    ->placeholder('Sin documento'),

                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->sortable(),

                TextColumn::make('stock_anterior')
                    ->label('Stock anterior')
                    ->sortable(),

                TextColumn::make('stock_nuevo')
                    ->label('Stock nuevo')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('Sistema'),

                TextColumn::make('observacion')
                    ->label('Observación')
                    ->limit(40),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver'),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
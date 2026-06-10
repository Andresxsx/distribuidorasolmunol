<?php

namespace App\Filament\Resources\Productos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del producto')
                    ->description('Datos principales del producto registrado.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('codigo')
                            ->label('Código')
                            ->copyable(),

                        TextEntry::make('nombre')
                            ->label('Producto'),

                        TextEntry::make('categoria')
                            ->label('Categoría'),

                        TextEntry::make('descripcion')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
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

                Section::make('Inventario y precios')
                    ->description('Información usada por compras, ventas y bodega.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('stock_actual')
                            ->label('Stock actual')
                            ->badge()
                            ->color(fn ($state, $record): string => match (true) {
                                (int) $state <= 0 => 'danger',
                                (int) $state <= (int) $record->stock_minimo => 'warning',
                                default => 'success',
                            }),

                        TextEntry::make('stock_minimo')
                            ->label('Stock mínimo'),

                        TextEntry::make('precio_compra')
                            ->label('Precio de compra')
                            ->money('USD'),

                        TextEntry::make('precio_venta')
                            ->label('Precio de venta')
                            ->money('USD'),
                    ]),

                Section::make('Información del registro')
                    ->description('Fechas de creación y última actualización.')
                    ->columns(2)
                    ->schema([
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
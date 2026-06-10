<?php

namespace App\Filament\Resources\Productos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock_actual')
                    ->label('Stock actual')
                    ->badge()
                    ->color(fn ($state, $record): string => match (true) {
                        (int) $state <= 0 => 'danger',
                        (int) $state <= (int) $record->stock_minimo => 'warning',
                        default => 'success',
                    })
                    ->sortable(),

                TextColumn::make('stock_minimo')
                    ->label('Stock mínimo')
                    ->sortable(),

                TextColumn::make('precio_compra')
                    ->label('Precio compra')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('precio_venta')
                    ->label('Precio venta')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Activo' => 'success',
                        'Inactivo' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
    ViewAction::make()
        ->label('Ver'),

    EditAction::make()
        ->label('Editar')
        ->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
])
->toolbarActions([
    BulkActionGroup::make([
        DeleteBulkAction::make()
            ->label('Eliminar seleccionados')
            ->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
    ])
        ->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
]);
    }
}
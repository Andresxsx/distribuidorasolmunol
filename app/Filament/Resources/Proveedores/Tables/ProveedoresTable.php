<?php

namespace App\Filament\Resources\Proveedores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProveedoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ruc')
                    ->label('RUC')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nombre')
                    ->label('Razón social')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),

                TextColumn::make('correo')
                    ->label('Correo')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('direccion')
                    ->label('Dirección')
                    ->searchable()
                    ->limit(35),

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
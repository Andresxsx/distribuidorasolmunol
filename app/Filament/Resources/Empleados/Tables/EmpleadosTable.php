<?php

namespace App\Filament\Resources\Empleados\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmpleadosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo_empleado')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nombres')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('apellidos')
                    ->label('Apellidos')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cargo')
                    ->label('Cargo')
                    ->searchable(),

                TextColumn::make('departamento')
                    ->label('Departamento')
                    ->searchable(),

                TextColumn::make('telefono')
                    ->label('Celular')
                    ->searchable(),

                TextColumn::make('correo')
                    ->label('Correo')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('sueldo')
                    ->label('Sueldo')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('fecha_ingreso')
                    ->label('Fecha ingreso')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Activo' => 'success',
                        'Inactivo' => 'danger',
                        'Suspendido' => 'warning',
                        'Retirado' => 'gray',
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
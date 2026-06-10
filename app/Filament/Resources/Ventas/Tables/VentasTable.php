<?php

namespace App\Filament\Resources\Ventas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
class VentasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_venta')
                    ->label('N° Venta')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->sortable(),

                TextColumn::make('precio_unitario')
                    ->label('Precio unitario')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->placeholder('Sistema'),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
    Action::make('exportar_ventas_excel')
        ->label('Exportar Excel')
        ->icon('heroicon-o-document-arrow-down')
        ->color('success')
        ->url(route('reportes.ventas.excel'))
        ->openUrlInNewTab(),

    Action::make('exportar_ventas_pdf')
        ->label('Exportar PDF')
        ->icon('heroicon-o-document-text')
        ->color('danger')
        ->url(route('reportes.ventas.pdf'))
        ->openUrlInNewTab(),
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
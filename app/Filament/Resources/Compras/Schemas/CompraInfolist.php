<?php

namespace App\Filament\Resources\Compras\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompraInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la compra')
                    ->description('Datos principales de la compra registrada.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('numero_compra')
                            ->label('Número de compra')
                            ->copyable(),

                        TextEntry::make('fecha')
                            ->label('Fecha de compra')
                            ->date('d/m/Y'),

                        TextEntry::make('proveedor.nombre')
                            ->label('Proveedor'),

                        TextEntry::make('producto.nombre')
                            ->label('Producto'),

                        TextEntry::make('cantidad')
                            ->label('Cantidad comprada'),

                        TextEntry::make('precio_unitario')
                            ->label('Precio unitario')
                            ->money('USD'),

                        TextEntry::make('total')
                            ->label('Total')
                            ->money('USD'),

                        TextEntry::make('observacion')
                            ->label('Observación')
                            ->placeholder('Sin observación')
                            ->columnSpanFull(),
                    ]),

                Section::make('Control del sistema')
                    ->description('Información automática registrada por el sistema.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Registrado por')
                            ->placeholder('Sistema'),

                        TextEntry::make('created_at')
                            ->label('Fecha de registro')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Última actualización')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
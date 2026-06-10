<?php

namespace App\Filament\Resources\Empleados\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmpleadoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información personal')
                    ->description('Datos principales del empleado.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('codigo_empleado')
                            ->label('Código empleado')
                            ->copyable(),

                        TextEntry::make('cedula')
                            ->label('Cédula')
                            ->copyable(),

                        TextEntry::make('nombres')
                            ->label('Nombres'),

                        TextEntry::make('apellidos')
                            ->label('Apellidos'),

                        TextEntry::make('telefono')
                            ->label('Celular')
                            ->copyable(),

                        TextEntry::make('correo')
                            ->label('Correo electrónico')
                            ->copyable(),
                    ]),

                Section::make('Información laboral')
                    ->description('Datos del cargo y estado laboral.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('cargo')
                            ->label('Cargo'),

                        TextEntry::make('departamento')
                            ->label('Departamento'),

                        TextEntry::make('sueldo')
                            ->label('Sueldo')
                            ->money('USD'),

                        TextEntry::make('fecha_ingreso')
                            ->label('Fecha de ingreso')
                            ->date('d/m/Y'),

                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Activo' => 'success',
                                'Inactivo' => 'danger',
                                'Suspendido' => 'warning',
                                'Retirado' => 'gray',
                                default => 'gray',
                            }),
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
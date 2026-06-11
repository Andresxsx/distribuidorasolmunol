<?php

namespace App\Filament\Resources\Empleados\Schemas;

use Filament\Infolists\Components\IconEntry;
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
                        TextEntry::make('codigo_empleado')->label('Código empleado')->copyable(),
                        TextEntry::make('cedula')->label('Cédula')->copyable(),
                        TextEntry::make('nombres')->label('Nombres'),
                        TextEntry::make('apellidos')->label('Apellidos'),
                        TextEntry::make('telefono')->label('Celular')->copyable(),
                        TextEntry::make('correo')->label('Correo electrónico')->copyable(),
                    ]),

                Section::make('Información laboral')
                    ->description('Cargo, salario fijo y estado laboral.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('cargo')->label('Cargo'),
                        TextEntry::make('departamento')->label('Departamento'),
                        TextEntry::make('sueldo')->label('Salario base por cargo')->money('USD'),
                        TextEntry::make('total_sanciones_aplicadas')->label('Total sanciones aplicadas')->money('USD'),
                        TextEntry::make('sueldo_neto_estimado')->label('Salario neto estimado')->money('USD'),
                        TextEntry::make('fecha_ingreso')->label('Fecha de ingreso')->date('d/m/Y'),
                        TextEntry::make('estado')->label('Estado')->badge()->color(fn (string $state): string => match ($state) {
                            'Activo' => 'success',
                            'Inactivo' => 'danger',
                            'Suspendido' => 'warning',
                            'Retirado' => 'gray',
                            default => 'gray',
                        }),
                    ]),

                Section::make('Seguro del empleado')
                    ->description('Control de afiliación o seguro registrado.')
                    ->columns(2)
                    ->schema([
                        IconEntry::make('tiene_seguro')->label('Tiene seguro')->boolean(),
                        TextEntry::make('tipo_seguro')->label('Tipo de seguro')->placeholder('No registrado'),
                        TextEntry::make('numero_afiliacion')->label('Número de afiliación')->placeholder('No registrado'),
                        TextEntry::make('estado_seguro')->label('Estado seguro')->badge()->color(fn (?string $state): string => match ($state) {
                            'Activo' => 'success',
                            'En trámite' => 'warning',
                            'Inactivo' => 'danger',
                            default => 'gray',
                        }),
                        TextEntry::make('fecha_afiliacion')->label('Fecha de afiliación')->date('d/m/Y')->placeholder('No registrada'),
                    ]),

                Section::make('Información del registro')
                    ->description('Fechas de creación y última actualización.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')->label('Registrado')->dateTime('d/m/Y H:i'),
                        TextEntry::make('updated_at')->label('Última actualización')->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}

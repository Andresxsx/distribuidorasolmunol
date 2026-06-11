<?php

namespace App\Filament\Resources\Empleados;

use App\Filament\Resources\Concerns\ControlaPermisosPorRol;
use App\Filament\Resources\Empleados\Pages\CreateEmpleado;
use App\Filament\Resources\Empleados\Pages\EditEmpleado;
use App\Filament\Resources\Empleados\Pages\ListEmpleados;
use App\Filament\Resources\Empleados\Pages\ViewEmpleado;
use App\Filament\Resources\Empleados\Schemas\EmpleadoInfolist;
use App\Filament\Resources\Empleados\Tables\EmpleadosTable;
use App\Models\Cargo;
use App\Models\Empleado;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EmpleadoResource extends Resource
{
    use ControlaPermisosPorRol;

    protected static ?string $model = Empleado::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $recordTitleAttribute = 'nombres';

    protected static ?string $navigationLabel = 'Empleados';

    protected static ?string $modelLabel = 'Empleado';

    protected static ?string $pluralModelLabel = 'Empleados';

    protected static ?string $slug = 'empleados';

    protected static string|\UnitEnum|null $navigationGroup = 'Talento Humano';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo_empleado')
                    ->label('Código empleado')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Se generará automáticamente')
                    ->helperText('El sistema genera el código del empleado.'),

                TextInput::make('cedula')
                    ->label('Cédula')
                    ->required()
                    ->minLength(10)
                    ->maxLength(10)
                    ->inputMode('numeric')
                    ->extraInputAttributes([
                        'maxlength' => 10,
                        'oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)",
                    ])
                    ->rules([
                        'required',
                        'digits:10',
                        function () {
                            return function (string $attribute, $value, \Closure $fail): void {
                                $cedula = preg_replace('/\D/', '', (string) $value);

                                if (! Empleado::validarCedulaEcuador($cedula)) {
                                    $fail('La cédula ingresada no es válida para Ecuador.');
                                }
                            };
                        },
                    ])
                    ->unique(table: 'empleados', column: 'cedula', ignoreRecord: true)
                    ->helperText('Ingrese una cédula ecuatoriana válida de 10 dígitos.'),

                TextInput::make('nombres')
                    ->label('Nombres')
                    ->required()
                    ->minLength(2)
                    ->maxLength(80)
                    ->rules(['required', 'regex:/^[\pL\s]+$/u'])
                    ->helperText('Solo letras y espacios.'),

                TextInput::make('apellidos')
                    ->label('Apellidos')
                    ->required()
                    ->minLength(2)
                    ->maxLength(80)
                    ->rules(['required', 'regex:/^[\pL\s]+$/u'])
                    ->helperText('Solo letras y espacios.'),

                Select::make('cargo_id')
                    ->label('Cargo')
                    ->required()
                    ->options(fn () => Cargo::where('estado', 'Activo')
                        ->orderBy('nombre')
                        ->get()
                        ->mapWithKeys(fn (Cargo $cargo) => [
                            $cargo->id => "{$cargo->nombre} - {$cargo->departamento} ($" . number_format((float) $cargo->salario_base, 2) . ")",
                        ])
                        ->toArray())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, $set): void {
                        $cargo = Cargo::find($state);

                        if ($cargo) {
                            $set('cargo', $cargo->nombre);
                            $set('departamento', $cargo->departamento);
                            $set('sueldo', $cargo->salario_base);
                        }
                    })
                    ->helperText('El cargo define automáticamente departamento y salario base.'),

                TextInput::make('cargo')
                    ->label('Cargo asignado')
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('Se asigna automáticamente desde el catálogo de cargos.'),

                TextInput::make('departamento')
                    ->label('Departamento')
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('Se asigna automáticamente desde el cargo.'),

                TextInput::make('sueldo')
                    ->label('Salario base')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('No se edita manualmente. Depende del cargo seleccionado.'),

                TextInput::make('telefono')
                    ->label('Celular')
                    ->required()
                    ->minLength(10)
                    ->maxLength(10)
                    ->inputMode('numeric')
                    ->extraInputAttributes([
                        'maxlength' => 10,
                        'oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)",
                    ])
                    ->rules(['required', 'regex:/^09[0-9]{8}$/'])
                    ->helperText('Debe ser un celular ecuatoriano válido. Ejemplo: 0993050589.'),

                TextInput::make('correo')
                    ->label('Correo electrónico')
                    ->required()
                    ->email()
                    ->maxLength(100)
                    ->helperText('Ejemplo: empleado@empresa.com'),

                DatePicker::make('fecha_ingreso')
                    ->label('Fecha de ingreso')
                    ->default(now())
                    ->maxDate(now())
                    ->required()
                    ->helperText('No se permiten fechas futuras.'),

                Select::make('estado')
                    ->label('Estado')
                    ->required()
                    ->options([
                        'Activo' => 'Activo',
                        'Inactivo' => 'Inactivo',
                        'Suspendido' => 'Suspendido',
                        'Retirado' => 'Retirado',
                    ])
                    ->default('Activo'),

                Toggle::make('tiene_seguro')
                    ->label('Tiene seguro')
                    ->live()
                    ->helperText('Actívelo si el empleado tiene seguro IESS o privado.'),

                Select::make('tipo_seguro')
                    ->label('Tipo de seguro')
                    ->options([
                        'IESS' => 'IESS',
                        'Privado' => 'Privado',
                    ])
                    ->visible(fn ($get): bool => (bool) $get('tiene_seguro'))
                    ->required(fn ($get): bool => (bool) $get('tiene_seguro')),

                TextInput::make('numero_afiliacion')
                    ->label('Número de afiliación')
                    ->maxLength(80)
                    ->visible(fn ($get): bool => (bool) $get('tiene_seguro')),

                Select::make('estado_seguro')
                    ->label('Estado del seguro')
                    ->options([
                        'Activo' => 'Activo',
                        'Inactivo' => 'Inactivo',
                        'En trámite' => 'En trámite',
                    ])
                    ->default('Activo')
                    ->visible(fn ($get): bool => (bool) $get('tiene_seguro'))
                    ->required(fn ($get): bool => (bool) $get('tiene_seguro')),

                DatePicker::make('fecha_afiliacion')
                    ->label('Fecha de afiliación')
                    ->maxDate(now())
                    ->visible(fn ($get): bool => (bool) $get('tiene_seguro'))
                    ->required(fn ($get): bool => (bool) $get('tiene_seguro')),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmpleadoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmpleadosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmpleados::route('/'),
            'create' => CreateEmpleado::route('/create'),
            'view' => ViewEmpleado::route('/{record}'),
            'edit' => EditEmpleado::route('/{record}/edit'),
        ];
    }
}

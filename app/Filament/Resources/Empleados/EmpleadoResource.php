<?php

namespace App\Filament\Resources\Empleados;
use App\Filament\Resources\Concerns\ControlaPermisosPorRol;
use App\Filament\Resources\Empleados\Pages\CreateEmpleado;
use App\Filament\Resources\Empleados\Pages\EditEmpleado;
use App\Filament\Resources\Empleados\Pages\ListEmpleados;
use App\Filament\Resources\Empleados\Pages\ViewEmpleado;
use App\Filament\Resources\Empleados\Schemas\EmpleadoInfolist;
use App\Filament\Resources\Empleados\Tables\EmpleadosTable;
use App\Models\Empleado;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

                                if (! self::validarCedulaEcuador($cedula)) {
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
                    ->extraInputAttributes([
                        'maxlength' => 80,
                    ])
                    ->rules([
                        'required',
                        'regex:/^[\pL\s]+$/u',
                    ])
                    ->helperText('Solo letras y espacios. Ejemplo: Javier Andrés.'),

                TextInput::make('apellidos')
                    ->label('Apellidos')
                    ->required()
                    ->minLength(2)
                    ->maxLength(80)
                    ->extraInputAttributes([
                        'maxlength' => 80,
                    ])
                    ->rules([
                        'required',
                        'regex:/^[\pL\s]+$/u',
                    ])
                    ->helperText('Solo letras y espacios. Ejemplo: Carranza Vera.'),

                Select::make('cargo')
                    ->label('Cargo')
                    ->required()
                    ->options([
                        'Gerente' => 'Gerente',
                        'Administrador' => 'Administrador',
                        'Vendedor' => 'Vendedor',
                        'Bodeguero' => 'Bodeguero',
                        'Comprador' => 'Comprador',
                        'Contador' => 'Contador',
                        'Asistente administrativo' => 'Asistente administrativo',
                        'Jefe de talento humano' => 'Jefe de talento humano',
                        'Analista de sistemas' => 'Analista de sistemas',
                    ])
                    ->searchable()
                    ->helperText('Seleccione un cargo válido.'),

                Select::make('departamento')
                    ->label('Departamento')
                    ->required()
                    ->options([
                        'Dirección' => 'Dirección',
                        'Administración' => 'Administración',
                        'Talento Humano' => 'Talento Humano',
                        'Bodega' => 'Bodega',
                        'Compras' => 'Compras',
                        'Ventas' => 'Ventas',
                        'Contabilidad' => 'Contabilidad',
                        'Sistemas' => 'Sistemas',
                    ])
                    ->searchable()
                    ->helperText('Seleccione el departamento del empleado.'),

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
                    ->rules([
                        'required',
                        'regex:/^09[0-9]{8}$/',
                    ])
                    ->helperText('Debe ser un celular ecuatoriano válido. Ejemplo: 0993050589.'),

                TextInput::make('correo')
                    ->label('Correo electrónico')
                    ->required()
                    ->email()
                    ->maxLength(100)
                    ->extraInputAttributes([
                        'maxlength' => 100,
                    ])
                    ->helperText('Ejemplo: empleado@empresa.com'),

                TextInput::make('sueldo')
                    ->label('Sueldo')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    ->extraInputAttributes([
                        'min' => 0.01,
                        'step' => '0.01',
                    ])
                    ->helperText('El sueldo debe ser mayor a cero.'),

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
        return [
            //
        ];
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

    private static function validarCedulaEcuador(string $cedula): bool
    {
        if (! preg_match('/^[0-9]{10}$/', $cedula)) {
            return false;
        }

        if (preg_match('/^(\d)\1{9}$/', $cedula)) {
            return false;
        }

        $provincia = intval(substr($cedula, 0, 2));
        $tercerDigito = intval($cedula[2]);

        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        if ($tercerDigito > 5) {
            return false;
        }

        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $valor = intval($cedula[$i]) * $coeficientes[$i];

            if ($valor >= 10) {
                $valor -= 9;
            }

            $suma += $valor;
        }

        $digitoCalculado = (10 - ($suma % 10)) % 10;
        $digitoReal = intval($cedula[9]);

        return $digitoCalculado === $digitoReal;
    }
}
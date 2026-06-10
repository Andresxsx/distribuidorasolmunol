<?php

namespace App\Filament\Resources\Clientes;
use App\Filament\Resources\Concerns\ControlaPermisosPorRol;
use App\Filament\Resources\Clientes\Pages\CreateCliente;
use App\Filament\Resources\Clientes\Pages\EditCliente;
use App\Filament\Resources\Clientes\Pages\ListClientes;
use App\Filament\Resources\Clientes\Pages\ViewCliente;
use App\Filament\Resources\Clientes\Schemas\ClienteInfolist;
use App\Filament\Resources\Clientes\Tables\ClientesTable;
use App\Models\Cliente;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ClienteResource extends Resource
{

    use ControlaPermisosPorRol;

    protected static ?string $model = Cliente::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?string $slug = 'clientes';

    protected static string|\UnitEnum|null $navigationGroup = 'Bodega, Compras y Ventas';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('cedula_ruc')
                    ->label('Cédula / RUC')
                    ->required()
                    ->minLength(10)
                    ->maxLength(13)
                    ->inputMode('numeric')
                    ->extraInputAttributes([
                        'maxlength' => 13,
                        'oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 13)",
                    ])
                    ->rules([
                        'required',
                        'regex:/^[0-9]{10}$|^[0-9]{13}$/',
                        function () {
                            return function (string $attribute, $value, \Closure $fail): void {
                                $documento = preg_replace('/\D/', '', (string) $value);

                                if (! self::validarCedulaRucEcuador($documento)) {
                                    $fail('La cédula o RUC ingresado no es válido para Ecuador.');
                                }
                            };
                        },
                    ])
                    ->unique(table: 'clientes', column: 'cedula_ruc', ignoreRecord: true)
                    ->helperText('Ingrese una cédula válida de 10 dígitos o un RUC válido de 13 dígitos.'),

                TextInput::make('nombre')
                    ->label('Nombre del cliente')
                    ->required()
                    ->minLength(3)
                    ->maxLength(120)
                    ->extraInputAttributes([
                        'maxlength' => 120,
                    ])
                    ->rules([
                        'required',
                        'regex:/^[\pL\s]+$/u',
                    ])
                    ->helperText('Solo letras y espacios. Ejemplo: Javier Carranza.'),

                TextInput::make('telefono')
                    ->label('Teléfono de contacto')
                    ->required()
                    ->minLength(9)
                    ->maxLength(10)
                    ->inputMode('numeric')
                    ->extraInputAttributes([
                        'maxlength' => 10,
                        'oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)",
                    ])
                    ->rules([
                        'required',
                        'regex:/^(09[0-9]{8}|0[2-7][0-9]{7})$/',
                    ])
                    ->helperText('Celular: 09XXXXXXXX. Convencional: 02XXXXXXX, 04XXXXXXX, etc.'),

                TextInput::make('correo')
                    ->label('Correo electrónico')
                    ->required()
                    ->email()
                    ->maxLength(100)
                    ->extraInputAttributes([
                        'maxlength' => 100,
                    ])
                    ->helperText('Ejemplo: cliente@gmail.com'),

                TextInput::make('direccion')
                    ->label('Dirección')
                    ->required()
                    ->minLength(5)
                    ->maxLength(150)
                    ->extraInputAttributes([
                        'maxlength' => 150,
                    ])
                    ->helperText('Ejemplo: Av. Principal y Calle 10, Baba'),

                Select::make('estado')
                    ->label('Estado')
                    ->options([
                        'Activo' => 'Activo',
                        'Inactivo' => 'Inactivo',
                    ])
                    ->default('Activo')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClienteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientesTable::configure($table);
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
            'index' => ListClientes::route('/'),
            'create' => CreateCliente::route('/create'),
            'view' => ViewCliente::route('/{record}'),
            'edit' => EditCliente::route('/{record}/edit'),
        ];
    }

    private static function validarCedulaRucEcuador(string $documento): bool
    {
        if (! preg_match('/^[0-9]+$/', $documento)) {
            return false;
        }

        if (strlen($documento) === 10) {
            return self::validarCedulaEcuador($documento);
        }

        if (strlen($documento) === 13) {
            return self::validarRucEcuador($documento);
        }

        return false;
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

    private static function validarRucEcuador(string $ruc): bool
    {
        if (! preg_match('/^[0-9]{13}$/', $ruc)) {
            return false;
        }

        if (preg_match('/^(\d)\1{12}$/', $ruc)) {
            return false;
        }

        $provincia = intval(substr($ruc, 0, 2));
        $tercerDigito = intval($ruc[2]);

        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        if ($tercerDigito >= 0 && $tercerDigito <= 5) {
            $cedula = substr($ruc, 0, 10);
            $establecimiento = substr($ruc, 10, 3);

            return self::validarCedulaEcuador($cedula) && intval($establecimiento) > 0;
        }

        if ($tercerDigito === 6) {
            return self::validarRucPublico($ruc);
        }

        if ($tercerDigito === 9) {
            return self::validarRucPrivado($ruc);
        }

        return false;
    }

    private static function validarRucPrivado(string $ruc): bool
    {
        $establecimiento = substr($ruc, 10, 3);

        if (intval($establecimiento) <= 0) {
            return false;
        }

        $coeficientes = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $suma += intval($ruc[$i]) * $coeficientes[$i];
        }

        $residuo = $suma % 11;
        $digitoCalculado = 11 - $residuo;

        if ($digitoCalculado === 11) {
            $digitoCalculado = 0;
        }

        if ($digitoCalculado === 10) {
            return false;
        }

        return $digitoCalculado === intval($ruc[9]);
    }

    private static function validarRucPublico(string $ruc): bool
    {
        $establecimiento = substr($ruc, 9, 4);

        if (intval($establecimiento) <= 0) {
            return false;
        }

        $coeficientes = [3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 8; $i++) {
            $suma += intval($ruc[$i]) * $coeficientes[$i];
        }

        $residuo = $suma % 11;
        $digitoCalculado = 11 - $residuo;

        if ($digitoCalculado === 11) {
            $digitoCalculado = 0;
        }

        if ($digitoCalculado === 10) {
            return false;
        }

        return $digitoCalculado === intval($ruc[8]);
    }
}
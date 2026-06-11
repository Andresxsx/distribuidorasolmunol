<?php

namespace App\Filament\Resources\Cargos;

use App\Filament\Resources\Cargos\Pages\CreateCargo;
use App\Filament\Resources\Cargos\Pages\EditCargo;
use App\Filament\Resources\Cargos\Pages\ListCargos;
use App\Filament\Resources\Cargos\Pages\ViewCargo;
use App\Filament\Resources\Concerns\ControlaPermisosPorRol;
use App\Models\Cargo;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CargoResource extends Resource
{
    use ControlaPermisosPorRol;

    protected static ?string $model = Cargo::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationLabel = 'Cargos';

    protected static ?string $modelLabel = 'Cargo';

    protected static ?string $pluralModelLabel = 'Cargos';

    protected static ?string $slug = 'cargos';

    protected static string|\UnitEnum|null $navigationGroup = 'Talento Humano';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label('Cargo')
                ->required()
                ->minLength(2)
                ->maxLength(80)
                ->unique(table: 'cargos', column: 'nombre', ignoreRecord: true)
                ->helperText('Ejemplo: Gerente, Vendedor, Bodeguero.'),

            Select::make('departamento')
                ->label('Departamento')
                ->required()
                ->options(Cargo::departamentos())
                ->searchable(),

            TextInput::make('salario_base')
                ->label('Salario base fijo')
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->prefix('$')
                ->helperText('Todos los empleados con este cargo usarán este salario base.'),

            Select::make('estado')
                ->label('Estado')
                ->required()
                ->options([
                    'Activo' => 'Activo',
                    'Inactivo' => 'Inactivo',
                ])
                ->default('Activo'),

            Textarea::make('descripcion')
                ->label('Descripción')
                ->maxLength(250)
                ->helperText('Funciones o responsabilidades generales del cargo.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información del cargo')
                ->columns(2)
                ->schema([
                    TextEntry::make('nombre')->label('Cargo'),
                    TextEntry::make('departamento')->label('Departamento'),
                    TextEntry::make('salario_base')->label('Salario base')->money('USD'),
                    TextEntry::make('estado')->label('Estado')->badge()->color(fn (string $state): string => $state === 'Activo' ? 'success' : 'danger'),
                    TextEntry::make('descripcion')->label('Descripción')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Cargo')->searchable()->sortable(),
                TextColumn::make('departamento')->label('Departamento')->searchable()->sortable(),
                TextColumn::make('salario_base')->label('Salario base')->money('USD')->sortable(),
                TextColumn::make('empleados_count')->label('Empleados')->counts('empleados')->sortable(),
                TextColumn::make('estado')->label('Estado')->badge()->color(fn (string $state): string => $state === 'Activo' ? 'success' : 'danger'),
                TextColumn::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar')->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Eliminar seleccionados')->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
                ])->visible(fn () => auth()->user()?->puedeGestionarRegistros() ?? false),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCargos::route('/'),
            'create' => CreateCargo::route('/create'),
            'view' => ViewCargo::route('/{record}'),
            'edit' => EditCargo::route('/{record}/edit'),
        ];
    }
}

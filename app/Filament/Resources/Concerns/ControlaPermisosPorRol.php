<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ControlaPermisosPorRol
{
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canView(Model $record): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->puedeGestionarRegistros() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->puedeGestionarRegistros() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->puedeGestionarRegistros() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->puedeGestionarRegistros() ?? false;
    }
}
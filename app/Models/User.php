<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function rolNormalizado(): string
    {
        return strtolower(trim((string) $this->rol));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->rolNormalizado(), [
            'administrador',
            'directivo',
            'operativo',
        ], true);
    }

    public function esAdministrador(): bool
    {
        return $this->rolNormalizado() === 'administrador';
    }

    public function esOperativo(): bool
    {
        return $this->rolNormalizado() === 'operativo';
    }

    public function esDirectivo(): bool
    {
        return $this->rolNormalizado() === 'directivo';
    }

    public function puedeGestionarRegistros(): bool
    {
        return in_array($this->rolNormalizado(), ['administrador', 'operativo'], true);
    }

    public function puedeConsultarDirectivo(): bool
    {
        return in_array($this->rolNormalizado(), ['administrador', 'directivo'], true);
    }
}
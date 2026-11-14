<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use \App\Models\Concerns\TieneFoto;
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'foto',
        'email',
        'password',
        'rol', // <--- Permite asignar el rol al registrar o actualizar usuarios
        'activo',
        'ultimo_acceso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'ultimo_acceso' => 'datetime',
        ];
    }

    // ==========================================
    // MÉTODOS DE JERARQUÍA DE ROLES
    // ==========================================

    /**
     * Verifica si el usuario es Dueño (Máximo nivel)
     */
    public function esDueno(): bool
    {
        return $this->rol === 'Dueno';
    }

    /**
     * Verifica si es Encargado o un nivel superior (Dueño)
     */
    public function esEncargadoOSuperior(): bool
    {
        return in_array($this->rol, ['Dueno', 'Encargado']);
    }

    /**
     * Comprueba si el rol del usuario coincide con alguno de la lista permitida
     */
    public function tieneRol(array $roles): bool
    {
        return in_array($this->rol, $roles);
    }

    // El rol se guarda sin tilde ('Dueno'); para mostrarlo se escribe con ñ.
    public function rolTexto(): string
    {
        return $this->rol === 'Dueno' ? 'Dueño' : $this->rol;
    }

    public function nivelLabel(): string
    {
        return match ($this->rol) {
            'Dueno' => 'Nivel 1 — Acceso Total',
            'Encargado' => 'Nivel 2 — Gestión Operativa',
            default => 'Nivel 3 — Operativo Básico',
        };
    }

    // Resumen de lo que puede hacer cada rol, reflejando los grupos de
    // middleware ya definidos en routes/web.php (no es un sistema de
    // permisos granular por usuario, solo una descripción del rol).
    public function permisosResumen(): array
    {
        return self::permisosDeRol($this->rol);
    }

    // Igual que permisosResumen() pero para cualquier rol: la pantalla de Personal
    // lo usa para explicar cada opción del selector antes de guardar.
    public static function permisosDeRol(?string $rol): array
    {
        return match ($rol) {
            'Dueno' => [
                'Acceso total al sistema',
                'Gestión de personal (crear/editar/revocar usuarios)',
                'Eliminar productos, categorías, proveedores y clientes',
                'Todos los reportes y estadísticas',
            ],
            'Encargado' => [
                'Ventas, caja e Inventario Inteligente',
                'Productos, categorías, compras, proveedores, clientes y pérdidas',
                'Reportes y estadísticas',
                'No puede eliminar registros críticos ni gestionar personal',
            ],
            default => [
                'Registrar ventas',
                'Abrir y cerrar caja',
                'Ver avisos de stock en la campana',
                'Sin acceso a reportes, compras ni gestión de personal',
            ],
        };
    }
}
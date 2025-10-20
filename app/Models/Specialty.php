<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para las especialidades médicas
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Specialty extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Relación con el personal médico
     * Una especialidad puede tener muchos personal médico
     */
    public function medicalStaff(): HasMany
    {
        return $this->hasMany(MedicalStaff::class);
    }

    /**
     * Relación con las citas
     * Una especialidad puede tener muchas citas
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Scope para especialidades activas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para incluir especialidades eliminadas
     */
    public function scopeWithTrashed($query)
    {
        return $query->withTrashed();
    }

    /**
     * Scope para obtener SOLO especialidades eliminadas
     */
    public function scopeOnlyTrashed($query)
    {
        return $query->onlyTrashed();
    }
}

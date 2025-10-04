<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para el personal médico
 * 
 * @property int $id
 * @property int $user_id
 * @property string $professional_license
 * @property int $specialty_id
 * @property string|null $subspecialty
 * @property bool $active
 * @property int $appointment_duration
 * @property array|null $work_schedule
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 */
class MedicalStaff extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'professional_license',
        'specialty_id',
        'subspecialty',
        'active',
        'appointment_duration',
        'work_schedule',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'work_schedule' => 'array',
    ];

    /**
     * Relación con el usuario
     * Un personal médico pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la especialidad
     * Un personal médico pertenece a una especialidad
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Relación con las citas
     * Un personal médico puede tener muchas citas
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Relación con los registros médicos
     * Un personal médico puede crear muchos registros
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Scope para personal médico activo
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para personal médico por especialidad
     */
    public function scopeBySpecialty($query, $specialtyId)
    {
        return $query->where('specialty_id', $specialtyId);
    }
}

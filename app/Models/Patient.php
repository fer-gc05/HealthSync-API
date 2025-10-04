<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para los pacientes
 * 
 * @property int $id
 * @property int $user_id
 * @property \Carbon\Carbon $birth_date
 * @property string $gender
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $blood_type
 * @property string|null $allergies
 * @property string|null $current_medications
 * @property string|null $insurance_number
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 */
class Patient extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'birth_date',
        'gender',
        'phone',
        'address',
        'blood_type',
        'allergies',
        'current_medications',
        'insurance_number',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Relación con el usuario
     * Un paciente pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con las citas
     * Un paciente puede tener muchas citas
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Relación con los registros médicos
     * Un paciente puede tener muchos registros médicos
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Calcular la edad del paciente
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date->age;
    }

    /**
     * Scope para pacientes por género
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }
}

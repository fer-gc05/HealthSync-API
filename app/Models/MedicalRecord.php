<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los registros médicos
 * 
 * @property int $id
 * @property int $appointment_id
 * @property int $patient_id
 * @property int $medical_staff_id
 * @property string|null $subjective
 * @property string|null $objective
 * @property string|null $assessment
 * @property string|null $plan
 * @property array|null $vital_signs
 * @property string|null $prescriptions
 * @property string|null $recommendations
 * @property string|null $file_url
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MedicalRecord extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'medical_staff_id',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'vital_signs',
        'prescriptions',
        'recommendations',
        'file_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vital_signs' => 'array',
    ];

    /**
     * Relación con la cita
     * Un registro pertenece a una cita
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Relación con el paciente
     * Un registro pertenece a un paciente
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relación con el personal médico
     * Un registro pertenece a un personal médico
     */
    public function medicalStaff(): BelongsTo
    {
        return $this->belongsTo(MedicalStaff::class);
    }

    /**
     * Scope para registros por paciente
     */
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope para registros por personal médico
     */
    public function scopeByMedicalStaff($query, $medicalStaffId)
    {
        return $query->where('medical_staff_id', $medicalStaffId);
    }
}

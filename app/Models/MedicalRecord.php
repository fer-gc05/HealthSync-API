<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes;

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

    /**
     * Relación con los archivos adjuntos
     * Un registro puede tener muchos archivos
     */
    public function files(): HasMany
    {
        return $this->hasMany(MedicalRecordFile::class);
    }

    /**
     * Relación con el historial de auditoría
     * Un registro puede tener muchos registros de auditoría
     */
    public function audits(): HasMany
    {
        return $this->hasMany(MedicalRecordAudit::class);
    }

    /**
     * Scope para registros con prescripciones
     */
    public function scopeWithPrescriptions($query)
    {
        return $query->whereNotNull('prescriptions');
    }

    /**
     * Scope para registros con archivos
     */
    public function scopeWithFiles($query)
    {
        return $query->whereHas('files');
    }

    /**
     * Scope para búsqueda en contenido médico
     */
    public function scopeSearchContent($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('subjective', 'like', "%{$searchTerm}%")
              ->orWhere('objective', 'like', "%{$searchTerm}%")
              ->orWhere('assessment', 'like', "%{$searchTerm}%")
              ->orWhere('plan', 'like', "%{$searchTerm}%")
              ->orWhere('prescriptions', 'like', "%{$searchTerm}%")
              ->orWhere('recommendations', 'like', "%{$searchTerm}%");
        });
    }
}

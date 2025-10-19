<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentWaitlist extends Model
{
    protected $table = 'appointment_waitlist';

    protected $fillable = [
        'patient_id',
        'specialty_id',
        'preferred_doctor_id',
        'type',
        'reason',
        'urgent',
        'priority',
        'position',
        'status',
        'preferred_date',
        'notes',
    ];

    protected $casts = [
        'urgent' => 'boolean',
        'preferred_date' => 'datetime',
    ];

    /**
     * Relación con el paciente
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relación con la especialidad
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Relación con el doctor preferido
     */
    public function preferredDoctor(): BelongsTo
    {
        return $this->belongsTo(MedicalStaff::class, 'preferred_doctor_id');
    }

    /**
     * Scope para solicitudes en espera
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Scope para solicitudes asignadas
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    /**
     * Scope para solicitudes canceladas
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope para solicitudes urgentes
     */
    public function scopeUrgent($query)
    {
        return $query->where('urgent', true);
    }

    /**
     * Scope para solicitudes virtuales
     */
    public function scopeVirtual($query)
    {
        return $query->where('type', 'virtual');
    }

    /**
     * Scope para solicitudes presenciales
     */
    public function scopePresencial($query)
    {
        return $query->where('type', 'presencial');
    }

    /**
     * Scope para una especialidad específica
     */
    public function scopeForSpecialty($query, $specialtyId)
    {
        return $query->where('specialty_id', $specialtyId);
    }

    /**
     * Scope ordenado por prioridad y posición
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc')
                    ->orderBy('position', 'asc');
    }

    /**
     * Verificar si la solicitud está en espera
     */
    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    /**
     * Verificar si la solicitud es urgente
     */
    public function isUrgent(): bool
    {
        return $this->urgent;
    }

    /**
     * Verificar si es una solicitud virtual
     */
    public function isVirtual(): bool
    {
        return $this->type === 'virtual';
    }

    /**
     * Avanzar en la lista de espera
     */
    public function advancePosition(): void
    {
        $this->decrement('position');
    }

    /**
     * Retroceder en la lista de espera
     */
    public function retreatPosition(): void
    {
        $this->increment('position');
    }
}

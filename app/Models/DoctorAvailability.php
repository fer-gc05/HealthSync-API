<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorAvailability extends Model
{
    protected $table = 'doctor_availability';

    protected $fillable = [
        'medical_staff_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'specific_date',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_available' => 'boolean',
        'specific_date' => 'date',
    ];

    /**
     * Relación con el personal médico
     */
    public function medicalStaff(): BelongsTo
    {
        return $this->belongsTo(MedicalStaff::class);
    }

    /**
     * Scope para horarios disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope para un día específico de la semana
     */
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope para horarios específicos en una fecha
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('specific_date', $date);
    }

    /**
     * Scope para horarios regulares (no específicos)
     */
    public function scopeRegular($query)
    {
        return $query->whereNull('specific_date');
    }

    /**
     * Verificar si el horario está disponible
     */
    public function isAvailable(): bool
    {
        return $this->is_available;
    }

    /**
     * Obtener la duración del horario en minutos
     */
    public function getDurationInMinutes(): int
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        return $start->diffInMinutes($end);
    }
}

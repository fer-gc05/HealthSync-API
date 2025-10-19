<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para las citas médicas
 *
 * @property int $id
 * @property int $patient_id
 * @property int $medical_staff_id
 * @property int $specialty_id
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property string $type
 * @property string $status
 * @property string|null $reason
 * @property bool $urgent
 * @property int $priority
 * @property string|null $video_url
 * @property string|null $cancellation_reason
 * @property string|null $attendance_status
 * @property string|null $attendance_notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Appointment extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'medical_staff_id',
        'specialty_id',
        'start_date',
        'end_date',
        'type',
        'status',
        'reason',
        'urgent',
        'priority',
        'video_url',
        'cancellation_reason',
        'attendance_status',
        'attendance_notes',
        'google_event_id',
        'meeting_link',
        'meeting_password',
        'calendar_synced',
        'auto_assigned',
        'waitlist_position',
        'google_event_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'urgent' => 'boolean',
        'calendar_synced' => 'boolean',
        'auto_assigned' => 'boolean',
        'google_event_data' => 'array',
    ];

    /**
     * Relación con el paciente
     * Una cita pertenece a un paciente
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relación con el personal médico
     * Una cita pertenece a un personal médico
     */
    public function medicalStaff(): BelongsTo
    {
        return $this->belongsTo(MedicalStaff::class);
    }

    /**
     * Relación con la especialidad
     * Una cita pertenece a una especialidad
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Relación con los registros médicos
     * Una cita puede generar muchos registros médicos
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Relación con los mensajes
     * Una cita puede tener muchos mensajes
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Scope para citas por estado
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para citas urgentes
     */
    public function scopeUrgent($query)
    {
        return $query->where('urgent', true);
    }

    /**
     * Scope para citas por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para citas en un rango de fechas
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }

    /**
     * Scope para citas sincronizadas con Google Calendar
     */
    public function scopeSyncedWithGoogle($query)
    {
        return $query->where('calendar_synced', true);
    }

    /**
     * Scope para citas asignadas automáticamente
     */
    public function scopeAutoAssigned($query)
    {
        return $query->where('auto_assigned', true);
    }

    /**
     * Scope para citas virtuales
     */
    public function scopeVirtual($query)
    {
        return $query->where('type', 'virtual');
    }

    /**
     * Scope para citas presenciales
     */
    public function scopePresencial($query)
    {
        return $query->where('type', 'presencial');
    }

    /**
     * Scope para citas por prioridad
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope para citas del día
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_date', today());
    }

    /**
     * Scope para citas de la semana
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('start_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Verificar si la cita es virtual
     */
    public function isVirtual(): bool
    {
        return $this->type === 'virtual';
    }

    /**
     * Verificar si la cita está sincronizada con Google Calendar
     */
    public function isSyncedWithGoogle(): bool
    {
        return $this->calendar_synced && !empty($this->google_event_id);
    }

    /**
     * Obtener el enlace de la teleconsulta
     */
    public function getTeleconsultationLink(): ?string
    {
        return $this->isVirtual() ? $this->meeting_link : null;
    }

    /**
     * Verificar si la cita está en la lista de espera
     */
    public function isInWaitlist(): bool
    {
        return !is_null($this->waitlist_position);
    }
}

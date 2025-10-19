<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo para las notificaciones
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $sender_id
 * @property int|null $recipient_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property bool $read
 * @property \Carbon\Carbon|null $read_at
 * @property array|null $metadata
 * @property int|null $appointment_id
 * @property int|null $medical_record_id
 * @property int|null $priority
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Notification extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'sender_id',
        'recipient_id',
        'type',
        'title',
        'message',
        'read',
        'read_at',
        'metadata',
        'appointment_id',
        'medical_record_id',
        'priority',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
        'priority' => 'integer',
    ];

    /**
     * Relación con el usuario
     * Una notificación pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario emisor
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Usuario receptor (destinatario)
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Cita asociada
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Historial médico asociado
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope para notificaciones por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para notificaciones por usuario
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para destinatario
     */
    public function scopeByRecipient($query, $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    /**
     * Scope de búsqueda por título o mensaje
     */
    public function scopeSearch(Builder $query, ?string $q): Builder
    {
        if (!$q) {
            return $query;
        }

        return $query->where(function ($sub) use ($q) {
            $sub->where('title', 'like', "%$q%")
                ->orWhere('message', 'like', "%$q%");
        });
    }

    /**
     * Rango por fechas de creación
     */
    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead(): void
    {
        $this->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Marcar notificación como no leída
     */
    public function markAsUnread(): void
    {
        $this->update([
            'read' => false,
            'read_at' => null,
        ]);
    }
}

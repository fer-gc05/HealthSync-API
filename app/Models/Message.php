<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para los mensajes
 *
 * @property int $id
 * @property int $appointment_id
 * @property int $sender_id
 * @property int $recipient_id
 * @property string $content
 * @property string $type
 * @property bool $read
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Message extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_id',
        'sender_id',
        'recipient_id',
        'content',
        'type',
        'read',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Relación con la cita
     * Un mensaje pertenece a una cita
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Relación con el remitente
     * Un mensaje tiene un remitente (usuario)
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Relación con el destinatario
     * Un mensaje tiene un destinatario (usuario)
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Scope para mensajes no leídos
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope para mensajes por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para mensajes por remitente
     */
    public function scopeBySender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    /**
     * Scope para mensajes por destinatario
     */
    public function scopeByRecipient($query, $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    /**
     * Marcar mensaje como leído
     */
    public function markAsRead(): void
    {
        $this->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }
}

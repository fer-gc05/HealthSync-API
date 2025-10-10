<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'google_token',
        'google_refresh_token',
        'google_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_token_expires_at' => 'datetime',
        ];
    }

    /**
     * JWT Methods
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->getRoleNames()->first(),  // Solo el primer rol (singular)
            'permissions' => $this->getPermissionsViaRoles()->pluck('name')->toArray()
        ];
    }

    /**
     * Helper methods for roles
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isDoctor(): bool
    {
        return $this->hasRole('doctor');
    }

    public function isPatient(): bool
    {
        return $this->hasRole('patient');
    }

    /**
     * Scope para filtrar por rol
     */
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Relación 1:1 con Patient
     * Un usuario puede ser un paciente
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Relación 1:1 con MedicalStaff
     * Un usuario puede ser personal médico
     */
    public function medicalStaff(): HasOne
    {
        return $this->hasOne(MedicalStaff::class);
    }

    /**
     * Relación 1:N con Notification
     * Un usuario puede tener muchas notificaciones
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Relación 1:N con Message (como remitente)
     * Un usuario puede enviar muchos mensajes
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Relación 1:N con Message (como destinatario)
     * Un usuario puede recibir muchos mensajes
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    /**
     * Google OAuth Helper Methods
     */
    
    /**
     * Verificar si el usuario tiene una cuenta Google vinculada
     */
    public function hasGoogleAccount(): bool
    {
        return !is_null($this->google_id);
    }

    /**
     * Verificar si el token de Google está expirado
     */
    public function isGoogleTokenExpired(): bool
    {
        if (!$this->google_token_expires_at) {
            return true;
        }
        
        return $this->google_token_expires_at->isPast();
    }

    /**
     * Vincular cuenta Google al usuario
     */
    public function linkGoogleAccount(string $googleId, string $token, ?string $refreshToken = null, ?\DateTime $expiresAt = null): void
    {
        $this->update([
            'google_id' => $googleId,
            'google_token' => $token,
            'google_refresh_token' => $refreshToken,
            'google_token_expires_at' => $expiresAt,
        ]);
    }

    /**
     * Desvincular cuenta Google del usuario
     */
    public function unlinkGoogleAccount(): void
    {
        $this->update([
            'google_id' => null,
            'google_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
        ]);
    }
}

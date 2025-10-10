<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'expires_at',
        'used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];

    /**
     * Generar un código de verificación único
     */
    public static function generateCode(): string
    {
        do {
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->where('expires_at', '>', now())->exists());
        
        return $code;
    }

    /**
     * Crear un nuevo código de verificación
     */
    public static function createVerification(string $email): self
    {
        // Eliminar códigos anteriores para el mismo email
        self::where('email', $email)->delete();

        return self::create([
            'email' => $email,
            'code' => self::generateCode(),
            'expires_at' => now()->addHours(24), // Expira en 24 horas
            'used' => false
        ]);
    }

    /**
     * Verificar si el código es válido
     */
    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    /**
     * Marcar como usado
     */
    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    /**
     * Limpiar códigos expirados
     */
    public static function cleanExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }

    /**
     * Scope para códigos válidos
     */
    public function scopeValid($query)
    {
        return $query->where('used', false)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope para un email específico
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }
}

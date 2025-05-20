<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ESBTPAttendanceCode extends Model
{
    protected $table = 'esbtp_attendance_codes';

    protected $fillable = [
        'code',
        'date',
        'expires_at',
        'is_used',
        'attempts',
        'used_by',
        'used_at'
    ];

    protected $casts = [
        'date' => 'date',
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'used_at' => 'datetime'
    ];

    /**
     * Génère un nouveau code d'assiduité unique
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6)); // Génère un code alphanumérique de 6 caractères
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Crée un nouveau code pour une date donnée
     */
    public static function createForDate($date)
    {
        return self::create([
            'code' => self::generateUniqueCode(),
            'date' => $date,
            'expires_at' => Carbon::parse($date)->addHours(24),
            'is_used' => false,
            'attempts' => 0
        ]);
    }

    /**
     * Vérifie si le code est valide et peut être utilisé
     */
    public function isValid(): bool
    {
        return !$this->is_used &&
               $this->attempts < 3 &&
               $this->expires_at->isFuture();
    }

    /**
     * Incrémente le compteur de tentatives
     */
    public function incrementAttempts()
    {
        $this->increment('attempts');
    }

    /**
     * Marque le code comme utilisé par un enseignant
     */
    public function markAsUsed(int $enseignantId)
    {
        $this->update([
            'is_used' => true,
            'used_by' => $enseignantId,
            'used_at' => now()
        ]);
    }

    /**
     * Relation avec l'enseignant qui a utilisé le code
     */
    public function usedByTeacher()
    {
        return $this->belongsTo(ESBTPEnseignant::class, 'used_by');
    }
}

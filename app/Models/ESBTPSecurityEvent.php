<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ESBTPSecurityEvent extends Model
{
    protected $table = 'esbtp_security_events';

    protected $fillable = [
        'user_id',
        'event_type',
        'description',
        'ip_address',
        'device_info',
        'metadata'
    ];

    protected $casts = [
        'device_info' => 'array',
        'metadata' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logEvent(string $eventType, string $description, array $metadata = []): void
    {
        $request = request();

        self::create([
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'description' => $description,
            'ip_address' => $request->ip(),
            'device_info' => $request->device_info ?? [],
            'metadata' => $metadata
        ]);
    }
}

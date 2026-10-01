<?php

namespace App\Domain\Support\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/** Un repere durable du suivi KLASSCI Care (ex. : jusqu'ou le Master a ete lu). */
class CurseurSupport extends Model
{
    public const SUIVI_DEMANDES = 'suivi_demandes';

    protected $table = 'support_curseurs';

    protected $fillable = ['cle', 'valeur'];

    protected $casts = ['valeur' => 'datetime'];

    public static function lire(string $cle): ?CarbonInterface
    {
        return static::where('cle', $cle)->first()?->valeur;
    }

    public static function existe(string $cle): bool
    {
        return static::where('cle', $cle)->exists();
    }

    public static function poser(string $cle, CarbonInterface $valeur): void
    {
        static::updateOrCreate(['cle' => $cle], ['valeur' => $valeur]);
    }
}

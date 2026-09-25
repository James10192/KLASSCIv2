<?php

namespace App\Domain\Assistant\Cles;

use Illuminate\Database\Eloquent\Model;

/**
 * Une clé d'API de fournisseur d'IA, chiffrée par le cast `encrypted` (APP_KEY).
 * Masquée à la sérialisation : un toArray() ou un toJson() ne l'emporte jamais.
 */
class CleFournisseur extends Model
{
    protected $table = 'assistant_cles';

    protected $fillable = ['fournisseur', 'cle', 'updated_by'];

    protected $hidden = ['cle'];

    protected $casts = ['cle' => 'encrypted'];
}

<?php

namespace App\Domain\Assistant\Retours;

use Illuminate\Database\Eloquent\Model;

/** Avis d'une personne sur une réponse de l'assistant. */
class RetourDeReponse extends Model
{
    public const UTILE = 'utile';
    public const PAS_UTILE = 'pas_utile';

    /** Ce qui ne va pas, quand la réponse n'a pas aidé. */
    public const RAISONS = [
        'faux' => 'Une information est fausse',
        'incomplet' => 'Il manque quelque chose',
        'incompris' => "Ma question n'a pas été comprise",
        'autre' => 'Autre',
    ];

    protected $table = 'assistant_retours';

    protected $fillable = [
        'message_id', 'conversation_id', 'user_id', 'avis', 'raison', 'commentaire',
        'modele', 'palier', 'care_reference',
    ];
}

<?php

namespace App\Domain\Assistant\Consommation;

use Illuminate\Database\Eloquent\Model;

/**
 * Ce qu'un modèle a consommé dans un échange, et ce que ça a coûté.
 * Une ligne par modèle appelé : un repli sur un second modèle en fait deux.
 */
class LigneDeConsommation extends Model
{
    protected $table = 'assistant_consommations';

    protected $fillable = [
        'user_id', 'conversation_id', 'message_id', 'fonction', 'modele', 'fournisseur',
        'identifiant_modele', 'palier', 'appels', 'tokens_entree', 'tokens_sortie', 'tokens_cache',
        'cout_usd', 'cout_fcfa', 'taux_usd_fcfa', 'cout_exact', 'statut', 'latence_ms',
    ];

    protected $casts = [
        'cout_usd' => 'float',
        'cout_fcfa' => 'float',
        'taux_usd_fcfa' => 'float',
        'cout_exact' => 'boolean',
    ];
}

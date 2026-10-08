<?php

namespace App\Services\RendezVous;

use App\Models\ESBTPRdvCreneau;
use Carbon\Carbon;

/**
 * Matérialise en base la fermeture des journées arrivées à échéance.
 *
 * La règle d'affichage est également portée par CatalogueCreneaux : même avant
 * ce passage planifié, le jour courant n'est plus proposé aux familles lorsque
 * le réglage est actif. Ici on rend cet état durable dans `ouvert` pour que les
 * écrans de gestion et les outils voient la même chose.
 */
final class FermetureAutomatiqueCreneaux
{
    public function __construct(private readonly RendezVousReglages $reglages) {}

    public function fermer(?Carbon $jour = null): int
    {
        if (! $this->reglages->fermerJourAMinuit()) {
            return 0;
        }

        $jour = ($jour ?? Carbon::today())->copy()->startOfDay();

        return ESBTPRdvCreneau::query()
            ->where('ouvert', true)
            ->whereDate('date', '<=', $jour->toDateString())
            ->update(['ouvert' => false, 'updated_at' => now()]);
    }
}

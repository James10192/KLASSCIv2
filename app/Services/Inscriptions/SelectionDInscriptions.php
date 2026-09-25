<?php

namespace App\Services\Inscriptions;

use App\Models\ESBTPInscription;
use Illuminate\Http\Request;

/**
 * Sur quelles inscriptions porte une action groupee de la liste.
 *
 * Avec le defilement infini, « tout cocher » ne coche que les lignes deja
 * chargees. La barre propose alors, comme une messagerie, d'etendre la
 * selection a TOUT le filtre : la requete porte `scope=filtre` — la cle que
 * « Regenerer les frais » utilisait deja — et les memes parametres que la
 * liste, et la portee se recalcule ici, par FiltresListeInscriptions : le
 * meme code que l'ecran.
 *
 * Sans `scope=filtre` : les identifiants coches, `inscription_ids[]`.
 */
final class SelectionDInscriptions
{
    public const PORTEE_FILTRE = 'filtre';

    /**
     * Une validation groupee notifie chaque famille dans la meme requete ; au-dela
     * de ce nombre elle depasse le delai d'execution de l'hebergement. Borne
     * technique, pas un choix d'ecole : l'export, qui n'ecrit rien, n'en a pas.
     */
    public const MAX_ECRITURE = 500;

    public function __construct(private readonly FiltresListeInscriptions $filtres)
    {
    }

    public function porteSurLeFiltre(Request $request): bool
    {
        return $request->input('scope') === self::PORTEE_FILTRE;
    }

    /**
     * @return list<int>
     */
    public function identifiants(Request $request, bool $ecriture = true): array
    {
        if (! $this->porteSurLeFiltre($request)) {
            return array_values(array_map('intval', (array) $request->input('inscription_ids', [])));
        }

        // La recherche libre retrouve une personne par ressemblance, avec un
        // seuil : elle ne definit pas un ensemble que l'on puisse verifier avant
        // de confirmer. Meme refus que « Regenerer les frais ».
        abort_if(
            filled($request->input('search')),
            422,
            'Une recherche libre ne définit pas une portée : videz la recherche, ou cochez les lignes.',
        );

        $requete = $this->filtres->appliquer(ESBTPInscription::query(), $request);

        if ($ecriture) {
            $nombre = (clone $requete)->count();
            abort_if(
                $nombre > self::MAX_ECRITURE,
                422,
                'Le filtre compte '.number_format($nombre, 0, ',', ' ').' inscriptions : une action groupée en traite '
                    .self::MAX_ECRITURE.' au plus. Affinez le filtre (classe, niveau, période).',
            );
        }

        return $requete->pluck('esbtp_inscriptions.id')->map(fn ($id) => (int) $id)->all();
    }
}

<?php

namespace App\Services\Inscriptions;

use App\Models\ESBTPInscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Sur quelles inscriptions porte une action groupee de la liste.
 *
 * Avec le defilement infini, « tout cocher » ne coche que les lignes deja
 * chargees. La barre propose alors, comme une messagerie, d'etendre la
 * selection a TOUT le filtre : la requete porte `scope=filtre` et les memes
 * parametres que la liste, et la portee se recalcule ici, par
 * FiltresListeInscriptions : le meme code que l'ecran.
 *
 * C'est le seul endroit qui definit « tout le filtre » : « Regenerer les
 * frais » passe par requete(), les actions groupees par identifiants().
 *
 * Sans `scope=filtre` : les identifiants coches, `inscription_ids[]`.
 */
class SelectionDInscriptions
{
    public const PORTEE_FILTRE = 'filtre';

    public const REFUS_RECHERCHE = 'Une recherche libre ne définit pas une portée : videz la recherche, ou cochez les lignes.';

    /**
     * Une validation groupee notifie chaque famille dans la meme requete ; au-dela
     * de ce nombre elle depasse le delai d'execution de l'hebergement. Borne
     * technique, pas un choix d'ecole : l'export, qui n'ecrit rien, n'en a pas.
     * Elle vaut aussi pour les lignes cochees : faire defiler 800 lignes puis
     * tout cocher coute exactement le meme temps.
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
     * Les inscriptions de « tout le filtre ».
     */
    public function requete(Request $request): Builder
    {
        // La recherche libre retrouve une personne par ressemblance, avec un
        // seuil : elle ne definit pas un ensemble que l'on puisse verifier avant
        // de confirmer.
        abort_if(filled($request->input('search')), 422, self::REFUS_RECHERCHE);

        return $this->filtres->appliquer(ESBTPInscription::query(), $request);
    }

    /**
     * @return list<int>
     */
    public function identifiants(Request $request, bool $ecriture = true): array
    {
        if ($this->porteSurLeFiltre($request)) {
            $requete = $this->requete($request);
            if ($ecriture) {
                $this->borner((clone $requete)->count());
            }

            return $requete->pluck('esbtp_inscriptions.id')->map(fn ($id) => (int) $id)->all();
        }

        // Une ligne affichee deux fois ne doit pas etre traitee deux fois.
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('inscription_ids', []))));
        if ($ecriture) {
            $this->borner(count($ids));
        }

        return $ids;
    }

    protected function plafond(): int
    {
        return self::MAX_ECRITURE;
    }

    private function borner(int $nombre): void
    {
        $plafond = $this->plafond();
        abort_if(
            $nombre > $plafond,
            422,
            'La sélection compte '.number_format($nombre, 0, ',', ' ').' inscriptions : une action groupée en traite '
                .$plafond.' au plus. Affinez le filtre (classe, niveau, période).',
        );
    }
}

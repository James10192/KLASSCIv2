<?php

namespace App\Services\LMD;

use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Str;

/**
 * La clé interne d'une UE à partir du code saisi, et les refus qui vont avec.
 * Une seule réponse pour le formulaire d'UE (UniteEnseignementRequest) et pour
 * Nanan (`proposer_modification_maquette_lmd`) : deux copies avaient divergé.
 *
 * Le code n'est unique que DANS un parcours (CodeDeMaquette) :
 *
 * - une UE déjà propre à un parcours garde son suffixe quand on la renomme ;
 * - « propre à ce parcours » dérive la clé du parcours choisi (création), ou de
 *   l'unique parcours de l'UE (modification) ;
 * - sinon la clé est le code saisi, unique dans l'école.
 */
class CodeDUnite
{
    public function __construct(private CodeDeMaquette $maquette)
    {
    }

    /**
     * @return array{cle: ?string, parcours_propre: ?ESBTPLMDParcours, champ: ?string, refus: ?string}
     */
    public function resoudre(string $saisi, ?ESBTPUniteEnseignement $ue, ?ESBTPLMDParcours $parcours, bool $propreAuParcours): array
    {
        $resultat = ['cle' => null, 'parcours_propre' => null, 'champ' => null, 'refus' => null];
        $refus = fn (string $champ, string $message) => ['champ' => $champ, 'refus' => $message] + $resultat;

        if ($ue && CodeDeMaquette::suffixe($ue->code) !== null) {
            $resultat['cle'] = $saisi . CodeDeMaquette::SEPARATEUR . Str::after($ue->code, CodeDeMaquette::SEPARATEUR);
        } elseif (! $propreAuParcours) {
            $resultat['cle'] = $saisi;
        } elseif (! $ue) {
            if (! $parcours) {
                return $refus('parcours_id', 'Choisissez le parcours auquel cette UE est propre.');
            }
            $resultat['cle'] = $this->maquette->cleUnitePropre($saisi, $parcours);
        } else {
            // Rendre propre une UE existante : son parcours se déduit de ses
            // rattachements, il n'y en a qu'un, sinon il faut d'abord la retirer des autres.
            $sesParcours = $ue->parcoursMultiple()->pluck('esbtp_lmd_parcours.id')->unique()->values();
            if ($sesParcours->count() !== 1) {
                return $refus('propre_au_parcours', $sesParcours->isEmpty()
                    ? 'Rattachez d\'abord cette UE à son parcours (« Lier à des parcours »).'
                    : sprintf('Cette UE sert %d parcours. Retirez d\'abord ceux auxquels elle n\'appartient pas (« Lier à des parcours »).', $sesParcours->count()));
            }
            $resultat['parcours_propre'] = ESBTPLMDParcours::find($sesParcours->first());
            $resultat['cle'] = $this->maquette->cleUnitePropre($saisi, $resultat['parcours_propre'], $ue->id);
        }

        $prise = ESBTPUniteEnseignement::withTrashed()
            ->where('code', $resultat['cle'])
            ->when($ue, fn ($q) => $q->where('id', '!=', $ue->id))
            ->first(['id', 'name']);
        if ($prise) {
            return $refus('code', $propreAuParcours
                ? sprintf('Ce parcours a déjà son UE propre « %s » sous ce code : modifiez-la plutôt que d\'en créer une seconde.', $prise->name)
                : sprintf(
                    'Ce code est déjà celui de l\'UE « %s ». S\'il s\'agit d\'une autre UE, propre à un parcours, cochez « UE propre à ce parcours » et choisissez le parcours.',
                    $prise->name
                ));
        }

        $parcoursIds = $parcours ? [(int) $parcours->id] : [];
        if ($ue) {
            $parcoursIds = array_merge($parcoursIds, $ue->parcoursMultiple()->pluck('esbtp_lmd_parcours.id')->map(fn ($id) => (int) $id)->all());
        }
        foreach (array_unique($parcoursIds) as $parcoursId) {
            if ($deja = $this->maquette->autreUniteDuParcours($parcoursId, $resultat['cle'], $ue?->id)) {
                return $refus('code', sprintf(
                    'Ce parcours imprime déjà le code %s pour l\'UE « %s ». Un relevé ne peut pas porter deux fois le même code.',
                    $saisi,
                    $deja->name
                ));
            }
        }

        return $resultat;
    }
}

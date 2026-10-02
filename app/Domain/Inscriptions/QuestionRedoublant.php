<?php

namespace App\Domain\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPClasse;
use App\Services\Inscription\PreRemplissageCandidature;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * La question « Redoublant ? » posée là où une inscription se crée : nouvelle
 * inscription, « Accepter et inscrire » d'une candidature, réinscription d'une
 * demande en ligne.
 *
 * Le logiciel propose une réponse (même niveau que l'année d'avant) ; la
 * personne la garde ou la change, et dit pourquoi si elle la change. Pour un
 * élève qui n'a pas d'année précédente dans KLASSCI, la proposition est ce que
 * le transféré a déclaré, sinon « non » ({@see DeclarationDuTransfere}).
 *
 * Le motif se vérifie AVANT toute écriture : une inscription ne doit pas être
 * créée puis refusée à cause de la question.
 */
class QuestionRedoublant
{
    /** @return array<string, array<int, string>> */
    public static function regles(): array
    {
        return [
            'redoublant' => ['nullable', 'in:0,1'],
            'redoublant_motif' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * La réponse donnée, ou null : pas de réponse, ou une personne qui n'a pas
     * le droit de confirmer (sa réponse ne vaut pas confirmation, la valeur
     * reste déduite).
     */
    public static function reponse(Request $request): ?bool
    {
        $valeur = (string) $request->input('redoublant', '');
        if (! in_array($valeur, ['0', '1'], true) || ! $request->user()?->can(StatutRedoublant::PERMISSION)) {
            return null;
        }

        return $valeur === '1';
    }

    /** Ce que le logiciel propose pour cet élève dans cette classe, cette année. */
    public static function proposition(?int $etudiantId, ESBTPClasse $classe, ?ESBTPAnneeUniversitaire $annee): bool
    {
        if ($etudiantId === null || $annee === null || $classe->niveau_etude_id === null) {
            return false;
        }

        $avant = StatutRedoublant::niveauxDeLAnneePrecedente($etudiantId, [$annee])[(string) $annee->id] ?? null;

        return $avant !== null && (int) $avant === (int) $classe->niveau_etude_id;
    }

    /**
     * Pour une candidature : ce que le candidat a déclaré. Un transféré n'a pas
     * d'année précédente dans KLASSCI, sa réponse est la seule source ; sans
     * réponse, ou s'il ne vient pas d'un autre établissement, c'est « non ».
     */
    public static function propositionDeCandidature(?ESBTPCandidature $candidature): bool
    {
        return $candidature !== null && $candidature->est_transfert && $candidature->redouble_niveau_origine === true;
    }

    /**
     * La proposition d'une nouvelle inscription, d'après la candidature qu'elle
     * inscrit. Même lecture que le formulaire (`aInscrire`) : un identifiant
     * posé à la main, sur un dossier clos ou par qui ne traite pas les
     * candidatures, ne propose rien.
     */
    public static function propositionDeLaRequete(Request $request): bool
    {
        return self::propositionDeCandidature(PreRemplissageCandidature::aInscrire($request->integer('candidature_id')));
    }

    /** @throws ValidationException quand la réponse change la proposition sans motif */
    public static function exigerLeMotif(?bool $reponse, bool $proposition, ?string $motif): void
    {
        if ($reponse === null || $reponse === $proposition) {
            return;
        }

        if (mb_strlen(trim((string) $motif)) < StatutRedoublant::MOTIF_MINIMUM) {
            throw ValidationException::withMessages([
                'redoublant_motif' => 'Vous changez la réponse proposée pour « Redoublant ? » : dites pourquoi (au moins '
                    .StatutRedoublant::MOTIF_MINIMUM.' caractères).',
            ]);
        }
    }
}

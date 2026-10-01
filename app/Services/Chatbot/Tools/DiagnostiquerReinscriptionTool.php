<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use App\Services\Inscriptions\NormalisationTypeInscription;
use App\Services\Reinscription\ClassesDeReinscription;
use App\Services\Reinscription\SoldeDeReinscription;
use Illuminate\Support\Facades\Route;

/**
 * « Pourquoi la réinscription de X est bloquée ? » — la même lecture que
 * l'écran de réinscription (ESBTPReinscriptionController::show), mais rendue au
 * modèle avec ce qu'il faut pour proposer la suite.
 *
 * Elle relève aussi ce que l'écran ne dit pas : une décision « Redoublement » à
 * 0/20 posée sur une année sans AUCUNE note n'est pas un verdict, c'est une
 * absence de données (élève repris d'un autre outil, le plus souvent).
 *
 * Les montants ne partent au modèle que pour qui a le droit de les voir.
 */
class DiagnostiquerReinscriptionTool extends ChatbotTool
{
    public function name(): string
    {
        return 'diagnostiquer_reinscription';
    }

    public function description(): string
    {
        return "Explique pourquoi la réinscription d'UN étudiant est bloquée ou possible : l'inscription qu'il quitte, ce qu'il doit frais par frais, "
            . "ce qu'il a payé, s'il est déjà réinscrit, et si sa décision (passage/redoublement) repose sur de vraies notes. "
            . "À utiliser pour « réinscription bloquée », « pourquoi X ne peut pas se réinscrire », « impayés à la réinscription ». "
            . "Passe etudiant_id si la page le donne, sinon le matricule exact, sinon le nom.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'etudiant_id' => ['type' => 'integer', 'description' => "Identifiant de l'étudiant (pas celui de l'inscription)."],
                'matricule' => ['type' => 'string', 'description' => 'Matricule EXACT.'],
                'nom' => ['type' => 'string', 'description' => 'Nom et/ou prénoms, si ni identifiant ni matricule.'],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        [$etudiant, $probleme] = $this->etudiant($args);
        if (! $etudiant) {
            return $probleme;
        }

        $inscription = app(ClassesDeReinscription::class)->inscriptionQuittee((int) $etudiant->id);
        if (! $inscription) {
            return ['display_type' => 'text', 'message' => "{$this->studentFullName($etudiant)} n'a aucune inscription validée avec classe : rien à réinscrire."];
        }

        // Le droit de l'écran de réinscription, pas un autre : Nanan ne montre
        // pas plus que la page.
        $voirMontants = $user->can('finances.etudiants.voir');
        $paye = ESBTPPaiement::netPaidByCategory((int) $inscription->id, false);
        $frais = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)->where('is_active', true)
            ->with('fraisCategory:id,name')->get()
            ->map(fn (ESBTPFraisSubscription $s) => [
                'categorie_id' => (int) $s->frais_category_id,
                'frais' => (string) ($s->fraisCategory->name ?? '#'.$s->frais_category_id),
                'du' => $s->chargedAmount(),
                'paye' => round((float) ($paye[$s->frais_category_id] ?? 0), 2),
            ])->values();
        // Le solde qui bloque est celui de l'écran ; le détail par frais ne
        // sert qu'à l'expliquer.
        $du = SoldeDeReinscription::du((int) $inscription->id);
        $verse = SoldeDeReinscription::paye((int) $inscription->id);
        $solde = SoldeDeReinscription::solde((int) $inscription->id);

        $notes = ESBTPNote::query()->where('etudiant_id', $etudiant->id)
            ->whereHas('evaluation', fn ($q) => $q->where('annee_universitaire_id', $inscription->annee_universitaire_id))
            ->count();
        // L'inscription « quittée » est la plus récente validée : si elle est
        // déjà sur l'année courante, l'élève y est inscrit et il n'y a rien à
        // débloquer. Sinon, une réinscription non annulée cette année compte —
        // la même lecture que l'écran.
        $dejaReinscrit = (bool) $inscription->anneeUniversitaire?->is_current
            || ESBTPInscription::query()->where('etudiant_id', $etudiant->id)
                ->where('type_inscription', NormalisationTypeInscription::REINSCRIPTION)
                ->where('status', '!=', 'annulée')
                ->whereHas('anneeUniversitaire', fn ($q) => $q->where('is_current', true))
                ->exists();

        $estSuperAdmin = method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        $bloque = ! $dejaReinscrit && $solde > 0 && ! $estSuperAdmin;

        return [
            'display_type' => 'cards',
            'results' => [[
                'nom' => $this->studentFullName($etudiant),
                'initials' => $this->studentInitials($etudiant),
                'classe' => $inscription->classe?->name ?? 'N/A',
                'detail' => 'Inscription quittée : '.($inscription->anneeUniversitaire?->name ?? '?'),
                'statut' => $dejaReinscrit ? 'Déjà réinscrit' : ($bloque ? 'Bloquée (impayé)' : 'Réinscription possible'),
                'reste' => $voirMontants ? $this->formatFCFA(max(0.0, $solde)) : null,
                'lien' => Route::has('esbtp.reinscription.show') ? route('esbtp.reinscription.show', $etudiant->id, false) : null,
                'lien_label' => 'Réinscription',
                'lien_icon' => 'fas fa-redo',
            ]],
            'diagnostic' => [
                'etudiant_id' => (int) $etudiant->id,
                'matricule' => $etudiant->matricule,
                'inscription_id' => (int) $inscription->id,
                'annee_quittee' => $inscription->anneeUniversitaire?->name,
                'annee_quittee_id' => (int) $inscription->annee_universitaire_id,
                'deja_reinscrit_cette_annee' => $dejaReinscrit,
                'bloquee' => $bloque,
                'cause' => $dejaReinscrit ? 'deja_reinscrit' : ($solde > 0 ? 'solde_impaye' : null),
                'solde' => $voirMontants ? max(0.0, $solde) : 'masqué (droit finances requis)',
                'frais' => $voirMontants ? $frais->all() : $frais->map(fn ($f) => ['categorie_id' => $f['categorie_id'], 'frais' => $f['frais']])->all(),
                'aucun_versement_enregistre' => $verse <= 0.0 && $du > 0.0,
                'notes_sur_l_annee_quittee' => $notes,
                'decision_fiable' => $notes > 0,
                'peut_autoriser_reliquat' => $estSuperAdmin,
                'peut_ajuster_le_du' => $user->can('frais.souscriptions.ajuster'),
            ],
        ];
    }

    /** @return array{0: ?ESBTPEtudiant, 1: array} */
    private function etudiant(array $args): array
    {
        if (($id = (int) ($args['etudiant_id'] ?? 0)) > 0) {
            $e = ESBTPEtudiant::find($id);

            return [$e, $e ? [] : ['display_type' => 'text', 'message' => "Étudiant #{$id} introuvable."]];
        }
        if (($matricule = trim((string) ($args['matricule'] ?? ''))) !== '') {
            $e = ESBTPEtudiant::where('matricule', $matricule)->first();

            return [$e, $e ? [] : ['display_type' => 'text', 'message' => "Aucun étudiant au matricule {$matricule}."]];
        }
        $nom = trim((string) ($args['nom'] ?? ''));
        if ($nom === '') {
            return [null, ['display_type' => 'text', 'message' => "Indiquez l'étudiant (identifiant, matricule ou nom)."]];
        }
        $q = ESBTPEtudiant::query();
        $this->applyFuzzyNameSearch($q, $nom);
        $trouves = $q->limit(6)->get(['id', 'nom', 'prenoms', 'matricule']);
        if ($trouves->count() === 1) {
            return [$trouves->first(), []];
        }

        return [null, [
            'display_type' => 'text',
            'message' => $trouves->isEmpty()
                ? "Aucun étudiant ne correspond à « {$nom} »."
                : 'Plusieurs étudiants correspondent : demande lequel (matricule).',
            'candidats' => $trouves->map(fn ($e) => ['etudiant_id' => $e->id, 'nom' => $this->studentFullName($e), 'matricule' => $e->matricule])->all(),
        ]];
    }
}

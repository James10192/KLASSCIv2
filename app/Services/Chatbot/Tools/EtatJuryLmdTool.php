<?php

namespace App\Services\Chatbot\Tools;

use App\Domain\OfficialDocuments\Services\JuryPvIssuanceGuard;
use App\Domain\OfficialDocuments\Services\PreparationDuJury;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDJuryMembre;
use Illuminate\Support\Facades\Route;

/**
 * « Où en est le jury de L2 Droit ? », « pourquoi je ne peux pas générer le
 * PV ? » : la même lecture que l'écran du jury, rendue au modèle.
 *
 * Les étapes et leurs motifs viennent de JuryPvIssuanceGuard, l'autorité qui
 * décide si le PV peut être émis : Nanan ne dit jamais « prêt » quand l'écran
 * refuserait. La confidentialité est celle de l'écran : un simple membre ne
 * voit que les jurys dont il fait partie.
 *
 * Lecture seule. Signer et publier restent des gestes humains, faits sur
 * l'écran du jury par la personne concernée.
 */
class EtatJuryLmdTool extends ChatbotTool
{
    private const MAX_JURYS = 8;
    private const MOTIFS_PAR_ETAPE = 3;

    public function name(): string
    {
        return 'etat_jury_lmd';
    }

    public function description(): string
    {
        return "Dit où en est un jury de délibération LMD et ce qui manque avant son procès-verbal (PV) : notes validées, composition et quorum, "
            . "décisions calculées, signatures, avec les membres qui n'ont pas signé. Donne aussi le numéro du PV s'il est émis et les liens "
            . "du PV officiel, du PV détaillé du semestre et du PV annuel. À utiliser pour « où en est le jury », « pourquoi le PV est grisé », "
            . "« qui n'a pas signé », « télécharger le PV ». Passe jury_id si la page le donne ; sinon le nom de la classe et le semestre ; "
            . "sans rien, liste les derniers jurys.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'jury_id' => ['type' => 'integer', 'description' => 'Identifiant du jury.'],
                'classe' => ['type' => 'string', 'description' => 'Nom ou code de la classe, par exemple « L2 Droit Privé ».'],
                'semestre' => ['type' => 'integer', 'description' => 'Numéro du semestre (1 à 10).'],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $jurys = $this->jurys($args, $user);
        if ($jurys->isEmpty()) {
            // Sans jury, le modèle complétait de mémoire et inventait (un rôle
            // de « rapporteur », des notes « saisies » au lieu de validées).
            // Les étapes et les rôles réels lui sont donc fournis.
            return [
                'display_type' => 'text',
                'message' => 'Aucun jury de délibération ne correspond, ou aucun ne vous est accessible.',
                'diagnostic' => [
                    'aucun_jury' => true,
                    'etapes_avant_le_pv' => array_column(app(PreparationDuJury::class)->etapes([]), 'aide', 'titre'),
                    'roles_des_membres' => array_map(fn ($r) => ESBTPLMDJuryMembre::LIBELLES_ROLES[$r] ?? $r, ESBTPLMDJuryMembre::ROLES),
                    'ou_creer' => Route::has('esbtp.lmd.jurys.index') ? route('esbtp.lmd.jurys.index', [], false) : null,
                ],
            ];
        }

        // Un jury visé reçoit tout son détail ; une liste ne garde que les
        // verdicts. Le résumé rendu au modèle est plafonné : les motifs de huit
        // jurys le débordaient, et la coupe tombait au milieu des verdicts.
        $detaille = $jurys->count() === 1;
        $cartes = [];
        $diagnostic = [];
        foreach ($jurys as $jury) {
            $etat = $this->etat($jury);
            $cartes[] = [
                'nom' => $jury->libelle,
                'classe' => trim(($jury->classe?->name ?? '').' · S'.$jury->semestre, ' ·'),
                'detail' => $jury->pv_numero ? 'PV '.$jury->pv_numero : 'PV non émis',
                'statut' => $etat['statut'],
                'lien' => Route::has('esbtp.lmd.jurys.show') ? route('esbtp.lmd.jurys.show', $jury, false) : null,
                'lien_label' => 'Ouvrir le jury',
                'lien_icon' => 'fas fa-gavel',
            ];
            $ligne = [
                'jury_id' => (int) $jury->id,
                'pret_pour_le_pv' => $etat['pret'],
                'statut' => $etat['statut'],
                'etapes_manquantes' => $detaille
                    ? $etat['manquantes']
                    : array_column($etat['manquantes'], 'etape'),
                'libelle' => $jury->libelle,
                'classe' => $jury->classe?->name,
                'semestre' => $jury->semestre,
                'pv_numero' => $jury->pv_numero,
            ];
            if ($detaille) {
                $ligne['membres_sans_signature'] = $etat['termine'] ? [] : $this->sansSignature($jury);
                $ligne['liens'] = $this->liens($jury, $user);
            }
            $diagnostic[] = $ligne;
        }
        $resume = ['jurys' => $diagnostic];
        if (! $detaille) {
            $resume['note'] = 'Pour les motifs d’un jury, rappelle etat_jury_lmd avec son jury_id.';
        }

        return ['display_type' => 'cards', 'results' => $cartes, 'diagnostic' => $resume];
    }

    /**
     * Le verdict d'un jury, par le contrôle de l'écran. Un jury que le contrôle
     * ne sait pas lire est signalé tel quel, sans faire tomber la liste.
     */
    private function etat(ESBTPLMDJury $jury): array
    {
        if (in_array($jury->status, ['publie', 'archive'], true)) {
            return ['termine' => true, 'pret' => false, 'statut' => $jury->libelleStatut(), 'manquantes' => []];
        }
        try {
            $etapes = app(PreparationDuJury::class)->etapes(app(JuryPvIssuanceGuard::class)->raisonsParEtape($jury));
        } catch (\LogicException $e) {
            // Les messages du contrôle d'émission sont écrits pour la personne.
            return ['termine' => false, 'pret' => false, 'statut' => 'Lecture impossible : '.$e->getMessage(), 'manquantes' => []];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ['termine' => false, 'pret' => false, 'statut' => 'Lecture impossible : ce jury n’existe plus.', 'manquantes' => []];
        }
        $manquantes = [];
        foreach ($etapes as $e) {
            if (! $e['ok']) {
                $manquantes[] = [
                    'etape' => $e['titre'],
                    'motifs' => array_slice($e['raisons'], 0, self::MOTIFS_PAR_ETAPE),
                    'ou_corriger' => $e['onglet'] !== null ? 'onglet '.$e['onglet'].' de l’écran du jury' : 'fiches de notes du pilotage académique',
                ];
            }
        }

        return [
            'termine' => false,
            'pret' => $manquantes === [],
            'statut' => $manquantes === [] ? 'Prêt pour le PV' : count($manquantes).' étape(s) à terminer',
            'manquantes' => $manquantes,
        ];
    }

    /** Les jurys visés, dans le périmètre de l'écran : tous pour qui préside ou délibère, les siens pour un membre. */
    private function jurys(array $args, $user)
    {
        $query = ESBTPLMDJury::query()->with(['classe:id,name', 'membres.user:id,name'])->latest('date_jury')->latest('id');

        if (! ESBTPLMDJury::utilisateurVoitTousLesJurys($user)) {
            $query->whereHas('membres', fn ($m) => $m->where('user_id', $user->getAuthIdentifier()));
        }
        if (! empty($args['jury_id'])) {
            return $query->whereKey((int) $args['jury_id'])->get();
        }
        // Comme la liste de l'écran : l'année en cours, sauf jury nommé.
        $courante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->value('id');
        if ($courante) {
            $query->where('annee_universitaire_id', $courante);
        }
        if (! empty($args['classe'])) {
            $this->applyClasseSearch($query, (string) $args['classe']);
        }
        if (! empty($args['semestre'])) {
            $query->where('semestre', (int) $args['semestre']);
        }

        return $query->limit(self::MAX_JURYS)->get();
    }

    private function sansSignature(ESBTPLMDJury $jury): array
    {
        return $jury->membres
            ->filter(fn ($m) => $m->present && ! $m->hasSigned())
            ->map(fn ($m) => ['nom' => $m->user?->name, 'role' => $m->role])
            ->values()->all();
    }

    private function liens(ESBTPLMDJury $jury, $user): array
    {
        $liens = [];
        if ($jury->pv_numero && Route::has('esbtp.lmd.jurys.pv-download')) {
            $liens['pv_officiel'] = route('esbtp.lmd.jurys.pv-download', $jury, false);
        }
        if ($user->can('lmd.pv.export')) {
            if ($jury->semestre && Route::has('esbtp.lmd.jurys.pv-semestre.pdf')) {
                $liens['pv_detaille_semestre_pdf'] = route('esbtp.lmd.jurys.pv-semestre.pdf', $jury, false);
                $liens['pv_detaille_semestre_excel'] = route('esbtp.lmd.jurys.pv-semestre.excel', $jury, false);
            }
            if (Route::has('esbtp.lmd.jurys.pv-annuel.pdf')) {
                $liens['pv_annuel_pdf'] = route('esbtp.lmd.jurys.pv-annuel.pdf', $jury, false);
            }
        }

        return $liens;
    }
}

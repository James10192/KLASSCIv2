<?php

namespace App\Domain\Assistant\Actions\Bulletins;

use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Designations;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Bulletins\Taches\BulletinTache;
use App\Domain\Bulletins\Taches\LancementTachesBulletins;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;

/**
 * Générer les bulletins BTS manquants d'une classe. Le pré-contrôle de
 * BtsBulkBulletinGenerationService fait autorité, comme sur l'écran et dans
 * `POST /api/cli/bulletins/generate-missing` : qui sera traité, et si la
 * génération est permise.
 *
 * L'écriture lance la même tâche en arrière-plan que l'écran (génération par
 * tranches, toast de suivi), pas la génération d'un bloc de la CLI : un clic
 * « Valider » ne doit pas tenir une requête au-delà de la limite d'exécution
 * de l'hébergement. Les tranches appellent le même moteur de génération.
 *
 * Classe LMD : refusée (lmd-bts-bulletin-separation) ; ses bulletins passent
 * par /esbtp/lmd/bulletins.
 */
class GenererBulletinsManquants extends ActionAgent
{
    use Designations;

    public function __construct(
        private BtsBulkBulletinGenerationService $generation,
        private LancementTachesBulletins $lancement,
    ) {
    }

    public function cle(): string
    {
        return 'generation_bulletins';
    }

    public function libelle(): string
    {
        return 'Pré-contrôle de la génération des bulletins…';
    }

    public function description(): string
    {
        return "PROPOSE de générer les bulletins BTS manquants d'UNE classe pour un semestre (les bulletins déjà générés ne sont pas refaits). "
            . "Le pré-contrôle dit qui sera traité ou ce qui bloque : relaie le blocage tel quel. "
            . "Un motif de bulletin incomplet n'est accepté que s'il vient de la personne. Classe LMD : refusée.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => ['type' => 'string', 'description' => 'Code ou identifiant de la classe.'],
                'periode' => ['type' => 'string', 'description' => 'S1 ou S2.'],
                'annee' => ['type' => 'string', 'description' => "Libellé exact de l'année. Omettre pour l'année courante."],
                'motif_incomplet' => ['type' => 'string', 'description' => 'Seulement si le pré-contrôle le demande ET que la personne le donne : pourquoi générer des bulletins incomplets.'],
            ],
            'required' => ['classe', 'periode'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Génération des bulletins';
        [$classe, $mClasse] = $this->designerClasse($args['classe_id'] ?? $args['classe'] ?? '');
        [$annee, $mAnnee] = $this->designerAnnee($args);
        $periode = $this->designerSemestre($args['periode'] ?? '');
        $manques = array_values(array_filter([$mClasse, $mAnnee, $periode === null ? 'Quel semestre (S1 ou S2) ?' : null]));
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }
        if (CoherenceSystemeAcademique::classeEstLmd($classe->systeme_academique)) {
            return $this->seulManque($titre, 'Cette classe est LMD : ses bulletins se génèrent depuis /esbtp/lmd/bulletins.');
        }

        $preflight = $this->generation->preflight($classe, (int) $annee->id, $periode, $user, false);
        $motif = trim((string) ($args['motif_incomplet'] ?? ''));
        $statut = (string) ($preflight['status'] ?? '');
        $permis = $statut === 'ready' || ($statut === 'needs_reason' && mb_strlen($motif) >= 8);
        if (! $permis) {
            return $this->seulManque($titre, $this->pourquoiBloque($preflight, $statut));
        }
        $ids = array_values(array_map('intval', $preflight['student_ids'] ?? []));
        if ($ids === []) {
            return $this->seulManque($titre, 'Aucun bulletin à générer pour cette classe et ce semestre.');
        }

        $etudiants = ESBTPEtudiant::whereIn('id', $ids)->orderBy('nom')->orderBy('prenoms')->get(['id', 'nom', 'prenoms', 'matricule']);

        return new Proposition(
            titre: "Générer les bulletins de {$classe->name} ({$this->libelleSemestre($periode)})",
            resume: count($ids) . " bulletin(s) à générer pour {$classe->name}, {$this->libelleSemestre($periode)}, {$annee->name}. Les bulletins déjà générés ne sont pas refaits.",
            tableau: [
                'colonnes' => ['Étudiant', 'Matricule'],
                'lignes' => $etudiants->map(fn ($e) => [trim(mb_strtoupper((string) $e->nom, 'UTF-8') . ' ' . $e->prenoms), (string) $e->matricule])->all(),
            ],
            avertissements: array_values(array_filter([
                $statut === 'needs_reason' ? 'Bulletins INCOMPLETS : données académiques manquantes. Motif journalisé : « ' . $motif . ' ».' : null,
                ((int) ($preflight['existing_count'] ?? 0)) > 0 ? $preflight['existing_count'] . ' bulletin(s) déjà généré(s) restent tels quels.' : null,
                'La génération tourne en arrière-plan ; une notification prévient quand elle est terminée.',
            ])),
            donnees: ['classe_id' => (int) $classe->id, 'annee_universitaire_id' => (int) $annee->id, 'periode' => $periode, 'motif' => $motif !== '' ? $motif : null],
            etat: ['statut' => $statut, 'etudiants' => $ids, 'existants' => (int) ($preflight['existing_count'] ?? 0)],
            risque: $statut === 'needs_reason' ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $classe = ESBTPClasse::find($d['classe_id']);
        if (! $classe) {
            throw new PropositionPerimee("Cette classe n'existe plus.");
        }
        $preflight = $this->generation->preflight($classe, (int) $d['annee_universitaire_id'], $d['periode'], $user, false);
        $ids = array_values(array_map('intval', $preflight['student_ids'] ?? []));
        if (($preflight['status'] ?? '') !== $proposition->etat['statut'] || $ids !== $proposition->etat['etudiants']) {
            throw new PropositionPerimee('Le pré-contrôle a changé depuis la proposition.');
        }

        $tache = $this->lancement->generation($user, $classe, (int) $d['annee_universitaire_id'], (string) $preflight['periode'], $ids, false, $d['motif']);

        return [
            'message' => 'Génération lancée en arrière-plan pour ' . count($ids) . " bulletin(s) de {$classe->name} : une notification prévient quand elle est terminée.",
            'lien' => route('esbtp.bulletins.select', [], false),
            'model_type' => BulletinTache::class,
            'model_id' => (int) $tache->id,
            'details' => ['tache_id' => (int) $tache->id, 'bulletins' => count($ids)],
        ];
    }

    private function pourquoiBloque(array $preflight, string $statut): string
    {
        $message = (string) ($preflight['message'] ?? 'Des prérequis bloquent la génération.');
        if ($statut === 'needs_reason') {
            return $message . ' Demande à la personne le motif (8 caractères au moins) ; ne l\'invente pas.';
        }
        $details = collect($preflight['blocking_errors'] ?? [])->pluck('message')->filter()->unique()->take(4)->implode(' ; ');

        return $message . ($details !== '' ? ' Détail : ' . $details . '.' : '');
    }
}

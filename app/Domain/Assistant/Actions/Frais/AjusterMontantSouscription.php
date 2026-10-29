<?php

namespace App\Domain\Assistant\Actions\Frais;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Comptabilite\Souscriptions\AjustementMontantSouscription;
use App\Domain\Comptabilite\Souscriptions\AjustementRefuse;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\Route;

/**
 * Propose de changer ce qu'UN étudiant doit sur UN frais : exonération, remise,
 * ou dette reprise d'un autre outil qui ne correspond pas à l'état de compte
 * de l'école. Cas typique : une réinscription bloquée par un « impayé » que
 * l'élève ne devait pas.
 *
 * Toute la règle vit dans AjustementMontantSouscription (le même chemin que la
 * CLI) : jamais sous ce qui est payé, motif obligatoire, audit, relecture sous
 * verrou à la validation.
 */
class AjusterMontantSouscription extends ActionAgent
{
    public function __construct(private AjustementMontantSouscription $ajustement)
    {
    }

    public function cle(): string
    {
        return 'ajustement_souscription';
    }

    public function libelle(): string
    {
        return 'Préparation de l’ajustement du montant dû…';
    }

    public function description(): string
    {
        return "PROPOSE de changer ce qu'UN étudiant doit sur UN frais d'UNE inscription (exonération, remise, dette reprise à tort). "
            . "Utilise l'inscription_id donné par diagnostiquer_reinscription. Le nouveau montant et le motif viennent de la personne ou d'un document qu'elle a fourni : "
            . "ne les invente jamais. Le serveur refuse de descendre sous ce qui est déjà payé. Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'inscription_id' => ['type' => 'integer', 'description' => "Identifiant de l'INSCRIPTION (pas de l'étudiant)."],
                'categorie_id' => ['type' => 'integer', 'description' => "Identifiant du frais, si l'inscription en a plusieurs."],
                'montant' => ['type' => 'number', 'description' => 'Nouveau montant dû, en FCFA (0 pour une exonération totale).'],
                'motif' => ['type' => 'string', 'description' => 'Pourquoi, en une phrase (au moins 10 caractères) : source, décision, document.'],
            ],
            'required' => ['inscription_id', 'montant', 'motif'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Ajuster le montant dû';
        $motif = trim((string) ($args['motif'] ?? ''));
        if (! isset($args['montant']) || ! is_numeric($args['montant'])) {
            return new Proposition(titre: $titre, resume: '', manques: ['Quel est le nouveau montant dû ?']);
        }

        $examen = $this->ajustement->examiner(
            (int) ($args['inscription_id'] ?? 0),
            isset($args['categorie_id']) ? (int) $args['categorie_id'] : null,
            (float) $args['montant'],
        );

        $manques = $examen['refus'];
        if (mb_strlen($motif) < AjustementMontantSouscription::MOTIF_MIN) {
            $manques[] = 'Pourquoi ce changement ? Le motif (au moins '.AjustementMontantSouscription::MOTIF_MIN.' caractères) reste au dossier.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_values($manques));
        }

        $inscription = ESBTPInscription::with(['etudiant:id,nom,prenoms,matricule', 'classe:id,name', 'anneeUniversitaire:id,name'])
            ->find($examen['inscription_id']);
        $nom = trim(($inscription->etudiant->nom ?? '').' '.($inscription->etudiant->prenoms ?? ''));
        $fcfa = fn (?float $v) => number_format((float) $v, 0, ',', ' ').' FCFA';

        return new Proposition(
            titre: $titre,
            resume: sprintf(
                '%s (%s, %s) devra %s au lieu de %s sur « %s ». Solde de l’inscription : %s → %s.',
                $nom, $inscription->classe->name ?? '?', $inscription->anneeUniversitaire->name ?? '?',
                $fcfa($examen['apres']), $fcfa($examen['avant']), $examen['categorie'],
                $fcfa($examen['solde_avant']), $fcfa($examen['solde_apres'])
            ),
            tableau: [
                'colonnes' => ['Étudiant', 'Matricule', 'Frais', 'Dû actuel', 'Déjà payé', 'Nouveau dû', 'Motif'],
                'lignes' => [[
                    $nom, (string) ($inscription->etudiant->matricule ?? '—'), (string) $examen['categorie'],
                    $fcfa($examen['avant']), $fcfa($examen['deja_paye']), $fcfa($examen['apres']), $motif,
                ]],
            ],
            avertissements: $examen['avertissements'],
            donnees: [
                'inscription_id' => $examen['inscription_id'],
                'categorie_id' => $examen['categorie_id'],
                'montant' => $examen['apres'],
                'motif' => $motif,
            ],
            etat: $examen['etat'],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('frais.souscriptions.ajuster')) {
            throw new PropositionPerimee("Vous n'avez plus le droit d'ajuster un montant dû.");
        }

        $d = $proposition->donnees;
        $examen = $this->ajustement->examiner((int) $d['inscription_id'], (int) $d['categorie_id'], (float) $d['montant']);
        if ($examen['etat'] !== $proposition->etat) {
            throw new PropositionPerimee('Ce frais a changé depuis la proposition.');
        }

        try {
            $resultat = $this->ajustement->appliquer($examen, (string) $d['motif'], (int) $user->id);
        } catch (AjustementRefuse $e) {
            throw new PropositionPerimee($e->getMessage());
        }

        $etudiantId = ESBTPInscription::whereKey($d['inscription_id'])->value('etudiant_id');

        return [
            'message' => sprintf(
                'Montant dû ajusté : %s → %s FCFA. Solde de l’inscription : %s FCFA.',
                number_format($resultat['avant'], 0, ',', ' '),
                number_format($resultat['apres'], 0, ',', ' '),
                number_format((float) $examen['solde_apres'], 0, ',', ' ')
            ),
            'lien' => $etudiantId && Route::has('esbtp.reinscription.show') ? route('esbtp.reinscription.show', $etudiantId, false) : null,
            'model_type' => ESBTPFraisSubscription::class,
            'model_id' => $resultat['souscription_id'],
            'details' => $resultat,
        ];
    }
}

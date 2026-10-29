<?php

declare(strict_types=1);

namespace App\Domain\Notes\Reclamations;

use App\Models\ESBTPReclamationNote;

/**
 * Une réclamation mise en forme pour l'écran, côté élève ou côté personnel.
 *
 * Les deux pages lisent la même forme : un seul endroit décide de ce qui est
 * montré, et l'élève ne voit jamais l'avis de l'enseignant avant la décision
 * (il ne doit pas lire une proposition que le personnel habilité peut refuser).
 */
final class PresentationReclamation
{
    /** @return array<string, mixed> */
    public static function pour(ESBTPReclamationNote $r, bool $cotePersonnel): array
    {
        $bareme = (float) ($r->evaluation->bareme ?? 20) ?: 20.0;
        $tranchee = ! $r->estOuverte();

        $ligne = [
            'id' => $r->id,
            'matiere' => (string) ($r->matiere->name ?? 'Matière'),
            'evaluation' => (string) ($r->evaluation->titre ?? 'Évaluation'),
            'evaluation_type' => (string) ($r->evaluation->type ?? ''),
            'bareme' => $bareme,
            'note_initiale' => $r->note_initiale !== null ? (float) $r->note_initiale : null,
            'note_finale' => $tranchee && $r->note_finale !== null ? (float) $r->note_finale : null,
            'statut' => $r->statut->value,
            'statut_label' => $r->statut->label(),
            'ton' => $r->statut->ton(),
            'ouverte' => ! $tranchee,
            'motif' => (string) $r->motif,
            'depose_le' => $r->created_at?->format('d/m/Y à H:i'),
            'depose_il_y_a' => $r->created_at?->diffForHumans(),
            'commentaire_decision' => $tranchee ? $r->commentaire_decision : null,
            'decide_le' => $r->decision_at?->format('d/m/Y'),
            'photo_pdf' => str_ends_with(strtolower((string) $r->photo_path), '.pdf'),
            'photo_url' => route($cotePersonnel ? 'esbtp.reclamations-notes.photo' : 'esbtp.mes-reclamations.photo', $r->id),
        ];

        if (! $cotePersonnel) {
            return $ligne;
        }

        return $ligne + [
            'etudiant' => trim(($r->etudiant->nom ?? '').' '.($r->etudiant->prenoms ?? '')),
            'matricule' => (string) ($r->etudiant->matricule ?? ''),
            'classe' => (string) ($r->classe->name ?? ''),
            'enseignant' => $r->enseignant?->name,
            'enseignant_id' => $r->enseignant_id,
            'avis' => $r->avis,
            'note_proposee' => $r->note_proposee !== null ? (float) $r->note_proposee : null,
            'commentaire_enseignant' => $r->commentaire_enseignant,
            'avis_par' => $r->avisPar?->name,
            'avis_le' => $r->avis_at?->format('d/m/Y à H:i'),
            'decide_par' => $r->decisionPar?->name,
        ];
    }

    /** @return list<string> */
    public static function relations(): array
    {
        return ['evaluation:id,titre,type,bareme', 'matiere:id,name', 'etudiant:id,nom,prenoms,matricule', 'classe:id,name', 'enseignant:id,name', 'avisPar:id,name', 'decisionPar:id,name'];
    }
}

<?php

namespace App\Services\Enseignants;

use App\Models\ESBTPClasse;
use App\Services\LMD\EnseignantDePlanificationLmd;
use Illuminate\Validation\ValidationException;

/**
 * Lecture SEULE des affectations LMD, distincte des saisies libres BTS.
 * Les ECUE et les enseignants proviennent du service canonique de maquette
 * et planification, avec son repli explicite sur les evaluations.
 */
final class AffectationsLmdPourExport
{
    public function __construct(
        private readonly EnseignantDePlanificationLmd $source,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function lire(
        int $anneeId,
        ?array $classeIds = null,
        ?array $matiereIds = null,
        ?int $semestre = null,
        ?array $parcoursIds = null,
        ?array $ueIds = null,
    ): array {
        if ($classeIds === [] || $parcoursIds === [] || $ueIds === []) return [];

        $query = ESBTPClasse::query()->where('systeme_academique', 'LMD')
            ->with(['filiere:id,name', 'niveau:id,name,type,year', 'parcours:id,name,filiere_id'])
            ->orderBy('id');

        if ($classeIds !== null) $query->whereIn('id', $classeIds);
        if ($parcoursIds !== null) $query->whereIn('parcours_id', $parcoursIds);

        // Une recherche non bornee sur des centaines de classes obligerait le
        // moteur de maquette a lire toutes les UE, ECUE et planifications.
        if ((clone $query)->count() > 100) {
            throw ValidationException::withMessages([
                'classe_ids' => 'Plus de 100 classes LMD dans ce périmètre. Sélectionnez des filières, des niveaux ou des classes pour alléger l’export.',
            ]);
        }

        $lignes = [];
        $deja = [];
        foreach ($query->get() as $classe) {
            foreach ($classe->getSemestresLMD() as $periode) {
                $periode = (int) $periode;
                if ($semestre !== null && $periode !== $semestre) continue;
                // elementsDuSemestre respecte les UE du parcours, les ECUE
                // reserves et l'affectation canonique pour ce semestre.
                foreach ($this->source->elementsDuSemestre($classe, $anneeId, $periode) as $item) {
                    $id = (int) ($item['matiere_id'] ?? 0);
                    if ($id < 1 || ($matiereIds !== null && ! in_array($id, $matiereIds, true))) continue;
                    $ueId = (int) ($item['ue_id'] ?? 0);
                    if ($ueIds !== null && ! in_array($ueId, $ueIds, true)) continue;

                    $noms = trim((string) ($item['enseignant_nom'] ?? ''));
                    if ($noms === '') continue; // Pas de faux professeur "non affecte".

                    // La source renvoie plusieurs enseignants au format "A / B".
                    // On lit aussi la collection canonique pour les individualiser
                    // par ID, sans decouper arbitrairement une chaine de noms.
                    $resolution = $this->source->resoudre($classe, $id, $anneeId, $periode, true);
                    $enseignants = $resolution['enseignants']->pluck('name')->filter()
                        ->unique(fn ($n) => mb_strtolower(trim((string) $n), 'UTF-8'))->values()->all();
                    // Les evaluations peuvent fournir un nom externe sans
                    // compte : conserver alors son libelle canonique tel quel.
                    if (($resolution['source'] ?? '') !== 'planning') {
                        $enseignants = [$noms];
                    }
                    if ($enseignants === []) $enseignants = [$noms];

                    foreach ($enseignants as $nom) {
                        $nom = trim((string) $nom);
                        if ($nom === '') continue;
                        $cle = implode('|', [(int) $classe->id, $periode, $id, mb_strtolower($nom, 'UTF-8')]);
                        if (isset($deja[$cle])) continue;
                        $deja[$cle] = true;
                        $lignes[] = [
                            'systeme' => 'LMD',
                            'classe' => (string) $classe->name,
                            'classe_id' => (int) $classe->id,
                            'filiere' => (string) ($classe->filiere?->name ?? 'Non renseignée'),
                            'niveau' => (string) ($classe->niveau?->name ?? 'Non renseigné'),
                            'parcours' => (string) ($classe->parcours?->name ?? 'Tronc commun LMD'),
                            'semestre' => 'S'.$periode,
                            'matiere' => (string) ($item['name'] ?? 'ECUE #'.$id),
                            'matiere_id' => $id,
                            'ue' => (string) ($item['ue'] ?? '—'),
                            'ue_id' => $ueId,
                            'parcours_id' => $classe->parcours_id ? (int) $classe->parcours_id : null,
                            'nom' => $nom,
                            'source' => ($resolution['source'] ?? '') === 'planning'
                                ? 'Planification LMD' : 'Évaluation LMD (repli)',
                        ];
                    }
                }
            }
        }

        usort($lignes, fn ($a, $b) => strnatcasecmp(
            implode('|', [$a['parcours'], $a['classe'], $a['semestre'], $a['ue'], $a['matiere'], $a['nom']]),
            implode('|', [$b['parcours'], $b['classe'], $b['semestre'], $b['ue'], $b['matiere'], $b['nom']])
        ));
        return $lignes;
    }
}

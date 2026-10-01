<?php

namespace App\Domain\Assistant\Outils;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Services\Chatbot\Tools\ChatbotTool;

/**
 * Lit la structure académique : années universitaires (et laquelle est en
 * cours), filières (code, tronc commun) et niveaux d'études (type, année).
 *
 * Nanan doit constater avant de proposer : sans cette lecture, « passe à
 * 2026-2027 » ou « marque GC comme tronc commun » ne pouvait se vérifier
 * qu'en devinant un identifiant. Chaque partie n'est rendue que si la personne
 * a le droit de la voir à l'écran.
 */
class LireStructureAcademique extends ChatbotTool
{
    /** Le résumé du modèle est plafonné (ResumeOutil, 6 000 octets) : au-delà, on le dit. */
    private const MAX_PAR_LISTE = 60;

    /**
     * Octets accordés au diagnostic, sous le plafond de ResumeOutil : les
     * lignes du widget et l'enveloppe prennent le reste. Au-delà, ResumeOutil
     * couperait le JSON en plein milieu ; on retire donc des lignes nous-mêmes,
     * en le disant (`non_transmises`), et l'année en cours passe toujours.
     */
    private const OCTETS_DIAGNOSTIC = 3800;

    public function name(): string
    {
        return 'lire_structure_academique';
    }

    public function description(): string
    {
        return "Lit les années universitaires (laquelle est en cours), les filières (code, tronc commun) et les niveaux d'études (type, année, code). `parties` : annees, filieres, niveaux (toutes par défaut). "
            . 'À appeler avant de proposer un changement d\'année, une filière, un niveau ou un tronc commun.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'parties' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['annees', 'filieres', 'niveaux']]],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $parties = array_values(array_intersect(['annees', 'filieres', 'niveaux'], (array) ($args['parties'] ?? []))) ?: ['annees', 'filieres', 'niveaux'];
        $diagnostic = [];
        $lignes = [];

        if (in_array('annees', $parties, true) && $user->can('annees.view')) {
            $annees = ESBTPAnneeUniversitaire::orderByDesc('start_date')->get();
            $courante = $annees->firstWhere('is_current', true);
            // Le verdict d'abord : il passe en entier, même si la liste est coupée.
            $diagnostic['annee_en_cours'] = $courante ? ['id' => (int) $courante->id, 'nom' => (string) $courante->name] : null;
            $diagnostic['annees'] = $this->liste($annees, fn ($a) => [(int) $a->id, (string) $a->name, $a->start_date?->toDateString(), $a->end_date?->toDateString(), (bool) $a->is_current]);
            $diagnostic['annees_colonnes'] = ['id', 'nom', 'debut', 'fin', 'en_cours'];
            foreach ($annees->take(10) as $a) {
                $lignes[] = ['type' => 'Année', 'nom' => $a->name.($a->is_current ? ' (en cours)' : ''), 'detail' => ($a->start_date?->format('d/m/Y') ?? '?').' – '.($a->end_date?->format('d/m/Y') ?? '?')];
            }
        }
        if (in_array('filieres', $parties, true) && $user->can('filieres.view')) {
            $filieres = ESBTPFiliere::horsMiroirLmd()->orderBy('code')->get();
            $diagnostic['filieres'] = $this->liste($filieres, fn ($f) => [(int) $f->id, (string) $f->code, (string) $f->name, $f->isTroncCommun(), (int) $f->semestres_tronc_commun, $f->parent_id ? (int) $f->parent_id : null, (bool) $f->is_active]);
            $diagnostic['filieres_colonnes'] = ['id', 'code', 'nom', 'tronc_commun', 'semestres_communs', 'parent_id', 'active'];
            foreach ($filieres->take(15) as $f) {
                $lignes[] = ['type' => 'Filière', 'nom' => $f->code.' · '.$f->name, 'detail' => $f->isTroncCommun() ? 'Tronc commun' : ($f->is_active ? 'Active' : 'Inactive')];
            }
        }
        if (in_array('niveaux', $parties, true) && $user->can('niveaux.view')) {
            $niveaux = ESBTPNiveauEtude::orderBy('type')->orderBy('year')->get();
            $diagnostic['niveaux'] = $this->liste($niveaux, fn ($n) => [(int) $n->id, $n->code, (string) $n->name, (string) $n->type, (int) $n->year]);
            $diagnostic['niveaux_colonnes'] = ['id', 'code', 'nom', 'type', 'annee'];
            foreach ($niveaux->take(15) as $n) {
                $lignes[] = ['type' => 'Niveau', 'nom' => $n->name, 'detail' => $n->type.' · année '.$n->year];
            }
        }

        if ($diagnostic === []) {
            return ['error' => 'Aucune de ces parties ne vous est accessible.'];
        }

        return ['results' => $lignes, 'count' => count($lignes), 'diagnostic' => $this->tenir($diagnostic)];
    }

    private function tenir(array $diagnostic): array
    {
        $taille = fn () => strlen((string) json_encode($diagnostic, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        while ($taille() > self::OCTETS_DIAGNOSTIC) {
            $plusLongue = collect(['filieres', 'niveaux', 'annees'])
                ->filter(fn ($k) => ! empty($diagnostic[$k]['lignes']))
                ->sortByDesc(fn ($k) => count($diagnostic[$k]['lignes']))->first();
            if ($plusLongue === null) {
                break;
            }
            array_pop($diagnostic[$plusLongue]['lignes']);
            $diagnostic[$plusLongue]['non_transmises'] = ($diagnostic[$plusLongue]['non_transmises'] ?? 0) + 1;
        }

        return $diagnostic;
    }

    /** @return array{lignes: array<int, array>, non_transmises?: int} */
    private function liste($collection, callable $ligne): array
    {
        $liste = ['lignes' => $collection->take(self::MAX_PAR_LISTE)->map($ligne)->values()->all()];
        if ($collection->count() > self::MAX_PAR_LISTE) {
            $liste['non_transmises'] = $collection->count() - self::MAX_PAR_LISTE;
        }

        return $liste;
    }
}

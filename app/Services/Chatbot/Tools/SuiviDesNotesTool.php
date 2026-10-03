<?php

namespace App\Services\Chatbot\Tools;

use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * « Qu'est-ce qui manque au S1 en LBU ? » — le même constat que le panneau
 * « Suivi du semestre » (AcademicNoteCoverageService), lu pour le modèle.
 *
 * Le périmètre est celui du panneau : un enseignant ne lit que ses classes, et
 * seul le droit `academic_health.view` donne les noms des élèves sans note.
 *
 * Un nom de parcours (« LBU ») désigne souvent plusieurs classes : l'outil les
 * lit toutes, jusqu'à six, plutôt que d'en choisir une.
 */
class SuiviDesNotesTool extends ChatbotTool
{
    private const CLASSES_MAX = 6;

    /** Élèves nommés par élément : au-delà, le modèle reçoit le compte. */
    private const ELEVES_MAX = 8;

    public function name(): string
    {
        return 'suivi_des_notes';
    }

    public function description(): string
    {
        return "Dit, pour une classe et un semestre, quels éléments (matières, ECUE) n'ont aucune note, lesquels sont partiels, s'ils sont notés en contrôle continu, en examen ou les deux, et quels élèves n'ont pas de note. "
            . "À utiliser pour « qu'est-ce qui manque au S1 en LBU », « où en sont les notes de cette classe », « quelles matières ne sont pas notées ». "
            . "Passe classe_id si la page le donne, sinon le nom ou le code de la classe ou du parcours (plusieurs classes possibles), et le numéro du semestre.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe_id' => ['type' => 'integer', 'description' => 'Identifiant de la classe.'],
                'classe' => ['type' => 'string', 'description' => 'Nom ou code de classe, ou code de parcours (ex. LBU).'],
                'semestre' => ['type' => 'integer', 'description' => 'Numéro du semestre (1 à 10). Par défaut, le premier semestre de la classe.'],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $anneeId = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');
        if (! $anneeId) {
            return ['display_type' => 'text', 'message' => 'Aucune année universitaire courante.'];
        }

        $perimetre = app(AcademicActorScopeService::class)->dashboardScope($user, (int) $anneeId);
        $autorisees = $perimetre->global ? null : $perimetre->classIds;

        $classes = $this->classes($args, $autorisees);
        if ($classes->isEmpty()) {
            return ['display_type' => 'text', 'message' => 'Aucune classe de votre périmètre ne correspond. Précisez le nom ou le code de la classe.'];
        }

        $detail = (bool) $user->can('academic_health.view');
        $demande = isset($args['semestre']) ? (int) $args['semestre'] : null;
        $service = app(AcademicNoteCoverageService::class);

        $constats = $classes->map(function (ESBTPClasse $classe) use ($service, $anneeId, $autorisees, $detail, $demande) {
            $semestres = strtoupper((string) $classe->systeme_academique) === 'LMD' ? $classe->getSemestresLMD() : [1, 2];
            $semestre = $demande ?? $semestres[0];
            if (! in_array($semestre, $semestres, true)) {
                return ['classe' => $classe->name, 'classe_id' => (int) $classe->id, 'semestre' => $semestre,
                    'refus' => 'Cette classe n\'a pas de semestre '.$semestre.' (semestres : '.implode(', ', $semestres).').'];
            }

            $payload = $service->summarize((int) $anneeId, 'semestre'.$semestre, null, (int) $classe->id, $autorisees);
            if (! $detail) {
                $payload = $service->sansLesNotesNiLeursAuteurs($payload);
            }

            return $this->constat($classe, $semestre, $payload);
        });

        $unique = $constats->count() === 1;

        return [
            'display_type' => 'cards',
            'results' => $constats->flatMap(fn (array $c) => $this->lignes($c))->values()->all(),
            'diagnostic' => [
                'noms_des_eleves_visibles' => $detail,
                'classes' => $constats->map(fn (array $c) => $unique ? $c : array_diff_key($c, ['eleves_sans_note' => 1]))->all(),
            ],
        ];
    }

    /** @return Collection<int, ESBTPClasse> */
    private function classes(array $args, ?Collection $autorisees): Collection
    {
        $requete = ESBTPClasse::query()->where('is_active', true)
            ->when($autorisees !== null, fn ($q) => $q->whereIn('id', $autorisees->all()));

        if (($id = (int) ($args['classe_id'] ?? 0)) > 0) {
            return $requete->whereKey($id)->get();
        }

        $texte = trim((string) ($args['classe'] ?? ''));
        if ($texte === '') {
            return collect();
        }

        return $requete->where(function ($q) use ($texte) {
            $q->where('name', 'like', "%{$texte}%")
                ->orWhere('code', 'like', "%{$texte}%")
                ->orWhereHas('parcours', fn ($p) => $p->where('code', $texte));
        })->orderBy('name')->limit(self::CLASSES_MAX)->get();
    }

    /** Le constat d'une classe, verdicts d'abord, listes bornées. */
    private function constat(ESBTPClasse $classe, int $semestre, array $payload): array
    {
        if (! ($payload['ok'] ?? false)) {
            return ['classe' => $classe->name, 'classe_id' => (int) $classe->id, 'semestre' => $semestre, 'refus' => $payload['message'] ?? 'Constat indisponible.'];
        }

        $elements = collect($payload['subjects'] ?? [])->where('is_orphan', false);
        $nom = fn (array $m) => trim(($m['code'] ? $m['code'].' ' : '').$m['name']);
        $nature = ['cc_examen' => 'CC + examen', 'examen' => 'examen seul', 'cc' => 'CC seul'];

        return [
            'classe' => $classe->name,
            'classe_id' => (int) $classe->id,
            'semestre' => $semestre,
            'etat' => $payload['summary']['state'] ?? null,
            'elements_attendus' => $elements->count(),
            'notes_manquantes' => (int) ($payload['summary']['missing_results'] ?? 0),
            'eleves_attendus' => (int) ($payload['summary']['students_expected'] ?? 0),
            'sans_aucune_note' => $elements->whereIn('statut', ['non_evaluee', 'programmee'])->map($nom)->values()->all(),
            'partiels' => $elements->where('statut', 'partielle')->map(fn (array $m) => [
                'element' => $nom($m),
                'manquantes' => (int) $m['missing_count'],
                'eleves' => array_slice(array_column($m['missing_students'] ?? [], 'name'), 0, self::ELEVES_MAX),
            ])->values()->all(),
            'notes_en' => $elements->filter(fn (array $m) => $m['nature'] !== null)
                ->map(fn (array $m) => $nom($m).' : '.$nature[$m['nature']])->values()->all(),
            'eleves_sans_note' => array_slice(array_column($payload['incomplete_students'] ?? [], 'name'), 0, self::ELEVES_MAX * 2),
            'hors_maquette' => collect($payload['subjects'] ?? [])->where('is_orphan', true)->map($nom)->values()->all(),
        ];
    }

    /** Ce que voit l'écran : une carte par élément qui demande un geste. */
    private function lignes(array $constat): array
    {
        if (isset($constat['refus'])) {
            return [['nom' => $constat['classe'], 'detail' => $constat['refus']]];
        }

        $lien = Route::has('esbtp.lmd.notes.index')
            ? route('esbtp.lmd.notes.index', ['classe' => $constat['classe_id']], false) : null;

        $cartes = collect($constat['sans_aucune_note'])->map(fn (string $e) => [
            'nom' => $e, 'classe' => $constat['classe'], 'detail' => 'S'.$constat['semestre'], 'statut' => 'Aucune note', 'lien' => $lien,
        ])->merge(collect($constat['partiels'])->map(fn (array $p) => [
            'nom' => $p['element'], 'classe' => $constat['classe'], 'detail' => 'S'.$constat['semestre'],
            'statut' => $p['manquantes'].' note(s) manquante(s)', 'lien' => $lien,
        ]));

        return $cartes->isEmpty()
            ? [['nom' => $constat['classe'], 'detail' => 'S'.$constat['semestre'], 'statut' => $constat['elements_attendus'] > 0 ? 'Toutes les notes sont saisies' : 'Aucun élément attendu']]
            : $cartes->all();
    }
}

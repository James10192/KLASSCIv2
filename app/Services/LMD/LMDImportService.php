<?php

namespace App\Services\LMD;

use App\Enums\TypeUE;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Bulk import a full LMD maquette (Domaine + Mention + Parcours + Filière + UEs +
 * ECUEs + Planifications) from a structured JSON spec. Idempotent end-to-end :
 * re-running with the same spec is a no-op (upsert by code at every level).
 *
 * Designed to consume a JSON file extracted from a tenant's PDF maquette
 * (UEMOA standard), enabling provisioning of an entire Licence in one CLI call
 * instead of dozens of UI form submits.
 */
class LMDImportService
{
    public function __construct(private ParcoursUeSyncService $parcoursUeSync) {}

    /**
     * @param  array  $spec  See JSON schema in resources/docs or LmdImportCommand help
     * @return array{domaine:array, mention:array, parcours:array, filiere:?array, niveaux:array, stats:array}
     */
    public function import(array $spec, ?int $userId = null): array
    {
        return DB::transaction(function () use ($spec, $userId) {
            $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first()
                ?? ESBTPAnneeUniversitaire::where('is_active', true)->orderByDesc('start_date')->first();
            if (!$annee) {
                throw new \RuntimeException("Aucune année universitaire courante/active trouvée. Créez-en une d'abord.");
            }
            $domaine = $this->upsertDomaine($spec['domaine'], $userId);
            $mention = $this->upsertMention($spec['mention'], $domaine, $userId);
            $filiere = isset($spec['filiere']) ? $this->upsertFiliere($spec['filiere'], $userId) : null;
            $parcours = $this->upsertParcours($spec['parcours'], $mention, $filiere, $userId);

            $niveauxByYear = [];
            foreach ($spec['niveaux'] ?? [] as $niveau) {
                $entity = $this->upsertNiveau($niveau);
                $niveauxByYear[(int) $niveau['year']] = $entity;
            }

            $stats = ['ues_attached' => 0, 'ues_updated' => 0, 'ecues_attached' => 0, 'ecues_updated' => 0, 'planifs_attached' => 0, 'planifs_updated' => 0];
            $linksByParcours = [];

            foreach ($spec['ues'] ?? [] as $ueSpec) {
                $niveauYear = (int) $ueSpec['niveau_year'];
                $niveau = $niveauxByYear[$niveauYear] ?? null;
                if (!$niveau) {
                    throw new \InvalidArgumentException("Niveau d'étude year={$niveauYear} référencé par UE {$ueSpec['code']} mais absent de spec.niveaux");
                }

                [$ue, $ueCreated] = $this->upsertUE($ueSpec, $parcours, $filiere, $niveau, $userId);
                $stats[$ueCreated ? 'ues_attached' : 'ues_updated']++;

                $linksByParcours[] = [
                    'id' => $ue->id,
                    'semestres' => [(int) $ueSpec['semestre']],
                    'is_optional' => (bool) ($ueSpec['is_optional'] ?? false),
                    'ordre' => (int) ($ueSpec['ordre'] ?? 0),
                ];

                foreach ($ueSpec['ecues'] ?? [] as $ecueSpec) {
                    [$ecue, $ecueCreated] = $this->upsertECUE($ecueSpec, $ue, $filiere, $niveau, $userId);
                    $stats[$ecueCreated ? 'ecues_attached' : 'ecues_updated']++;

                    [, $planifCreated] = $this->upsertPlanification($ecueSpec, $ecue, $filiere, $niveau, (int) $ueSpec['semestre'], $annee);
                    $stats[$planifCreated ? 'planifs_attached' : 'planifs_updated']++;
                }
            }

            $linkStats = $this->parcoursUeSync->sync($parcours, $linksByParcours, detachMissing: false);
            $stats['ues_linked_to_parcours'] = $linkStats['attached'] + $linkStats['updated'] + $linkStats['unchanged'];

            return [
                'domaine' => $this->summarize($domaine, ['name', 'code']),
                'mention' => $this->summarize($mention, ['name', 'code']),
                'parcours' => $this->summarize($parcours, ['name', 'code']),
                'filiere' => $filiere ? $this->summarize($filiere, ['name', 'code']) : null,
                'niveaux' => array_map(fn ($n) => $this->summarize($n, ['name', 'year']), $niveauxByYear),
                'stats' => $stats,
            ];
        });
    }

    private function upsertDomaine(array $data, ?int $userId): ESBTPLMDDomaine
    {
        return ESBTPLMDDomaine::updateOrCreate(
            ['code' => $data['code'] ?? Str::slug($data['name'])],
            ['name' => $data['name'], 'description' => $data['description'] ?? null, 'created_by' => $userId, 'is_active' => true]
        );
    }

    private function upsertMention(array $data, ESBTPLMDDomaine $domaine, ?int $userId): ESBTPLMDMention
    {
        return ESBTPLMDMention::updateOrCreate(
            ['code' => $data['code'] ?? Str::slug($data['name'])],
            ['name' => $data['name'], 'domaine_id' => $domaine->id, 'created_by' => $userId, 'is_active' => true]
        );
    }

    private function upsertParcours(array $data, ESBTPLMDMention $mention, ?ESBTPFiliere $filiere, ?int $userId): ESBTPLMDParcours
    {
        return ESBTPLMDParcours::updateOrCreate(
            ['code' => $data['code'] ?? Str::slug($data['name'])],
            [
                'name' => $data['name'],
                'mention_id' => $mention->id,
                'filiere_id' => $filiere?->id,
                'credits_licence' => (int) ($data['credits_licence'] ?? 180),
                'credits_master' => (int) ($data['credits_master'] ?? 120),
                'created_by' => $userId,
                'is_active' => true,
            ]
        );
    }

    private function upsertFiliere(array $data, ?int $userId): ESBTPFiliere
    {
        return ESBTPFiliere::updateOrCreate(
            ['code' => $data['code'] ?? Str::slug($data['name'])],
            ['name' => $data['name'], 'description' => $data['description'] ?? null, 'is_active' => true]
        );
    }

    private function upsertNiveau(array $data): ESBTPNiveauEtude
    {
        return ESBTPNiveauEtude::firstOrCreate(
            ['year' => (int) $data['year']],
            ['name' => $data['name'], 'libelle' => $data['libelle'] ?? $data['name'], 'is_active' => true]
        );
    }

    /** @return array{0: ESBTPUniteEnseignement, 1: bool} */
    private function upsertUE(array $data, ESBTPLMDParcours $parcours, ?ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, ?int $userId): array
    {
        $code = $data['code'] ?? null;
        $existing = $code ? ESBTPUniteEnseignement::where('code', $code)->first() : null;
        $created = $existing === null;

        $payload = [
            'name' => $data['name'],
            'code' => $code,
            'credit' => (int) ($data['credit'] ?? 0),
            'type_ue' => TypeUE::tryFrom($data['type_ue'] ?? '') ?? TypeUE::FONDAMENTALE,
            'semestre' => (int) ($data['semestre'] ?? 1),
            'parcours_id' => $parcours->id,
            'filiere_id' => $filiere?->id,
            'niveau_id' => $niveau->id,
            'is_active' => true,
            'created_by' => $existing?->created_by ?? $userId,
            'updated_by' => $userId,
        ];

        $ue = $existing ?? new ESBTPUniteEnseignement();
        $ue->fill($payload);
        $ue->save();
        return [$ue, $created];
    }

    /** @return array{0: ESBTPMatiere, 1: bool} */
    private function upsertECUE(array $data, ESBTPUniteEnseignement $ue, ?ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, ?int $userId): array
    {
        $code = $data['code'] ?? null;
        $existing = $code ? ESBTPMatiere::where('code', $code)->first() : null;
        $created = $existing === null;

        // Note: filiere_id was dropped from esbtp_matieres in 2025-04 cleanup migration —
        // the relationship lives in pivot esbtp_matiere_filiere now (see linkMatiereFiliere).
        $payload = [
            'name' => $data['name'],
            'code' => $code,
            'unite_enseignement_id' => $ue->id,
            'niveau_etude_id' => $niveau->id,
            'coefficient' => (int) ($data['credit_ecue'] ?? 1),
            'type_formation' => 'generale',
            'is_active' => true,
            'created_by' => $existing?->created_by ?? $userId,
            'updated_by' => $userId,
        ];

        $matiere = $existing ?? new ESBTPMatiere();
        $matiere->fill($payload);
        // LMD-specific columns added in 2026-03 migration; not in $fillable, set directly.
        $matiere->credit_ecue = (int) ($data['credit_ecue'] ?? 1);
        $matiere->coefficient_ecue = (float) ($data['credit_ecue'] ?? 1);
        $matiere->save();

        if ($filiere) {
            $this->linkMatiereFiliere($matiere->id, $filiere->id);
        }

        return [$matiere, $created];
    }

    private function linkMatiereFiliere(int $matiereId, int $filiereId): void
    {
        $now = now();
        DB::table('esbtp_matiere_filiere')->upsert(
            [['matiere_id' => $matiereId, 'filiere_id' => $filiereId, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]],
            ['matiere_id', 'filiere_id'],
            ['is_active', 'updated_at']
        );
    }

    /** @return array{0: ESBTPPlanificationAcademique, 1: bool} */
    private function upsertPlanification(array $data, ESBTPMatiere $matiere, ?ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, int $semestre, ESBTPAnneeUniversitaire $annee): array
    {
        if (!$filiere) {
            throw new \InvalidArgumentException("Planification requiert une filière (matière {$matiere->name})");
        }

        $cm = (int) ($data['cm'] ?? 0);
        $td = (int) ($data['td'] ?? 0);
        $tp = (int) ($data['tp'] ?? 0);
        $projet = (int) ($data['projet'] ?? 0);
        $tpe = (int) ($data['tpe'] ?? 0);

        $existing = ESBTPPlanificationAcademique::where('annee_universitaire_id', $annee->id)
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->where('semestre', $semestre)
            ->where('matiere_id', $matiere->id)
            ->first();
        $created = $existing === null;

        $payload = [
            'annee_universitaire_id' => $annee->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'semestre' => $semestre,
            'matiere_id' => $matiere->id,
            'volume_horaire_cm' => $cm,
            'volume_horaire_td' => $td,
            'volume_horaire_tp' => $tp,
            'volume_horaire_projet' => $projet,
            'volume_horaire_tpe' => $tpe,
            'volume_horaire_total' => $cm + $td + $tp + $projet + $tpe,
            'coefficient' => (int) ($data['credit_ecue'] ?? 1),
            'credits_ects' => (int) ($data['credit_ecue'] ?? 1),
        ];

        $planif = $existing ?? new ESBTPPlanificationAcademique();
        $planif->fill($payload);
        $planif->save();
        return [$planif, $created];
    }

    private function summarize($model, array $keys): array
    {
        $out = ['id' => $model->id];
        foreach ($keys as $k) {
            $out[$k] = $model->{$k} ?? null;
        }
        return $out;
    }
}

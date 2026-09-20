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
use App\Services\LMD\ConflitDeMaquette;
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
    private readonly LmdAcademicRuleProfile $rules;

    public function __construct(
        private ParcoursUeSyncService $parcoursUeSync,
        private CompositionUe $composition,
        private RefusDeDeplacement $deplacement,
        ?LmdAcademicRuleProfile $rules = null,
    ) {
        $this->rules = $rules ?? new LmdAcademicRuleProfile();
    }

    /**
     * @param  array  $spec  See JSON schema in resources/docs or LmdImportCommand help
     * @return array{domaine:array, mention:array, parcours:array, filiere:?array, niveaux:array, stats:array}
     */
    /** @var list<array{type: string, code: string, detail: string}> */
    private array $conflits = [];

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
            $creditsPropres = [];

            foreach ($spec['ues'] ?? [] as $ueSpec) {
                $niveauYear = (int) $ueSpec['niveau_year'];
                $niveau = $niveauxByYear[$niveauYear] ?? null;
                if (!$niveau) {
                    throw new \InvalidArgumentException("Niveau d'étude year={$niveauYear} référencé par UE {$ueSpec['code']} mais absent de spec.niveaux");
                }

                [$ue, $ueCreated] = $this->upsertUE($ueSpec, $parcours, $filiere, $niveau, $userId);
                $stats[$ueCreated ? 'ues_attached' : 'ues_updated']++;
                // Une unite partagee garde le credit de sa fiche. Si cette maquette
                // lui en donne un autre, il est a elle seule : sur le pivot.
                $creditMaquette = (int) ($ueSpec['credit'] ?? 0);
                if ((int) $ue->credit !== $creditMaquette) {
                    // Le SEMESTRE fait partie de l'adresse, pas seulement l'unité.
                    //
                    // La clé d'unicité du pivot est (parcours, unité, semestre) :
                    // une unité posée sur deux semestres d'un même parcours y a
                    // deux lignes. Une liste indexée par la seule unité écrasait
                    // la première entrée par la seconde à l'intérieur d'un même
                    // import, et l'écriture sans `where('semestre')` reportait
                    // ensuite le crédit trouvé sur les DEUX semestres.
                    $creditsPropres[] = [
                        'ue_id' => (int) $ue->id,
                        'semestre' => (int) $ueSpec['semestre'],
                        'credit' => $creditMaquette,
                    ];
                }

                $linksByParcours[] = [
                    'id' => $ue->id,
                    'semestres' => [(int) $ueSpec['semestre']],
                    'is_optional' => (bool) ($ueSpec['is_optional'] ?? false),
                    'ordre' => (int) ($ueSpec['ordre'] ?? 0),
                ];

                foreach ($ueSpec['ecues'] ?? [] as $ecueSpec) {
                    // La maquette qu'on importe. Le code d'une unite est unique
                    // dans l'ecole : la meme unite sert plusieurs parcours, et
                    // chacun peut lui donner des elements differents.
                    [$ecue, $ecueCreated] = $this->upsertECUE($ecueSpec, $ue, $filiere, $niveau, $userId, (int) $parcours->id);
                    $stats[$ecueCreated ? 'ecues_attached' : 'ecues_updated']++;

                    [, $planifCreated] = $this->upsertPlanification($ecueSpec, $ecue, $filiere, $niveau, (int) $ueSpec['semestre'], $annee);
                    $stats[$planifCreated ? 'planifs_attached' : 'planifs_updated']++;
                }
            }

            $linkStats = $this->parcoursUeSync->sync($parcours, $linksByParcours, detachMissing: false);
            // Le lien est pose : on y grave le credit propre a cette maquette. La
            // synchronisation ne touche jamais `credit` (c est une decision de
            // l ecole), c est donc a l import de le faire, pour ce parcours seul.
            foreach ($creditsPropres as $propre) {
                DB::table('esbtp_lmd_parcours_ue')
                    ->where('parcours_id', $parcours->id)
                    ->where('unite_enseignement_id', $propre['ue_id'])
                    // Sans ce troisième critère, importer le S3 gravait son
                    // crédit sur le S3 ET le S5 de la même unité, puis importer
                    // le S5 les écrasait tous les deux à son tour. C'est
                    // exactement l'arbitrage que la migration de septembre
                    // refusait de forcer, et que l'import forçait en silence.
                    ->where('semestre', $propre['semestre'])
                    ->update(['credit' => $propre['credit'], 'updated_at' => now()]);
            }
            $stats['ues_linked_to_parcours'] = $linkStats['attached'] + $linkStats['updated'] + $linkStats['unchanged'];

            // Une seule levee, apres avoir tout parcouru : l utilisateur voit TOUS
            // les conflits d un coup, au lieu d en corriger un par tentative. La
            // transaction annule l import entier.
            if ($this->conflits !== []) {
                throw new ConflitDeMaquette($this->conflits);
            }

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
        $valeurs = $this->deplacement->preserver(
            ['name' => $data['name'], 'description' => $data['description'] ?? null, 'created_by' => $userId, 'is_active' => true],
            $data,
            'nature',
        );

        return ESBTPLMDDomaine::updateOrCreate(['code' => $data['code'] ?? Str::slug($data['name'])], $valeurs);
    }

    private function upsertMention(array $data, ESBTPLMDDomaine $domaine, ?int $userId): ESBTPLMDMention
    {
        $code = $data['code'] ?? Str::slug($data['name']);
        $existante = ESBTPLMDMention::where('code', $code)->first();
        if ($conflit = $this->deplacement->siAutreParent(
            $existante,
            'domaine_id',
            (int) $domaine->id,
            'MENTION',
            (string) $code,
            fn ($e) => sprintf(
                "Le code « %s » désigne déjà la mention « %s » du domaine « %s ». L'importer sous « %s » l'y déplacerait avec ses parcours : donnez à cette mention un code propre.",
                $code,
                $e->name,
                optional($e->domaine)->name ?? ('#'.$e->domaine_id),
                $domaine->name
            ),
        )) {
            $this->conflits[] = $conflit;

            return $existante;
        }

        return ESBTPLMDMention::updateOrCreate(
            ['code' => $code],
            ['name' => $data['name'], 'domaine_id' => $domaine->id, 'created_by' => $userId, 'is_active' => true]
        );
    }

    private function upsertParcours(array $data, ESBTPLMDMention $mention, ?ESBTPFiliere $filiere, ?int $userId): ESBTPLMDParcours
    {
        $code = $data['code'] ?? Str::slug($data['name']);
        $existant = ESBTPLMDParcours::where('code', $code)->first();
        if ($conflit = $this->deplacement->siAutreParent(
            $existant,
            'mention_id',
            (int) $mention->id,
            'PARCOURS',
            (string) $code,
            fn ($e) => sprintf(
                "Le code « %s » désigne déjà le parcours « %s » de la mention « %s ». L'importer sous « %s » l'y déplacerait avec sa maquette : donnez à ce parcours un code propre.",
                $code,
                $e->name,
                optional($e->mention)->name ?? ('#'.$e->mention_id),
                $mention->name
            ),
        )) {
            $this->conflits[] = $conflit;

            return $existant;
        }

        return ESBTPLMDParcours::updateOrCreate(
            ['code' => $code],
            [
                'name' => $data['name'],
                'mention_id' => $mention->id,
                'filiere_id' => $filiere?->id,
                // Totaux par defaut lus dans les reglages de l'ecole, pas ecrits en dur.
                'credits_licence' => (int) ($data['credits_licence'] ?? $this->rules->diplomaCreditTotal('licence')),
                'credits_master' => (int) ($data['credits_master'] ?? $this->rules->diplomaCreditTotal('master')),
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
        // Match by (year + type) — multiple niveaux can share a year (BTS 1ère, Licence 1ère, etc.).
        // Sans type fourni, le cycle se deduit de l'annee continue (4 → Master).
        // Le defaut « Licence » d'autrefois creait des « Licence 4 » pour un Master 1.
        $year = (int) $data['year'];
        $type = $data['type'] ?? ESBTPNiveauEtude::cycleLmdPourAnnee($year) ?? 'Licence';

        // Generate a unique code per type×year for LMD niveaux, prefixed to avoid collision
        // with legacy niveau codes (BTS '1A', '2A', or legacy untyped 'L1', 'L2' etc.).
        $baseCode = $data['code'] ?? ('LMD-' . strtoupper(substr($type, 0, 1)) . $year);
        $code = $baseCode;
        $suffix = 1;
        while (ESBTPNiveauEtude::where('code', $code)
            ->where(function ($q) use ($year, $type) {
                $q->where('year', '!=', $year)->orWhere('type', '!=', $type);
            })
            ->exists()) {
            $code = $baseCode . '-' . $suffix++;
        }

        return ESBTPNiveauEtude::firstOrCreate(
            ['year' => $year, 'type' => $type],
            ['name' => $data['name'], 'libelle' => $data['libelle'] ?? $data['name'], 'code' => $code, 'is_active' => true]
        );
    }

    /** @return array{0: ESBTPUniteEnseignement, 1: bool} */
    private function upsertUE(array $data, ESBTPLMDParcours $parcours, ?ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, ?int $userId): array
    {
        $code = $data['code'] ?? null;
        $existing = $code ? ESBTPUniteEnseignement::where('code', $code)->first() : null;
        $created = $existing === null;

        // Une unite deja tenue par un AUTRE parcours est partagee, pas ecrasee.
        //
        // Le code d une UE est unique dans toute la base : on la retrouvait donc par
        // son code, puis on reecrivait parcours_id, semestre, credit et niveau_id.
        // Importer la maquette d un second parcours REECRIVAIT celle du premier, en
        // silence. L import a d abord refuse ; il sait maintenant partager : la
        // fiche de l unite reste celle du premier parcours, le second n y touche
        // pas. Ce qui lui est propre vit dans les pivots — son semestre et son
        // credit dans `esbtp_lmd_parcours_ue`, ses elements dans `esbtp_ue_matiere`
        // avec son `parcours_id` — et c est l appelant qui les y ecrit.
        if ($existing !== null
            && $existing->parcours_id !== null
            && (int) $existing->parcours_id !== (int) $parcours->id) {
            return [$existing, false];
        }

        $payload = [
            'name' => $data['name'],
            'code' => $code,
            'credit' => (int) ($data['credit'] ?? 0),
            'type_ue' => TypeUE::tryFrom($data['type_ue'] ?? '') ?? TypeUE::Fondamentale,
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
    private function upsertECUE(
        array $data,
        ESBTPUniteEnseignement $ue,
        ?ESBTPFiliere $filiere,
        ESBTPNiveauEtude $niveau,
        ?int $userId,
        int $parcoursId = CompositionUe::COMMUN
    ): array {
        $code = $data['code'] ?? null;
        $existing = $code ? ESBTPMatiere::where('code', $code)->first() : null;
        $created = $existing === null;

        // Meme raison : reparenter un ECUE vers une autre UE le faisait DISPARAITRE
        // de la premiere, puisque la lecture retombe sur la cle etrangere quand le
        // pivot est vide. C est ce qui a oblige a renommer cinq ECUE a la main lors
        // de l import du Genie Civil sur abidjan.
        // La cle etrangere ne peut designer qu'UNE unite : c'est elle qui rendait
        // le partage impossible. Elle n'est plus la source de verite, le pivot
        // l'est — on ne la reecrit donc que si elle est libre ou deja la notre, et
        // le conflit ne se leve que pour un rattachement a une AUTRE unite, ce que
        // le pivot ne sait pas exprimer.
        $conflitEcue = $this->deplacement->siAutreParent(
            $existing,
            'unite_enseignement_id',
            (int) $ue->id,
            'ECUE',
            (string) $code,
            fn ($e) => sprintf(
                "L'ECUE « %s » appartient déjà à l'UE « %s ». Le rattacher à « %s » le retirerait de la première.",
                $e->name,
                optional($e->uniteEnseignement)->name ?? ('#'.$e->unite_enseignement_id),
                $ue->name
            ),
        );
        $appartientAilleurs = $conflitEcue !== null;
        if ($conflitEcue) {
            $this->conflits[] = $conflitEcue;
        }

        // Note: filiere_id was dropped from esbtp_matieres in 2025-04 cleanup migration —
        // the relationship lives in pivot esbtp_matiere_filiere now (see linkMatiereFiliere).
        $payload = [
            'name' => $data['name'],
            'code' => $code,
            // Ne pas la reprendre a l'unite voisine : sans ligne de pivot, c'est
            // par elle qu'elle lit ses elements, et la lui voler la depouillerait.
            'unite_enseignement_id' => $appartientAilleurs
                ? $existing->unite_enseignement_id
                : $ue->id,
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

        // Le pivot, et lui seul, sait dire « cet element appartient a cette unite
        // POUR CETTE MAQUETTE ». Sans cette ecriture, importer la meme unite pour
        // un second parcours avec d'autres elements reecrivait la cle etrangere
        // des premiers et les faisait disparaitre de la maquette d'origine —
        // c'est ce qui a oblige a renommer cinq elements a la main lors de
        // l'import du Genie Civil sur abidjan.
        $this->composition->poser($ue, (int) $matiere->id, [
            'coefficient_ecue' => (float) ($data['credit_ecue'] ?? 1),
            'credit_ecue' => (int) ($data['credit_ecue'] ?? 1),
            'ordre_bulletin' => (int) ($data['ordre'] ?? 0),
        ], $parcoursId);

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

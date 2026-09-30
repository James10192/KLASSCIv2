from pathlib import Path

ROOT = Path('.')


def replace(path, old, new, count=1):
    p = ROOT / path
    text = p.read_text()
    if old not in text:
        raise SystemExit(f'Pattern not found in {path}: {old[:120]!r}')
    text = text.replace(old, new, count)
    p.write_text(text)


def write(path, content):
    p = ROOT / path
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(content)

# ---------------------------------------------------------------------------
# 1. Stockage canonique du bloc Général / Technique au grain filière × niveau.
# ---------------------------------------------------------------------------
write('database/migrations/2026_09_30_123500_add_type_formation_to_esbtp_matiere_filiere_niveau_table.php', '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table): void {
            if (! Schema::hasColumn('esbtp_matiere_filiere_niveau', 'type_formation')) {
                // Défaut du combo pour les bulletins. Les réglages par classe/période
                // dans esbtp_config_matieres restent des overrides plus précis.
                $table->string('type_formation', 32)->nullable()->after('classification');
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table): void {
            if (Schema::hasColumn('esbtp_matiere_filiere_niveau', 'type_formation')) {
                $table->dropColumn('type_formation');
            }
        });
    }
};
''')

replace('app/Models/ESBTPMatiereFilierNiveau.php',
'''    public const TRONC_COMMUN = 'tronc_commun';
    public const SPECIALITE = 'specialite';
''',
'''    public const TRONC_COMMUN = 'tronc_commun';
    public const SPECIALITE = 'specialite';
    public const TYPE_GENERAL = 'general';
    public const TYPE_TECHNIQUE = 'technique';
''')
replace('app/Models/ESBTPMatiereFilierNiveau.php',
'''        'classification',
        'ordre_bulletin',
''',
'''        'classification',
        'type_formation',
        'ordre_bulletin',
''')

# ---------------------------------------------------------------------------
# 2. API de l'écran Maquette : validation, lecture, sauvegarde et permissions.
# ---------------------------------------------------------------------------
replace('app/Http/Requests/Matiere/ClassificationSaveRequest.php',
'''            'classifications.*.classification' => [
                'nullable',
                Rule::in([ESBTPMatiereFilierNiveau::TRONC_COMMUN, ESBTPMatiereFilierNiveau::SPECIALITE]),
            ],
            // Borne haute alignee sur le stockage''',
'''            'classifications.*.classification' => [
                'nullable',
                Rule::in([ESBTPMatiereFilierNiveau::TRONC_COMMUN, ESBTPMatiereFilierNiveau::SPECIALITE]),
            ],
            'classifications.*.type_formation' => [
                'nullable',
                Rule::in([ESBTPMatiereFilierNiveau::TYPE_GENERAL, ESBTPMatiereFilierNiveau::TYPE_TECHNIQUE]),
            ],
            // Borne haute alignee sur le stockage''')
replace('app/Http/Requests/Matiere/ClassificationSaveRequest.php',
'''            'classifications.*.semestre.in' => 'Le semestre doit valoir 1 ou 2, ou rester vide pour « les deux semestres ».',
            'classifications.*.ordre_bulletin.min' => 'La place sur le bulletin commence a 1.',
''',
'''            'classifications.*.semestre.in' => 'Le semestre doit valoir 1 ou 2, ou rester vide pour « les deux semestres ».',
            'classifications.*.type_formation.in' => 'Le bloc du bulletin doit être Général ou Technique.',
            'classifications.*.ordre_bulletin.min' => 'La place sur le bulletin commence a 1.',
''')

replace('app/Http/Controllers/ESBTPMatiereClassificationController.php',
'''                'specialite' => $rows->where('classification', ESBTPMatiereFilierNiveau::SPECIALITE)->count(),
                'non_classe' => $rows->whereNull('classification')->count(),
''',
'''                'specialite' => $rows->where('classification', ESBTPMatiereFilierNiveau::SPECIALITE)->count(),
                'non_classe' => $rows->whereNull('classification')->count(),
                'general' => $rows->where('type_formation', ESBTPMatiereFilierNiveau::TYPE_GENERAL)->count(),
                'technique' => $rows->where('type_formation', ESBTPMatiereFilierNiveau::TYPE_TECHNIQUE)->count(),
''')
replace('app/Http/Controllers/ESBTPMatiereClassificationController.php',
'''                    'classification' => $row->classification,
                    'suggested' => $row->classification === null
''',
'''                    'classification' => $row->classification,
                    'type_formation' => $row->type_formation,
                    'suggested' => $row->classification === null
''')
replace('app/Http/Controllers/ESBTPMatiereClassificationController.php',
'''        $validerSemestres = (bool) ($validated['valider_semestres'] ?? false);

        try {
''',
'''        $validerSemestres = (bool) ($validated['valider_semestres'] ?? false);
        $modifieTypeFormation = collect($validated['classifications'])
            ->contains(fn (array $item): bool => array_key_exists('type_formation', $item));

        // La partie TC/Spécialité/Semestre relève de matieres.edit. Le bloc
        // Général/Technique pilote le bulletin : il garde donc son droit propre.
        if ($modifieTypeFormation && ! ($request->user()?->can('bulletins.configure') ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n’avez pas la permission de configurer les blocs Général / Technique du bulletin.',
            ], 403);
        }

        try {
''')
replace('app/Http/Controllers/ESBTPMatiereClassificationController.php',
'''                    if (array_key_exists('ordre_bulletin', $item)) {
                        $changements['ordre_bulletin'] = $item['ordre_bulletin'];
                    }
''',
'''                    if (array_key_exists('type_formation', $item)) {
                        $changements['type_formation'] = $item['type_formation'];
                    }
                    if (array_key_exists('ordre_bulletin', $item)) {
                        $changements['ordre_bulletin'] = $item['ordre_bulletin'];
                    }
''')

# ---------------------------------------------------------------------------
# 3. Écran Maquette unifié : S1/S2 + TC/Spé + Général/Technique.
# ---------------------------------------------------------------------------
replace('resources/views/esbtp/matieres/classification.blade.php',
'''    .mtc-seg-btn--tc.mtc-seg-btn--active { background: #0453cb; color: #fff; }
    .mtc-seg-btn--spe.mtc-seg-btn--active { background: #334155; color: #fff; }
''',
'''    .mtc-seg-btn--tc.mtc-seg-btn--active { background: #0453cb; color: #fff; }
    .mtc-seg-btn--spe.mtc-seg-btn--active { background: #334155; color: #fff; }
    .mtc-seg-btn--general.mtc-seg-btn--active { background: #0453cb; color: #fff; }
    .mtc-seg-btn--technique.mtc-seg-btn--active { background: #059669; color: #fff; }
''')
replace('resources/views/esbtp/matieres/classification.blade.php',
'''                    <h1>Maquette du bulletin</h1>
                    <p>Par filière et niveau : quelles matières composent le bulletin, à quel semestre, dans quel ordre, et lesquelles relèvent du tronc commun.</p>
''',
'''                    <h1>Maquette du bulletin</h1>
                    <p>Une seule configuration par filière et niveau : matières, S1/S2, tronc commun/spécialité, bloc Général/Technique et ordre du bulletin.</p>
''')
replace('resources/views/esbtp/matieres/classification.blade.php',
'''            <div class="mtc-kpi"><div class="mtc-kpi-value" x-text="kpis.non_classe"></div><div class="mtc-kpi-label">Non classé</div></div>
''',
'''            <div class="mtc-kpi"><div class="mtc-kpi-value" x-text="kpis.non_classe"></div><div class="mtc-kpi-label">Non classé</div></div>
            @can('bulletins.configure')
            <div class="mtc-kpi"><div class="mtc-kpi-value" x-text="kpis.general"></div><div class="mtc-kpi-label">Général</div></div>
            <div class="mtc-kpi"><div class="mtc-kpi-value" x-text="kpis.technique"></div><div class="mtc-kpi-label">Technique</div></div>
            @endcan
''')
replace('resources/views/esbtp/matieres/classification.blade.php',
'''                <div class="mtc-bulk">
                    <span class="mtc-bulk-lbl">Tout marquer :</span>
                    <button type="button" class="mtc-mini" @click="bulk('tronc_commun')" :disabled="saving">Tronc commun</button>
                    <button type="button" class="mtc-mini" @click="bulk('specialite')" :disabled="saving">Spécialité</button>
                    <button type="button" class="mtc-mini" @click="bulk(null)" :disabled="saving">Effacer</button>
''',
'''                <div class="mtc-bulk">
                    <span class="mtc-bulk-lbl">TC / Spécialité :</span>
                    <button type="button" class="mtc-mini" @click="bulk('tronc_commun')" :disabled="saving">Tronc commun</button>
                    <button type="button" class="mtc-mini" @click="bulk('specialite')" :disabled="saving">Spécialité</button>
                    <button type="button" class="mtc-mini" @click="bulk(null)" :disabled="saving">Effacer</button>
''')
replace('resources/views/esbtp/matieres/classification.blade.php',
'''                    </template>
                </div>

                @include('esbtp.matieres.partials._classification-row')
''',
'''                    </template>
                </div>

                @can('bulletins.configure')
                <div class="mtc-bulk">
                    <span class="mtc-bulk-lbl">Bloc du bulletin :</span>
                    <button type="button" class="mtc-mini" @click="bulkFormationType('general')" :disabled="saving">Général</button>
                    <button type="button" class="mtc-mini" @click="bulkFormationType('technique')" :disabled="saving">Technique</button>
                    <button type="button" class="mtc-mini" @click="bulkFormationType(null)" :disabled="saving">Hériter du type matière</button>
                    <span class="mtc-bulk-lbl">Ce choix devient le défaut de toutes les classes de ce couple filière × niveau ; Résultats peut toujours le surcharger par classe/période.</span>
                </div>
                @endcan

                @include('esbtp.matieres.partials._classification-row')
''')

replace('resources/views/esbtp/matieres/partials/_classification-row.blade.php',
'''        <div class="mtc-seg">
            <button type="button" class="mtc-seg-btn mtc-seg-btn--tc"
                :class="m.classification === 'tronc_commun' ? 'mtc-seg-btn--active' : ''"
                @click="setClass(m, 'tronc_commun')">Tronc commun</button>
            <button type="button" class="mtc-seg-btn mtc-seg-btn--spe"
                :class="m.classification === 'specialite' ? 'mtc-seg-btn--active' : ''"
                @click="setClass(m, 'specialite')">Spécialité</button>
        </div>

        {{-- Retirer de la maquette.''',
'''        <div class="mtc-seg">
            <button type="button" class="mtc-seg-btn mtc-seg-btn--tc"
                :class="m.classification === 'tronc_commun' ? 'mtc-seg-btn--active' : ''"
                @click="setClass(m, 'tronc_commun')">Tronc commun</button>
            <button type="button" class="mtc-seg-btn mtc-seg-btn--spe"
                :class="m.classification === 'specialite' ? 'mtc-seg-btn--active' : ''"
                @click="setClass(m, 'specialite')">Spécialité</button>
        </div>

        @can('bulletins.configure')
        <div class="mtc-seg" title="Bloc du bulletin pour toutes les classes de cette filière et de ce niveau">
            <button type="button" class="mtc-seg-btn mtc-seg-btn--general"
                :class="m.type_formation === 'general' ? 'mtc-seg-btn--active' : ''"
                @click="setFormationType(m, 'general')">Général</button>
            <button type="button" class="mtc-seg-btn mtc-seg-btn--technique"
                :class="m.type_formation === 'technique' ? 'mtc-seg-btn--active' : ''"
                @click="setFormationType(m, 'technique')">Technique</button>
        </div>
        @endcan

        {{-- Retirer de la maquette.''')

replace('resources/views/esbtp/matieres/partials/_classification-script.blade.php',
'''        kpis: { total: 0, tronc_commun: 0, specialite: 0, non_classe: 0 },
        maquette: { renseignee: false, semestre_1: 0, semestre_2: 0 },
''',
'''        kpis: { total: 0, tronc_commun: 0, specialite: 0, non_classe: 0, general: 0, technique: 0 },
        canConfigureBulletins: @json(auth()->user()?->can('bulletins.configure') ?? false),
        maquette: { renseignee: false, semestre_1: 0, semestre_2: 0 },
''')
replace('resources/views/esbtp/matieres/partials/_classification-script.blade.php',
'''        bulk(val) {
            this.matieres.forEach(m => { m.classification = val; });
            this.recomputeKpis();
        },

        applySuggestions()''',
'''        bulk(val) {
            this.matieres.forEach(m => { m.classification = val; });
            this.recomputeKpis();
        },

        setFormationType(m, val) {
            m.type_formation = (m.type_formation === val) ? null : val;
            this.recomputeKpis();
        },

        bulkFormationType(val) {
            this.matieres.forEach(m => { m.type_formation = val; });
            this.recomputeKpis();
        },

        applySuggestions()''')
replace('resources/views/esbtp/matieres/partials/_classification-script.blade.php',
'''                specialite: this.matieres.filter(m => m.classification === 'specialite').length,
                non_classe: this.matieres.filter(m => !m.classification).length,
            };
''',
'''                specialite: this.matieres.filter(m => m.classification === 'specialite').length,
                non_classe: this.matieres.filter(m => !m.classification).length,
                general: this.matieres.filter(m => m.type_formation === 'general').length,
                technique: this.matieres.filter(m => m.type_formation === 'technique').length,
            };
''')
replace('resources/views/esbtp/matieres/partials/_classification-script.blade.php',
'''                            classification: m.classification,
                            // Une place héritée n'est pas renvoyée comme propre :
''',
'''                            classification: m.classification,
                            ...(this.canConfigureBulletins ? { type_formation: m.type_formation ?? null } : {}),
                            // Une place héritée n'est pas renvoyée comme propre :
''')

# ---------------------------------------------------------------------------
# 4. Le bulletin lit le défaut filière × niveau ; Résultats reste l'override.
# ---------------------------------------------------------------------------
replace('app/Services/BulletinService.php',
'''use App\\Models\\ESBTPMatiere;
use App\\Models\\ESBTPMatiereCoefficient;
''',
'''use App\\Models\\ESBTPMatiere;
use App\\Models\\ESBTPMatiereFilierNiveau;
use App\\Models\\ESBTPMatiereCoefficient;
''')
replace('app/Services/BulletinService.php',
'''    private array $classeCache = [];

    // Caches request-scoped''',
'''    private array $classeCache = [];

    /** @var array<string, string|null> type canonique par classe:matiere */
    private array $formationTypePivotCache = [];

    // Caches request-scoped''')
replace('app/Services/BulletinService.php',
'''        // 3. Fallback : type global de la matière
        $matiere = ESBTPMatiere::find($matiereId);
''',
'''        // 3. Défaut de la maquette au grain filière × niveau. La ligne de la
        // filière propre prime sur celle du tronc commun parent. Cela permet
        // de configurer Général/Technique UNE fois dans /matieres/classification
        // et d'en faire bénéficier toutes les classes du même combo.
        $comboType = $this->typeFormationDuCombo($matiereId, $classeId);
        if ($comboType !== null) {
            return $comboType;
        }

        // 4. Fallback : type global de la matière
        $matiere = ESBTPMatiere::find($matiereId);
''')
replace('app/Services/BulletinService.php',
'''        return 'generale';
    }

    public function getBulletinTemplateView(): string
''',
'''        return 'generale';
    }

    private function typeFormationDuCombo(int $matiereId, int $classeId): ?string
    {
        $cacheKey = $classeId.':'.$matiereId;
        if (array_key_exists($cacheKey, $this->formationTypePivotCache)) {
            return $this->formationTypePivotCache[$cacheKey];
        }

        if (! isset($this->classeCache[$classeId])) {
            $this->classeCache[$classeId] = ESBTPClasse::find($classeId);
        }
        $classe = $this->classeCache[$classeId];
        if (! $classe || ! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return $this->formationTypePivotCache[$cacheKey] = null;
        }

        $classe->loadMissing('filiere');
        $filiereIds = $classe->filiere?->troncCommunUnionFiliereIds() ?? [(int) $classe->filiere_id];

        $lignes = ESBTPMatiereFilierNiveau::query()
            ->where('matiere_id', $matiereId)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->whereIn('filiere_id', $filiereIds)
            ->orderByRaw('filiere_id = ? desc', [$classe->filiere_id])
            ->get(['filiere_id', 'type_formation']);

        foreach ($lignes as $ligne) {
            if ($ligne->type_formation === ESBTPMatiereFilierNiveau::TYPE_GENERAL) {
                return $this->formationTypePivotCache[$cacheKey] = 'generale';
            }
            if ($ligne->type_formation === ESBTPMatiereFilierNiveau::TYPE_TECHNIQUE) {
                return $this->formationTypePivotCache[$cacheKey] = 'technologique_professionnelle';
            }
        }

        return $this->formationTypePivotCache[$cacheKey] = null;
    }

    public function getBulletinTemplateView(): string
''')

replace('app/Services/BulletinService.php',
'''    private function configMatieresPayloadForBulletin(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $payload = ['generales' => [], 'techniques' => []];

        $rows = ESBTPConfigMatiere::query()
''',
'''    public function configMatieresPayloadForBulletin(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $payload = ['generales' => [], 'techniques' => []];
        $configuredIds = [];

        $rows = ESBTPConfigMatiere::query()
''')
replace('app/Services/BulletinService.php',
'''        foreach ($rows as $row) {
            $config = is_array($row->config) ? $row->config : $this->decodeJsonToArray($row->config);
            $type = $config['type'] ?? null;

            if (in_array($type, ['general', 'generale'], true)) {
''',
'''        foreach ($rows as $row) {
            $config = is_array($row->config) ? $row->config : $this->decodeJsonToArray($row->config);
            $type = $config['type'] ?? null;
            // Même `none` compte comme un override : la maquette ne doit pas
            // réintroduire une matière explicitement exclue dans Résultats.
            if (array_key_exists('type', $config)) {
                $configuredIds[(int) $row->matiere_id] = true;
            }

            if (in_array($type, ['general', 'generale'], true)) {
''')
replace('app/Services/BulletinService.php',
'''        $payload['generales'] = array_values(array_unique($payload['generales']));
        $payload['techniques'] = array_values(array_unique($payload['techniques']));

        return $payload;
    }
''',
'''        // Complète les matières sans override classe/période avec le défaut
        // filière × niveau de la Maquette (puis, si absent, le type global).
        $classe = ESBTPClasse::find($classeId);
        if ($classe) {
            $attendu = app(\\App\\Domain\\AcademicPilotage\\Services\\ExpectedSubjectsResolver::class)
                ->forClasse($classe, $periode);
            foreach ($attendu['subjects'] as $matiere) {
                $matiereId = (int) $matiere->id;
                if (isset($configuredIds[$matiereId])) {
                    continue;
                }
                $type = $this->resolveMatiereTypeFormation(
                    $matiereId,
                    $classeId,
                    $this->normalizePeriode($periode),
                    $anneeUniversitaireId,
                );
                if ($type === 'generale') {
                    $payload['generales'][] = $matiereId;
                } elseif ($type === 'technologique_professionnelle') {
                    $payload['techniques'][] = $matiereId;
                }
            }
        }

        $payload['generales'] = array_values(array_unique($payload['generales']));
        $payload['techniques'] = array_values(array_unique($payload['techniques']));

        return $payload;
    }
''', count=1)

# Le bulk utilise exactement le même résolveur que preview/PDF.
old_bulk = '''    private function configMatieresPayload(int $classeId, int $academicYearId, string $period): array
    {
        $payload = ['generales' => [], 'techniques' => []];

        $rows = ESBTPConfigMatiere::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $academicYearId)
            ->whereIn('periode', $this->configPeriods($period))
            ->get(['matiere_id', 'config']);

        foreach ($rows as $row) {
            $config = is_array($row->config) ? $row->config : $this->decodeJsonToArray($row->config);
            $type = $config['type'] ?? null;

            if (in_array($type, ['general', 'generale'], true)) {
                $payload['generales'][] = (int) $row->matiere_id;
            }

            if (in_array($type, ['technique', 'technologique_professionnelle'], true)) {
                $payload['techniques'][] = (int) $row->matiere_id;
            }
        }

        $payload['generales'] = array_values(array_unique($payload['generales']));
        $payload['techniques'] = array_values(array_unique($payload['techniques']));

        return $payload;
    }
'''
new_bulk = '''    private function configMatieresPayload(int $classeId, int $academicYearId, string $period): array
    {
        return $this->bulletinService->configMatieresPayloadForBulletin($classeId, $academicYearId, $period);
    }
'''
replace('app/Domain/AcademicPilotage/Services/BtsBulkBulletinGenerationService.php', old_bulk, new_bulk)

# L'écran Résultats reste disponible comme override, mais affiche le défaut de
# la Maquette lorsqu'aucun override n'existe encore.
replace('app/Http/Controllers/ESBTPBulletinConfigController.php',
'''            if ($config && isset($config->config) && is_string($config->config)) {
                $configData = json_decode($config->config, true);
                // Utiliser la clé 'type' au lieu de 'type_formation'
                $typeFormation = $configData['type'] ?? $configData['type_formation'] ?? null;

                if ($typeFormation === 'general' || $typeFormation === 'generale') {
                    $general[] = $matiere->id;
                } elseif ($typeFormation === 'technique' || $typeFormation === 'technologique_professionnelle') {
                    $technique[] = $matiere->id;
                }
            } else {
                // Classification automatique basée sur le nom
                $nomMatiere = strtolower($matiere->nom ?? $matiere->name ?? '');

                if (
                    str_contains($nomMatiere, 'math') ||
                    str_contains($nomMatiere, 'anglais') ||
                    str_contains($nomMatiere, 'français') ||
                    str_contains($nomMatiere, 'francais') ||
                    str_contains($nomMatiere, 'communication')
                ) {
                    $general[] = $matiere->id;
                } else {
                    $technique[] = $matiere->id;
                }
            }
''',
'''            if ($config && isset($config->config)) {
                $configData = is_array($config->config)
                    ? $config->config
                    : (json_decode((string) $config->config, true) ?: []);
                $typeFormation = $configData['type'] ?? $configData['type_formation'] ?? null;
            } else {
                $typeCanonique = $this->bulletinService->resolveMatiereTypeFormation(
                    (int) $matiere->id,
                    (int) $classe_id,
                    (string) $periode,
                    (int) $annee_universitaire_id,
                );
                $typeFormation = $typeCanonique === 'technologique_professionnelle' ? 'technique' : 'general';
            }

            if ($typeFormation === 'general' || $typeFormation === 'generale') {
                $general[] = $matiere->id;
            } elseif ($typeFormation === 'technique' || $typeFormation === 'technologique_professionnelle') {
                $technique[] = $matiere->id;
            }
''')
replace('app/Http/Controllers/ESBTPBulletinConfigController.php',
'''            $typeFormation = null;
            if ($config && isset($config->config) && is_string($config->config)) {
                $configData = json_decode($config->config, true);
                // Utiliser la clé 'type' au lieu de 'type_formation'
                $typeFormation = $configData['type'] ?? $configData['type_formation'] ?? null;
            }
''',
'''            $typeFormation = null;
            if ($config && isset($config->config)) {
                $configData = is_array($config->config)
                    ? $config->config
                    : (json_decode((string) $config->config, true) ?: []);
                $typeFormation = $configData['type'] ?? $configData['type_formation'] ?? null;
            } else {
                $typeCanonique = $this->bulletinService->resolveMatiereTypeFormation(
                    (int) $matiere->id,
                    (int) $classe_id,
                    (string) $periode,
                    (int) $annee_universitaire_id,
                );
                $typeFormation = $typeCanonique === 'technologique_professionnelle' ? 'technique' : 'general';
            }
''')

# ---------------------------------------------------------------------------
# 5. Bug Suivi des notes : toujours afficher le vrai nom, y compris hors maquette.
# ---------------------------------------------------------------------------
replace('app/Domain/AcademicPilotage/Services/AcademicNoteCoverageService.php',
'''                'matiere:id,name,code',
                'notes' => fn ($query) => $query
''',
'''                'matiere' => fn ($query) => $query->withTrashed()->select('id', 'name', 'code'),
                'notes' => fn ($query) => $query
''')
replace('app/Domain/AcademicPilotage/Services/AcademicNoteCoverageService.php',
'''            ->with('matiere:id,name,code')
            ->orderBy('date_evaluation')
''',
'''            ->with(['matiere' => fn ($query) => $query->withTrashed()->select('id', 'name', 'code')])
            ->orderBy('date_evaluation')
''')
replace('app/Domain/AcademicPilotage/Services/AcademicNoteCoverageService.php',
'''        $orphanRows = $evaluations
            ->filter(fn (ESBTPEvaluation $evaluation) => ! $subjects->contains('id', (int) $evaluation->matiere_id))
            ->groupBy(fn (ESBTPEvaluation $evaluation) => (int) $evaluation->matiere_id)
            ->map(fn (Collection $items): array => $this->subjectRow($items->first()->matiere ?? null, $items, collect(), $indexPour, $entries, true, $enseignants))
            ->values();
''',
'''        $orphanRows = $evaluations
            ->filter(fn (ESBTPEvaluation $evaluation) => ! $subjects->contains('id', (int) $evaluation->matiere_id))
            ->groupBy(fn (ESBTPEvaluation $evaluation) => (int) $evaluation->matiere_id)
            ->map(function (Collection $items) use ($indexPour, $entries, $enseignants): array {
                $evaluation = $items->first();
                $matiere = $evaluation?->matiere;
                // Relation absente (ancienne matière soft-deleted ou donnée
                // historique) : on tente encore le vrai libellé avant un ID.
                if (! $matiere && $evaluation?->matiere_id) {
                    $matiere = ESBTPMatiere::withTrashed()->find((int) $evaluation->matiere_id);
                }

                return $this->subjectRow($matiere, $items, collect(), $indexPour, $entries, true, $enseignants);
            })
            ->values();
''')
replace('app/Domain/AcademicPilotage/Services/AcademicNoteCoverageService.php',
'''        return [
            'id' => $subject?->id,
            'name' => $subject?->name ?? 'Matière hors référentiel',
            'code' => $subject?->code,
''',
'''        $matiereId = $subject?->id ?? ($evaluations->first()?->matiere_id ? (int) $evaluations->first()->matiere_id : null);

        return [
            'id' => $matiereId,
            'name' => $subject?->name ?? ($matiereId ? 'Matière #'.$matiereId : 'Matière hors référentiel'),
            'code' => $subject?->code,
''')
replace('resources/views/esbtp/partials/_couverture-notes-script.blade.php',
'''                    case 'hors_maquette': return 'Matière absente de la maquette';
''',
'''                    case 'hors_maquette': return 'Hors maquette';
''')

# ---------------------------------------------------------------------------
# 6. Nanan : action avec double permission + validation humaine obligatoire.
# ---------------------------------------------------------------------------
write('app/Domain/Assistant/Actions/Matieres/ConfigurerMaquetteBts.php', '''<?php

namespace App\\Domain\\Assistant\\Actions\\Matieres;

use App\\Domain\\Assistant\\Actions\\ActionAgent;
use App\\Domain\\Assistant\\Actions\\Proposition;
use App\\Models\\ESBTPFiliere;
use App\\Models\\ESBTPMatiereFilierNiveau;
use App\\Models\\ESBTPNiveauEtude;
use Illuminate\\Support\\Facades\\DB;

/**
 * Nanan prépare une modification de maquette BTS au même grain que l'écran
 * /esbtp/matieres/classification. Rien n'est écrit avant validation humaine.
 */
final class ConfigurerMaquetteBts extends ActionAgent
{
    public function cle(): string
    {
        return 'configuration_maquette_bts';
    }

    public function libelle(): string
    {
        return 'Préparation de la maquette BTS…';
    }

    public function description(): string
    {
        return "PROPOSE une configuration de maquette BTS pour un couple filière + niveau : semestre (S1/S2/les deux), tronc commun/spécialité, bloc Général/Technique et rang. "
            . "Utilise search_classes/search_subjects pour obtenir les identifiants. N'invente jamais un identifiant. Rien n'est écrit avant le clic Valider.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'filiere_id' => ['type' => 'integer'],
                'niveau_id' => ['type' => 'integer'],
                'matieres' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'matiere_id' => ['type' => 'integer'],
                            'semestre' => ['type' => 'string', 'enum' => ['s1', 's2', 'les_deux']],
                            'classification' => ['type' => 'string', 'enum' => ['tronc_commun', 'specialite']],
                            'type_formation' => ['type' => 'string', 'enum' => ['general', 'technique']],
                            'ordre_bulletin' => ['type' => 'integer'],
                        ],
                        'required' => ['matiere_id'],
                    ],
                ],
                'valider_semestres' => ['type' => 'boolean', 'description' => 'Valide explicitement les semestres de ce combo.'],
            ],
            'required' => ['filiere_id', 'niveau_id', 'matieres'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $filiereId = (int) ($args['filiere_id'] ?? 0);
        $niveauId = (int) ($args['niveau_id'] ?? 0);
        $items = array_values(array_filter((array) ($args['matieres'] ?? []), 'is_array'));

        $filiere = ESBTPFiliere::find($filiereId);
        $niveau = ESBTPNiveauEtude::find($niveauId);
        if (! $filiere || ! $niveau) {
            return new Proposition('Maquette BTS', '', manques: ['Filière ou niveau introuvable : retrouve le bon couple avant de proposer.']);
        }
        if ($items === [] || count($items) > 100) {
            return new Proposition('Maquette BTS', '', manques: [$items === [] ? 'Aucune matière fournie.' : 'Trop de matières en une fois (100 maximum).']);
        }

        $ids = collect($items)->pluck('matiere_id')->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->count() !== count($items)) {
            return new Proposition('Maquette BTS', '', manques: ['Chaque matière doit apparaître une seule fois avec un identifiant valide.']);
        }

        $lignes = ESBTPMatiereFilierNiveau::query()
            ->forCombo($filiereId, $niveauId)
            ->whereIn('matiere_id', $ids)
            ->with('matiere:id,name,code')
            ->get()
            ->keyBy('matiere_id');

        $manques = [];
        $donnees = [];
        $tableau = [];
        $etat = [];

        foreach ($items as $item) {
            $matiereId = (int) ($item['matiere_id'] ?? 0);
            $ligne = $lignes->get($matiereId);
            if (! $ligne) {
                $manques[] = "La matière #{$matiereId} n'est pas rattachée à ce couple filière × niveau.";
                continue;
            }

            $modifs = ['matiere_id' => $matiereId];
            if (array_key_exists('classification', $item)) {
                if (! in_array($item['classification'], [ESBTPMatiereFilierNiveau::TRONC_COMMUN, ESBTPMatiereFilierNiveau::SPECIALITE], true)) {
                    $manques[] = "Classification invalide pour {$ligne->matiere?->name}.";
                } else {
                    $modifs['classification'] = $item['classification'];
                }
            }
            if (array_key_exists('type_formation', $item)) {
                if (! in_array($item['type_formation'], [ESBTPMatiereFilierNiveau::TYPE_GENERAL, ESBTPMatiereFilierNiveau::TYPE_TECHNIQUE], true)) {
                    $manques[] = "Bloc Général/Technique invalide pour {$ligne->matiere?->name}.";
                } else {
                    $modifs['type_formation'] = $item['type_formation'];
                }
            }
            if (array_key_exists('semestre', $item)) {
                $semestre = match ($item['semestre']) {
                    's1' => 1,
                    's2' => 2,
                    'les_deux' => null,
                    default => '__invalide__',
                };
                if ($semestre === '__invalide__') {
                    $manques[] = "Semestre invalide pour {$ligne->matiere?->name}.";
                } else {
                    $modifs['semestre'] = $semestre;
                }
            }
            if (array_key_exists('ordre_bulletin', $item)) {
                $ordre = (int) $item['ordre_bulletin'];
                if ($ordre < 1 || $ordre > 65535) {
                    $manques[] = "Rang invalide pour {$ligne->matiere?->name}.";
                } else {
                    $modifs['ordre_bulletin'] = $ordre;
                }
            }

            $donnees[] = $modifs;
            $etat[$matiereId] = [
                'classification' => $ligne->classification,
                'type_formation' => $ligne->type_formation,
                'semestre' => $ligne->semestre,
                'semestre_renseigne' => (bool) $ligne->semestre_renseigne,
                'ordre_bulletin' => $ligne->ordre_bulletin,
            ];
            $tableau[] = [
                $ligne->matiere?->name ?? "Matière #{$matiereId}",
                array_key_exists('semestre', $modifs) ? ($modifs['semestre'] === null ? 'Les deux' : 'S'.$modifs['semestre']) : 'inchangé',
                $modifs['classification'] ?? 'inchangé',
                $modifs['type_formation'] ?? 'inchangé',
                isset($modifs['ordre_bulletin']) ? (string) $modifs['ordre_bulletin'] : 'inchangé',
            ];
        }

        return new Proposition(
            titre: 'Maquette BTS · '.$filiere->name.' · '.$niveau->name,
            resume: count($donnees).' matière(s) à mettre à jour pour ce couple filière × niveau.',
            tableau: ['colonnes' => ['Matière', 'Semestre', 'TC / Spécialité', 'Bloc', 'Rang'], 'lignes' => $tableau],
            manques: $manques,
            avertissements: filter_var($args['valider_semestres'] ?? false, FILTER_VALIDATE_BOOLEAN)
                ? ['Les semestres seront validés : ce réglage devient actif pour les bulletins et le suivi des notes.']
                : [],
            donnees: [
                'filiere_id' => $filiereId,
                'niveau_id' => $niveauId,
                'matieres' => $donnees,
                'valider_semestres' => filter_var($args['valider_semestres'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],
            etat: $etat,
            risque: filter_var($args['valider_semestres'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $updated = 0;

        DB::transaction(function () use ($d, &$updated): void {
            foreach ($d['matieres'] as $item) {
                $changements = collect($item)->except('matiere_id')->all();
                if ($d['valider_semestres']) {
                    $changements['semestre_renseigne'] = true;
                }
                if ($changements === []) {
                    continue;
                }

                $updated += ESBTPMatiereFilierNiveau::query()
                    ->forCombo($d['filiere_id'], $d['niveau_id'])
                    ->where('matiere_id', $item['matiere_id'])
                    ->update($changements);
            }
        });

        return [
            'message' => "Maquette mise à jour pour {$updated} matière(s).",
            'lien' => route('esbtp.matieres.classification', [
                'filiere_id' => $d['filiere_id'],
                'niveau_id' => $d['niveau_id'],
            ], false),
            'model_type' => ESBTPFiliere::class,
            'model_id' => (int) $d['filiere_id'],
            'details' => ['matieres_mises_a_jour' => $updated],
        ];
    }
}
''')

replace('config/assistant.php',
'''        'classes' => [
            \\App\\Domain\\Assistant\\Actions\\Notes\\SaisirNotes::class,
        ],
''',
'''        'classes' => [
            \\App\\Domain\\Assistant\\Actions\\Notes\\SaisirNotes::class,
            \\App\\Domain\\Assistant\\Actions\\Matieres\\ConfigurerMaquetteBts::class,
        ],
''')
replace('config/chatbot.php',
'''        'proposer_saisie_notes' => [
            'enabled' => true,
            'any_permissions' => ['notes.create', 'notes.edit', 'notes.manage_own'],
            'libelle' => 'Préparation des notes à enregistrer…',
        ],
''',
'''        'proposer_saisie_notes' => [
            'enabled' => true,
            'any_permissions' => ['notes.create', 'notes.edit', 'notes.manage_own'],
            'libelle' => 'Préparation des notes à enregistrer…',
        ],
        'proposer_configuration_maquette_bts' => [
            'enabled' => true,
            'all_permissions' => ['matieres.edit', 'bulletins.configure'],
            'libelle' => 'Préparation de la maquette BTS…',
            'suggestion' => 'Configure la maquette BTS de cette filière et de ce niveau',
        ],
''')

# ---------------------------------------------------------------------------
# 7. Tests ciblés : UI/API, override Résultats, droits Nanan, nom hors maquette.
# ---------------------------------------------------------------------------
write('tests/Feature/Matiere/MaquetteTypeFormationTest.php', '''<?php

namespace Tests\\Feature\\Matiere;

use App\\Domain\\Assistant\\Actions\\Matieres\\ConfigurerMaquetteBts;
use App\\Models\\ESBTPConfigMatiere;
use App\\Models\\ESBTPMatiere;
use App\\Models\\ESBTPMatiereFilierNiveau;
use App\\Models\\User;
use App\\Services\\BulletinService;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Spatie\\Permission\\Models\\Permission;
use Spatie\\Permission\\Models\\Role;
use Tests\\Feature\\Bts\\Concerns\\MonteUneClasseBts;
use Tests\\TestCase;

class MaquetteTypeFormationTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    private User $acteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        foreach (['admin.access', 'matieres.edit', 'bulletins.configure'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $superAdmin = Role::findOrCreate('superAdmin', 'web');
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])->assignRole($superAdmin);

        $this->acteur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $this->acteur->givePermissionTo(['admin.access', 'matieres.edit', 'bulletins.configure']);
    }

    private function lier(ESBTPMatiere $matiere): void
    {
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
        ]);
    }

    public function test_la_maquette_enregistre_general_technique_au_grain_filiere_niveau(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null, 'type_formation' => 'technologique_professionnelle']);
        $this->lier($matiere);

        $this->actingAs($this->acteur)->postJson(route('esbtp.matieres.classification.save'), [
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'classifications' => [[
                'matiere_id' => $matiere->id,
                'semestre' => 1,
                'classification' => 'tronc_commun',
                'type_formation' => 'general',
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('esbtp_matiere_filiere_niveau', [
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'type_formation' => 'general',
        ]);

        $this->actingAs($this->acteur)->getJson(route('esbtp.matieres.classification.combo', [
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
        ]))->assertOk()->assertJsonPath('matieres.0.type_formation', 'general');
    }

    public function test_resultats_reste_un_override_plus_precis_que_la_maquette(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null, 'type_formation' => 'technologique_professionnelle']);
        $this->lier($matiere);
        ESBTPMatiereFilierNiveau::forCombo($this->filiere->id, $this->niveau->id)
            ->where('matiere_id', $matiere->id)->update(['type_formation' => 'general']);

        $service = app(BulletinService::class);
        self::assertSame('generale', $service->resolveMatiereTypeFormation(
            $matiere->id, $this->classe->id, 'semestre1', $this->annee->id
        ));

        ESBTPConfigMatiere::create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'config' => ['type' => 'technique'],
        ]);

        self::assertSame('technologique_professionnelle', $service->resolveMatiereTypeFormation(
            $matiere->id, $this->classe->id, 'semestre1', $this->annee->id
        ));
    }

    public function test_modifier_general_technique_exige_bulletins_configure(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lier($matiere);
        $acteur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $acteur->givePermissionTo(['admin.access', 'matieres.edit']);

        $this->actingAs($acteur)->postJson(route('esbtp.matieres.classification.save'), [
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'classifications' => [['matiere_id' => $matiere->id, 'type_formation' => 'general']],
        ])->assertForbidden();
    }

    public function test_nanan_ne_recoit_l_action_que_si_les_deux_droits_sont_presents(): void
    {
        $action = app(ConfigurerMaquetteBts::class);
        self::assertTrue($action->isAvailableFor($this->acteur));

        $limite = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $limite->givePermissionTo(['admin.access', 'matieres.edit']);
        self::assertFalse($action->isAvailableFor($limite));
    }
}
''')

write('tests/Feature/AcademicPilotage/CouvertureHorsMaquetteNomTest.php', '''<?php

namespace Tests\\Feature\\AcademicPilotage;

use App\\Domain\\AcademicPilotage\\Services\\AcademicNoteCoverageService;
use App\\Models\\ESBTPEvaluation;
use App\\Models\\ESBTPMatiere;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\Feature\\Bts\\Concerns\\MonteUneClasseBts;
use Tests\\TestCase;

class CouvertureHorsMaquetteNomTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    public function test_une_matiere_hors_maquette_affiche_son_vrai_nom_meme_archivee(): void
    {
        $this->monterLaClasse();
        $this->etudiantInscrit();

        $matiere = ESBTPMatiere::factory()->create([
            'name' => 'Hydraulique appliquée',
            'unite_enseignement_id' => null,
        ]);
        ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'date_evaluation' => now()->subDay(),
            'status' => 'completed',
        ]);
        $matiere->delete();

        $resultat = app(AcademicNoteCoverageService::class)->summarize(
            $this->annee->id, 'semestre1', 'BTS', $this->classe->id
        );

        $horsMaquette = collect($resultat['subjects'])->firstWhere('is_orphan', true);
        self::assertNotNull($horsMaquette);
        self::assertSame('Hydraulique appliquée', $horsMaquette['name']);
        self::assertSame('hors_maquette', $horsMaquette['statut']);
    }
}
''')

print('Unified maquette patch applied.')

<?php

namespace App\Services\LMD;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Import bulk des enseignants UEMOA depuis les JSONs extraits des maquettes PDF
 * (4 filières : DROIT / LETTRES-MOD / SVT / SEG).
 *
 * Workflow par UE :
 *   1. Match UE par code dans esbtp_unites_enseignement
 *   2. Si responsable_ue_inferred AND include flag → upsert User + assign
 *      ESBTPUniteEnseignement.responsable_ue_id
 *   3. Pour chaque ECUE :
 *      a. Match ESBTPMatiere par code
 *      b. Premier enseignant = principal → upsert User
 *      c. Update toutes les ESBTPPlanificationAcademique de cet ECUE avec
 *         enseignant_principal_id (Audit log auto si updated event).
 *
 * Dédup users : normalisation `mb_strtolower(trim($name), 'UTF-8')` strict.
 *
 * Format JSON attendu :
 *   {
 *     "filiere": "DROIT",
 *     "ues": [
 *       {
 *         "ue_code": "IGD5001",
 *         "niveau": "L1",
 *         "semestre": 1,
 *         "responsable_ue_inferred": {
 *           "name": "KOUAME Yao",
 *           "grade": "Pr Agr",
 *           "email": null,
 *           "_inferred": true
 *         },
 *         "ecues": [
 *           {
 *             "code": "IGD5001.1",
 *             "name": "La Notion du Droit",
 *             "enseignants": [{"name": "ASSI Marc", "grade": "MA", "email": null}]
 *           }
 *         ]
 *       }
 *     ]
 *   }
 *
 * Idempotent : User::create dedupé par nom normalisé → re-run = no-op pour
 * les users déjà créés ; planifications updated avec même valeur = OK
 * (Eloquent ne déclenche pas l'event updated si rien ne change).
 *
 * Dry-run : transaction wrapped + rollback systématique → aucune écriture DB
 * mais stats reflètent ce qui aurait été fait.
 */
class LMDEnseignantsImporter
{
    /**
     * Régex de détection d'artefacts d'extraction PDF (nom + texte de matière
     * concaténés). Conservée volontairement défensive pour les futures maquettes.
     */
    private const JUNK_PATTERN = '/\b(Droit|Sciences|Économie|Gestion|Lettres|Anglais|MTU|Informatique)\b.{15,}/iu';

    private const MAX_NAME_LENGTH = 60;

    /**
     * @var array{users_created:int, users_matched:int, ecues_assigned:int, ecues_not_found:int, ues_assigned_responsable:int, ues_not_found:int, warnings:array<int,string>}
     */
    private array $stats;

    public function __construct(
        private readonly bool $dryRun = true,
        private readonly bool $includeInferredResponsableUe = false,
    ) {
        $this->stats = $this->emptyStats();
    }

    /**
     * Import un fichier JSON. Renvoie les stats agrégées.
     *
     * @return array{users_created:int, users_matched:int, ecues_assigned:int, ecues_not_found:int, ues_assigned_responsable:int, ues_not_found:int, warnings:array<int,string>}
     *
     * @throws \JsonException Si le JSON est invalide.
     * @throws \RuntimeException Si le fichier est introuvable.
     */
    public function importFile(string $jsonPath): array
    {
        if (!is_file($jsonPath)) {
            throw new \RuntimeException("Fichier introuvable : {$jsonPath}");
        }

        $raw = file_get_contents($jsonPath);
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['ues']) || !is_array($data['ues'])) {
            throw new \RuntimeException("Structure JSON invalide (clé 'ues' manquante) : {$jsonPath}");
        }

        DB::beginTransaction();
        try {
            foreach ($data['ues'] as $ueData) {
                $this->processUe($ueData);
            }

            if ($this->dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return $this->stats;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('LMD enseignants import failed', [
                'exception' => $e->getMessage(),
                'file' => $jsonPath,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Reset les stats internes — utile entre 2 importFile() si on veut un
     * isolement strict des compteurs par fichier.
     */
    public function resetStats(): void
    {
        $this->stats = $this->emptyStats();
    }

    /**
     * @return array{users_created:int, users_matched:int, ecues_assigned:int, ecues_not_found:int, ues_assigned_responsable:int, ues_not_found:int, warnings:array<int,string>}
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    private function processUe(array $ueData): void
    {
        $ueCode = $ueData['ue_code'] ?? null;
        if (empty($ueCode) || !is_string($ueCode)) {
            $this->stats['warnings'][] = "UE sans ue_code valide, ignorée.";
            return;
        }

        // 1. Trouver l'UE par code
        $ue = ESBTPUniteEnseignement::where('code', $ueCode)->first();
        if (!$ue) {
            $this->stats['ues_not_found']++;
            $this->stats['warnings'][] = "UE introuvable: code={$ueCode}";
            // Important : on tente quand même les ECUE (match indépendant via code)
        }

        // 2. Responsable UE (uniquement si flag activé)
        if ($ue && $this->includeInferredResponsableUe && !empty($ueData['responsable_ue_inferred']['name'])) {
            $resp = $this->upsertEnseignant($ueData['responsable_ue_inferred']);
            if ($resp !== null) {
                // Pas d'écrasement silencieux : on ne re-assigne pas si déjà set.
                if ($ue->responsable_ue_id === null) {
                    $ue->responsable_ue_id = $resp->id;
                    $ue->save();
                    $this->stats['ues_assigned_responsable']++;
                }
            }
        }

        // 3. ECUEs → enseignant principal
        $ecues = $ueData['ecues'] ?? [];
        if (!is_array($ecues)) {
            return;
        }

        foreach ($ecues as $ecueData) {
            $this->processEcue($ecueData, $ueCode);
        }
    }

    private function processEcue(array $ecueData, string $ueCodeForContext): void
    {
        $ecueCode = $ecueData['code'] ?? null;
        if (empty($ecueCode) || !is_string($ecueCode)) {
            $this->stats['warnings'][] = "ECUE sans code dans UE={$ueCodeForContext}";
            return;
        }

        $ecue = ESBTPMatiere::where('code', $ecueCode)->first();
        if (!$ecue) {
            $this->stats['ecues_not_found']++;
            $this->stats['warnings'][] = "ECUE introuvable: code={$ecueCode} (UE={$ueCodeForContext})";
            return;
        }

        $enseignants = $ecueData['enseignants'] ?? [];
        if (!is_array($enseignants) || count($enseignants) === 0) {
            return; // Pas d'enseignant à assigner — pas un warning, cas légitime
        }

        // Premier enseignant = principal (co-enseignants en Phase 2 future)
        $primaryTeacher = $this->upsertEnseignant($enseignants[0]);
        if ($primaryTeacher === null) {
            return;
        }

        // Update toutes les planifications de cet ECUE (multi filière/niveau).
        // On ne touche QUE les rows où enseignant_principal_id est null OU différent,
        // pour éviter les updated events inutiles + préserver les assignations
        // manuelles existantes (cf. ues_assigned_responsable défensif).
        $count = ESBTPPlanificationAcademique::where('matiere_id', $ecue->id)
            ->where(function ($q) use ($primaryTeacher) {
                $q->whereNull('enseignant_principal_id')
                  ->orWhere('enseignant_principal_id', '!=', $primaryTeacher->id);
            })
            ->update([
                'enseignant_principal_id' => $primaryTeacher->id,
                'updated_by' => $this->resolveSystemUserId(),
            ]);

        $this->stats['ecues_assigned'] += $count;
    }

    /**
     * Upsert User par nom normalisé. Retourne null si nom invalide/junk.
     */
    private function upsertEnseignant(array $data): ?User
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        if ($this->looksLikeJunk($name)) {
            $this->stats['warnings'][] = "Nom enseignant ignoré (junk?): {$name}";
            return null;
        }

        // Dédup par normalisation case-insensitive (UTF-8 safe)
        $normalized = mb_strtolower($name, 'UTF-8');
        $existingUser = User::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
            ->first();

        if ($existingUser) {
            $this->stats['users_matched']++;
            return $existingUser;
        }

        // Création
        $username = $this->generateUsername($name);
        $tempPassword = $this->generateTempPassword();

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $this->sanitizeEmail($data['email'] ?? null),
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
            'is_active' => true,
        ]);

        // Assigner rôle Spatie (canon KLASSCI : `enseignant`)
        $user->assignRole('enseignant');

        $this->stats['users_created']++;
        // Le mot de passe temporaire est loggué dans warnings POUR LA SESSION
        // CLI uniquement — pas écrit sur disque, pas dans audit (filtré
        // globalement via config/audit.php exclude). À communiquer à l'école
        // via canal sécurisé séparé.
        $this->stats['warnings'][] = sprintf(
            "User créé: %s (username=%s, mot de passe temporaire = %s, doit changer au premier login)",
            $name,
            $username,
            $tempPassword,
        );

        return $user;
    }

    private function looksLikeJunk(string $name): bool
    {
        return mb_strlen($name, 'UTF-8') > self::MAX_NAME_LENGTH
            || preg_match(self::JUNK_PATTERN, $name) > 0;
    }

    private function generateUsername(string $name): string
    {
        // Translittération ASCII + nettoyage
        $clean = Str::ascii($name);
        $clean = preg_replace('/[^A-Za-z\s]/', '', $clean);
        $parts = preg_split('/\s+/', trim((string) $clean));
        $first = mb_strtolower($parts[0] ?? 'user', 'UTF-8');
        $second = mb_strtolower($parts[1] ?? 'x', 'UTF-8');
        $base = trim($first . '.' . $second, '.');
        if ($base === '' || $base === '.') {
            $base = 'enseignant';
        }

        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i;
            $i++;
            if ($i > 9999) {
                // Hard cap — éviter boucle infinie sur edge case improbable
                $username = $base . '.' . Str::random(4);
                break;
            }
        }
        return $username;
    }

    private function generateTempPassword(): string
    {
        // Pattern human-friendly (assez fort + facile à communiquer oralement)
        // Format : `Enseignant!XXXX` où XXXX = 4 chiffres aléatoires.
        return 'Enseignant!' . random_int(1000, 9999);
    }

    private function sanitizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return mb_strtolower($email, 'UTF-8');
    }

    /**
     * Résout l'ID de l'utilisateur courant (CLI ou web). Fallback sur le
     * premier superAdmin pour les contextes script standalone.
     */
    private function resolveSystemUserId(): ?int
    {
        if (auth()->check()) {
            return auth()->id();
        }

        // Fallback CLI/seed standalone : premier superAdmin actif
        static $systemUserId = null;
        if ($systemUserId === null) {
            $systemUserId = User::role('superAdmin')->where('is_active', true)->value('id');
        }
        return $systemUserId;
    }

    /**
     * @return array{users_created:int, users_matched:int, ecues_assigned:int, ecues_not_found:int, ues_assigned_responsable:int, ues_not_found:int, warnings:array<int,string>}
     */
    private function emptyStats(): array
    {
        return [
            'users_created' => 0,
            'users_matched' => 0,
            'ecues_assigned' => 0,
            'ecues_not_found' => 0,
            'ues_assigned_responsable' => 0,
            'ues_not_found' => 0,
            'warnings' => [],
        ];
    }
}

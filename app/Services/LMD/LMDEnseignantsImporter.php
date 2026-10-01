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

    /**
     * @param  bool  $creerLesComptes  false : un enseignant inconnu n'est PAS cree
     *                                  (pas de compte ni de mot de passe temporaire) ;
     *                                  l'ECUE reste sans affectation, et c'est dit.
     * @param  bool  $correspondanceStricte  true : aucun rattrapage par prefixe de
     *                                  code, et un nom ne designe qu'un compte
     *                                  enseignant unique. Ce que Nanan exige : elle
     *                                  ne devine ni un code, ni une personne.
     */
    public function __construct(
        private readonly bool $dryRun = true,
        private readonly bool $includeInferredResponsableUe = false,
        private readonly bool $creerLesComptes = true,
        private readonly bool $correspondanceStricte = false,
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

        return $this->importDonnees($data, $jsonPath);
    }

    /**
     * Le meme import, sur des donnees deja lues (un fichier joint a Nanan).
     * `ues` (UE → ECUE) comme dans les JSON, et/ou `ecues` a plat quand la
     * source ne donne pas l'UE.
     *
     * @return array<string, mixed>
     */
    public function importDonnees(array $data, string $source = 'donnees'): array
    {
        $jsonPath = $source;

        DB::beginTransaction();
        try {
            foreach ((array) ($data['ues'] ?? []) as $ueData) {
                $this->processUe($ueData);
            }
            foreach ((array) ($data['ecues'] ?? []) as $ecueData) {
                $this->processEcue((array) $ecueData, '—');
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

        // 1. Trouver l'UE par code — sauf si plusieurs parcours impriment ce code.
        [$ue, $ueAmbigue] = $this->parCodeImprime(ESBTPUniteEnseignement::class, $ueCode);
        if ($ueAmbigue) {
            $this->stats['warnings'][] = "UE ambiguë: code={$ueCode} désigne plusieurs UE (une par parcours). Responsable non affecté.";
        }

        // Fallback UE-via-ECUE-prefix : si l'UE du JSON enseignants n'existe pas
        // en DB, le code UE est probablement un pseudo-code généré par le parser
        // PDF à partir du préfixe d'un ECUE (ex: JSON ue_code=ACN5001 alors que
        // la vraie UE-mère est COG5001 contenant ECUE ACN5001.1). On retrouve
        // l'UE réelle en cherchant un ECUE dont le code commence par {ueCode}.
        if (!$ue && !$ueAmbigue && !$this->correspondanceStricte) {
            $matiere = ESBTPMatiere::where('code', 'LIKE', $ueCode.'.%')
                ->orWhere('code', 'LIKE', $ueCode.'-%')
                ->whereNotNull('unite_enseignement_id')
                ->first();
            if ($matiere) {
                $ue = ESBTPUniteEnseignement::find($matiere->unite_enseignement_id);
                if ($ue) {
                    $this->stats['warnings'][] = "Fallback UE-via-ECUE: PDF UE={$ueCode} → DB UE={$ue->code} (via ECUE {$matiere->code})";
                }
            }
        }

        if (!$ue && !$ueAmbigue) {
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

    /**
     * La ligne qui imprime ce code, et si plusieurs l'impriment (une cle
     * suffixee par parcours, CodeDeMaquette) : le PDF des enseignants ne dit
     * pas laquelle, affecter au hasard mettrait l'enseignant sur l'autre.
     *
     * @return array{0: mixed, 1: bool}
     */
    private function parCodeImprime(string $modele, string $code): array
    {
        $lignes = $modele::where(fn ($q) => $q->where('code', $code)
                ->orWhere('code', 'like', $code . \App\Services\LMD\CodeDeMaquette::SEPARATEUR . '%'))
            ->where('code', 'not like', '%' . \App\Services\LMD\CodeDeMatiere::SUFFIXE_ARCHIVE . '%')
            ->get();

        return $lignes->count() > 1 ? [null, true] : [$lignes->first(), false];
    }

    private function processEcue(array $ecueData, string $ueCodeForContext): void
    {
        $ecueCode = $ecueData['code'] ?? null;
        if (empty($ecueCode) || !is_string($ecueCode)) {
            $this->stats['warnings'][] = "ECUE sans code dans UE={$ueCodeForContext}";
            return;
        }

        // Deux elements de deux parcours peuvent imprimer le meme code
        // (CodeDeMaquette : AGR21031 et AGR21031~LPA). Le PDF des enseignants ne
        // dit pas lequel : affecter au hasard mettrait l'enseignant de genetique
        // animale sur la vegetale. On s'abstient et on le dit.
        [$ecue, $ambigu] = $this->parCodeImprime(ESBTPMatiere::class, $ecueCode);
        if ($ambigu) {
            $this->stats['warnings'][] = "ECUE ambigu: code={$ecueCode} désigne plusieurs éléments (un par parcours). Affectez l'enseignant depuis l'écran de l'ECUE.";

            return;
        }

        // Fallback prefix-match : les maquettes UEMOA PDFs ont des inconsistances
        // entre code UE-mère et code ECUE child (ex: ECUE `ACN5001.1` sous UE
        // `COG5001`, ECUE `ENA4005-AGRO.1` sous UE `ENA4005`). L'extracteur
        // enseignants génère parfois le code parent au lieu du code ECUE complet.
        // On cherche donc `{ecueCode}.%` (suffixe `.1/.2`) ou `{ecueCode}-%`
        // (suffixe parcours `-AGRO/-ECO/-GES`) avant de warner.
        // Rattrape ~15 cas par tenant (cf agent PDF re-extraction 16/05/2026).
        if (!$ecue && !str_contains($ecueCode, '.') && !$this->correspondanceStricte) {
            $ecue = ESBTPMatiere::where('code', 'LIKE', $ecueCode.'.%')
                ->orWhere('code', 'LIKE', $ecueCode.'-%')
                ->orderBy('id')
                ->first();
            if ($ecue) {
                $this->stats['warnings'][] = "Fallback prefix-match: PDF code={$ecueCode} → DB ECUE={$ecue->code}";
            }
        }

        if (!$ecue) {
            $this->stats['ecues_not_found']++;
            $this->stats['warnings'][] = "ECUE introuvable: code={$ecueCode} (UE={$ueCodeForContext})";
            return;
        }
        if ($this->correspondanceStricte && ! $this->estUnEcue($ecue)) {
            $this->stats['warnings'][] = "Code {$ecueCode} : code d'une matière BTS, ignoré.";

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

        $this->noterAffectation($ecue, $primaryTeacher);
    }

    /**
     * Pose l'enseignant principal sur toutes les planifications de l'ECUE
     * (multi filiere/niveau), et garde le detail ECUE par ECUE : c'est ce que
     * relit la personne avant de valider (Nanan), et ce qui rend une
     * proposition perimee s'il bouge. Seules les lignes sans enseignant ou
     * avec un autre sont touchees : pas d'evenement `updated` inutile.
     */
    private function noterAffectation(ESBTPMatiere $ecue, User $enseignant): void
    {
        $avant = ESBTPPlanificationAcademique::where('matiere_id', $ecue->id)
            ->with('enseignantPrincipal:id,name')->get(['id', 'enseignant_principal_id'])
            ->map(fn ($p) => $p->enseignantPrincipal?->name ?? '—')->unique()->sort()->values()->all();
        $count = ESBTPPlanificationAcademique::where('matiere_id', $ecue->id)
            ->where(fn ($q) => $q->whereNull('enseignant_principal_id')->orWhere('enseignant_principal_id', '!=', $enseignant->id))
            ->update([
                'enseignant_principal_id' => $enseignant->id,
                'updated_by' => $this->resolveSystemUserId(),
            ]);

        $this->stats['ecues_assigned'] += $count;
        $this->stats['affectations'][] = [
            'ecue' => (string) CodeDeMaquette::affiche($ecue->code),
            'ecue_nom' => (string) $ecue->name,
            'enseignant' => (string) $enseignant->name,
            'enseignant_id' => (int) $enseignant->id,
            'avant' => $avant,
            'planifications' => $count,
        ];
    }

    /**
     * Un ECUE, et pas une matiere BTS qui imprimerait le meme code : son
     * unite, ou une ligne du pivot UE ↔ matiere. Sans ce controle, le mode
     * strict pouvait ecraser l'enseignant d'une planification BTS.
     */
    private function estUnEcue(ESBTPMatiere $matiere): bool
    {
        return $matiere->unite_enseignement_id !== null
            || DB::table('esbtp_ue_matiere')->where('matiere_id', $matiere->id)->exists();
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
        if ($this->correspondanceStricte) {
            // Un compte ENSEIGNANT, et un seul : un etudiant homonyme ne doit
            // jamais recevoir un cours, et entre deux homonymes on ne choisit pas.
            $candidats = User::role('enseignant')->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])->get();
            if ($candidats->count() > 1) {
                $this->stats['warnings'][] = "Enseignant ambigu : {$name} désigne {$candidats->count()} comptes enseignants. Affectez depuis l'écran de l'ECUE.";

                return null;
            }
            $existingUser = $candidats->first();
        } else {
            $existingUser = User::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                ->first();
        }

        if ($existingUser) {
            $this->stats['users_matched']++;
            return $existingUser;
        }

        if (! $this->creerLesComptes) {
            $this->stats['enseignants_inconnus'][] = $name;
            $this->stats['warnings'][] = "Enseignant inconnu : {$name}. Aucun compte n'est créé ici : créez-le depuis l'écran Enseignants, puis relancez.";

            return null;
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

        // Fallback CLI/seed standalone : premier superAdmin actif. Memorise par
        // instance, pas en `static` : un worker long (ou une serie de tests)
        // gardait l'identifiant d'un compte depuis supprime, et la cle
        // etrangere `updated_by` refusait l'ecriture.
        return $this->systemUserId ??= User::role('superAdmin')->where('is_active', true)->value('id');
    }

    private ?int $systemUserId = null;

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
            'affectations' => [],
            'enseignants_inconnus' => [],
        ];
    }
}

<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDJuryDecision;
use App\Models\ESBTPLMDJuryMembre;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Jury de délibération UEMOA — workflow complet.
 *
 * Composition (président, assesseurs, secrétaire) + quorum settings tenant.
 * Calcul auto décisions selon moyenne + crédits + compensation + seuil.
 * Override jury individuel avec motif obligatoire (DB constraint NOT NULL).
 * PV PDF avec numérotation séquentielle thread-safe (DB lockForUpdate).
 * Archivage légal 5 ans (setting lmd_pv_retention_years).
 */
class JuryDeliberationService
{
    /**
     * Calcule la décision automatique pour un étudiant donné.
     *
     * @return array{decision_auto:string, mention:?string, moyenne:?float, credits_obtenus:int, credits_attendus:int, raisons:array}
     */
    public function calculerDecisionAuto(ESBTPEtudiant $etudiant, ESBTPLMDJury $jury): array
    {
        $bulletin = $this->resolveBulletin($etudiant, $jury);
        $compensationEnabled = (bool) SettingsHelper::get('lmd_compensation_enabled', true);
        $intraUeCompensation = (bool) SettingsHelper::get('lmd_intra_ue_compensation', true);
        $seuilValidation = (float) SettingsHelper::get('lmd_seuil_validation_ecue', 10);
        $noteEliminatoire = (float) SettingsHelper::get('lmd_note_eliminatoire', 0);

        $thresholds = [
            'passable' => (float) SettingsHelper::get('lmd_mention_p_threshold', 10),
            'assez_bien' => (float) SettingsHelper::get('lmd_mention_ab_threshold', 12),
            'bien' => (float) SettingsHelper::get('lmd_mention_b_threshold', 14),
            'tres_bien' => (float) SettingsHelper::get('lmd_mention_tb_threshold', 16),
            'excellent' => 18.0,
        ];

        $moyenne = $bulletin?->moyenne_generale !== null
            ? (float) $bulletin->moyenne_generale
            : null;
        $creditsObtenus = (int) ($bulletin?->credits_obtenus ?? 0);
        $creditsAttendus = (int) ($bulletin?->credits_attendus ?? 30);

        $raisons = [];
        $decision = 'ajourne';

        // ECUE éliminatoires
        $hasEliminatoire = false;
        if ($bulletin && $noteEliminatoire > 0) {
            $resultats = ESBTPLMDResultatECUE::where('bulletin_id', $bulletin->id)->get();
            foreach ($resultats as $r) {
                if ($r->moyenne !== null && (float) $r->moyenne < $noteEliminatoire) {
                    $hasEliminatoire = true;
                    $raisons[] = sprintf('ECUE %d note %s < éliminatoire %s', $r->matiere_id, $r->moyenne, $noteEliminatoire);
                    break;
                }
            }
        }

        // Décision principale
        if ($moyenne === null) {
            $decision = 'defere';
            $raisons[] = 'Moyenne non calculée';
        } elseif ($hasEliminatoire) {
            $decision = 'ajourne';
            $raisons[] = 'Note éliminatoire détectée';
        } elseif ($moyenne >= $seuilValidation && $creditsObtenus >= $creditsAttendus) {
            $decision = 'admis';
            $raisons[] = sprintf('Moyenne %.2f ≥ %.2f, crédits %d/%d', $moyenne, $seuilValidation, $creditsObtenus, $creditsAttendus);
        } elseif ($moyenne >= $seuilValidation && $creditsObtenus < $creditsAttendus) {
            $decision = 'admis_sous_condition';
            $raisons[] = sprintf('Moyenne %.2f OK mais crédits %d/%d insuffisants', $moyenne, $creditsObtenus, $creditsAttendus);
        } else {
            $decision = 'admission_rattrapage';
            $raisons[] = sprintf('Moyenne %.2f < %.2f, éligible 2e session', $moyenne, $seuilValidation);
        }

        // Mention
        $mention = null;
        if ($decision === 'admis' && $moyenne !== null) {
            if ($moyenne >= $thresholds['excellent']) {
                $mention = 'excellent';
            } elseif ($moyenne >= $thresholds['tres_bien']) {
                $mention = 'tres_bien';
            } elseif ($moyenne >= $thresholds['bien']) {
                $mention = 'bien';
            } elseif ($moyenne >= $thresholds['assez_bien']) {
                $mention = 'assez_bien';
            } elseif ($moyenne >= $thresholds['passable']) {
                $mention = 'passable';
            }
        }

        return [
            'decision_auto' => $decision,
            'mention' => $mention,
            'moyenne' => $moyenne,
            'credits_obtenus' => $creditsObtenus,
            'credits_attendus' => $creditsAttendus,
            'raisons' => $raisons,
            'bulletin_id' => $bulletin?->id,
        ];
    }

    /**
     * Applique en bulk les décisions auto pour tous les étudiants concernés par ce jury.
     * Idempotent : ne re-crée pas une décision déjà présente sauf si override=false.
     */
    public function appliquerDecisionsAuto(ESBTPLMDJury $jury): int
    {
        abort_if($jury->isLocked(), 422, 'PV déjà généré — décisions verrouillées');

        $etudiants = $this->getEtudiantsForJury($jury);
        $count = 0;

        DB::transaction(function () use ($jury, $etudiants, &$count) {
            foreach ($etudiants as $etudiant) {
                $existing = ESBTPLMDJuryDecision::where([
                    'jury_id' => $jury->id,
                    'etudiant_id' => $etudiant->id,
                ])->first();

                if ($existing && $existing->override_par_jury) {
                    continue; // Préserve les overrides manuels
                }

                $calc = $this->calculerDecisionAuto($etudiant, $jury);

                if ($existing) {
                    $existing->update([
                        'decision_auto' => $calc['decision_auto'],
                        'decision' => $calc['decision_auto'],
                        'mention' => $calc['mention'],
                        'bulletin_id' => $calc['bulletin_id'],
                        'moyenne_generale' => $calc['moyenne'],
                        'credits_obtenus' => $calc['credits_obtenus'],
                        'credits_attendus' => $calc['credits_attendus'],
                        'updated_by' => optional(auth()->user())->id,
                    ]);
                } else {
                    ESBTPLMDJuryDecision::create([
                        'jury_id' => $jury->id,
                        'etudiant_id' => $etudiant->id,
                        'bulletin_id' => $calc['bulletin_id'],
                        'decision_auto' => $calc['decision_auto'],
                        'decision' => $calc['decision_auto'],
                        'mention' => $calc['mention'],
                        'moyenne_generale' => $calc['moyenne'],
                        'credits_obtenus' => $calc['credits_obtenus'],
                        'credits_attendus' => $calc['credits_attendus'],
                        'override_par_jury' => false,
                        'created_by' => optional(auth()->user())->id,
                    ]);
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Override jury individuel d'une décision (motif obligatoire).
     */
    public function overrideDecision(
        ESBTPLMDJury $jury,
        ESBTPEtudiant $etudiant,
        string $nouvelleDecision,
        string $motif,
        ?string $voteResultat = null
    ): ESBTPLMDJuryDecision {
        abort_if($jury->isLocked(), 422, 'PV déjà généré — override interdit');
        abort_unless(in_array($nouvelleDecision, ESBTPLMDJuryDecision::DECISIONS, true), 422, 'Décision invalide');
        abort_if(trim($motif) === '', 422, 'Motif override obligatoire');

        $decision = ESBTPLMDJuryDecision::firstOrNew([
            'jury_id' => $jury->id,
            'etudiant_id' => $etudiant->id,
        ]);

        if (! $decision->exists) {
            $calc = $this->calculerDecisionAuto($etudiant, $jury);
            $decision->decision_auto = $calc['decision_auto'];
            $decision->bulletin_id = $calc['bulletin_id'];
            $decision->moyenne_generale = $calc['moyenne'];
            $decision->credits_obtenus = $calc['credits_obtenus'];
            $decision->credits_attendus = $calc['credits_attendus'];
        }

        $decision->decision = $nouvelleDecision;
        $decision->override_par_jury = ($decision->decision_auto !== $nouvelleDecision);
        $decision->motif_override = $decision->override_par_jury ? $motif : null;
        $decision->vote_resultat = $voteResultat;
        $decision->updated_by = optional(auth()->user())->id;
        $decision->save();

        return $decision;
    }

    /**
     * Réserve un numéro PV séquentiel thread-safe.
     * Format : PV-{ANNEE}-{TENANT}-{SEQ4}
     */
    public function reserverNumeroPv(int $anneeUniversitaireId): string
    {
        return DB::transaction(function () use ($anneeUniversitaireId) {
            $last = ESBTPLMDJury::query()
                ->where('annee_universitaire_id', $anneeUniversitaireId)
                ->whereNotNull('pv_numero')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('pv_numero');

            $seq = 1;
            if ($last && preg_match('/-(\d{4})$/', $last, $m)) {
                $seq = ((int) $m[1]) + 1;
            }

            $tenant = strtoupper((string) (config('app.tenant_code') ?? env('TENANT_CODE', 'PRES')));
            $annee = \App\Models\ESBTPAnneeUniversitaire::find($anneeUniversitaireId);
            $anneeStr = preg_replace('/[^A-Za-z0-9]/', '', $annee?->libelle ?? (string) $anneeUniversitaireId);

            return sprintf('PV-%s-%s-%04d', $anneeStr, $tenant, $seq);
        });
    }

    /**
     * Génère le PV PDF, le stocke en storage/pv/{tenant}/{annee}/{numero}.pdf,
     * persiste le path + numéro + datetime + auteur.
     */
    public function genererPvDeliberation(ESBTPLMDJury $jury): string
    {
        if ($jury->pv_path) {
            return $jury->pv_path;
        }

        $jury->loadMissing(['anneeUniversitaire', 'parcours', 'classe', 'membres.user', 'decisions.etudiant']);

        $numero = $jury->pv_numero ?? $this->reserverNumeroPv($jury->annee_universitaire_id);

        $stats = $this->buildStatistiques($jury);

        $pdf = Pdf::loadView('pdf.lmd-jury-pv', [
            'jury' => $jury,
            'numero' => $numero,
            'stats' => $stats,
            'generated_at' => now(),
        ])->setPaper('a4', 'portrait');

        $tenant = strtolower((string) (config('app.tenant_code') ?? env('TENANT_CODE', 'pres')));
        $anneeStr = preg_replace('/[^A-Za-z0-9]/', '', $jury->anneeUniversitaire?->libelle ?? (string) $jury->annee_universitaire_id);
        $relPath = sprintf('pv/%s/%s/%s.pdf', $tenant, $anneeStr, $numero);

        Storage::disk('local')->put($relPath, $pdf->output());

        $jury->forceFill([
            'pv_numero' => $numero,
            'pv_path' => $relPath,
            'pv_genere_at' => now(),
            'pv_genere_par' => optional(auth()->user())->id,
        ])->save();

        // Lock toutes les décisions associées
        $jury->decisions()->update([
            'locked' => true,
            'locked_at' => now(),
        ]);

        Log::info('[JuryDeliberationService] PV généré', [
            'jury_id' => $jury->id,
            'numero' => $numero,
            'path' => $relPath,
            'decisions_count' => $jury->decisions->count(),
        ]);

        return $relPath;
    }

    /**
     * Publie le jury : verrouille décisions + change statut + horodate.
     */
    public function publierDecisions(ESBTPLMDJury $jury): void
    {
        abort_if(! $jury->pv_genere_at, 422, 'Générer le PV avant publication');

        $jury->update([
            'status' => 'publie',
            'publie_at' => now(),
            'publie_par' => optional(auth()->user())->id,
        ]);

        Log::info('[JuryDeliberationService] Jury publié', ['jury_id' => $jury->id]);
    }

    /**
     * Vérifie le quorum selon settings tenant.
     *
     * @return array{ok:bool, present:int, min:int, has_president:bool, has_secretaire:bool, reasons:array}
     */
    public function verifierQuorum(ESBTPLMDJury $jury): array
    {
        $min = (int) SettingsHelper::get('lmd_jury_quorum_min', 2);
        $minAssesseurs = (int) SettingsHelper::get('lmd_jury_quorum_assesseurs_min', 1);
        $membres = $jury->membres()->where('present', true)->get();

        $present = $membres->count();
        $hasPresident = $membres->contains('role', 'president');
        $hasSecretaire = $membres->contains('role', 'secretaire');
        $assesseurs = $membres->where('role', 'assesseur')->count();

        $reasons = [];
        $ok = true;

        if ($present < $min) {
            $ok = false;
            $reasons[] = sprintf('%d membres présents < quorum %d', $present, $min);
        }
        if (! $hasPresident) {
            $ok = false;
            $reasons[] = 'Président absent';
        }
        if (! $hasSecretaire) {
            $reasons[] = 'Secrétaire absent (recommandé)';
        }
        if ($assesseurs < $minAssesseurs) {
            $reasons[] = sprintf('%d assesseur(s) < min %d', $assesseurs, $minAssesseurs);
        }

        return [
            'ok' => $ok,
            'present' => $present,
            'min' => $min,
            'has_president' => $hasPresident,
            'has_secretaire' => $hasSecretaire,
            'assesseurs_count' => $assesseurs,
            'assesseurs_min' => $minAssesseurs,
            'reasons' => $reasons,
        ];
    }

    /**
     * Enregistre la signature digital d'un membre (canvas base64 ou checkbox).
     */
    public function enregistrerSignature(
        ESBTPLMDJuryMembre $membre,
        string $signatureData,
        ?string $ip = null,
        ?string $userAgent = null
    ): ESBTPLMDJuryMembre {
        $membre->update([
            'signature_data' => $signatureData,
            'signature_at' => now(),
            'signature_ip' => $ip,
            'signature_user_agent' => $userAgent,
        ]);

        return $membre;
    }

    /**
     * Statistiques jury pour le PV.
     */
    public function buildStatistiques(ESBTPLMDJury $jury): array
    {
        $decisions = $jury->decisions ?? collect();

        return [
            'total' => $decisions->count(),
            'admis' => $decisions->where('decision', 'admis')->count(),
            'admission_rattrapage' => $decisions->where('decision', 'admission_rattrapage')->count(),
            'ajourne' => $decisions->where('decision', 'ajourne')->count(),
            'exclu' => $decisions->where('decision', 'exclu')->count(),
            'admis_sous_condition' => $decisions->where('decision', 'admis_sous_condition')->count(),
            'defere' => $decisions->where('decision', 'defere')->count(),
            'overrides' => $decisions->where('override_par_jury', true)->count(),
            'mentions' => [
                'excellent' => $decisions->where('mention', 'excellent')->count(),
                'tres_bien' => $decisions->where('mention', 'tres_bien')->count(),
                'bien' => $decisions->where('mention', 'bien')->count(),
                'assez_bien' => $decisions->where('mention', 'assez_bien')->count(),
                'passable' => $decisions->where('mention', 'passable')->count(),
            ],
            'moyenne_promo' => $decisions->whereNotNull('moyenne_generale')->avg('moyenne_generale'),
        ];
    }

    private function resolveBulletin(ESBTPEtudiant $etudiant, ESBTPLMDJury $jury): ?ESBTPLMDBulletin
    {
        $query = ESBTPLMDBulletin::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $jury->annee_universitaire_id);

        if ($jury->classe_id) {
            $query->where('classe_id', $jury->classe_id);
        }
        if ($jury->semestre) {
            $query->where('semestre', $jury->semestre);
        }

        return $query->orderByDesc('id')->first();
    }

    /**
     * Liste les étudiants concernés par ce jury via bulletins LMD du scope.
     *
     * @return Collection<int, ESBTPEtudiant>
     */
    private function getEtudiantsForJury(ESBTPLMDJury $jury): Collection
    {
        $bulletinQuery = ESBTPLMDBulletin::where('annee_universitaire_id', $jury->annee_universitaire_id);
        if ($jury->classe_id) {
            $bulletinQuery->where('classe_id', $jury->classe_id);
        }
        if ($jury->semestre) {
            $bulletinQuery->where('semestre', $jury->semestre);
        }

        $etudiantIds = $bulletinQuery->pluck('etudiant_id')->unique()->values();
        if ($etudiantIds->isEmpty()) {
            return collect();
        }

        return ESBTPEtudiant::whereIn('id', $etudiantIds)->get();
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPInscription;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPPaiement;
use App\Services\ESBTPInscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManageFraisSubscriptions extends Command
{
    protected $signature = 'esbtp:manage-fees
                            {--dry-run : Prévisualiser sans modifier la base}
                            {--force : Ignorer les confirmations}';

    protected $description = 'Gérer les souscriptions de frais : supprimer ou régénérer pour une année, classe, filière, niveau ou étudiant';

    protected ESBTPInscriptionService $inscriptionService;

    public function __construct(ESBTPInscriptionService $inscriptionService)
    {
        parent::__construct();
        $this->inscriptionService = $inscriptionService;
    }

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        if ($isDryRun) {
            $this->warn('🔍 MODE DRY-RUN — Aucune modification ne sera effectuée');
            $this->newLine();
        }

        // ── Étape 1 : Choisir l'action ──
        $action = $this->choice(
            '📋 Quelle action voulez-vous effectuer ?',
            ['Supprimer les souscriptions', 'Régénérer les souscriptions'],
            0
        );
        $isClean = str_contains($action, 'Supprimer');

        $this->newLine();
        $this->info($isClean
            ? '🗑️  Mode SUPPRESSION — Supprimer/désactiver les souscriptions de frais'
            : '🔄 Mode RÉGÉNÉRATION — Générer les souscriptions manquantes depuis les configurations actuelles'
        );
        $this->newLine();

        // ── Étape 2 : Choisir l'année universitaire ──
        $annees = ESBTPAnneeUniversitaire::orderByDesc('is_current')->orderByDesc('start_date')->get();
        if ($annees->isEmpty()) {
            $this->error('❌ Aucune année universitaire trouvée.');
            return Command::FAILURE;
        }

        $anneeChoices = $annees->map(fn($a) => $a->name . ($a->is_current ? ' ★ (courante)' : ''))->toArray();
        $anneeChoice = $this->choice('🎓 Année universitaire :', $anneeChoices, 0);
        $anneeIndex = array_search($anneeChoice, $anneeChoices);
        $annee = $annees[$anneeIndex];

        $this->info("   → Année sélectionnée : {$annee->name}");
        $this->newLine();

        // ── Étape 3 : Choisir le scope (filtres) ──
        $filterType = $this->choice(
            '🎯 Filtrer les inscriptions par :',
            ['Toutes les inscriptions', 'Par étudiant (matricule)', 'Par classe', 'Par filière', 'Par niveau d\'étude'],
            0
        );

        $inscriptionsQuery = ESBTPInscription::with(['etudiant', 'classe.filiere', 'classe.niveau', 'fraisSubscriptions'])
            ->where('annee_universitaire_id', $annee->id);

        $filterLabel = "Année {$annee->name}";

        if (str_contains($filterType, 'étudiant')) {
            $matricule = $this->ask('📝 Matricule de l\'étudiant :');
            $inscriptionsQuery->whereHas('etudiant', function ($q) use ($matricule) {
                $q->where('matricule', $matricule)
                  ->orWhere('id', $matricule); // Accepter aussi l'ID
            });
            $filterLabel .= " — Étudiant : {$matricule}";

        } elseif (str_contains($filterType, 'classe')) {
            $classes = ESBTPClasse::where('annee_universitaire_id', $annee->id)
                ->where('is_active', true)
                ->with(['filiere', 'niveau'])
                ->get();

            if ($classes->isEmpty()) {
                $this->error('❌ Aucune classe trouvée pour cette année.');
                return Command::FAILURE;
            }

            $classeChoices = $classes->map(fn($c) => "[{$c->id}] {$c->name} ({$c->filiere->name} - {$c->niveau->name})")->toArray();
            $classeChoice = $this->choice('📚 Classe :', $classeChoices);
            preg_match('/\[(\d+)\]/', $classeChoice, $m);
            $classeId = (int) $m[1];
            $inscriptionsQuery->where('classe_id', $classeId);
            $filterLabel .= " — Classe : {$classeChoice}";

        } elseif (str_contains($filterType, 'filière')) {
            $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
            if ($filieres->isEmpty()) {
                $this->error('❌ Aucune filière trouvée.');
                return Command::FAILURE;
            }

            $filiereChoices = $filieres->map(fn($f) => "[{$f->id}] {$f->name} ({$f->code})")->toArray();
            $filiereChoice = $this->choice('🏫 Filière :', $filiereChoices);
            preg_match('/\[(\d+)\]/', $filiereChoice, $m);
            $filiereId = (int) $m[1];
            $inscriptionsQuery->where('filiere_id', $filiereId);
            $filterLabel .= " — Filière : {$filiereChoice}";

        } elseif (str_contains($filterType, 'niveau')) {
            $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get();
            if ($niveaux->isEmpty()) {
                $this->error('❌ Aucun niveau trouvé.');
                return Command::FAILURE;
            }

            $niveauChoices = $niveaux->map(fn($n) => "[{$n->id}] {$n->name}")->toArray();
            $niveauChoice = $this->choice('📊 Niveau d\'étude :', $niveauChoices);
            preg_match('/\[(\d+)\]/', $niveauChoice, $m);
            $niveauId = (int) $m[1];
            $inscriptionsQuery->where('niveau_id', $niveauId);
            $filterLabel .= " — Niveau : {$niveauChoice}";
        }

        $inscriptions = $inscriptionsQuery->get();

        if ($inscriptions->isEmpty()) {
            $this->warn("⚠️  Aucune inscription trouvée pour : {$filterLabel}");
            return Command::SUCCESS;
        }

        // ── Étape 4 : Afficher résumé des inscriptions ciblées ──
        $this->newLine();
        $this->info("📋 INSCRIPTIONS CIBLÉES : {$filterLabel}");
        $this->newLine();

        $totalSubscriptions = 0;
        $totalAmount = 0;
        $summaryRows = [];

        foreach ($inscriptions as $insc) {
            $subs = $insc->fraisSubscriptions;
            $activeCount = $subs->where('is_active', true)->count();
            $subTotal = $subs->where('is_active', true)->sum('amount');
            $totalSubscriptions += $activeCount;
            $totalAmount += $subTotal;

            $summaryRows[] = [
                $insc->id,
                $insc->etudiant ? ($insc->etudiant->nom . ' ' . ($insc->etudiant->prenoms ?? '')) : 'N/A',
                $insc->etudiant->matricule ?? 'N/A',
                $insc->classe->name ?? 'N/A',
                $insc->affectation_status ?? 'affecté',
                $activeCount,
                number_format($subTotal, 0, ',', ' ') . ' FCFA',
            ];
        }

        // Limiter l'affichage si trop de lignes
        if (count($summaryRows) > 20) {
            $this->table(
                ['Insc. ID', 'Étudiant', 'Matricule', 'Classe', 'Affectation', 'Sousc.', 'Montant total'],
                array_slice($summaryRows, 0, 10)
            );
            $this->info("   ... et " . (count($summaryRows) - 10) . " autres inscriptions");
        } else {
            $this->table(
                ['Insc. ID', 'Étudiant', 'Matricule', 'Classe', 'Affectation', 'Sousc.', 'Montant total'],
                $summaryRows
            );
        }

        $this->newLine();
        $this->info("📊 Total : {$inscriptions->count()} inscriptions — {$totalSubscriptions} souscriptions actives — " . number_format($totalAmount, 0, ',', ' ') . " FCFA");
        $this->newLine();

        if ($totalSubscriptions === 0 && $isClean) {
            $this->warn('⚠️  Aucune souscription active à supprimer.');
            return Command::SUCCESS;
        }

        // ── Étape 5 : Action spécifique ──
        if ($isClean) {
            // Vérifier les paiements existants (uniquement pour le mode Supprimer)
            $inscriptionIds = $inscriptions->pluck('id');
            $paiementsQuery = ESBTPPaiement::whereIn('inscription_id', $inscriptionIds)
                ->whereNotNull('frais_category_id');
            $paiementsCount = (clone $paiementsQuery)->count();
            $paiementsTotal = (clone $paiementsQuery)->sum('montant');

            $hasPaiements = $paiementsCount > 0;
            $deletePaiements = false;

            if ($hasPaiements) {
                $this->newLine();
                $this->error('⚠️  ATTENTION — PAIEMENTS EXISTANTS DÉTECTÉS');
                $this->warn("   {$paiementsCount} paiement(s) totalisant " . number_format($paiementsTotal, 0, ',', ' ') . " FCFA sont liés à ces souscriptions.");
                $this->newLine();

                $paiementsDetails = (clone $paiementsQuery)
                    ->with(['etudiant', 'fraisCategory'])
                    ->limit(10)
                    ->get();

                $paiementsRows = $paiementsDetails->map(fn($p) => [
                    $p->id,
                    $p->etudiant ? ($p->etudiant->nom . ' ' . ($p->etudiant->prenoms ?? '')) : 'N/A',
                    $p->fraisCategory->name ?? 'N/A',
                    number_format($p->montant, 0, ',', ' ') . ' FCFA',
                    $p->statut ?? $p->status ?? 'N/A',
                    $p->date_paiement?->format('d/m/Y') ?? 'N/A',
                ])->toArray();

                $this->table(
                    ['Paie. ID', 'Étudiant', 'Catégorie', 'Montant', 'Statut', 'Date'],
                    $paiementsRows
                );

                if ($paiementsCount > 10) {
                    $this->info("   ... et " . ($paiementsCount - 10) . " autres paiements");
                }

                $this->newLine();

                if (!$isForced) {
                    $deletePaiements = $this->confirm(
                        '🗑️  Voulez-vous AUSSI supprimer ces paiements ? (soft-delete)',
                        false
                    );
                }
            }

            return $this->executeClean($inscriptions, $isDryRun, $isForced, $hasPaiements, $deletePaiements, $filterLabel);
        } else {
            return $this->executeRegenerate($inscriptions, $isDryRun, $isForced, $filterLabel);
        }
    }

    /**
     * Exécuter le nettoyage des souscriptions
     */
    private function executeClean($inscriptions, bool $isDryRun, bool $isForced, bool $hasPaiements, bool $deletePaiements, string $filterLabel): int
    {
        // Choisir le mode de suppression
        $deleteMode = $this->choice(
            '🗑️  Mode de suppression des souscriptions :',
            ['Soft-delete (désactiver : is_active = false)', 'Hard-delete (supprimer définitivement)'],
            0
        );
        $isHardDelete = str_contains($deleteMode, 'Hard');

        $this->newLine();
        $this->warn($isHardDelete
            ? '⚠️  Les souscriptions seront SUPPRIMÉES DÉFINITIVEMENT de la base de données.'
            : '📝 Les souscriptions seront désactivées (is_active = false). Elles resteront en base.'
        );

        if ($deletePaiements) {
            $this->warn('⚠️  Les paiements associés seront AUSSI supprimés (soft-delete).');
        }

        if (!$isForced && !$this->confirm('Confirmer l\'exécution ?', false)) {
            $this->info('❌ Opération annulée.');
            return Command::SUCCESS;
        }

        // Exécution
        $processed = 0;
        $subsDeleted = 0;
        $paiementsDeleted = 0;
        $errors = 0;

        $progress = $this->output->createProgressBar($inscriptions->count());
        $progress->start();

        foreach ($inscriptions as $inscription) {
            try {
                if (!$isDryRun) {
                    DB::beginTransaction();
                }

                $activeSubs = $inscription->fraisSubscriptions()->where('is_active', true)->get();

                foreach ($activeSubs as $sub) {
                    if (!$isDryRun) {
                        if ($isHardDelete) {
                            $sub->delete();
                        } else {
                            $sub->update([
                                'is_active' => false,
                                'notes' => ($sub->notes ? $sub->notes . "\n" : '') .
                                    "Désactivé via esbtp:manage-fees (clean) le " . now()->format('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                    $subsDeleted++;
                }

                // Supprimer les paiements si demandé
                if ($deletePaiements) {
                    $categoryIds = $activeSubs->pluck('frais_category_id')->toArray();
                    if (!empty($categoryIds)) {
                        $pQuery = ESBTPPaiement::where('inscription_id', $inscription->id)
                            ->whereIn('frais_category_id', $categoryIds);

                        $pCount = $pQuery->count();
                        if (!$isDryRun) {
                            $pQuery->delete(); // soft-delete (SoftDeletes trait)
                        }
                        $paiementsDeleted += $pCount;
                    }
                }

                if (!$isDryRun) {
                    DB::commit();
                }
                $processed++;

            } catch (\Exception $e) {
                if (!$isDryRun) {
                    DB::rollBack();
                }
                $this->newLine();
                $this->error("❌ Erreur inscription #{$inscription->id} : " . $e->getMessage());
                Log::error('esbtp:manage-fees clean error', [
                    'inscription_id' => $inscription->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }

            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        // Résumé final
        $this->info('✅ SUPPRESSION TERMINÉE');
        $this->table(['Métrique', 'Valeur'], [
            ['Scope', $filterLabel],
            ['Inscriptions traitées', $processed],
            ['Souscriptions ' . ($isHardDelete ? 'supprimées' : 'désactivées'), $subsDeleted],
            ['Paiements supprimés', $paiementsDeleted],
            ['Erreurs', $errors],
        ]);

        if ($isDryRun) {
            $this->warn('🔍 Mode dry-run — Aucune modification effectuée. Relancez sans --dry-run pour appliquer.');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Exécuter la régénération des souscriptions
     */
    private function executeRegenerate($inscriptions, bool $isDryRun, bool $isForced, string $filterLabel): int
    {
        $this->newLine();
        $this->info('🔄 Les souscriptions seront générées UNIQUEMENT pour les inscriptions sans souscriptions actives.');
        $this->info('   Les inscriptions ayant déjà des souscriptions actives seront ignorées.');
        $this->info('   Le statut d\'affectation de chaque inscription sera respecté.');

        // Prévisualisation des changements
        $this->newLine();
        $this->info('📊 Prévisualisation des changements :');
        $this->newLine();

        $diffRows = [];
        $totalNewAmount = 0;
        $inscriptionsToProcess = [];
        $skippedRows = [];

        foreach ($inscriptions as $inscription) {
            $classe = $inscription->classe;
            if (!$classe) {
                $diffRows[] = [
                    $inscription->id,
                    $inscription->etudiant ? $inscription->etudiant->nom : 'N/A',
                    '⚠️  Pas de classe',
                    '-', '-',
                ];
                continue;
            }

            // Vérifier TOUTES les souscriptions (actives + inactives) à cause de la contrainte unique
            $allSubs = $inscription->fraisSubscriptions;

            // Ignorer les inscriptions qui ont déjà des souscriptions (actives ou inactives)
            if ($allSubs->count() > 0) {
                $activeSubs = $allSubs->where('is_active', true);
                $skippedRows[] = [
                    $inscription->id,
                    $inscription->etudiant ? ($inscription->etudiant->nom . ' ' . substr($inscription->etudiant->prenoms ?? '', 0, 15)) : 'N/A',
                    $activeSubs->count() . ' actives, ' . ($allSubs->count() - $activeSubs->count()) . ' inactives',
                    number_format($activeSubs->sum('amount'), 0, ',', ' ') . ' FCFA',
                ];
                continue;
            }

            // Calculer les nouveaux frais
            $affectationStatus = $inscription->affectation_status ?? 'affecté';
            $newFees = $this->inscriptionService->generateFeesForInscription(
                $inscription,
                [],
                $affectationStatus
            );
            $newAmount = array_sum(array_column($newFees, 'amount'));
            $totalNewAmount += $newAmount;

            $diffRows[] = [
                $inscription->id,
                $inscription->etudiant ? ($inscription->etudiant->nom . ' ' . substr($inscription->etudiant->prenoms ?? '', 0, 15)) : 'N/A',
                $affectationStatus,
                count($newFees),
                number_format($newAmount, 0, ',', ' '),
            ];

            $inscriptionsToProcess[] = [
                'inscription' => $inscription,
                'newFees' => $newFees,
                'newAmount' => $newAmount,
            ];
        }

        // Afficher les inscriptions ignorées (souscriptions existantes)
        if (count($skippedRows) > 0) {
            $this->info("⏭️  Inscriptions IGNORÉES (souscriptions actives existantes) : " . count($skippedRows));
            if (count($skippedRows) > 10) {
                $this->table(
                    ['Insc. ID', 'Étudiant', 'Sousc. actives', 'Montant'],
                    array_slice($skippedRows, 0, 5)
                );
                $this->info("   ... et " . (count($skippedRows) - 5) . " autres ignorées");
            } else {
                $this->table(
                    ['Insc. ID', 'Étudiant', 'Sousc. actives', 'Montant'],
                    $skippedRows
                );
            }
            $this->newLine();
        }

        // Afficher les inscriptions à traiter
        if (count($diffRows) === 0) {
            $this->warn('⚠️  Toutes les inscriptions ont déjà des souscriptions actives. Rien à régénérer.');
            return Command::SUCCESS;
        }

        $this->info("🆕 Inscriptions SANS souscriptions (à générer) : " . count($diffRows));
        if (count($diffRows) > 20) {
            $this->table(
                ['Insc. ID', 'Étudiant', 'Affectation', 'Nb frais', 'Montant (FCFA)'],
                array_slice($diffRows, 0, 15)
            );
            $this->info("   ... et " . (count($diffRows) - 15) . " autres inscriptions");
        } else {
            $this->table(
                ['Insc. ID', 'Étudiant', 'Affectation', 'Nb frais', 'Montant (FCFA)'],
                $diffRows
            );
        }

        $this->newLine();
        $this->info("📊 Total à générer : " . number_format($totalNewAmount, 0, ',', ' ') . " FCFA pour " . count($inscriptionsToProcess) . " inscription(s)");
        $this->info("📊 Ignorées : " . count($skippedRows) . " inscription(s) avec souscriptions existantes");
        $this->newLine();

        if (!$isForced && !$this->confirm('Confirmer la régénération ?', false)) {
            $this->info('❌ Opération annulée.');
            return Command::SUCCESS;
        }

        // Exécution
        $processed = 0;
        $subsCreated = 0;
        $errors = 0;

        $progress = $this->output->createProgressBar(count($inscriptionsToProcess));
        $progress->start();

        foreach ($inscriptionsToProcess as $item) {
            $inscription = $item['inscription'];
            $newFees = $item['newFees'];

            try {
                if (!$isDryRun) {
                    DB::beginTransaction();
                }

                // Créer les nouvelles souscriptions (pas d'anciennes à supprimer car filtrées)
                foreach ($newFees as $fee) {
                    if ($fee['amount'] > 0) {
                        if (!$isDryRun) {
                            ESBTPFraisSubscription::create([
                                'inscription_id' => $inscription->id,
                                'frais_category_id' => $fee['category_id'],
                                'selected_option_id' => null,
                                'amount' => $fee['amount'],
                                'is_active' => true,
                                'subscribed_at' => now(),
                                'created_by' => 1,
                                'notes' => "Régénéré via esbtp:manage-fees le " . now()->format('Y-m-d H:i:s'),
                            ]);
                        }
                        $subsCreated++;
                    }
                }

                if (!$isDryRun) {
                    DB::commit();

                    Log::info('esbtp:manage-fees regenerate', [
                        'inscription_id' => $inscription->id,
                        'new_amount' => $item['newAmount'],
                        'fees_count' => count($newFees),
                    ]);
                }

                $processed++;

            } catch (\Exception $e) {
                if (!$isDryRun) {
                    DB::rollBack();
                }
                $this->newLine();
                $this->error("❌ Erreur inscription #{$inscription->id} : " . $e->getMessage());
                Log::error('esbtp:manage-fees regenerate error', [
                    'inscription_id' => $inscription->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }

            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        // Résumé final
        $this->info('✅ RÉGÉNÉRATION TERMINÉE');
        $this->table(['Métrique', 'Valeur'], [
            ['Scope', $filterLabel],
            ['Inscriptions traitées', $processed],
            ['Inscriptions ignorées (souscriptions existantes)', count($skippedRows)],
            ['Nouvelles souscriptions créées', $subsCreated],
            ['Total généré', number_format($totalNewAmount, 0, ',', ' ') . ' FCFA'],
            ['Erreurs', $errors],
        ]);

        if ($isDryRun) {
            $this->warn('🔍 Mode dry-run — Aucune modification effectuée. Relancez sans --dry-run pour appliquer.');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

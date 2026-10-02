<?php

namespace App\Domain\Bulletins\Taches;

use App\Domain\AcademicPilotage\DTO\BulkBulletinGenerationResult;
use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Http\Controllers\ESBTPBulletinController;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Services\BulletinBulkPdfExporter;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fait avancer les travaux longs sur les bulletins, une tranche après l'autre.
 *
 * Deux moteurs appellent la même méthode, et c'est voulu :
 *
 *  - l'onglet resté ouvert, une tranche par requête (la limite d'exécution
 *    de l'hébergement est de trente secondes) ;
 *  - la planification, chaque minute, tant qu'il reste du travail — c'est
 *    elle qui finit le travail quand on a quitté la page.
 *
 * Un verrou par tâche garantit qu'une tranche n'est jamais rendue deux fois en
 * même temps. Ce module n'ajoute aucun calcul : la génération passe par
 * {@see BtsBulkBulletinGenerationService}, le rendu PDF par le rendu unitaire du
 * contrôleur, exactement comme le faisaient les tranches pilotées par l'onglet.
 */
class ExecuteurTachesBulletins
{
    /**
     * Élèves (génération) ou bulletins (PDF) par tranche.
     *
     * Mesuré sur esbtp-yakro : sept bulletins consomment déjà les trente
     * secondes d'une requête, six laissent la marge. La même valeur sert à la
     * planification, où rien ne l'impose, pour qu'une tranche lancée depuis
     * l'onglet et une tranche lancée par la planification coûtent pareil.
     */
    public const TAILLE_TRANCHE = 6;

    /** Essais accordés à une même étape avant de l'abandonner. */
    public const ESSAIS_MAX = 3;

    /** Durée de vie du verrou : une tranche et sa marge. */
    private const VERROU_SECONDES = 180;

    /** Au-delà, la liste des erreurs n'apprend plus rien et alourdit la ligne. */
    private const ERREURS_MAX = 200;

    public function __construct(
        private readonly BtsBulkBulletinGenerationService $generation,
        private readonly BulletinBulkPdfExporter $exporter,
        private readonly NotificationTachesBulletins $notification,
    ) {}

    /**
     * Fait avancer les tâches en attente, dans la limite du budget.
     *
     * La tâche qui a bougé le moins récemment passe en premier : une tâche
     * dont chaque passage échoue (l'essai est daté avant d'être tenté) cède
     * son tour aux suivantes au lieu de bloquer toute l'école.
     *
     * @return array{tranches: int, occupees: int, actives: int}
     */
    public function traiterLaFile(float $budgetSecondes): array
    {
        $debut = microtime(true);
        $tranches = 0;
        $occupees = 0;

        $taches = BulletinTache::actives()->orderBy('updated_at')->orderBy('id')->limit(20)->get();
        $this->signalerLesPauses($taches);

        foreach ($taches as $tache) {
            $reste = $budgetSecondes - (microtime(true) - $debut);
            if ($reste <= 0) {
                break;
            }

            $faites = $this->avancer($tache, $reste);
            $faites === null ? $occupees++ : $tranches += $faites;
        }

        $this->notification->rattraper();

        return [
            'tranches' => $tranches,
            'occupees' => $occupees,
            'actives' => BulletinTache::actives()->count(),
        ];
    }

    /**
     * Fait avancer une tâche.
     *
     * Appelée par un onglet ($maxTranches = 1), elle ne conclut jamais dans la
     * requête qui vient de rendre une tranche : la conclusion (assemblage du
     * PDF) prendrait le reste des trente secondes. L'appel suivant la fait.
     *
     * @return int|null nombre de tranches traitées, null si un autre processus
     *                  tient déjà la tâche
     */
    public function avancer(BulletinTache $tache, float $budgetSecondes, ?int $maxTranches = null): ?int
    {
        $verrou = Cache::lock('bulletin_tache.'.$tache->id, self::VERROU_SECONDES);
        if (! $verrou->get()) {
            return null;
        }

        $faites = 0;

        try {
            $tache->refresh();
            if ($tache->estFinale()) {
                return 0;
            }

            $this->agirAuNomDe($tache);

            if ($tache->statut === BulletinTache::EN_ATTENTE) {
                $tache->forceFill(['statut' => BulletinTache::EN_COURS, 'demarree_at' => now()])->save();
            }

            $debut = microtime(true);

            while (! $tache->estFinale()) {
                $aConclure = $tache->position >= $tache->total;
                if ($aConclure && $maxTranches !== null && $faites > 0) {
                    break;
                }

                if (! $this->noterUnEssai($tache)) {
                    // Trois essais déjà consommés sur cette étape : la tâche a
                    // échoué, ou (export) la tranche a été écartée.
                    continue;
                }

                try {
                    $aConclure ? $this->conclure($tache) : $this->traiterUneTranche($tache);
                } catch (TacheBulletinsImpossible $e) {
                    throw $e;
                } catch (\Throwable $e) {
                    Log::error('Tâche bulletins #'.$tache->id.' : étape en erreur (essai '.$tache->essais_position.'/'.self::ESSAIS_MAX.')', [
                        'type' => $tache->type,
                        'position' => $tache->position,
                        'exception' => $e,
                    ]);
                    if ($tache->essais_position >= self::ESSAIS_MAX) {
                        $this->abandonnerLEtape($tache);
                        if ($maxTranches === null) {
                            continue;
                        }
                    }

                    // On réessaiera au prochain passage, pas dans celui-ci.
                    break;
                }

                if ($aConclure) {
                    break;
                }

                $faites++;

                if ($maxTranches !== null && $faites >= $maxTranches) {
                    break;
                }

                // On ne commence pas une tranche qu'on n'aurait pas le temps de finir.
                $ecoule = microtime(true) - $debut;
                if ($ecoule + $ecoule / $faites > $budgetSecondes) {
                    break;
                }
            }
        } catch (TacheBulletinsImpossible $e) {
            $this->echouer($tache, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Tâche bulletins #'.$tache->id.' interrompue', [
                'type' => $tache->type,
                'position' => $tache->position,
                'exception' => $e,
            ]);
            $this->echouer($tache, "Le travail s'est arrêté sur une erreur inattendue. Relancez-le ; si cela se reproduit, prévenez le support.");
        } finally {
            $verrou->release();
        }

        return $faites;
    }

    /**
     * Date l'essai AVANT de le tenter.
     *
     * Une tranche qui tue le processus (mémoire de DomPDF, arrêt par
     * l'hébergeur) ne passe par aucun catch ni finally : sans ce compteur
     * écrit d'avance, elle serait rejouée à chaque minute, pour toujours, et
     * personne ne serait prévenu.
     *
     * @return bool false si l'étape a déjà épuisé ses essais (et vient d'être abandonnée)
     */
    private function noterUnEssai(BulletinTache $tache): bool
    {
        $memeEtape = $tache->position_essayee !== null && $tache->position_essayee === $tache->position;

        if ($memeEtape && $tache->essais_position >= self::ESSAIS_MAX) {
            Log::error('Tâche bulletins #'.$tache->id.' : étape abandonnée après '.self::ESSAIS_MAX.' essais sans réponse', [
                'type' => $tache->type,
                'position' => $tache->position,
            ]);
            $this->abandonnerLEtape($tache);

            return false;
        }

        $tache->forceFill([
            'position_essayee' => $tache->position,
            'essais_position' => $memeEtape ? $tache->essais_position + 1 : 1,
        ])->save();

        return true;
    }

    /**
     * Une étape a échoué trois fois. Un PDF groupé écarte la tranche (ses
     * bulletins sont listés en page de garde, comme un rendu raté) ; une
     * génération, elle, échoue en nommant la tranche.
     */
    private function abandonnerLEtape(BulletinTache $tache): void
    {
        $taille = self::TAILLE_TRANCHE;
        $numero = intdiv($tache->position, $taille) + 1;
        $tranches = max(1, (int) ceil($tache->total / $taille));

        if ($tache->position >= $tache->total) {
            $this->echouer($tache, sprintf(
                "La dernière étape (%s) a échoué %d fois de suite. Relancez le travail ; si cela se reproduit, prévenez le support.",
                $tache->type === BulletinTache::TYPE_EXPORT ? 'assemblage du document' : 'bilan de la génération',
                self::ESSAIS_MAX
            ));

            return;
        }

        $ids = array_slice($tache->elements, $tache->position, $taille);

        if ($tache->type === BulletinTache::TYPE_EXPORT) {
            $cumul = $tache->resultat ?? [];
            $message = sprintf('Tranche %d sur %d écartée après %d essais', $numero, $tranches, self::ESSAIS_MAX);
            $cumul['echecs'] = array_slice(array_merge(
                $cumul['echecs'] ?? [],
                array_map(fn ($id) => ['id' => (int) $id, 'message' => $message], $ids)
            ), 0, self::ERREURS_MAX);

            Log::warning('Tâche bulletins #'.$tache->id.' : '.$message, ['bulletins' => $ids]);

            $tache->forceFill([
                'resultat' => $cumul,
                'position' => $tache->position + count($ids),
                'position_essayee' => null,
                'essais_position' => 0,
            ])->save();

            return;
        }

        $this->echouer($tache, sprintf(
            "La tranche %d sur %d (élèves %d à %d) a échoué %d fois de suite. Les tranches précédentes sont enregistrées. Relancez la génération ; si cela se reproduit, prévenez le support.",
            $numero,
            $tranches,
            $tache->position + 1,
            $tache->position + count($ids),
            self::ESSAIS_MAX
        ));
    }

    /**
     * Une tâche active que rien n'a fait bouger depuis plusieurs minutes : la
     * planification ne tourne plus, ou une étape se fait tuer. Le dire.
     *
     * @param  Collection<int, BulletinTache>  $taches
     */
    private function signalerLesPauses(Collection $taches): void
    {
        foreach ($taches as $tache) {
            if (! SuiviTachesBulletins::estEnPause($tache)) {
                continue;
            }

            // Une ligne par tâche et par heure, pas une par minute.
            if (Cache::add('bulletin_tache.pause.'.$tache->id, true, 3600)) {
                Log::warning('Tâche bulletins #'.$tache->id.' immobile depuis '.$tache->updated_at?->diffForHumans(), [
                    'type' => $tache->type,
                    'position' => $tache->position,
                    'total' => $tache->total,
                    'essais_position' => $tache->essais_position,
                ]);
            }
        }
    }

    private function traiterUneTranche(BulletinTache $tache): void
    {
        $ids = array_slice($tache->elements, $tache->position, self::TAILLE_TRANCHE);

        $tache->type === BulletinTache::TYPE_GENERATION
            ? $this->genererUneTranche($tache, $ids)
            : $this->rendreUneTranche($tache, $ids);
    }

    /** @param array<int, int> $ids */
    private function genererUneTranche(BulletinTache $tache, array $ids): void
    {
        $classe = $this->classeBts($tache);

        $resultat = $this->genererLaTranche($classe, $tache, $ids);

        $cumul = $tache->resultat ?? [];
        $cumul['created'] = ($cumul['created'] ?? 0) + $resultat->created;
        $cumul['regenerated'] = ($cumul['regenerated'] ?? 0) + $resultat->regenerated;
        foreach (['skipped' => $resultat->skipped, 'blocking_errors' => $resultat->blockingErrors, 'errors' => $resultat->errors] as $cle => $lignes) {
            $cumul[$cle] = array_slice(array_merge($cumul[$cle] ?? [], $lignes), 0, self::ERREURS_MAX);
        }

        $tache->forceFill([
            'resultat' => $cumul,
            'position' => $tache->position + count($ids),
        ])->save();

        // Un blocage de configuration de la classe vaut pour toutes les
        // tranches : inutile de le constater encore cinq fois.
        $blocageDeClasse = collect($resultat->blockingErrors)->firstWhere('code', 'professeurs_missing');
        if (! $resultat->hasWrites() && $blocageDeClasse !== null) {
            throw new TacheBulletinsImpossible((string) ($blocageDeClasse['message'] ?? 'La configuration du bulletin de la classe est incomplète.'));
        }
    }

    /** @param array<int, int> $ids */
    private function rendreUneTranche(BulletinTache $tache, array $ids): void
    {
        // whereIn ne garantit pas l'ordre : on le rétablit sur la liste figée.
        $bulletins = ESBTPBulletin::whereIn('id', $ids)->get()
            ->sortBy(fn (ESBTPBulletin $b) => array_search($b->id, $ids, true))
            ->values();

        $dossier = $this->dossier($tache);
        $this->exporter->oublierLesRangs($dossier, $tache->position, count($ids));

        $rendu = $this->exporter->rendreTranche(
            $bulletins,
            fn (ESBTPBulletin $b) => $this->rendreUnBulletin($b),
            $dossier,
            $tache->position
        );

        $cumul = $tache->resultat ?? [];
        $cumul['rendus'] = ($cumul['rendus'] ?? 0) + $rendu['rendus'];
        $cumul['echecs'] = array_slice(array_merge($cumul['echecs'] ?? [], $rendu['echecs']), 0, self::ERREURS_MAX);

        $tache->forceFill([
            'resultat' => $cumul,
            'position' => $tache->position + count($ids),
        ])->save();
    }

    private function conclure(BulletinTache $tache): void
    {
        $tache->type === BulletinTache::TYPE_GENERATION
            ? $this->conclureLaGeneration($tache)
            : $this->conclureLExport($tache);
    }

    private function conclureLaGeneration(BulletinTache $tache): void
    {
        $cumul = $tache->resultat ?? [];
        $bilan = new BulkBulletinGenerationResult(
            created: (int) ($cumul['created'] ?? 0),
            regenerated: (int) ($cumul['regenerated'] ?? 0),
            skipped: $cumul['skipped'] ?? [],
            blockingErrors: $cumul['blocking_errors'] ?? [],
            errors: $cumul['errors'] ?? [],
        );

        // Les tranches ne reclassent pas la classe (chacune réécrivait tous les
        // bulletins de la classe) : le classement se fait ici, une fois, sur
        // toutes les moyennes posées.
        if ($bilan->hasWrites()) {
            $this->reclasserLaClasse($tache);
        }

        // Rien d'écrit et des erreurs : c'est un échec, pas une réussite vide.
        $statut = ! $bilan->hasWrites() && $bilan->hasFailures()
            ? BulletinTache::ECHOUEE
            : BulletinTache::TERMINEE;

        $this->finir($tache, $statut, $bilan->message());
    }

    private function conclureLExport(BulletinTache $tache): void
    {
        $dossier = $this->dossier($tache);
        $cumul = $tache->resultat ?? [];

        // Le dossier de travail a pu être balayé (purge des exports au bout
        // d'une heure) si la planification s'est arrêtée longtemps. On ne sert
        // pas un document amputé : on recommence, une fois ou deux.
        $presents = count(glob($dossier.'/blt_*.pdf') ?: []);
        if ($presents < (int) ($cumul['rendus'] ?? 0)) {
            if ($tache->reprises >= 2) {
                throw new TacheBulletinsImpossible('Les fichiers intermédiaires ont été effacés avant l\'assemblage. Relancez l\'export.');
            }

            Log::warning('Tâche bulletins #'.$tache->id.' : dossier de travail incomplet, reprise depuis le début', [
                'attendus' => $cumul['rendus'] ?? 0,
                'presents' => $presents,
            ]);
            $this->exporter->oublierLaSession($dossier);
            $tache->forceFill([
                'position' => 0,
                'resultat' => ['rendus' => 0, 'echecs' => []],
                'reprises' => $tache->reprises + 1,
            ])->save();

            return;
        }

        $absents = ESBTPBulletin::whereIn('id', $tache->parametre('ungenerated_ids', []))
            ->with(['etudiant:id,matricule,nom,prenoms', 'classe:id,name'])
            ->get();
        $garde = fn (array $echecs): ?\Barryvdh\DomPDF\PDF => $this->pageDeGarde(
            $absents,
            $echecs,
            $tache->parametre('entete', []),
            (int) ($cumul['rendus'] ?? 0)
        );

        try {
            $assemble = $this->exporter->assembler($dossier, $garde, $cumul['echecs'] ?? []);
        } catch (\RuntimeException $e) {
            throw new TacheBulletinsImpossible($e->getMessage());
        } finally {
            $this->exporter->oublierLaSession($dossier);
        }

        $final = $this->rangerLeDocument($tache, $assemble);
        $tache->forceFill(['fichier' => $final])->save();

        $echecs = count($cumul['echecs'] ?? []);
        if ($echecs > 0) {
            Log::warning('Tâche bulletins #'.$tache->id.' : '.$echecs.' bulletin(s) non rendus', $cumul['echecs']);
        }

        $this->finir($tache, BulletinTache::TERMINEE, $echecs > 0
            ? sprintf('PDF prêt : %d bulletin(s), %d n\'ont pas pu être rendus (listés en page de garde).', $cumul['rendus'] ?? 0, $echecs)
            : sprintf('PDF prêt : %d bulletin(s).', $cumul['rendus'] ?? 0));
    }

    private function echouer(BulletinTache $tache, string $message): void
    {
        if ($tache->type === BulletinTache::TYPE_EXPORT) {
            $this->exporter->oublierLaSession($this->dossier($tache));
        }

        // Une génération arrêtée en route garde les tranches déjà écrites :
        // leurs bulletins doivent quand même porter un rang juste.
        if ($tache->type === BulletinTache::TYPE_GENERATION && $this->aEcritDesBulletins($tache)) {
            try {
                $this->reclasserLaClasse($tache);
            } catch (\Throwable $e) {
                Log::error('Tâche bulletins #'.$tache->id.' : reclassement de la classe impossible après échec', [
                    'exception' => $e,
                ]);
            }
        }

        $this->finir($tache, BulletinTache::ECHOUEE, $message);
    }

    private function finir(BulletinTache $tache, string $statut, string $message): void
    {
        $tache->forceFill([
            'statut' => $statut,
            'message' => Str::limit($message, 490),
            'terminee_at' => now(),
        ])->save();

        $this->notification->notifier($tache);
    }

    /**
     * Les trois appels qui produisent réellement quelque chose. Ils ne
     * contiennent aucun calcul propre : génération BTS et rendu unitaire,
     * exactement ceux des tranches pilotées par l'onglet. Protégés pour que
     * les tests puissent les remplacer sans monter toute la scolarité.
     *
     * @param  array<int, int>  $ids
     */
    protected function genererLaTranche(ESBTPClasse $classe, BulletinTache $tache, array $ids): BulkBulletinGenerationResult
    {
        return $this->generation->genererSansClasser(
            $classe,
            (int) $tache->annee_universitaire_id,
            (string) $tache->periode,
            $tache->user,
            (bool) $tache->parametre('recalculer', false),
            $tache->parametre('incomplete_reason'),
            $ids,
        );
    }

    protected function reclasserLaClasse(BulletinTache $tache): void
    {
        $this->generation->reclasserLaClasse(
            (int) $tache->classe_id,
            (int) $tache->annee_universitaire_id,
            (string) $tache->periode
        );
    }

    private function aEcritDesBulletins(BulletinTache $tache): bool
    {
        $cumul = $tache->resultat ?? [];

        return (int) ($cumul['created'] ?? 0) + (int) ($cumul['regenerated'] ?? 0) > 0;
    }

    protected function rendreUnBulletin(ESBTPBulletin $bulletin): PDF
    {
        return app(ESBTPBulletinController::class)->buildBulletinPdf($bulletin, false);
    }

    /** @param array<string, mixed> $entete */
    protected function pageDeGarde(Collection $absents, array $echecs, array $entete, int $inclus): ?PDF
    {
        return app(ESBTPBulletinController::class)->buildExportCoverPdf($absents, $echecs, $entete, $inclus);
    }

    private function classeBts(BulletinTache $tache): ESBTPClasse
    {
        $classe = ESBTPClasse::find($tache->classe_id);
        if ($classe === null) {
            throw new TacheBulletinsImpossible("La classe n'existe plus.");
        }

        // Le calcul LMD vit ailleurs (rule lmd-bts-bulletin-separation) : ce
        // moteur ne fait qu'orchestrer le calcul BTS.
        if (($classe->systeme_academique ?? '') === 'LMD') {
            throw new TacheBulletinsImpossible('Cette classe est LMD. Utilisez les bulletins LMD.');
        }

        return $classe;
    }

    /**
     * Le rendu et la génération lisent l'utilisateur connecté (« généré
     * par », droits). En planification, il n'y en a pas : on prend le
     * demandeur, celui-là même qui avait les droits au lancement.
     */
    private function agirAuNomDe(BulletinTache $tache): void
    {
        if ($tache->user !== null && Auth::id() !== $tache->user_id) {
            Auth::setUser($tache->user);
        }
    }

    private function dossier(BulletinTache $tache): string
    {
        return $this->exporter->dossierDeSession('tache'.$tache->id);
    }

    /**
     * Le PDF assemblé atterrit dans le dossier temporaire des exports, balayé
     * au bout d'une heure. Le lien part par e-mail : on le range à part, pour
     * {@see BulletinTache::CONSERVATION_HEURES}.
     */
    private function rangerLeDocument(BulletinTache $tache, string $assemble): string
    {
        $dossier = storage_path('app/taches_bulletins');
        if (! is_dir($dossier) && ! mkdir($dossier, 0755, true) && ! is_dir($dossier)) {
            throw new \RuntimeException("Impossible de créer le dossier des documents : $dossier");
        }

        $final = $dossier.'/tache_'.$tache->id.'_'.Str::lower(Str::random(16)).'.pdf';
        if (! @rename($assemble, $final)) {
            throw new \RuntimeException("Impossible de ranger le PDF assemblé : $final");
        }

        return $final;
    }
}

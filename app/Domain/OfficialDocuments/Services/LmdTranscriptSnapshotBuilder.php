<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDResultatUE;
use App\Models\User;
use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMDBulletinService;
use Carbon\CarbonInterface;

/**
 * Instantane d'un releve de notes LMD.
 *
 * Meme contrat que JuryPvSnapshotBuilder : le tableau produit est canonicalise
 * (cles triees) puis hache, et c'est lui — pas la base — qui fait foi ensuite.
 * Le rendu PDF ne lit que cet instantane, jamais les modeles.
 */
class LmdTranscriptSnapshotBuilder
{
    /**
     * Version du jeu de regles academiques grave dans le releve.
     *
     * v1 : n'inscrit que les regles qui gouvernent reellement l'acquisition d'une
     * UE et le total de credits. Les ponderations controle continu / examen
     * terminal en sont absentes pour la meme raison que dans le PV : elles
     * n'entrent aujourd'hui dans aucun calcul.
     */
    public const RULES_VERSION = 'lmd-transcript-profile-v1';

    /**
     * Les deux mises en page du releve, et le reglage qui tranche.
     *
     * `klassci` est celle qui existait : bandeau de l'etablissement, couleurs de
     * l'ecole, code de verification. `mesrs` reprend le modele officiel du
     * Ministere de l'Enseignement Superieur — en-tete a deux colonnes, emblemes,
     * mention et decision par UE, semestres en marge.
     *
     * Le choix est un REGLAGE, pas un remplacement : trois ecoles LMD impriment
     * deja le premier, et changer leur papier sans qu'elles l'aient demande
     * serait une regression.
     */
    public const MODELE_KLASSCI = 'lmd-releve-notes-v1';
    public const MODELE_MESRS = 'lmd-releve-notes-mesrs-v1';
    public const REGLAGE_MODELE = 'lmd_releve_modele';

    /** Textes du gabarit officiel d'origine, et de tout instantane qui ne porte pas `authority`. */
    public const REPUBLIQUE_PAR_DEFAUT = "République de Côte d'Ivoire";
    public const DEVISE_PAR_DEFAUT = 'Union – Discipline – Travail';
    public const MINISTERE_PAR_DEFAUT = "Ministère de l'Enseignement Supérieur\net de la Recherche Scientifique";

    public function __construct(
        private readonly LmdAcademicRuleProfile $profile,
        private readonly LMDBulletinService $bulletins,
    ) {}

    /**
     * @param array{student: mixed, year: mixed, bulletins: \Illuminate\Support\Collection} $state
     */
    public function build(array $state, array $identity, User $actor, CarbonInterface $issuedAt): array
    {
        $bulletins = $state['bulletins'];
        $semesters = $bulletins->map(fn ($bulletin) => $this->semesterData($bulletin))->values()->all();

        $snapshot = [
            'schema' => 'lmd-transcript-snapshot-v3',
            'document' => [
                'reference' => $identity['reference'],
                'version' => $identity['version'],
                'number' => $identity['number'],
            ],
            'institution' => SettingsHelper::getSchoolInfo(),
            'authority' => $this->authority(),
            'student' => $this->studentData($state['student'], $state['year']),
            'scope' => $this->scopeData($state['year'], $bulletins->first()),
            'semesters' => $semesters,
            'totals' => $this->totals($semesters),
            'rules' => $this->rules(),
            'issuance' => [
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'issued_at' => $issuedAt->toIso8601String(),
                'template_version' => $this->modele(),
                'renderer_version' => 'dompdf-v2',
            ],
        ];

        return $this->canonicalize($snapshot);
    }

    public function canonicalJson(array $snapshot): string
    {
        return json_encode(
            $this->canonicalize($snapshot),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * L'Etat au nom duquel le releve est delivre : republique, devise, ministere.
     *
     * Le modele officiel les ecrivait en dur (Cote d'Ivoire). Un releve delivre
     * au Benin serait sorti au nom d'un autre Etat, et un releve signe ne se
     * corrige pas. Ils sont lus dans les reglages que l'ecole renseigne deja pour
     * ses bulletins LMD, et GELES ici comme le reste. A defaut de reglage, les
     * textes du gabarit d'origine : rien ne change pour une ecole ivoirienne.
     */
    private function authority(): array
    {
        return [
            'republic' => SettingsHelper::get('lmd_bulletin_republic_text', self::REPUBLIQUE_PAR_DEFAUT),
            'motto' => SettingsHelper::get('lmd_bulletin_union_text', self::DEVISE_PAR_DEFAUT),
            'ministry' => SettingsHelper::get('lmd_bulletin_ministry_text', self::MINISTERE_PAR_DEFAUT),
        ];
    }

    private function studentData($student, $year = null): array
    {
        return [
            'id' => $student->id,
            'matricule' => $student->matricule,
            'last_name' => $student->nom,
            'first_names' => $student->prenoms,
            'birth_date' => $student->date_naissance
                ? \Carbon\Carbon::parse($student->date_naissance)->toDateString()
                : null,
            'birth_place' => $student->lieu_naissance ?: $student->ville_naissance,
            // Le sexe est GELE ici, comme le reste. Ce n'est pas une coquetterie :
            // le modele officiel imprime « Genre » et accorde sa decision —
            // « Admise » — et un releve reedite doit ressortir identique meme si
            // la fiche a ete corrigee depuis.
            'sexe' => $student->sexe,
            'is_redoublant' => $year
                ? (bool) ESBTPInscription::query()
                    ->where('etudiant_id', $student->id)
                    ->where('annee_universitaire_id', $year->id)
                    ->value('is_redoublant')
                : null,
        ];
    }

    private function scopeData($year, $firstBulletin): array
    {
        return [
            'year' => [
                'id' => $year->id,
                'label' => $year->display_name,
            ],
            'level' => $firstBulletin?->niveau,
            'domain' => $firstBulletin?->domaine_label,
            'mention' => $firstBulletin?->mention_label,
            'parcours' => [
                'id' => $firstBulletin?->parcours_id,
                'label' => $firstBulletin?->parcours_label ?? $firstBulletin?->parcours?->name,
                'code' => $firstBulletin?->parcours?->code,
            ],
            // Le nom des rangs (Domaine, ou Composante...) et la nature du
            // premier (UFR, Ecole...) sont des reglages : geles ici, un releve
            // reedite ne change pas de vocabulaire si l'ecole change le sien.
            'vocabulary' => app(\App\Services\LMD\VocabulaireStructure::class)->tous(),
            'domain_nature' => $firstBulletin?->parcours?->mention?->domaine?->nature?->label(),
        ];
    }

    private function semesterData($bulletin): array
    {
        $units = $bulletin->resultatsUEs
            ->map(fn ($resultat) => $this->unitData($resultat))
            ->sortBy('code')
            ->values()
            ->all();

        return [
            'semester' => (int) $bulletin->semestre,
            'class' => $bulletin->classe?->name,
            'average' => $this->decimal($bulletin->moyenne_generale),
            'mention' => $bulletin->mention_generale,
            'credits_earned' => (int) $bulletin->credits_capitalises,
            'credits_expected' => (int) $bulletin->credits_totaux,
            'rank' => $bulletin->rang !== null ? (int) $bulletin->rang : null,
            'headcount' => $bulletin->effectif !== null ? (int) $bulletin->effectif : null,
            'decision' => $bulletin->decision_deliberation,
            'units' => $units,
        ];
    }

    private function unitData(ESBTPLMDResultatUE $resultat): array
    {
        // La note portee par l'element constitutif doit etre celle que l'unite a
        // agregee : la note effective, c'est-a-dire le resultat de seconde session
        // quand il y en a eu un. Lire `moyenne` ici afficherait un element a 07,00
        // dans une unite a 11,50 declaree acquise — un document officiel fige a
        // l'emission, conserve, et incoherent avec lui-meme pour toujours.
        $elements = $resultat->resultatsECUEs
            ->map(fn ($ecue) => [
                'code' => $ecue->matiere?->code,
                'name' => $ecue->matiere?->name,
                'credits' => (int) $ecue->credit,
                'average' => $this->decimal($this->bulletins->noteEffectiveECUE($ecue)),
                // Dire laquelle des deux notes le releve imprime.
                'session' => $ecue->note_finale !== null ? 'rattrapage' : 'normale',
            ])
            ->sortBy('code')
            ->values()
            ->all();

        $moyenne = $this->decimal($resultat->moyenne);
        $acquise = $resultat->isValidee();

        return [
            'code' => $resultat->uniteEnseignement?->code,
            'name' => $resultat->uniteEnseignement?->name,
            'credits' => (int) $resultat->credit,
            'average' => $moyenne,
            'status' => $resultat->statut,
            'status_label' => $this->statusLabel($resultat->statut),
            'acquired' => $acquise,
            // La mention et la decision par UE sont GELEES, pas calculees au
            // rendu. Les seuils de mention sont un reglage d'ecole : les lire au
            // moment d'imprimer ferait changer un document deja signe le jour ou
            // l'ecole les deplace.
            'mention' => $this->mentionLabel($moyenne),
            'decision' => $acquise ? 'admis' : 'ajourne',
            'elements' => $elements,
        ];
    }

    /**
     * Le modele choisi par l'ecole, grave dans l'instantane.
     *
     * C'est l'instantane qui decide du rendu, pas le reglage du jour : une ecole
     * qui bascule sur le modele officiel ne doit pas voir changer la mise en
     * page des releves qu'elle a deja emis et signes.
     */
    private function modele(): string
    {
        $choix = strtolower(trim((string) SettingsHelper::get(self::REGLAGE_MODELE, '')));

        return $choix === 'mesrs' ? self::MODELE_MESRS : self::MODELE_KLASSCI;
    }

    /**
     * La mention en clair, telle que le modele officiel l'imprime.
     *
     * Le profil rend un identifiant — « assez_bien » — parce que c'est ce qui se
     * compare. Le papier, lui, porte « Assez-bien ».
     */
    private function mentionLabel(?float $average): ?string
    {
        return match ($this->profile->mentionFor($average)) {
            'excellent' => 'Excellent',
            'tres_bien' => 'Très bien',
            'bien' => 'Bien',
            'assez_bien' => 'Assez-bien',
            'passable' => 'Passable',
            default => null,
        };
    }

    /**
     * Libelle en clair des trois statuts UEMOA.
     *
     * Le code brut (AQ / APC / NAQ) reste la donnee ; le libelle n'est la que
     * pour que le releve soit lisible par un etablissement qui ne connait pas
     * nos abreviations.
     */
    private function statusLabel(?string $status): ?string
    {
        return match ($status) {
            ESBTPLMDResultatUE::STATUT_AQ => 'Acquise',
            ESBTPLMDResultatUE::STATUT_APC => 'Acquise par compensation',
            ESBTPLMDResultatUE::STATUT_NAQ => 'Non acquise',
            default => $status,
        };
    }

    /**
     * Cumul annuel.
     *
     * La moyenne annuelle est ponderee par les credits attendus de chaque
     * semestre. Un semestre a 30 credits ne peut pas peser autant qu'un semestre
     * a 12. Si aucun semestre n'annonce de credits attendus, on retombe sur la
     * moyenne arithmetique — et le document dit laquelle des deux il montre,
     * pour qu'aucun lecteur n'ait a le deviner.
     */
    private function totals(array $semesters): array
    {
        $earned = 0;
        $expected = 0;
        $weighted = 0.0;
        $weights = 0;
        $plainSum = 0.0;
        $plainCount = 0;

        foreach ($semesters as $semester) {
            $earned += (int) $semester['credits_earned'];
            $expected += (int) $semester['credits_expected'];

            if ($semester['average'] === null) {
                continue;
            }

            $plainSum += (float) $semester['average'];
            $plainCount++;

            $weight = (int) $semester['credits_expected'];
            if ($weight > 0) {
                $weighted += ((float) $semester['average']) * $weight;
                $weights += $weight;
            }
        }

        if ($weights > 0) {
            $average = round($weighted / $weights, 2);
            $method = 'weighted_by_expected_credits';
        } elseif ($plainCount > 0) {
            $average = round($plainSum / $plainCount, 2);
            $method = 'arithmetic_mean';
        } else {
            $average = null;
            $method = 'unavailable';
        }

        return [
            'credits_earned' => $earned,
            'credits_expected' => $expected,
            'average' => $average,
            'average_method' => $method,
            'mention' => $average !== null
                ? app(\App\Services\AppreciationScaleService::class)->labelFor($average, 'lmd', '')
                : null,
        ];
    }

    private function rules(): array
    {
        return [
            'profile_version' => self::RULES_VERSION,
            'validation_threshold' => $this->profile->validationThreshold(),
            'eliminatory_grade' => $this->profile->eliminatoryGrade(),
            'inter_ue_compensation' => $this->profile->interUeCompensationEnabled(),
            'intra_ue_compensation' => $this->profile->intraUeCompensationEnabled(),
            'mention_thresholds' => $this->profile->mentionThresholds(),
            'expected_credits_per_semester' => $this->profile->expectedCreditsPerSemester(),
        ];
    }

    private function decimal($value): ?float
    {
        return $value === null ? null : round((float) $value, 2);
    }

    private function canonicalize(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }
}

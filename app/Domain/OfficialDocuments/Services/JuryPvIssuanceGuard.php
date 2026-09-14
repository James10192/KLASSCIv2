<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\OfficialDocuments\Exceptions\JuryPvNotIssuableException;
use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\User;
use App\Services\LMD\AgregatDeLaPeriode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JuryPvIssuanceGuard
{
    public function assertIssuable(int $juryId, bool $rectification = false): array
    {
        $jury = ESBTPLMDJury::query()->lockForUpdate()->findOrFail($juryId);
        $this->assertJuryStatus($jury, $rectification);
        $this->lockScopeLabels($jury);

        $members = $jury->membres()->orderBy('id')->lockForUpdate()->get();
        $decisions = $jury->decisions()->orderBy('etudiant_id')->lockForUpdate()->get();
        $bulletins = ESBTPLMDBulletin::query()->forJury($jury)->orderBy('etudiant_id')->lockForUpdate()->get();
        $sheets = $this->gradeSheetQuery($jury)->orderBy('id')->lockForUpdate()->get();
        $this->lockIdentities($members, $decisions);

        $reasons = array_merge(
            $this->quorumReasons($members),
            $this->signatureReasons($members),
            $this->cohortReasons($bulletins, $decisions),
            $this->concordanceReasons($bulletins, $decisions),
            $this->gradeSheetReasons($sheets),
        );

        if ($reasons !== []) {
            throw new JuryPvNotIssuableException($reasons);
        }

        $jury->setRelation('membres', $members->load('user'));
        $jury->setRelation('decisions', $decisions->load('etudiant'));

        return compact('jury', 'members', 'decisions', 'bulletins', 'sheets');
    }

    public function readiness(ESBTPLMDJury $jury): array
    {
        try {
            $state = DB::transaction(fn (): array => $this->assertIssuable($jury->id));

            return ['ok' => true, 'grade_sheets_count' => $state['sheets']->count(), 'reasons' => []];
        } catch (JuryPvNotIssuableException $exception) {
            return ['ok' => false, 'grade_sheets_count' => 0, 'reasons' => $exception->reasons];
        } catch (\LogicException $exception) {
            return ['ok' => false, 'grade_sheets_count' => 0, 'reasons' => [$exception->getMessage()]];
        }
    }

    private function assertJuryStatus(ESBTPLMDJury $jury, bool $rectification): void
    {
        $hasPriorDocument = OfficialDocument::query()
            ->where('document_type', OfficialDocument::TYPE_LMD_JURY_PV)
            ->where('source_type', ESBTPLMDJury::class)
            ->where('source_id', $jury->id)
            ->exists();

        if (! $hasPriorDocument && $jury->status !== 'en_cours') {
            throw new \LogicException('La première émission du PV exige un jury en cours.');
        }

        if ($hasPriorDocument && in_array($jury->status, ['publie', 'archive'], true) && ! $rectification) {
            throw new \LogicException('Une rectification atomique est requise pour ce jury publié ou archivé.');
        }
    }

    private function gradeSheetQuery(ESBTPLMDJury $jury)
    {
        return GradeSheet::query()->where('academic_system', 'LMD')
            ->where('annee_universitaire_id', $jury->annee_universitaire_id)
            ->where('status', '!=', GradeSheetStatus::CANCELLED->value)
            ->when($jury->classe_id, fn ($query, $id) => $query->where('classe_id', $id))
            ->when($jury->semestre, fn ($query, $semester) => $query->where('semester', $semester))
            ->when(
                ! $jury->classe_id && $jury->parcours_id,
                fn ($query) => $query->whereHas(
                    'classe',
                    fn ($classes) => $classes->where('parcours_id', $jury->parcours_id),
                ),
            );
    }

    private function lockIdentities(Collection $members, Collection $decisions): void
    {
        User::query()->whereIn('id', $members->pluck('user_id'))->lockForUpdate()->get();
        ESBTPEtudiant::query()->whereIn('id', $decisions->pluck('etudiant_id'))->lockForUpdate()->get();
    }

    private function lockScopeLabels(ESBTPLMDJury $jury): void
    {
        $relations = [
            'anneeUniversitaire' => ['esbtp_annee_universitaires', $jury->annee_universitaire_id],
            'session' => ['esbtp_lmd_sessions', $jury->session_id],
            'parcours' => ['esbtp_lmd_parcours', $jury->parcours_id],
            'classe' => ['esbtp_classes', $jury->classe_id],
        ];

        foreach ($relations as $name => [$table, $id]) {
            $value = $id ? DB::table($table)->where('id', $id)->lockForUpdate()->first() : null;
            $jury->setRelation($name, $value);
        }
    }

    private function quorumReasons(Collection $members): array
    {
        $present = $members->where('present', true);
        $reasons = [];

        if ($present->count() < (int) SettingsHelper::get('lmd_jury_quorum_min', 2)) {
            $reasons[] = 'Le quorum minimal n’est pas atteint.';
        }
        if (! $present->contains('role', 'president')) {
            $reasons[] = 'Le président du jury doit être présent.';
        }
        if ($present->where('role', 'assesseur')->count() < (int) SettingsHelper::get('lmd_jury_quorum_assesseurs_min', 1)) {
            $reasons[] = 'Le nombre minimal d’assesseurs présents n’est pas atteint.';
        }

        return $reasons;
    }

    private function signatureReasons(Collection $members): array
    {
        if (! filter_var(SettingsHelper::get('lmd_jury_signature_required', true), FILTER_VALIDATE_BOOL)) {
            return [];
        }

        return $members->where('present', true)->contains(fn ($member) => ! $member->hasSigned())
            ? ['Tous les membres présents doivent signer avant l’émission du PV.']
            : [];
    }

    private function cohortReasons(Collection $bulletins, Collection $decisions): array
    {
        if ($bulletins->isEmpty()) {
            return ['La cohorte du jury est vide.'];
        }

        $cohortIds = $bulletins->pluck('etudiant_id')->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $decisionIds = $decisions->pluck('etudiant_id')->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $reasons = $cohortIds->all() === $decisionIds->all()
            ? []
            : ['Chaque étudiant de la cohorte doit avoir exactement une décision.'];
        $bulletinIds = $bulletins->pluck('id')->map(fn ($id) => (int) $id);

        if ($decisions->contains(fn ($decision) => ! $decision->decision || ! $bulletinIds->contains((int) $decision->bulletin_id))) {
            $reasons[] = 'Chaque décision doit être complète et liée à un bulletin du périmètre.';
        }

        return $reasons;
    }

    /**
     * La délibération dit-elle encore ce que disent les bulletins ?
     *
     * Une décision est GELÉE : sa moyenne et ses crédits sont recopiés au
     * moment du calcul, puis verrouillés à l'émission du PV — et jamais
     * déverrouillés. Le parcours réel d'une réclamation aboutie le montre : la
     * feuille est rouverte, la note passe de 08 à 14, le bulletin recalcule,
     * l'étudiant devient admis à 11,2 — et la décision reste « ajourné, 9,4 ».
     * Le PV rectificatif certifiait alors 9,4 pendant que le relevé réémis
     * affichait 11,2 : deux documents officiels signés, tous deux vérifiables
     * par code public, qui se contredisent sur le même étudiant.
     *
     * Le contrôle d'intégrité ne pouvait pas le voir : il vérifie l'empreinte
     * de l'instantané par elle-même, jamais sa concordance avec la source.
     *
     * On refuse donc l'émission plutôt que de sceller un écart. Le message dit
     * quoi faire — relancer le calcul des décisions — parce qu'un refus qui
     * n'indique pas la sortie est un mur.
     *
     * Les décisions que le jury a reprises à son compte (`override_par_jury`)
     * sont exclues : un humain les a motivées et signées, elles ne sont pas un
     * calcul à rejouer. Le jury reste souverain.
     */
    private function concordanceReasons(Collection $bulletins, Collection $decisions): array
    {
        $parEtudiant = $bulletins->groupBy('etudiant_id');
        $divergents = [];

        foreach ($decisions as $decision) {
            if ($decision->override_par_jury) {
                continue;
            }

            $periode = AgregatDeLaPeriode::parSemestre($parEtudiant->get($decision->etudiant_id, collect()));
            if ($periode === []) {
                // L'absence de bulletin est déjà dite par le contrôle de cohorte ;
                // la répéter ici noierait le message utile.
                continue;
            }

            $geleeMoyenne = $decision->moyenne_generale !== null ? (float) $decision->moyenne_generale : null;
            $divergence = ! AgregatDeLaPeriode::moyennesConcordent(
                $geleeMoyenne,
                AgregatDeLaPeriode::moyenne($periode),
            );

            if (AgregatDeLaPeriode::creditsDisponibles($periode)) {
                $divergence = $divergence
                    || (int) $decision->credits_obtenus !== AgregatDeLaPeriode::creditsObtenus($periode)
                    || (int) $decision->credits_attendus !== AgregatDeLaPeriode::creditsAttendus($periode);
            }

            if ($divergence) {
                $divergents[] = (int) $decision->etudiant_id;
            }
        }

        if ($divergents === []) {
            return [];
        }

        $cites = array_slice($divergents, 0, 10);

        return [sprintf(
            'La délibération ne reflète plus les bulletins pour %d étudiant(s) (identifiants : %s%s). '
            .'Relancez le calcul des décisions avant d’émettre le procès-verbal.',
            count($divergents),
            implode(', ', $cites),
            count($divergents) > count($cites) ? ', …' : '',
        )];
    }

    private function gradeSheetReasons(Collection $sheets): array
    {
        if ($sheets->isEmpty()) {
            return ['Aucune feuille de notes LMD ne couvre ce jury.'];
        }

        return $sheets->contains(fn ($sheet) => $sheet->status !== GradeSheetStatus::VALIDATED)
            ? ['Toutes les feuilles de notes du périmètre doivent être validées et verrouillées.']
            : [];
    }
}

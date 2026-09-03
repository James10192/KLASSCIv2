<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Helpers\SettingsHelper;
use App\Models\User;
use App\Services\LMD\LmdAcademicRuleProfile;
use Carbon\CarbonInterface;

class JuryPvSnapshotBuilder
{
    /**
     * Version du jeu de regles academiques grave dans le proces-verbal.
     *
     * v2 : les ponderations controle continu / examen terminal ne sont plus affirmees,
     * car elles n'entrent dans aucun calcul de note. Un PV conserve cinq ans ne doit
     * enoncer que des regles reellement appliquees.
     */
    public const RULES_VERSION = 'lmd-academic-profile-v2';

    public function __construct(private readonly LmdAcademicRuleProfile $profile) {}

    public function build(array $state, array $identity, User $actor, CarbonInterface $issuedAt): array
    {
        $jury = $state['jury'];
        $snapshot = [
            'schema' => 'lmd-jury-pv-snapshot-v2',
            'document' => ['reference' => $identity['reference'], 'version' => $identity['version'], 'number' => $identity['number']],
            'institution' => SettingsHelper::getSchoolInfo(),
            'jury' => $this->juryData($jury),
            'members' => $state['members']->map(fn ($member) => $this->memberData($member))->sortBy('user_id')->values()->all(),
            'decisions' => $state['decisions']->map(fn ($decision) => $this->decisionData($decision))->sortBy('student_id')->values()->all(),
            'grade_sheets' => $state['sheets']->map(fn ($sheet) => ['id' => $sheet->id, 'code' => $sheet->code, 'status' => $sheet->status->value, 'lock_version' => $sheet->lock_version])->sortBy('id')->values()->all(),
            'statistics' => $this->statistics($state['decisions']),
            'rules' => $this->rules(),
            'issuance' => ['actor_id' => $actor->id, 'actor_name' => $actor->name, 'issued_at' => $issuedAt->toIso8601String(), 'template_version' => 'lmd-jury-pv-v3', 'renderer_version' => 'dompdf-v2'],
        ];

        return $this->canonicalize($snapshot);
    }

    public function canonicalJson(array $snapshot): string
    {
        return json_encode($this->canonicalize($snapshot), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function juryData($jury): array
    {
        return ['id' => $jury->id, 'label' => $jury->libelle, 'date' => $jury->date_jury?->toDateString(), 'observations' => $jury->observations, 'year' => ['id' => $jury->annee_universitaire_id, 'label' => $jury->anneeUniversitaire?->libelle], 'session' => ['id' => $jury->session_id, 'label' => $jury->session?->libelle], 'parcours' => ['id' => $jury->parcours_id, 'label' => $jury->parcours?->name, 'code' => $jury->parcours?->code], 'class' => ['id' => $jury->classe_id, 'label' => $jury->classe?->name], 'semester' => $jury->semestre];
    }

    private function memberData($member): array
    {
        return ['id' => $member->id, 'user_id' => $member->user_id, 'name' => $member->user?->name, 'role' => $member->role, 'present' => (bool) $member->present, 'notes' => $member->notes, 'signed_at' => $member->signature_at?->toIso8601String(), 'signature_data' => $member->signature_data, 'signature_ip_digest' => $member->signature_ip ? hash('sha256', $member->signature_ip) : null];
    }

    private function decisionData($decision): array
    {
        $student = $decision->etudiant;
        return ['id' => $decision->id, 'student_id' => $decision->etudiant_id, 'matricule' => $student?->matricule, 'last_name' => $student?->nom, 'first_names' => $student?->prenoms, 'bulletin_id' => $decision->bulletin_id, 'automatic_decision' => $decision->decision_auto, 'decision' => $decision->decision, 'mention' => $decision->mention, 'average' => $decision->moyenne_generale, 'credits' => $decision->credits_obtenus, 'expected_credits' => $decision->credits_attendus, 'overridden' => (bool) $decision->override_par_jury, 'override_reason' => $decision->motif_override, 'vote' => $decision->vote_resultat];
    }

    private function rules(): array
    {
        // N'inscrire ici que les regles qui pilotent reellement une decision de jury.
        return [
            'profile_version' => self::RULES_VERSION,
            'validation_threshold' => $this->profile->validationThreshold(),
            'eliminatory_grade' => $this->profile->eliminatoryGrade(),
            'inter_ue_compensation' => $this->profile->interUeCompensationEnabled(),
            'intra_ue_compensation' => $this->profile->intraUeCompensationEnabled(),
            'mention_thresholds' => $this->profile->mentionThresholds(),
            'expected_credits' => $this->profile->expectedCreditsPerSemester(),
        ];
    }

    private function statistics($decisions): array
    {
        $result = ['total' => $decisions->count(), 'overrides' => $decisions->where('override_par_jury', true)->count(), 'average' => $decisions->whereNotNull('moyenne_generale')->avg('moyenne_generale'), 'decisions' => []];
        foreach (\App\Models\ESBTPLMDJuryDecision::DECISIONS as $decision) $result['decisions'][$decision] = $decisions->where('decision', $decision)->count();
        return $result;
    }

    private function canonicalize(array $value): array
    {
        foreach ($value as $key => $item) if (is_array($item)) $value[$key] = $this->canonicalize($item);
        if (! array_is_list($value)) ksort($value, SORT_STRING);
        return $value;
    }
}
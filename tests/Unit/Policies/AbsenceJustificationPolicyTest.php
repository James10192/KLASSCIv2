<?php

namespace Tests\Unit\Policies;

use App\Enums\JustificationStatus;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use App\Policies\AbsenceJustificationPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Tests Unit isolés (pas de DB) pour AbsenceJustificationPolicy.
 *
 * Vérifie les règles d'autorisation pures (mocking User+Absence in-memory).
 */
class AbsenceJustificationPolicyTest extends TestCase
{
    private AbsenceJustificationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AbsenceJustificationPolicy();
    }

    // =====================================================================
    // submit()
    // =====================================================================

    public function test_admin_with_process_perm_can_submit_for_any_pending(): void
    {
        $admin = $this->makeUser(canProcess: true, canSubmitOwn: false, etudiantId: null);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::PENDING);

        $this->assertTrue($this->policy->submit($admin, $absence));
    }

    public function test_admin_cannot_submit_on_already_approved_absence(): void
    {
        $admin = $this->makeUser(canProcess: true, canSubmitOwn: false, etudiantId: null);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::APPROVED);

        $this->assertFalse($this->policy->submit($admin, $absence));
    }

    public function test_student_owner_can_submit_when_not_approved(): void
    {
        $student = $this->makeUser(canProcess: false, canSubmitOwn: true, etudiantId: 42);
        $absence = $this->makeAbsence(etudiantId: 42, status: null);

        $this->assertTrue($this->policy->submit($student, $absence));
    }

    public function test_student_owner_can_resubmit_when_rejected(): void
    {
        $student = $this->makeUser(canProcess: false, canSubmitOwn: true, etudiantId: 42);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::REJECTED);

        $this->assertTrue($this->policy->submit($student, $absence));
    }

    public function test_student_cannot_submit_after_approval(): void
    {
        $student = $this->makeUser(canProcess: false, canSubmitOwn: true, etudiantId: 42);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::APPROVED);

        $this->assertFalse($this->policy->submit($student, $absence));
    }

    public function test_student_cannot_submit_for_another_student(): void
    {
        // student is 42 but absence belongs to 99
        $student = $this->makeUser(canProcess: false, canSubmitOwn: true, etudiantId: 42);
        $absence = $this->makeAbsence(etudiantId: 99, status: null);

        $this->assertFalse($this->policy->submit($student, $absence));
    }

    public function test_user_without_perms_cannot_submit(): void
    {
        $user = $this->makeUser(canProcess: false, canSubmitOwn: false, etudiantId: 42);
        $absence = $this->makeAbsence(etudiantId: 42, status: null);

        $this->assertFalse($this->policy->submit($user, $absence));
    }

    // =====================================================================
    // process()
    // =====================================================================

    public function test_admin_can_process_pending(): void
    {
        $admin = $this->makeUser(canProcess: true);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::PENDING);

        $this->assertTrue($this->policy->process($admin, $absence));
    }

    public function test_admin_cannot_process_already_approved(): void
    {
        $admin = $this->makeUser(canProcess: true);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::APPROVED);

        $this->assertFalse($this->policy->process($admin, $absence));
    }

    public function test_admin_cannot_process_rejected_directly(): void
    {
        // Pour re-traiter, l'etudiant doit re-soumettre (-> PENDING) d'abord.
        $admin = $this->makeUser(canProcess: true);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::REJECTED);

        $this->assertFalse($this->policy->process($admin, $absence));
    }

    public function test_student_without_process_perm_cannot_process(): void
    {
        $student = $this->makeUser(canProcess: false, canSubmitOwn: true, etudiantId: 42);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::PENDING);

        $this->assertFalse($this->policy->process($student, $absence));
    }

    // =====================================================================
    // viewDocument()
    // =====================================================================

    public function test_admin_can_view_any_document(): void
    {
        $admin = $this->makeUser(canProcess: true);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::PENDING);

        $this->assertTrue($this->policy->viewDocument($admin, $absence));
    }

    public function test_student_can_view_own_document(): void
    {
        $student = $this->makeUser(canProcess: false, canSubmitOwn: true, etudiantId: 42);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::PENDING);

        $this->assertTrue($this->policy->viewDocument($student, $absence));
    }

    public function test_student_cannot_view_other_document(): void
    {
        $student = $this->makeUser(canProcess: false, canSubmitOwn: true, etudiantId: 42);
        $absence = $this->makeAbsence(etudiantId: 99, status: JustificationStatus::APPROVED);

        $this->assertFalse($this->policy->viewDocument($student, $absence));
    }

    public function test_user_without_any_perm_cannot_view(): void
    {
        $user = $this->makeUser(canProcess: false, canSubmitOwn: false);
        $absence = $this->makeAbsence(etudiantId: 42, status: JustificationStatus::PENDING);

        $this->assertFalse($this->policy->viewDocument($user, $absence));
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function makeUser(
        bool $canProcess = false,
        bool $canSubmitOwn = false,
        ?int $etudiantId = null
    ): User {
        $perms = [];
        if ($canProcess) {
            $perms[] = 'attendances.justify_process';
        }
        if ($canSubmitOwn) {
            $perms[] = 'attendances.justify_own';
        }

        $user = new class($perms) extends User {
            private array $_perms;
            public function __construct(array $perms)
            {
                parent::__construct();
                $this->_perms = $perms;
            }
            public function can($abilities, $arguments = []): bool
            {
                if (is_array($abilities)) {
                    foreach ($abilities as $ab) {
                        if (in_array($ab, $this->_perms, true)) {
                            return true;
                        }
                    }
                    return false;
                }
                return in_array($abilities, $this->_perms, true);
            }
        };

        if ($etudiantId !== null) {
            $etu = new ESBTPEtudiant();
            $etu->id = $etudiantId;
            $user->setRelation('etudiant', $etu);
        } else {
            $user->setRelation('etudiant', null);
        }

        return $user;
    }

    private function makeAbsence(int $etudiantId, ?JustificationStatus $status): ESBTPAttendance
    {
        $abs = new ESBTPAttendance();
        $abs->id = mt_rand(1, 100000);
        $abs->etudiant_id = $etudiantId;
        // Bypass the cast — set raw attribute via setRawAttributes-like
        $abs->setAttribute('justification_status', $status);
        return $abs;
    }
}

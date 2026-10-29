<?php

namespace Tests\Feature\LMD;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMDBulletinService;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * La moyenne d'un ECUE avec la pondération contrôle continu / examen, comme sur
 * les fiches d'ESBTP Abidjan (octobre 2026) : 40 % CC + 60 % test final.
 * Désactivée, rien ne change : toutes les évaluations pèsent leur coefficient.
 */
class PonderationCcExamenTest extends TestCase
{
    private function service(array $reglages): LMDBulletinService
    {
        $this->app->instance(LmdAcademicRuleProfile::class, new LmdAcademicRuleProfile(
            fn (string $cle, mixed $defaut = null) => $reglages[$cle] ?? $defaut
        ));

        return $this->app->make(LMDBulletinService::class);
    }

    /** @param  list<array{0: string, 1: ?float}>  $notes  [type, note] ; note null = absent */
    private function notes(array $notes): Collection
    {
        return collect($notes)->map(function (array $n) {
            $note = new ESBTPNote(['note' => $n[1] ?? 0, 'is_absent' => $n[1] === null]);
            $note->setRelation('evaluation', new ESBTPEvaluation(['type' => $n[0], 'bareme' => 20, 'coefficient' => 1]));

            return $note;
        });
    }

    private function moyenne(array $reglages, array $notes): ?float
    {
        return $this->service($reglages)->calculerMoyenneECUE(1, 1, 1, 1, 1, $this->notes($notes));
    }

    public function test_desactivee_la_moyenne_reste_celle_des_coefficients(): void
    {
        $this->assertSame(15.5, $this->moyenne([], [['controle', 20], ['examen', 11]]));
    }

    public function test_activee_le_controle_continu_pese_40_et_l_examen_60(): void
    {
        $actif = [LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE => '1'];

        // Fiche de mécanique du point, L1 Bâtiment A : 20 et 11 → 14,6 (la fiche arrondit à 15).
        $this->assertSame(14.6, $this->moyenne($actif, [['controle', 20], ['examen', 11]]));
        // Deux contrôles continus : leur moyenne d'abord, puis la pondération.
        $this->assertSame(10.0, $this->moyenne($actif, [['controle', 12], ['controle', 8], ['examen', 10]]));
        // Absent à l'examen : 0, comme sur la fiche (16 et ABS → 6,4).
        $this->assertSame(6.4, $this->moyenne($actif, [['controle', 16], ['examen', null]]));
    }

    public function test_une_seule_partie_presente_compte_seule(): void
    {
        $actif = [LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE => '1'];

        $this->assertSame(12.0, $this->moyenne($actif, [['examen', 12]]));
        $this->assertSame(11.0, $this->moyenne($actif, [['controle', 11]]));
    }

    public function test_les_poids_de_l_ecole_sont_lus(): void
    {
        $this->assertSame(15.5, $this->moyenne(
            [LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE => '1', 'lmd_cc_weight' => 50, 'lmd_exam_weight' => 50],
            [['controle', 20], ['examen', 11]],
        ));
    }

    /** Le PV et le relevé gravent la règle seulement quand elle a produit leurs moyennes. */
    public function test_les_documents_officiels_ne_gravent_la_ponderation_que_si_elle_s_applique(): void
    {
        $profil = fn (array $r) => new LmdAcademicRuleProfile(fn (string $cle, mixed $defaut = null) => $r[$cle] ?? $defaut);

        $this->assertNull($profil([])->ponderationGravee());
        $this->assertSame(['cc' => 40.0, 'examen' => 60.0], $profil([LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE => '1'])->ponderationGravee());
    }
}

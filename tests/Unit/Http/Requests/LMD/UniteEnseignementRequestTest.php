<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\LMD;

use App\Http\Requests\LMD\UniteEnseignementRequest;
use Tests\TestCase;

/**
 * `esbtp_matieres.code` est NOT NULL et porte un index unique. Un element
 * constitutif sans code faisait echouer l'INSERT, et comme la creation d'une UE
 * ecrit dans une transaction, le rollback emportait aussi l'UE : l'utilisateur
 * perdait toute sa saisie sans message exploitable.
 */
class UniteEnseignementRequestTest extends TestCase
{
    public function test_le_code_d_un_element_constitutif_est_obligatoire(): void
    {
        $regles = (new UniteEnseignementRequest())->rules();

        $this->assertArrayHasKey('ecues.*.code', $regles);
        $this->assertContains('required', $regles['ecues.*.code']);
        $this->assertNotContains('nullable', $regles['ecues.*.code']);
    }

    public function test_l_intitule_d_un_element_constitutif_reste_obligatoire(): void
    {
        $regles = (new UniteEnseignementRequest())->rules();

        $this->assertContains('required', $regles['ecues.*.name']);
    }
}

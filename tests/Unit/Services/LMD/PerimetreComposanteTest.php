<?php

namespace Tests\Unit\Services\LMD;

use App\Models\User;
use App\Services\LMD\PerimetreComposante;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PerimetreComposanteTest extends TestCase
{
    public function test_sans_rattachement_rien_n_est_restreint(): void
    {
        $user = new User;
        $user->setRelation('composantes', new Collection);

        $this->assertSame([], (new PerimetreComposante)->idsPour($user));
    }
}

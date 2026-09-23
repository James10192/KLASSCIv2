<?php

namespace App\Console\Commands;

use App\Services\Verification\ExpirationVerifications;
use Illuminate\Console\Command;

class ExpirerVerificationsContact extends Command
{
    protected $signature = 'inscriptions:expirer-verifications-contact';

    protected $description = 'Rend visibles, avec un badge, les demandes du portail restees sans confirmation de contact au-dela du delai.';

    public function handle(ExpirationVerifications $expiration): int
    {
        $this->info($expiration->expirer().' demande(s) rendue(s) visible(s).');

        return self::SUCCESS;
    }
}

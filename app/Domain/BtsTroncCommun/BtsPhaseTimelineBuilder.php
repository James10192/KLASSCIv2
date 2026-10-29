<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPInscription;

class BtsPhaseTimelineBuilder
{
    public function __construct(
        private BtsPhaseResolver $resolver
    ) {
    }

    public function build(ESBTPInscription $inscription): array
    {
        return $this->resolver->buildJourney($inscription)['timeline'];
    }
}

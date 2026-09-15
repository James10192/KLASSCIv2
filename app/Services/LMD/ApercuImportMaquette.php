<?php

namespace App\Services\LMD;

final class ApercuImportMaquette extends \RuntimeException
{
    /** @param  array<string, mixed>  $resultat */
    public function __construct(public readonly array $resultat)
    {
        parent::__construct('dry_run');
    }
}

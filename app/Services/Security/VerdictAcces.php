<?php

namespace App\Services\Security;

final class VerdictAcces
{
    public const OK = 'ok';

    public const COMPTE_INACTIF = 'compte_inactif';

    public const PERMISSION_MANQUANTE = 'permission_manquante';

    public const FENETRE_FERMEE = 'fenetre_fermee';

    public const CONFIG_ABSENTE = 'config_absente';

    public function __construct(
        public readonly bool $autorise,
        public readonly string $cause,
        public readonly string $motif,
        public readonly string $libelleDroit,
    ) {}
}

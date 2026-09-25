<?php

declare(strict_types=1);

namespace App\Domain\EmploiTemps;

/**
 * Où tombe un émargement de début par rapport aux délais réglés par l'école.
 * Décidé une seule fois, par `FenetresDEmargement::classerDebut()`.
 */
enum MomentDEmargement: string
{
    case TropTot = 'trop_tot';
    case Present = 'present';
    case Retard = 'retard';
    case Depasse = 'depasse';
}

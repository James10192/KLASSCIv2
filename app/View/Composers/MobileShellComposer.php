<?php

namespace App\View\Composers;

use App\Services\Mobile\MobileProfileResolver;
use Illuminate\View\View;

/**
 * Partage a toutes les vues de quoi decider du shell mobile :
 *
 *  - $mobileShellEnabled (bool)  : le reglage d'instance est actif ;
 *  - $mobileProfile (string|null): profil de la personne connectee, l'un de
 *    MobileProfileResolver::PROFILS, ou null (reglage coupe, pas d'utilisateur,
 *    ou personne sans profil).
 *
 * Le shell se rend quand les deux sont vrais : `$mobileShellEnabled && $mobileProfile`.
 *
 * Enregistre sur '*' : le resolver est memoise par requete, le cout est donc
 * celui d'une lecture de reglage (elle-meme en cache) et d'un tableau.
 */
class MobileShellComposer
{
    public function __construct(private MobileProfileResolver $resolver)
    {
    }

    public function compose(View $view): void
    {
        $actif = $this->resolver->actif();
        $profil = $actif && auth()->check() ? $this->resolver->resolve(auth()->user()) : null;

        $view->with('mobileShellEnabled', $actif);
        $view->with('mobileProfile', $profil);
    }
}

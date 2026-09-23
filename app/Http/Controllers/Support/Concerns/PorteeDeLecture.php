<?php

namespace App\Http\Controllers\Support\Concerns;

use Illuminate\Http\Request;

trait PorteeDeLecture
{
    /**
     * `school` seulement avec la permission ; tout autre cas retombe sur `mine`.
     * Le Master borne deja a l'instance, cette permission borne a la personne.
     * Elle ne vaut que pour LIRE : ecrire se fait toujours en `mine`.
     */
    private function portee(Request $request, string $defaut = 'mine'): string
    {
        $voulue = $request->query('portee', $defaut === 'school' ? 'ecole' : 'moi');

        return $voulue === 'ecole' && $request->user()->can('support.tickets.view_school') ? 'school' : 'mine';
    }
}

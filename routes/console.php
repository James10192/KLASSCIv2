<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// La règle métier est effective dès 00:00 dans CatalogueCreneaux. Ce passage
// matérialise aussi `ouvert = false` en base et rattrape un éventuel arrêt du
// scheduler : au premier tick suivant, les créneaux du jour/passés sont fermés.
Schedule::command('inscriptions:fermer-creneaux-rdv-du-jour')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('rdv-fermeture-jour-minuit')
    ->description('Ferme les créneaux du jour quand le réglage école est actif');

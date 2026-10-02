<?php

namespace App\Providers;

use App\Domain\Exploitation\TracesLentes\EnregistreurDeTraces;
use App\Domain\Exploitation\TracesLentes\MesuresEnCours;
use App\Domain\Exploitation\TracesLentes\ObservateurDesTravaux;
use App\Domain\Exploitation\TracesLentes\SeuilsDesTraces;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Traces des actions lentes : un seul écouteur SQL, qui compte et ne garde
 * rien, et les événements qui encadrent tâches, commandes et courriels.
 */
class TracesLentesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MesuresEnCours::class);
        $this->app->singleton(SeuilsDesTraces::class);
        $this->app->singleton(EnregistreurDeTraces::class);
        $this->app->singleton(ObservateurDesTravaux::class);
    }

    public function boot(): void
    {
        // Résolu à chaque requête plutôt que capturé : le conteneur garde un seul
        // registre, même s'il est reconstruit (tests, travailleur de file).
        $app = $this->app;
        DB::listen(static fn (QueryExecuted $q) => $app->make(MesuresEnCours::class)->compter($q->time, $q->sql));

        $o = ObservateurDesTravaux::class;
        Event::listen(JobProcessing::class, [$o, 'tacheCommence']);
        Event::listen(JobProcessed::class, [$o, 'tacheTerminee']);
        Event::listen(JobExceptionOccurred::class, [$o, 'tacheTerminee']);
        Event::listen(CommandStarting::class, [$o, 'commandeCommence']);
        Event::listen(CommandFinished::class, [$o, 'commandeTerminee']);
        Event::listen(MessageSending::class, [$o, 'courrielPart']);
        Event::listen(MessageSent::class, [$o, 'courrielParti']);
    }
}

<?php

namespace App\Console\Commands\Notes;

use App\Services\Notes\UniciteDesNotes;
use Illuminate\Console\Command;

class UniciteDesNotesCommand extends Command
{
    protected $signature = 'notes:unicite';

    protected $description = 'Liste les notes en double (même élève, même évaluation) et pose l\'unicité s\'il n\'en reste aucune';

    public function handle(UniciteDesNotes $unicite): int
    {
        if (! $unicite->disponible()) {
            $this->warn('Unicité disponible sur MySQL uniquement.');

            return self::SUCCESS;
        }

        $doublons = $unicite->doublons();
        if ($doublons->isNotEmpty()) {
            $this->warn("{$doublons->count()} paire(s) élève × évaluation portent plusieurs notes en vigueur (ni effacées ni archivées). Aucune n'est supprimée d'office : gardez la bonne, effacez les autres, puis relancez.");
            $this->table(['Élève', 'Évaluation', 'Notes', 'Identifiants'], $doublons->map(fn ($d) => [
                $d->etudiant_id, $d->evaluation_id, $d->nombre, $d->note_ids,
            ])->all());

            return self::FAILURE;
        }

        $this->info($unicite->poser() ? 'Unicité en place : une seule note vivante par élève et par évaluation.' : 'Unicité non posée.');

        return self::SUCCESS;
    }
}

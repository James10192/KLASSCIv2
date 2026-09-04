<?php

namespace App\Console\Commands;

use App\Models\ESBTPEtudiantDocument;
use App\Services\Documents\StockageDocumentEtudiant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Deplace les pieces deja deposees du disque expose vers le disque prive.
 *
 * Les depots d'avant vivent sous `storage/app/public`, que le lien symbolique
 * `public/storage` livre au serveur web : celui-ci les sert DIRECTEMENT, sans
 * passer par Laravel, donc sans authentification, sans permission et sans trace.
 * Le code n'ecrit plus la, mais ce qui y est deja ecrit y reste tant que cette
 * commande n'est pas passee.
 *
 * Elle ne detruit rien avant d'avoir verifie. Pour chaque fichier : copie,
 * comparaison des empreintes, et suppression de l'original seulement si les deux
 * concordent. Une copie interrompue laisse donc l'original en place, et la
 * commande peut etre relancee autant de fois qu'il faut.
 *
 * Le chemin en base ne change PAS : la lecture resout le disque, elle ne le
 * suppose pas. Un document deplace s'ouvre donc sans qu'aucune ligne ne soit
 * touchee, et un retour en arriere se fait en recopiant les fichiers, sans
 * toucher a la base.
 */
class DocumentsEtudiantsMettreALAbri extends Command
{
    protected $signature = 'documents:mettre-a-l-abri
                            {--appliquer : Deplace reellement. Sans ce drapeau, la commande se contente de dire ce qu elle ferait.}
                            {--lot=200 : Nombre de documents lus a la fois.}';

    protected $description = "Deplace les pieces des etudiants du disque expose au serveur web vers le disque prive";

    public function handle(StockageDocumentEtudiant $stockage): int
    {
        $appliquer = (bool) $this->option('appliquer');
        $lot = max(1, (int) $this->option('lot'));

        $exposes = 0;
        $deplaces = 0;
        $echecs = 0;
        $introuvables = 0;
        $dejaSurs = 0;

        $this->info($appliquer
            ? 'Deplacement en cours.'
            : "Releve seulement. Rien ne sera deplace : ajoutez --appliquer pour agir.");

        ESBTPEtudiantDocument::query()
            ->orderBy('id')
            ->chunkById($lot, function ($documents) use (
                $stockage, $appliquer,
                &$exposes, &$deplaces, &$echecs, &$introuvables, &$dejaSurs
            ) {
                foreach ($documents as $document) {
                    $disque = $stockage->disqueDe($document->file_path);

                    if ($disque === null) {
                        // Le chemin ne designe aucun fichier, sur aucun disque.
                        // Ce n'est pas notre affaire ici : on le compte et on le
                        // dit, sans rien supprimer en base.
                        $introuvables++;
                        continue;
                    }

                    if ($disque !== StockageDocumentEtudiant::DISQUE_HERITE) {
                        $dejaSurs++;
                        continue;
                    }

                    $exposes++;

                    if (! $appliquer) {
                        continue;
                    }

                    if ($this->deplacer($document->file_path)) {
                        $deplaces++;
                    } else {
                        $echecs++;
                    }
                }
            });

        $this->newLine();
        $this->line(sprintf('Deja a l abri        : %d', $dejaSurs));
        $this->line(sprintf('Exposes             : %d', $exposes));

        if ($appliquer) {
            $this->line(sprintf('Deplaces            : %d', $deplaces));
            $this->line(sprintf('Echecs              : %d', $echecs));
        }

        if ($introuvables > 0) {
            $this->warn(sprintf(
                'Chemins introuvables : %d. Ces lignes designent un fichier absent des deux disques : '
                .'a examiner separement, rien n a ete supprime.',
                $introuvables
            ));
        }

        if (! $appliquer && $exposes > 0) {
            $this->newLine();
            $this->warn(sprintf(
                '%d piece(s) restent lisibles par qui devine leur adresse. Relancez avec --appliquer.',
                $exposes
            ));
        }

        return $echecs > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Copie, verifie, puis efface l'original. Jamais l'inverse.
     */
    private function deplacer(string $chemin): bool
    {
        $expose = Storage::disk(StockageDocumentEtudiant::DISQUE_HERITE);
        $prive = Storage::disk(StockageDocumentEtudiant::DISQUE);

        try {
            $source = $expose->path($chemin);
            $flux = fopen($source, 'rb');

            if ($flux === false) {
                $this->error(sprintf('Lecture impossible : %s', $chemin));

                return false;
            }

            // Par flux : une piece jointe peut peser dix megaoctets, et la
            // commande en traite des milliers d'affilee.
            $prive->put($chemin, $flux);

            if (is_resource($flux)) {
                fclose($flux);
            }

            // On ne supprime qu'apres avoir compare les empreintes. Une copie
            // tronquee laisse donc l'original en place, et la commande se
            // relance sans avoir rien perdu.
            $destination = $prive->path($chemin);

            if (! is_file($destination) || md5_file($destination) !== md5_file($source)) {
                $this->error(sprintf('Copie non conforme, original conserve : %s', $chemin));

                return false;
            }

            $expose->delete($chemin);

            return true;
        } catch (Throwable $e) {
            $this->error(sprintf('%s : %s', $chemin, $e->getMessage()));

            return false;
        }
    }
}

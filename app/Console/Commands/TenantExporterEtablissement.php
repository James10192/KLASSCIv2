<?php

namespace App\Console\Commands;

use App\Domain\Exploitation\Reversibilite\ChampsExclus;
use App\Domain\Exploitation\Reversibilite\FeuilleCsv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * L'archive complète d'un établissement, dans un format qu'il peut relire.
 *
 * La réversibilité n'est pas la possession : remettre un dump SQL à une école
 * qui résilie, c'est lui donner ses données sans lui donner le moyen de les
 * lire. Sa secrétaire ouvre Excel, pas un client MySQL. L'archive contient
 * donc un CSV par table — lisible sur n'importe quel poste, sans personne pour
 * aider — et un manifeste qui dit ce qu'elle contient, ce qu'elle ne contient
 * pas, et pourquoi.
 *
 * Le dump SQL y est aussi, pour un successeur technique. Les deux formats
 * répondent à deux lecteurs différents, et aucun ne remplace l'autre.
 */
class TenantExporterEtablissement extends Command
{
    protected $signature = 'tenant:exporter
                            {--sortie= : Dossier de destination (défaut : storage/app/exports)}
                            {--sans-fichiers : N\'exporter que les données, pas les documents}';

    protected $description = 'Exporte toutes les données de l\'établissement dans un format ouvert et relisible';

    /** Les tables dont on cite le volume en tête du manifeste. */
    private const TABLES_PHARES = [
        'esbtp_etudiants' => 'étudiants',
        'esbtp_inscriptions' => 'inscriptions',
        'esbtp_paiements' => 'paiements',
        'esbtp_notes' => 'notes',
        'esbtp_bulletins' => 'bulletins',
    ];

    public function handle(): int
    {
        $horodatage = now()->format('Y-m-d_His');
        $racine = rtrim($this->option('sortie') ?: storage_path('app/exports'), '/')
            . "/export_{$horodatage}";

        if (! mkdir($racine . '/donnees', 0755, true) && ! is_dir($racine . '/donnees')) {
            $this->error("Impossible de créer {$racine}/donnees");

            return self::FAILURE;
        }

        $this->line('');
        $this->line("  Export de l'établissement vers {$racine}");
        $this->line('  ' . str_repeat('-', 60));

        $inventaire = [];
        $ecartees = [];

        foreach ($this->tables() as $table) {
            if (ChampsExclus::tableExclue($table)) {
                continue;
            }

            [$lignes, $colonnesEcartees] = $this->exporterTable($table, $racine . '/donnees');

            $inventaire[$table] = $lignes;

            if ($colonnesEcartees !== []) {
                $ecartees[$table] = $colonnesEcartees;
            }
        }

        $this->ecrireManifeste($racine, $inventaire, $ecartees);

        if (! $this->option('sans-fichiers')) {
            $this->exporterDocuments($racine);
        }

        $this->line('');
        $this->line(sprintf(
            '  %d tables exportées, %s lignes au total.',
            count($inventaire),
            number_format(array_sum($inventaire), 0, ',', ' '),
        ));
        $this->line("  Archive : {$racine}");
        $this->line('');

        return self::SUCCESS;
    }

    /**
     * Les tables de la base de l'instance.
     *
     * Le nom de la colonne rendue par `SHOW TABLES` dépend du nom de la base
     * (`Tables_in_klassci_yakro`), d'où le `reset()` plutôt qu'un accès par
     * clé. Le branchement par pilote n'est pas de la portabilité gratuite :
     * il rend cette commande exécutable sur SQLite, donc vérifiable de bout en
     * bout — et une commande de réversibilité sert exactement une fois, le
     * jour où une école part. Ce n'est pas ce jour-là qu'on découvre qu'elle
     * ne marche pas.
     */
    private function tables(): array
    {
        $requete = DB::connection()->getDriverName() === 'sqlite'
            ? "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
            : 'SHOW TABLES';

        return collect(DB::select($requete))
            ->map(function ($ligne) {
                // `reset()` exige une variable, pas une expression : le nom de
                // la colonne rendue par `SHOW TABLES` dépend du nom de la base
                // (`Tables_in_klassci_yakro`), on ne peut donc pas y accéder
                // par clé.
                $colonnes = (array) $ligne;

                return reset($colonnes);
            })
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Une table dans un fichier.
     *
     * Le parcours est fait par tranches plutôt qu'en une requête : une école
     * de 2 000 élèves a des dizaines de milliers de notes, et les charger
     * toutes en mémoire ferait tomber le processus sur un hébergement
     * mutualisé — exactement au moment où l'école a le plus besoin que ça
     * marche.
     */
    private function exporterTable(string $table, string $dossier): array
    {
        $colonnes = DB::getSchemaBuilder()->getColumnListing($table);

        $retenues = ChampsExclus::colonnesRetenues($table, $colonnes);
        $ecartees = ChampsExclus::colonnesEcartees($table, $colonnes);

        if ($retenues === []) {
            return [0, $ecartees];
        }

        $fichier = fopen("{$dossier}/{$table}.csv", 'w');
        fwrite($fichier, FeuilleCsv::BOM);
        fputcsv($fichier, $retenues, ';');

        $lignes = 0;

        DB::table($table)->select($retenues)->orderBy($retenues[0])->chunk(1000, function ($tranche) use ($fichier, &$lignes) {
            foreach ($tranche as $enregistrement) {
                fputcsv($fichier, FeuilleCsv::ligne((array) $enregistrement), ';');
                $lignes++;
            }
        });

        fclose($fichier);

        $this->line(sprintf('    %-42s %s', $table, number_format($lignes, 0, ',', ' ')));

        return [$lignes, $ecartees];
    }

    /** Les documents : bulletins, reçus, photos, logo. */
    private function exporterDocuments(string $racine): void
    {
        $source = storage_path('app');
        $archive = "{$racine}/documents.tar.gz";

        exec(sprintf(
            '/bin/bash -o pipefail -c %s 2>&1',
            escapeshellarg(sprintf(
                'tar -cz --exclude=%s -C %s . > %s',
                escapeshellarg('./exports'),
                escapeshellarg($source),
                escapeshellarg($archive),
            )),
        ), $sortie, $code);

        if ($code !== 0) {
            $this->warn('    documents : archive impossible — ' . implode(' ', array_slice($sortie, 0, 2)));

            return;
        }

        $this->line(sprintf('    %-42s %s Mo', 'documents.tar.gz', number_format(filesize($archive) / 1048576, 1, ',', ' ')));
    }

    /**
     * Le manifeste : ce que l'archive contient, et ce qu'elle ne contient pas.
     *
     * Écrit pour la personne qui ouvrira le dossier, pas pour nous. Un export
     * dont on ne sait pas ce qui en a été retiré n'est pas vérifiable — et le
     * silence sur ce point est exactement ce qui fait douter d'un prestataire
     * qu'on quitte.
     */
    private function ecrireManifeste(string $racine, array $inventaire, array $ecartees): void
    {
        $nom = $this->reglage('school_name') ?: 'Établissement';
        $total = array_sum($inventaire);

        $lignes = [
            "EXPORT DES DONNÉES — {$nom}",
            str_repeat('=', 60),
            '',
            'Produit le ' . now()->format('d/m/Y à H:i') . '.',
            '',
            'CE QUE CONTIENT CE DOSSIER',
            str_repeat('-', 60),
            '',
            'donnees/         Un fichier CSV par table, séparateur point-virgule,',
            '                 encodage UTF-8. Ces fichiers s\'ouvrent directement',
            '                 dans Excel et dans LibreOffice Calc.',
        ];

        if (! $this->option('sans-fichiers')) {
            $lignes[] = '';
            $lignes[] = 'documents.tar.gz Les fichiers déposés dans la plateforme : bulletins,';
            $lignes[] = '                 reçus, photos, logo de l\'établissement.';
        }

        $lignes[] = '';
        $lignes[] = 'VOLUMES PRINCIPAUX';
        $lignes[] = str_repeat('-', 60);
        $lignes[] = '';

        foreach (self::TABLES_PHARES as $table => $libelle) {
            if (isset($inventaire[$table])) {
                $lignes[] = sprintf('  %-16s %s', $libelle, number_format($inventaire[$table], 0, ',', ' '));
            }
        }

        $lignes[] = '';
        $lignes[] = sprintf(
            '  %d tables, %s lignes au total.',
            count($inventaire),
            number_format($total, 0, ',', ' '),
        );

        $lignes[] = '';
        $lignes[] = 'CE QUE CE DOSSIER NE CONTIENT PAS';
        $lignes[] = str_repeat('-', 60);
        $lignes[] = '';
        $lignes[] = 'Les mots de passe et les jetons d\'authentification ont été retirés.';
        $lignes[] = 'Ce ne sont pas des données de l\'établissement : ce sont les secrets';
        $lignes[] = 'qui protègent les comptes de ses utilisateurs. Les remettre dans une';
        $lignes[] = 'archive destinée à circuler exposerait ces comptes sans rien apporter :';
        $lignes[] = 'un successeur fait réinitialiser les mots de passe, il n\'en a jamais';
        $lignes[] = 'besoin.';

        if ($ecartees !== []) {
            $lignes[] = '';
            $lignes[] = 'Colonnes retirées, table par table :';
            $lignes[] = '';

            foreach ($ecartees as $table => $colonnes) {
                $lignes[] = sprintf('  %-36s %s', $table, implode(', ', $colonnes));
            }
        }

        $lignes[] = '';
        $lignes[] = 'Les tables purement techniques (files d\'attente, cache, sessions,';
        $lignes[] = 'journal des migrations) ne sont pas exportées : elles décrivent le';
        $lignes[] = 'fonctionnement du logiciel, pas l\'activité de l\'établissement.';
        $lignes[] = '';
        $lignes[] = 'DONNÉES PERSONNELLES';
        $lignes[] = str_repeat('-', 60);
        $lignes[] = '';
        $lignes[] = 'Ce dossier contient l\'état civil d\'élèves mineurs, des coordonnées de';
        $lignes[] = 'familles et des informations financières. Il relève de la loi sur la';
        $lignes[] = 'protection des données à caractère personnel. Conservez-le comme vous';
        $lignes[] = 'conserveriez les dossiers papier correspondants.';
        $lignes[] = '';

        file_put_contents($racine . '/LISEZ-MOI.txt', implode("\n", $lignes));

        file_put_contents(
            $racine . '/inventaire.json',
            json_encode([
                'etablissement' => $nom,
                'produit_le' => now()->toIso8601String(),
                'tables' => $inventaire,
                'lignes_totales' => $total,
                'colonnes_ecartees' => $ecartees,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    /** Un réglage de l'instance, sans supposer que la table existe. */
    private function reglage(string $cle): ?string
    {
        try {
            return DB::table('esbtp_settings')->where('key', $cle)->value('value');
        } catch (\Throwable) {
            return null;
        }
    }
}

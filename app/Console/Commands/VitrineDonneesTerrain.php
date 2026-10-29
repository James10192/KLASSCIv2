<?php

namespace App\Console\Commands;

use App\Domain\Exploitation\DonneesTerrain\ReleveTerrain;
use Illuminate\Console\Command;

/**
 * Releve, sur cette instance, les chiffres que le site annonce sans les avoir
 * mesures.
 *
 *   php artisan vitrine:donnees-terrain
 *   php artisan vitrine:donnees-terrain --json > terrain-yakro.json
 *
 * En lecture seule, du premier au dernier octet : aucune ecriture, aucune
 * migration, rien a configurer, aucun jeton a generer. Se lance depuis le
 * terminal cPanel de chaque instance, dans le dossier de l'instance.
 *
 * Ce que la base ne sait pas, la commande le dit au lieu de l'estimer : la
 * derniere section nomme les marqueurs du site qui resteront a la main de
 * l'equipe, avec la raison.
 */
class VitrineDonneesTerrain extends Command
{
    protected $signature = 'vitrine:donnees-terrain
                            {--annee= : ID de l\'annee universitaire (par defaut : courante)}
                            {--json : Sortie JSON brute, a coller ou a rassembler}';

    protected $description = 'Releve en lecture seule les chiffres de terrain annonces sur le site (encaissements, echeances, maquettes LMD, jurys)';

    public function handle(ReleveTerrain $releve): int
    {
        $annee = $this->option('annee');
        $rapport = $releve->relever($annee !== null ? (int) $annee : null);

        if ($this->option('json')) {
            $this->line(json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->rendre($rapport);

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $r */
    private function rendre(array $r): void
    {
        $this->newLine();
        $this->line("  Chiffres de terrain — instance <options=bold>{$r['instance']}</>");
        $this->line('  '.str_repeat('-', 52));
        $this->line('  Annee : '.($r['annee']['libelle'] ?? '(aucune annee courante)'));
        $this->newLine();

        $this->section('Encaissements valides, par mode', $r['encaissements'], function (array $e) {
            $this->line("    {$e['nombre_total']} paiements, ".$this->argent($e['montant_total']));
            $this->newLine();
            $this->table(
                ['Mode', 'Paiements', 'Part du nombre', 'Montant', 'Part du montant'],
                $this->lignesModes($e),
            );
            $this->line('    → repond a : part par canal, et part du Mobile Money.');
            $this->line('      La reconciliation automatique n\'est pas ici : elle se lit');
            $this->line('      dans les sessions de reconciliation, pas dans le paiement.');
        });

        $this->section('Retard d\'echeance', $r['echeances'], function (array $e) {
            $this->line("    {$e['inscriptions_en_retard']} inscriptions en retard sur {$e['inscriptions_avec_snapshot']}"
                .' ('.$this->pct($e['part_en_retard_pct']).')');
            $this->line('    Montant en retard : '.$this->argent($e['montant_en_retard_total']));
            $this->line('    Jours de retard : '.$this->resume($e['jours_de_retard']));
            $this->line("    ⚠ {$e['reserve']}");
        });

        $this->section('Maquettes LMD', $r['maquettes_lmd'], function (array $m) {
            $this->line("    {$m['unites_enseignement']} UE, {$m['ecues']} ECUE, {$m['credits_declares']} credits declares");
            $this->line('    Heures par credit : '.$this->resume($m['heures_par_credit']));
            if ($m['ecues_planifiees_sans_credit'] > 0 || $m['ecues_creditees_sans_volume'] > 0) {
                $this->line("    Trous : {$m['ecues_planifiees_sans_credit']} planifiees sans credit, "
                    ."{$m['ecues_creditees_sans_volume']} creditees sans volume");
            }
        });

        $this->section('Jurys de deliberation', $r['jurys'], function (array $j) {
            $this->line("    {$j['jurys']} jurys, {$j['jurys_clos']} clos, {$j['jurys_avec_pv']} avec PV"
                .' ('.$this->pct($j['part_avec_pv_pct']).' des clos)');
            $this->line('    Heures ouverture → cloture : '.$this->resume($j['heures_ouverture_a_cloture']));
            $this->line("    ⚠ {$j['reserve']}");

            $e = $j['ecarts_a_la_decision_automatique'];
            if ($e['applicable'] ?? false) {
                $this->line("    Decisions : {$e['decisions_total']}, dont {$e['decisions_ecartees_du_calcul']} ecartees du calcul"
                    .' ('.$this->pct($e['part_ecartee_pct']).')');
                $this->line('    Ecarts par jury : '.$this->resume($e['ecarts_par_jury']));
            }
        });

        $this->newLine();
        $this->line('  <options=bold>Ce que cette instance ne peut pas dire</>');
        $this->line('  '.str_repeat('-', 52));
        foreach ($r['hors_base'] as $marqueur => $raison) {
            $this->line("    {$marqueur}");
            $this->line("      {$raison}");
        }
        $this->newLine();
        $this->line('  Ces chiffres-la ne sont pas dans une base. Ils sont dans');
        $this->line('  l\'experience de l\'equipe, ou nulle part.');
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $bloc
     * @param  callable(array<string, mixed>): void  $rendu
     */
    private function section(string $titre, array $bloc, callable $rendu): void
    {
        $this->line("  <options=bold>{$titre}</>");
        if (! ($bloc['applicable'] ?? false)) {
            $this->line('    non applicable — '.($bloc['raison'] ?? 'raison non precisee'));
            $this->newLine();

            return;
        }
        $rendu($bloc);
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $e
     * @return array<int, array<int, string>>
     */
    private function lignesModes(array $e): array
    {
        $montants = [];
        foreach ($e['par_montant'] as $ligne) {
            $montants[$ligne['cle']] = $ligne;
        }

        $lignes = [];
        foreach ($e['par_nombre'] as $ligne) {
            $m = $montants[$ligne['cle']] ?? ['valeur' => 0, 'part' => null];
            $lignes[] = [
                $ligne['cle'],
                (string) (int) $ligne['valeur'],
                $this->pct($ligne['part']),
                $this->argent($m['valeur']),
                $this->pct($m['part']),
            ];
        }

        return $lignes;
    }

    /** @param array{n:int, min:float|null, max:float|null, mediane:float|null, moyenne:float|null} $r */
    private function resume(array $r): string
    {
        if ($r['n'] === 0) {
            return 'aucune observation';
        }

        return "mediane {$r['mediane']} (min {$r['min']}, max {$r['max']}, moyenne {$r['moyenne']}, sur {$r['n']})";
    }

    private function pct(?float $v): string
    {
        return $v === null ? 'n/a' : number_format($v, 1, ',', ' ').' %';
    }

    private function argent(float $v): string
    {
        return number_format($v, 0, ',', ' ').' FCFA';
    }
}

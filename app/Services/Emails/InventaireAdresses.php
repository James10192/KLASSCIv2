<?php

namespace App\Services\Emails;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les colonnes d'adresse e-mail de l'instance, lues par domaine.
 *
 * Le classement se fait sur les DOMAINES distincts, pas ligne par ligne :
 * deux mille etudiants tiennent en quelques dizaines de domaines, et la
 * verification MX ne part qu'une fois par domaine.
 *
 * Les demandes de reinscription n'ont pas de colonne e-mail : elles lisent
 * celle de l'etudiant, inventoriee ici.
 */
class InventaireAdresses
{
    /** @var array<string, list<string>> table => colonnes */
    public const SOURCES = [
        'esbtp_etudiants' => ['email', 'email_personnel'],
        'esbtp_parents' => ['email'],
        'users' => ['email'],
        'esbtp_candidatures' => ['email'],
        'esbtp_rdv_reservations' => ['email'],
    ];

    /** @return list<array{table: string, colonne: string}> */
    public function colonnes(): array
    {
        $colonnes = [];
        foreach (self::SOURCES as $table => $noms) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($noms as $colonne) {
                if (Schema::hasColumn($table, $colonne)) {
                    $colonnes[] = ['table' => $table, 'colonne' => $colonne];
                }
            }
        }

        return $colonnes;
    }

    /** @return list<array{table: string, colonne: string, domaine: string, nombre: int}> */
    public function domaines(): array
    {
        $lignes = [];
        foreach ($this->colonnes() as ['table' => $table, 'colonne' => $colonne]) {
            $expression = 'LOWER(TRIM(SUBSTRING_INDEX('.$colonne.", '@', -1)))";
            $resultats = DB::table($table)
                ->selectRaw($expression.' AS domaine, COUNT(*) AS nombre')
                ->whereNotNull($colonne)->whereRaw('TRIM('.$colonne.") <> ''")
                ->groupByRaw($expression)
                ->get();

            foreach ($resultats as $r) {
                $lignes[] = ['table' => $table, 'colonne' => $colonne, 'domaine' => (string) $r->domaine, 'nombre' => (int) $r->nombre];
            }
        }

        return $lignes;
    }

    /** @return array{total: int, sans_email: int} */
    public function comptes(): array
    {
        $total = 0;
        $vides = 0;
        foreach ($this->colonnes() as ['table' => $table, 'colonne' => $colonne]) {
            $total += DB::table($table)->count();
            $vides += DB::table($table)->where(fn ($q) => $q->whereNull($colonne)->orWhereRaw('TRIM('.$colonne.") = ''"))->count();
        }

        return ['total' => $total, 'sans_email' => $vides];
    }

    /**
     * Les lignes dont l'adresse est sur l'un des domaines donnes.
     *
     * @param  list<string>  $domaines
     * @return \Illuminate\Support\LazyCollection<int, object{id: int, email: string}>
     */
    public function lignes(string $table, string $colonne, array $domaines, ?int $limite = null)
    {
        $requete = DB::table($table)
            ->select(['id', $colonne.' as email'])
            ->whereIn(DB::raw('LOWER(TRIM(SUBSTRING_INDEX('.$colonne.", '@', -1)))"), $domaines)
            ->orderBy('id');

        return $limite === null ? $requete->lazyById(500) : $requete->limit($limite)->get()->lazy();
    }
}

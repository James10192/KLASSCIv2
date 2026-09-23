<?php

namespace App\Services\Emails;

use App\Enums\EtatEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Retrouve les adresses factices et fautives de l'instance, et remet a NULL
 * les FACTICES seulement.
 *
 * Une faute de frappe n'est jamais corrigee d'office : `gmail.con` est
 * presque surement `gmail.com`, mais pas surement, et ecrire a la place de la
 * famille une adresse qu'elle n'a pas donnee serait pire que de la laisser
 * vide. Elle est rapportee avec sa suggestion, pour que l'ecole appelle.
 *
 * Les COMPTES (`users`) ne sont vides que sur demande expresse : un compte du
 * personnel peut se connecter ou recuperer son mot de passe par cette adresse,
 * meme fabriquee. Le rapport les liste par role pour que l'ecole decide.
 *
 * Avant toute ecriture, une sauvegarde (table, id, colonne, ancienne valeur)
 * est ecrite et relue : sans elle, rien n'est modifie.
 */
class NettoyageAdressesFactices
{
    public const TABLE_COMPTES = 'users';

    public function __construct(
        private readonly InventaireAdresses $inventaire,
        private readonly DiagnosticEmail $classement,
    ) {}

    /**
     * @return list<array{table: string, colonne: string, domaine: string, nombre: int, etat: string, suggestion: ?string}>
     */
    public function rapport(bool $avecMx = true): array
    {
        $lignes = [];
        foreach ($this->inventaire->domaines() as $ligne) {
            $analyse = $this->classement->classerDomaine($ligne['domaine'], $avecMx);
            if ($analyse->etat->typeSuspect() === null) {
                continue;
            }
            $lignes[] = $ligne + ['etat' => $analyse->etat->value, 'suggestion' => $analyse->domaineSuggere()];
        }

        return $lignes;
    }

    /** @return array<string, int> role => nombre de comptes a adresse factice */
    public function comptesParRole(): array
    {
        $domaines = collect($this->factices(true))->firstWhere('table', self::TABLE_COMPTES)['domaines'] ?? [];
        if ($domaines === []) {
            return [];
        }

        return DB::table('users')
            ->leftJoin('model_has_roles as mr', fn ($j) => $j->on('mr.model_id', '=', 'users.id')->where('mr.model_type', \App\Models\User::class))
            ->leftJoin('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn(DB::raw("LOWER(TRIM(SUBSTRING_INDEX(users.email, '@', -1)))"), $domaines)
            ->selectRaw("COALESCE(r.name, 'sans rôle') AS role, COUNT(DISTINCT users.id) AS n")
            ->groupByRaw("COALESCE(r.name, 'sans rôle')")
            ->orderByDesc('n')
            ->pluck('n', 'role')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /** @return array{sauvegarde: ?string, modifiees: int} */
    public function executer(bool $inclureComptes = false): array
    {
        $sauvegarde = [];
        foreach ($this->factices($inclureComptes) as ['table' => $table, 'colonne' => $colonne, 'domaines' => $domaines]) {
            foreach ($this->inventaire->lignes($table, $colonne, $domaines) as $r) {
                $sauvegarde[] = ['table' => $table, 'id' => (int) $r->id, 'colonne' => $colonne, 'ancienne_valeur' => (string) $r->email];
            }
        }

        if ($sauvegarde === []) {
            return ['sauvegarde' => null, 'modifiees' => 0];
        }

        $chemin = $this->sauvegarder($sauvegarde);

        $modifiees = DB::transaction(function () use ($sauvegarde) {
            $n = 0;
            foreach (collect($sauvegarde)->groupBy(fn ($l) => $l['table'].'.'.$l['colonne']) as $groupe) {
                $premier = $groupe->first();
                foreach ($groupe->pluck('id')->chunk(500) as $ids) {
                    $n += DB::table($premier['table'])->whereIn('id', $ids->all())->update([$premier['colonne'] => null]);
                }
            }

            return $n;
        });

        return ['sauvegarde' => $chemin, 'modifiees' => $modifiees];
    }

    /** @return list<array{table: string, colonne: string, domaines: list<string>}> */
    private function factices(bool $inclureComptes): array
    {
        $parColonne = [];
        foreach ($this->inventaire->domaines() as $ligne) {
            if (! $inclureComptes && $ligne['table'] === self::TABLE_COMPTES) {
                continue;
            }
            if ($this->classement->classerDomaine($ligne['domaine'], false)->etat === EtatEmail::Factice) {
                $cle = $ligne['table'].'.'.$ligne['colonne'];
                $parColonne[$cle] ??= ['table' => $ligne['table'], 'colonne' => $ligne['colonne'], 'domaines' => []];
                $parColonne[$cle]['domaines'][] = $ligne['domaine'];
            }
        }

        return array_values($parColonne);
    }

    /** @param  list<array<string, mixed>>  $lignes */
    private function sauvegarder(array $lignes): string
    {
        $chemin = 'backups/emails-factices-'.now()->format('Ymd_His').'.json';
        $contenu = json_encode($lignes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Storage::disk('local')->put($chemin, (string) $contenu);

        if (Storage::disk('local')->size($chemin) === 0 || Storage::disk('local')->get($chemin) !== $contenu) {
            throw new RuntimeException('Sauvegarde illisible : aucune adresse modifiee.');
        }

        return Storage::disk('local')->path($chemin);
    }
}

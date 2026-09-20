<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deux recus valides ne peuvent plus porter le meme numero.
 *
 * `genererNumeroRecu()` verrouille sa lecture du dernier numero, et ne la fait
 * plus en ignorant les paiements supprimes. Restait une fenetre : la toute
 * premiere emission d'une annee, ou il n'existe aucune ligne a verrouiller.
 * Seule la base peut la fermer.
 *
 * ## Pourquoi une colonne generee, et pas un simple index unique
 *
 * Un index unique pose directement sur `numero_recu` refuserait aussi les
 * numeros des paiements SUPPRIMES : un numero legitimement reattribue apres
 * une suppression deviendrait impossible, alors que rien ne l'interdit.
 *
 * Et le motif Laravel habituel — un index unique sur `(numero_recu,
 * deleted_at)` — ne garantit RIEN sur MySQL : chaque `NULL` y est considere
 * comme distinct des autres, donc deux recus vivants (tous deux
 * `deleted_at IS NULL`) passeraient sans encombre. C'est le piege exact que
 * cette migration evite.
 *
 * La colonne generee retourne la difficulte : elle vaut le numero tant que le
 * paiement est vivant, et `NULL` des qu'il est supprime. L'unicite s'applique
 * donc aux seuls recus en circulation, et les effaces sortent d'eux-memes du
 * champ de la contrainte — par la tolerance de MySQL aux `NULL` multiples,
 * qui devient ici la propriete recherchee au lieu d'etre l'obstacle.
 *
 * Un `numero_recu` nul sur un paiement vivant reste possible, comme
 * aujourd'hui : la colonne vaut alors `NULL` elle aussi.
 *
 * ## Pourquoi elle echoue plutot que de passer son tour
 *
 * Si des doublons subsistent, la pose de l'index echouerait de toute facon,
 * avec un message d'erreur SQL qui ne dit ni lesquels ni quoi faire. On les
 * cherche donc d'abord, et on s'arrete en les nommant, avec la commande qui
 * les leve. Poser l'index « quand c'est possible » et l'omettre sinon serait
 * pire que tout : les instances se croiraient protegees sans l'etre.
 *
 * Au 14 septembre 2026, les six instances sont a zero doublon.
 */
return new class extends Migration
{
    private const COLONNE = 'recu_en_circulation';
    private const INDEX = 'esbtp_paiements_recu_en_circulation_unique';

    public function up(): void
    {
        if (! Schema::hasTable('esbtp_paiements') || ! Schema::hasColumn('esbtp_paiements', 'numero_recu')) {
            return;
        }

        if (Schema::hasColumn('esbtp_paiements', self::COLONNE)) {
            return;
        }

        $this->refuserSiDesDoublonsSubsistent();

        Schema::table('esbtp_paiements', function (Blueprint $table) {
            // `storedAs` plutot que `virtualAs` : l'index unique sur colonne
            // virtuelle est supporte, mais son comportement a varie selon les
            // versions de MySQL et de MariaDB. La table se compte en milliers
            // de lignes, le cout de stockage ne pese rien face a cette
            // incertitude.
            // 191 caracteres, soit exactement la longueur de `numero_recu`
            // (`Schema::defaultStringLength(191)` dans AppServiceProvider).
            // Plus court, la colonne tronquerait ou refuserait la valeur ; plus
            // long ne servirait a rien. En utf8mb4 la cle tient dans la limite
            // InnoDB de 3072 octets.
            $table->string(self::COLONNE, 191)
                ->nullable()
                ->storedAs('CASE WHEN deleted_at IS NULL THEN numero_recu ELSE NULL END');
        });

        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->unique(self::COLONNE, self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('esbtp_paiements', self::COLONNE)) {
            return;
        }

        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });

        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropColumn(self::COLONNE);
        });
    }

    /**
     * Deux recus valides sous le meme numero, ce sont deux preuves de paiement
     * indiscernables. On ne pose pas une contrainte par-dessus sans le dire.
     */
    private function refuserSiDesDoublonsSubsistent(): void
    {
        $doublons = DB::table('esbtp_paiements')
            ->select('numero_recu', DB::raw('COUNT(*) as occurrences'))
            ->whereNull('deleted_at')
            ->whereNotNull('numero_recu')
            ->where('numero_recu', '!=', '')
            ->groupBy('numero_recu')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('occurrences')
            ->limit(20)
            ->get();

        if ($doublons->isEmpty()) {
            return;
        }

        throw new RuntimeException(sprintf(
            "Des numeros de recu sont encore portes par plusieurs paiements VIVANTS : %s.\n"
            ."L'index unique ne peut pas etre pose tant qu'ils subsistent.\n"
            ."Diagnostic complet   : GET  /api/cli/comptabilite/recus-en-double\n"
            ."Levee de l'ambiguite : POST /api/cli/comptabilite/recus-en-double/renumeroter (dry_run=1 pour simuler)\n"
            ."Si un numero porte deux recus valides, le choix de celui qui le garde revient a la comptabilite.",
            $doublons->map(fn ($d) => $d->numero_recu . ' (x' . $d->occurrences . ')')->implode(', ')
        ));
    }
};

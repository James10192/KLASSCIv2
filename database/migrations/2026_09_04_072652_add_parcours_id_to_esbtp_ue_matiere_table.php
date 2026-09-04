<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Donne au lien « cet element appartient a cette unite » la maquette a laquelle
 * il appartient.
 *
 * Le code d une unite est unique dans l ecole : l unite est donc REELLEMENT
 * partagee entre parcours, jamais dupliquee. Mais ses elements, eux, ne le sont
 * pas forcement : deux parcours qui partagent l unite « Physique 3 » peuvent
 * n avoir en commun que trois de ses cinq matieres. Aujourd hui le pivot ne sait
 * pas le dire — les deux parcours voient le meme sac — et c est ce qui a force,
 * a l import des maquettes de Genie Civil, a renommer cinq elements a la main
 * pour eviter qu un parcours ecrase l autre.
 *
 * Cette migration N ACTIVE RIEN. Elle pose la colonne et l unicite qui vont
 * porter le decoupage ; le defaut la laisse inerte (voir plus bas).
 */
return new class extends Migration
{
    private const INDEX_PAIRE = 'ue_matiere_unique';
    private const INDEX_TRIPLET = 'ue_matiere_parcours_unique';

    /**
     * Chaque etape est gardee pour que la migration se rejoue apres un echec en
     * cours de route. Ce n est pas theorique : la premiere version de ce fichier
     * retirait l ancienne unicite avant de poser la nouvelle et s arretait sur
     * « 1553 » — la colonne, elle, etait deja posee. Sans ces gardes, la reprise
     * apres correctif echouait a son tour, sur « Duplicate column ».
     */
    public function up()
    {
        if (! Schema::hasColumn('esbtp_ue_matiere', 'parcours_id')) {
            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
            // 0 = « commun a toutes les maquettes de l unite », soit exactement ce
            // que le pivot signifie aujourd hui. Toute ligne existante et toute
            // ecriture actuelle prennent donc ce defaut : le comportement livre ne
            // bouge pas d un pouce tant que personne n ecrit autre chose que 0.
            //
            // Pourquoi 0 et pas NULL : sur MySQL comme sur MariaDB, deux NULL sont
            // consideres DISTINCTS dans un index unique. Un « commun » a NULL
            // pourrait donc etre insere autant de fois qu on le demande, sans que
            // l unicite ne bronche — et un element compte deux fois dans une unite,
            // c est sa note ajoutee deux fois a la moyenne et son credit compte
            // deux fois. Avec 0, l unicite mord.
            //
            // Le prix de ce choix : pas de cle etrangere vers esbtp_lmd_parcours,
            // puisque 0 n y designe aucune ligne. C est un prix faible ici : ce
            // parcours est SoftDeletes, donc un ON DELETE CASCADE ne se serait de
            // toute facon jamais declenche a la suppression usuelle.
            $table->unsignedBigInteger('parcours_id')
                ->default(0)
                ->after('matiere_id');

                // Retrouver « les elements reserves a ce parcours » sans balayer la table.
                $table->index('parcours_id', 'ue_matiere_parcours_idx');
            });
        }

        // L unicite passe au triplet : le meme element peut desormais figurer une
        // fois en commun et une fois par maquette qui le reserve.
        //
        // L ORDRE N EST PAS UN DETAIL — poser le nouvel index AVANT de retirer
        // l ancien. `ue_matiere_unique` est le seul index qui couvre
        // `unite_enseignement_id`, et la cle etrangere de cette colonne s en sert :
        // le retirer d abord fait echouer la migration sur
        // « 1553 Cannot drop index: needed in a foreign key constraint », sur
        // chacune des instances. Le triplet commence par la meme colonne, il peut
        // donc prendre le relais — a condition d exister deja.
        if (! $this->indexExiste(self::INDEX_TRIPLET)) {
            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                $table->unique(
                    ['unite_enseignement_id', 'matiere_id', 'parcours_id'],
                    self::INDEX_TRIPLET
                );
            });
        }

        if ($this->indexExiste(self::INDEX_PAIRE)) {
            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_PAIRE);
            });
        }
    }

    /**
     * Laravel 9 n expose pas de `hasIndex()` — on interroge le catalogue.
     */
    private function indexExiste(string $nom): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'esbtp_ue_matiere')
            ->where('INDEX_NAME', $nom)
            ->exists();
    }

    public function down()
    {
        // Revenir en arriere n est possible que si le decoupage n a pas encore
        // servi : deux lignes (unite, matiere) qui ne different que par leur
        // parcours ne peuvent pas tenir sous l ancienne unicite. On refuse alors,
        // en nommant les couples a arbitrer — plutot que d en supprimer un au
        // hasard, ce qui retirerait un element d une maquette sans que personne
        // ne l ait decide.
        $doublons = DB::table('esbtp_ue_matiere')
            ->select('unite_enseignement_id', 'matiere_id', DB::raw('COUNT(*) as total'))
            ->groupBy('unite_enseignement_id', 'matiere_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($doublons->isNotEmpty()) {
            $apercu = $doublons->take(10)
                ->map(fn ($ligne) => "UE #{$ligne->unite_enseignement_id} / matiere #{$ligne->matiere_id}")
                ->implode(', ');

            throw new RuntimeException(
                'Retour arriere impossible : ' . $doublons->count() . ' couple(s) (unite, matiere) '
                . 'existent en plusieurs exemplaires depuis que le decoupage par parcours est actif. '
                . 'Choisissez la ligne a garder pour chacun avant de rejouer ce down. Apercu : ' . $apercu
            );
        }

        // Meme precaution qu a l aller, en sens inverse : l ancienne unicite est
        // reposee AVANT de retirer le triplet, sinon la cle etrangere de
        // `unite_enseignement_id` se retrouve un instant sans index et MySQL
        // refuse (1553).
        if (! $this->indexExiste(self::INDEX_PAIRE)) {
            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                $table->unique(['unite_enseignement_id', 'matiere_id'], self::INDEX_PAIRE);
            });
        }

        if ($this->indexExiste(self::INDEX_TRIPLET)) {
            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_TRIPLET);
            });
        }

        if (Schema::hasColumn('esbtp_ue_matiere', 'parcours_id')) {
            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                $table->dropIndex('ue_matiere_parcours_idx');
                $table->dropColumn('parcours_id');
            });
        }
    }
};

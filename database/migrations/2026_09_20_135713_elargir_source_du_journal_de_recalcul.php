<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `esbtp_resultats_recompute_log.source` etait un `enum('observer','command','manual')`.
 *
 * Toute source posee hors de cette liste fait LEVER l'INSERT — Laravel active
 * `STRICT_TRANS_TABLES` (`config/database.php`) — et `writeAuditLog()` avale
 * l'exception dans un `catch (\Throwable)` avec un simple avertissement. Le
 * recalcul se declare alors reussi, et sa trace n'existe pas.
 *
 * Mesure le 20 septembre 2026 : la suite de tests produisait 53 « audit log
 * write failed » pour 82 recalculs de source `deplacement` et 12 de source
 * `cli`. Zero ligne ecrite, aucune erreur visible.
 *
 * La colonne devient un `string(30)`, comme `cash_counts.mode_paiement` :
 * ajouter une source ne demandera plus ni `ALTER` d'enumeration ni verrou de
 * table sur les huit instances. La liste connue reste documentee ici et dans
 * le job, mais elle n'est plus une contrainte qui echoue en silence.
 */
return new class extends Migration
{
    public function up(): void
    {
        // `change()` sur un enum demande doctrine/dbal, absent du projet.
        DB::statement("ALTER TABLE esbtp_resultats_recompute_log MODIFY source VARCHAR(30) NOT NULL DEFAULT 'observer'");
    }

    /**
     * Volontairement sans effet.
     *
     * Une premiere version ramenait a `'manual'` toute source hors de
     * l'enumeration d'origine, pour que l'`ALTER` inverse passe. Elle **faisait
     * mentir un journal d'audit** : la seule raison d'etre de cette table est de
     * repondre a « quand cette moyenne a-t-elle change, et QUI l'a declenche ? »,
     * et c'est la garantie meme qu'on invoque pour autoriser un endpoint a
     * ecraser des moyennes saisies a la main. Ecrire « manual » sur un recalcul
     * automatique, c'est attribuer a une personne un geste qu'elle n'a pas fait.
     *
     * Le retour arriere n'est donc pas possible sans perdre une donnee
     * forensique. On ne le simule pas : on ne fait rien, et on le dit. Un
     * `string(30)` qui reste large ne casse rien — l'enum d'origine n'etait pas
     * une garantie metier, c'etait un piege qui echouait en silence.
     */
    public function down(): void
    {
        // Rien. Voir le docbloc ci-dessus.
    }
};

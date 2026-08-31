<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * « Transfert » n'est pas un type d'inscription, c'est une provenance.
 *
 * On s'inscrit chez nous pour la premiere fois, ou on se reinscrit. Venir
 * d'ailleurs se superpose au premier cas — cela ne le remplace pas. La colonne
 * portait pourtant les trois valeurs sur le meme rang depuis la creation de la
 * table, et le code s'est construit autour du modele JUSTE tout en laissant la
 * troisieme valeur choisissable.
 *
 * Le resultat etait une contradiction visible a l'ecran : le bloc « transfert »
 * du formulaire d'edition ne s'affiche que si le type vaut
 * « premiere_inscription » (edit-form.blade.php), et ESBTPInscriptionController
 * remet `est_transfert` a false pour tout autre type. Choisir « Transfert »
 * dans la liste EFFACAIT donc le drapeau de transfert et cachait le champ de
 * l'etablissement d'origine. L'option la plus explicite produisait le contraire
 * de ce qu'elle annoncait.
 *
 * Cette migration remet les lignes concernees dans le modele juste, puis
 * retire la valeur de l'enum pour que personne ne la reintroduise.
 */
return new class extends Migration
{
    private const CIBLES = ['première_inscription', 'réinscription'];

    public function up(): void
    {
        if (! Schema::hasTable('esbtp_inscriptions')) {
            return;
        }

        // 1. Les lignes « transfert » deviennent ce qu'elles ont toujours ete :
        //    une premiere inscription, faite par quelqu'un qui vient d'ailleurs.
        //    Le drapeau qu'on leur pose n'invente rien — il rend enfin lisible
        //    l'information que le type portait a sa place.
        $converties = DB::table('esbtp_inscriptions')
            ->where('type_inscription', 'transfert')
            ->update([
                'type_inscription' => 'première_inscription',
                'est_transfert' => true,
            ]);

        if ($converties > 0) {
            Log::info('[migration] type_inscription=transfert converti en première_inscription + est_transfert', [
                'lignes' => $converties,
            ]);
        }

        // 2. Retrecir l'enum, mais SEULEMENT si plus rien ne depasse.
        //
        //    Le code lit partout `reinscription` ET `réinscription` : quelqu'un
        //    a deja vu passer la forme sans accent. Retrecir sans regarder
        //    transformerait ces lignes en chaine vide sur un MySQL permissif,
        //    ou ferait echouer le deploiement sur un MySQL strict — pour une
        //    valeur qui n'a rien a voir avec « transfert ». Ce n'est pas le
        //    sujet de cette migration, donc on ne le tranche pas ici : on
        //    signale et on laisse l'enum large. La correction du modele (etape 1)
        //    est acquise dans tous les cas.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $intruses = DB::table('esbtp_inscriptions')
            ->whereNotIn('type_inscription', self::CIBLES)
            ->count();

        if ($intruses > 0) {
            Log::warning('[migration] enum type_inscription laisse large : valeurs hors modele presentes', [
                'lignes_hors_modele' => $intruses,
                'valeurs_attendues' => self::CIBLES,
            ]);

            return;
        }

        DB::statement(
            "ALTER TABLE `esbtp_inscriptions`
             MODIFY `type_inscription` ENUM('première_inscription', 'réinscription')
             NOT NULL DEFAULT 'première_inscription'"
        );
    }

    /**
     * On rouvre l'enum, on ne defait pas la conversion.
     *
     * Remettre « transfert » sur les lignes qui portent `est_transfert` serait
     * faux : la plupart d'entre elles ont toujours ete des premieres
     * inscriptions correctement saisies, et rien ne distingue apres coup celles
     * qui venaient de l'ancienne valeur. Rendre la valeur de nouveau
     * choisissable suffit a annuler ce que cette migration empeche.
     */
    public function down(): void
    {
        if (! Schema::hasTable('esbtp_inscriptions') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `esbtp_inscriptions`
             MODIFY `type_inscription` ENUM('première_inscription', 'réinscription', 'transfert')
             NOT NULL DEFAULT 'première_inscription'"
        );
    }
};

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
 * Cette migration remet les lignes concernees dans le modele juste. Elle ne
 * touche PAS a l'enum, et c'est deliberé : le retrecir demanderait un ALTER
 * TABLE sur esbtp_inscriptions — plusieurs milliers de lignes sur les deux
 * instances Elite — pour n'interdire qu'une ecriture SQL directe. Le
 * formulaire n'offre plus l'option et ESBTPInscriptionController ne l'accepte
 * plus : la valeur est deja hors d'atteinte par tous les chemins reels. Un
 * verrou de table sur la production, plus un schema qui diverge d'un tenant a
 * l'autre selon ce que l'ALTER aurait accepte, coutent plus que ce qu'ils
 * achetent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('esbtp_inscriptions')) {
            return;
        }

        // Les lignes « transfert » deviennent ce qu'elles ont toujours ete :
        // une premiere inscription, faite par quelqu'un qui vient d'ailleurs.
        // Le drapeau qu'on leur pose n'invente rien — il rend enfin lisible
        // l'information que le type portait a sa place.
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
    }

    /**
     * Rien a defaire.
     *
     * Remettre « transfert » sur les lignes qui portent `est_transfert` serait
     * faux : la plupart d'entre elles ont toujours ete des premieres
     * inscriptions correctement saisies, et rien ne distingue apres coup celles
     * qui venaient de l'ancienne valeur. L'enum n'ayant pas ete touche, la
     * valeur reste techniquement ecrivable : annuler cette migration n'a donc
     * rien a restaurer.
     */
    public function down(): void
    {
        //
    }
};

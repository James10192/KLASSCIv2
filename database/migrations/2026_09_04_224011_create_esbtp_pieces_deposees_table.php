<?php

use App\Enums\EtatPieceDossier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le dépôt : ce qu'un étudiant a réellement remis, une fois.
 *
 * La ligne est ancrée sur l'ÉTUDIANT, pas sur l'année. Un extrait de naissance,
 * des photos d'identité, un diplôme sont déposés une fois et durent ; ce qui est
 * propre à l'année, c'est ce qu'une inscription en consomme, et cela vit dans
 * `esbtp_inscription_pieces`.
 *
 * Une version précédente enseignait l'inverse en toutes lettres — « l'école
 * reprend un exemplaire chaque année, un licence 3 a donc trois lignes extrait
 * de naissance, et c'est voulu ». C'était faux, et c'est rappelé ici pour que
 * personne ne le rétablisse en croyant réparer.
 *
 * UNE LIGNE = UN DÉPÔT, et il n'y a volontairement AUCUNE contrainte d'unicité
 * sur (étudiant, pièce). Deux dépôts du même extrait de naissance, l'un délivré
 * en 2019 et l'autre en 2026, sont deux lignes : leur validité ne court pas
 * depuis la même date, donc les fondre en une seule ligne à quantité cumulée
 * rendrait la péremption incalculable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_pieces_deposees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('etudiant_id')
                ->constrained('esbtp_etudiants')
                ->cascadeOnDelete();

            // RESTRICT, et pas cascade : retirer une pièce du catalogue ne doit
            // pas effacer la trace de ce que des étudiants ont déjà remis. Le
            // catalogue se désactive (`is_active`), il ne se vide pas.
            $table->foreignId('piece_dossier_id')
                ->constrained('esbtp_pieces_dossier')
                ->restrictOnDelete();

            // LE CONTEXTE du dépôt : à quel guichet, c'est-à-dire à quelle
            // rentrée, l'étudiant a remis ce papier. Ce n'est pas la même chose
            // que « l'année à laquelle il appartient ».
            //
            // Pour une pièce ANNUELLE, les deux coïncident, et le calcul ne
            // retient que les dépôts de l'année en cours. Pour une pièce qui
            // DURE, le calcul les ignore tous — un extrait remis en première
            // année vaut encore en troisième. La colonne sert alors uniquement à
            // savoir quel geste décocher : sans elle, retirer une coche en
            // troisième année effacerait le dépôt de la première.
            //
            // `nullOnDelete` et non cascade : KLASSCI supprime définitivement des
            // inscriptions. En cascade, la suppression évaporerait le dépôt
            // lui-même, alors que le papier, lui, est toujours dans le classeur.
            $table->foreignId('inscription_id')
                ->nullable()
                ->constrained('esbtp_inscriptions')
                ->nullOnDelete();

            $table->unsignedSmallInteger('quantite_deposee')->default(1);

            $table->string('etat', 20)->default(EtatPieceDossier::ATTENDUE->value);

            // Obligatoire pour un refus, libre ailleurs. La contrainte en base,
            // plus bas, est ce qui le tient réellement.
            $table->text('motif')->nullable();

            $table->foreignId('decidee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decidee_at')->nullable();

            // LA VALIDITÉ COURT DEPUIS LA DÉLIVRANCE, PAS DEPUIS LE DÉPÔT. Un
            // extrait délivré en 2019 et remis en 2026 est déjà périmé sous une
            // validité de trois mois. Quand cette date est nulle, on retombe sur
            // la date de dépôt, faute de mieux.
            $table->date('date_delivrance')->nullable();
            $table->date('date_depot')->nullable();

            // Le fichier vit ici, et une seule fois. Le téléversement reste
            // FACULTATIF : « déposée » n'exige aucun fichier, et une école qui ne
            // numérise rien doit pouvoir suivre ses dossiers de bout en bout. Le
            // scan évite le trajet jusqu'au classeur, il ne remplace pas
            // l'original, qui reste la pièce qui fait foi.
            $table->foreignId('document_id')
                ->nullable()
                ->constrained('esbtp_etudiant_documents')
                ->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['etudiant_id', 'piece_dossier_id'], 'pieces_deposees_dossier_idx');
            $table->index(['etat'], 'pieces_deposees_etat_idx');
        });

        $this->poserLesGardes();
    }

    public function down(): void
    {
        $this->retirerLesGardes();

        Schema::dropIfExists('esbtp_pieces_deposees');
    }

    /**
     * Deux contraintes, et pas seulement la seconde.
     *
     * La garde du motif dans un hook `saving` du modèle ne tient pas : une
     * écriture de masse — `->where(...)->update(['etat' => 'refusee'])` —
     * n'instancie aucun modèle, ne déclenche aucun événement, et pose un refus
     * sans motif. Une commande, un import ou une reprise de données
     * contourneraient exactement la règle qu'ils doivent respecter.
     *
     * Et la garde du motif ne vaut que si le vocabulaire des états est clos :
     * sans la première contrainte, une écriture posant « REFUSEE » ou « refuse »
     * passerait à côté de la seconde sans rien déclencher.
     *
     * Contrepartie assumée : ajouter un état exigera une migration. C'est le
     * prix d'une règle que le moteur applique, et c'est le bon prix pour une
     * règle qui décide si le dossier d'un étudiant peut être débloqué.
     */
    private function poserLesGardes(): void
    {
        if (! $this->moteurAppliqueLesCheck()) {
            return;
        }

        $etats = collect(EtatPieceDossier::values())
            ->map(fn (string $v) => "'".$v."'")
            ->implode(', ');

        DB::statement("
            ALTER TABLE esbtp_pieces_deposees
            ADD CONSTRAINT esbtp_pieces_deposees_etat_connu
            CHECK (etat IN ({$etats}))
        ");

        DB::statement("
            ALTER TABLE esbtp_pieces_deposees
            ADD CONSTRAINT esbtp_pieces_deposees_refus_motive
            CHECK (etat <> 'refusee' OR (motif IS NOT NULL AND TRIM(motif) <> ''))
        ");
    }

    /**
     * Le `drop` de la table les emporte de toute façon. La ligne explicite sert
     * le jour où un `down()` conservera la table.
     */
    private function retirerLesGardes(): void
    {
        if (! $this->moteurAppliqueLesCheck() || ! Schema::hasTable('esbtp_pieces_deposees')) {
            return;
        }

        foreach (['esbtp_pieces_deposees_refus_motive', 'esbtp_pieces_deposees_etat_connu'] as $nom) {
            try {
                DB::statement("ALTER TABLE esbtp_pieces_deposees DROP CONSTRAINT {$nom}");
            } catch (\Throwable $e) {
                // Absente : le down doit rester rejouable.
            }
        }
    }

    /**
     * MySQL n'applique les CHECK qu'à partir de 8.0.16 — avant, il les acceptait
     * et les ignorait, ce qui est pire qu'une erreur. MariaDB les applique depuis
     * 10.2. SQLite ne connaît pas `ALTER TABLE ... ADD CONSTRAINT`.
     */
    private function moteurAppliqueLesCheck(): bool
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        $version = (string) DB::selectOne('SELECT VERSION() AS v')->v;

        if (stripos($version, 'mariadb') !== false) {
            return version_compare($this->numeroDeVersion($version), '10.2.0', '>=');
        }

        return version_compare($this->numeroDeVersion($version), '8.0.16', '>=');
    }

    private function numeroDeVersion(string $version): string
    {
        preg_match('/(\d+\.\d+\.\d+)/', $version, $trouve);

        return $trouve[1] ?? '0.0.0';
    }
};

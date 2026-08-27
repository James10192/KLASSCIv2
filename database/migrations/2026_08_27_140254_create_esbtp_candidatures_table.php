<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Candidature deposee depuis le portail public par un NOUVEL eleve.
 *
 * Le pendant de esbtp_reinscription_demandes pour ceux qui n'ont pas encore de
 * dossier : les nouveaux bacheliers. Une candidature est inerte au meme titre
 * qu'une demande de reinscription — elle ne cree ni etudiant, ni inscription,
 * ni frais. C'est l'ecole qui la convertit, et c'est cette separation qui rend
 * un canal public acceptable.
 *
 * Difference de nature avec la reinscription, qui explique la forme de cette
 * table : il n'y a rien a retrouver. Personne a identifier, donc aucun risque
 * d'enumeration — mais en echange, n'importe qui peut deposer. Le risque
 * bascule de la fuite d'information vers le remplissage abusif, d'ou l'index
 * sur le telephone et l'empreinte d'adresse, qui permettent a l'ecole de voir
 * les doublons au lieu de les subir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_candidatures', function (Blueprint $table) {
            $table->id();

            // Identite. Volontairement le strict minimum : le dossier complet
            // se constitue a l'ecole, avec les pieces. Ici on veut de quoi
            // rappeler la famille et preparer le dossier, rien de plus.
            $table->string('nom', 100);
            $table->string('prenoms', 150);
            $table->date('date_naissance');
            $table->enum('sexe', ['M', 'F'])->nullable();

            $table->string('telephone', 30);
            $table->string('email', 150)->nullable();

            // Voeu d'orientation. Nullable et double : soit le candidat choisit
            // dans la liste que l'ecole publie, soit il ecrit ce qu'il veut —
            // un bachelier ne connait pas toujours le nom exact d'une filiere.
            $table->foreignId('filiere_id')->nullable()
                ->constrained('esbtp_filieres')->nullOnDelete();
            $table->foreignId('niveau_id')->nullable()
                ->constrained('esbtp_niveau_etudes')->nullOnDelete();
            $table->string('voeu_libre', 255)->nullable();

            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires')->cascadeOnDelete();

            // Serie et etablissement d'origine : ce que l'ecole regarde en
            // premier pour juger une candidature de bachelier.
            $table->string('serie_bac', 60)->nullable();
            $table->string('etablissement_origine', 150)->nullable();
            $table->year('annee_bac')->nullable();

            $table->text('message')->nullable();

            $table->enum('statut', ['en_attente', 'acceptee', 'rejetee', 'convertie'])
                ->default('en_attente');

            $table->timestamp('consentement_at');
            $table->string('ip_hash', 64)->nullable();

            $table->text('motif_rejet')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_at')->nullable();

            // L'etudiant ne du traitement de la candidature, s'il a ete cree.
            $table->foreignId('etudiant_id')->nullable()
                ->constrained('esbtp_etudiants')->nullOnDelete();
            $table->foreignId('inscription_id')->nullable()
                ->constrained('esbtp_inscriptions')->nullOnDelete();

            $table->timestamps();

            // Pas de suppression douce, pour la meme raison que les demandes de
            // reinscription : une candidature archivee resterait invisible aux
            // requetes tout en occupant sa place dans les index.

            // Le doublon est ici la nuisance principale : une famille qui
            // renvoie le formulaire, ou un candidat qui hesite. On l'evite au
            // depot plutot que de le laisser encombrer la corbeille.
            $table->unique(
                ['telephone', 'annee_universitaire_id'],
                'unique_candidature_telephone_annee'
            );

            $table->index(['statut', 'created_at'], 'idx_candidatures_statut_date');
            $table->index('ip_hash', 'idx_candidatures_ip');
        });

        $this->semerLesReglages();
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_candidatures');

        DB::table('settings')->whereIn('key', [
            'inscriptions.en_ligne.enabled',
        ])->delete();
    }

    /**
     * Le canal des nouvelles inscriptions a son propre interrupteur.
     *
     * Une ecole peut vouloir ouvrir la reinscription de ses eleves sans ouvrir
     * les candidatures exterieures, ou l'inverse. Les fenetres de dates, elles,
     * restent communes : c'est la meme saison.
     */
    private function semerLesReglages(): void
    {
        if (DB::table('settings')->where('key', 'inscriptions.en_ligne.enabled')->exists()) {
            return;
        }

        // Cle etrangere settings.created_by en ON DELETE SET NULL : coder « 1 »
        // en dur casserait la suite de tests sur une base fraiche.
        $createur = DB::table('users')->min('id');

        DB::table('settings')->insert([
            'key' => 'inscriptions.en_ligne.enabled',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'scolarite',
            'category' => 'scolarite',
            'default_value' => '0',
            'description' => "Ouvre les candidatures des NOUVEAUX eleves depuis klassci.com. Independant de la reinscription. Desactive par defaut.",
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'sort_order' => 161,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};

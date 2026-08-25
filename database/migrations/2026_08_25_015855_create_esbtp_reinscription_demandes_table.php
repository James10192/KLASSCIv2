<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes de reinscription deposees depuis le portail public.
 *
 * Une demande n'est PAS une inscription. Elle est inerte : elle n'ouvre aucun
 * droit, ne genere aucun frais, ne compte dans aucun effectif. Seule la
 * scolarite la convertit, et cette conversion passe par le flux canonique
 * (ReeinscriptionService::effectuerReinscription) qui gere deja les frais, les
 * reliquats et la cloture de l'inscription precedente.
 *
 * Cette separation est ce qui rend le canal public acceptable : la surface
 * exposee ne peut rien creer d'engageant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_reinscription_demandes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->cascadeOnDelete();
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->cascadeOnDelete();

            // Classe proposee au moment du depot. Indicative : la scolarite
            // tranche a la conversion, l'etudiant ne choisit pas sa classe.
            $table->foreignId('classe_souhaitee_id')->nullable()
                ->constrained('esbtp_classes')->nullOnDelete();

            $table->enum('statut', ['en_attente', 'rejetee', 'convertie'])
                ->default('en_attente');

            // Trace du consentement : obligation de la loi ivoirienne 2013-450.
            $table->timestamp('consentement_at');

            // Empreinte de l'adresse, jamais l'adresse elle-meme : elle suffit a
            // reperer un abus sans conserver de donnee identifiante (minimisation).
            $table->string('ip_hash', 64)->nullable();

            $table->text('motif_rejet')->nullable();

            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_at')->nullable();

            // Inscription nee de la conversion, quand elle a eu lieu.
            $table->foreignId('inscription_id')->nullable()
                ->constrained('esbtp_inscriptions')->nullOnDelete();

            $table->timestamps();

            // Pas de suppression douce, volontairement : l'index unique
            // ci-dessous ne porte pas sur `deleted_at`. Une demande archivee
            // resterait invisible aux requetes tout en continuant a bloquer
            // l'insertion, condamnant cet etudiant a une erreur definitive.
            // Une demande inerte n'a d'ailleurs rien a archiver — le statut
            // « rejetee » couvre deja le besoin de garder une trace.

            // Une seule demande par etudiant et par annee : un double clic ou un
            // rejeu de requete ne doit pas encombrer la corbeille de la scolarite.
            $table->unique(['etudiant_id', 'annee_universitaire_id'], 'unique_demande_etudiant_annee');

            // La corbeille filtre sur le statut et trie par date de depot.
            $table->index(['statut', 'created_at'], 'idx_demandes_statut_date');
        });

        $this->seedReglages();
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_column($this->reglages(), 'key'))->delete();

        Schema::dropIfExists('esbtp_reinscription_demandes');
    }

    /**
     * Reglages du portail, seedes ici plutot que dans le contrôleur des
     * parametres : c'est le patron du depot (voir la migration des reglages de
     * reconciliation), et cela evite d'ajouter un effet de bord d'ecriture au
     * chemin `update()` d'un contrôleur qui frole deja les 1800 lignes.
     *
     * Le canal est ferme par defaut : chaque ecole decide de l'ouvrir, et sur
     * quelle periode. Rien n'est code en dur cote application.
     */
    private function seedReglages(): void
    {
        $lignes = $this->reglages();

        $existants = DB::table('settings')
            ->whereIn('key', array_column($lignes, 'key'))
            ->pluck('key')
            ->all();

        $aInserer = array_values(array_filter(
            $lignes,
            fn ($ligne) => ! in_array($ligne['key'], $existants, true)
        ));

        if ($aInserer !== []) {
            DB::table('settings')->insert($aInserer);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function reglages(): array
    {
        // Premier utilisateur existant, null sur une base vide : la cle
        // etrangere settings.created_by est ON DELETE SET NULL. Coder « 1 » en
        // dur casserait toute la suite de tests sur une base fraiche.
        $createur = DB::table('users')->min('id');

        $commun = [
            'group' => 'scolarite',
            'category' => 'scolarite',
            'is_required' => 0,
            'validation_rules' => null,
            'is_active' => 1,
            'created_by' => $createur,
            'updated_by' => $createur,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return [
            $commun + [
                'key' => 'reinscriptions.en_ligne.enabled',
                'value' => '0',
                'type' => 'boolean',
                'default_value' => '0',
                'description' => "Ouvre la reinscription en ligne depuis klassci.com. Desactive par defaut : chaque ecole choisit d'ouvrir ce canal.",
                'sort_order' => 157,
            ],
            $commun + [
                'key' => 'reinscriptions.en_ligne.ouverture',
                'value' => '',
                'type' => 'string',
                'default_value' => '',
                'description' => "Premier jour ou le portail accepte les demandes. Vide = des l'activation.",
                'sort_order' => 158,
            ],
            $commun + [
                'key' => 'reinscriptions.en_ligne.fermeture',
                'value' => '',
                'type' => 'string',
                'default_value' => '',
                'description' => 'Dernier jour ou le portail accepte les demandes. Vide = pas de date de fin.',
                'sort_order' => 159,
            ],
        ];
    }
};

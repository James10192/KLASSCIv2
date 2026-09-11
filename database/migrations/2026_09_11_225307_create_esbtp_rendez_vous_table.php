<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rendez-vous de guichet pris depuis le portail public.
 *
 * Deposer en ligne ne finit rien : les pieces et le paiement se remettent sur
 * place. Sans creneau, les huit cents familles d'une rentree se presentent le
 * meme matin. Cette table est ce qui etale cette file.
 *
 * Elle sert les DEUX canaux — la reinscription d'un eleve connu et la
 * candidature d'un nouveau — d'ou deux cles etrangeres nullables plutot qu'une
 * relation polymorphe. Le choix n'est pas stylistique : une colonne de type
 * polymorphe ne porte aucune cle etrangere, et ces deux tables parentes
 * disparaissent EN CASCADE avec leur annee universitaire, sans suppression
 * douce. Une reservation orpheline resterait alors sur un creneau qu'elle
 * occuperait encore, pointant vers une ligne qui n'existe plus.
 *
 * Deux colonnes meritent leur justification, parce qu'elles ressemblent a de la
 * duplication et n'en sont pas.
 *
 * `debut_at` / `fin_at` sont FIGES a la reservation, jamais recalcules depuis
 * la configuration. C'est ce qui protege les familles le jour ou l'ecole decale
 * son guichet de 8h-12h a 9h-13h en pleine campagne : leurs rendez-vous ne
 * bougent pas. Un rendez-vous qui ne porterait qu'un indice de creneau se
 * deplacerait avec le reglage, en silence, apres que le message annoncant
 * l'heure est parti.
 *
 * `nom`, `prenoms` et `telephone` sont un instantane de l'identite au moment de
 * la reservation. Les lire a travers la cle etrangere serait plus propre en
 * apparence — sauf qu'un redepot REECRIT la ligne parente : rouvrir une
 * candidature rejetee y remet l'etat civil du formulaire. Le rendez-vous
 * changerait donc de titulaire tout seul, et l'ecole appellerait quelqu'un
 * d'autre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_rendez_vous', function (Blueprint $table) {
            $table->id();

            // La reference que la famille recoit et qu'elle redonne pour
            // consulter, deplacer ou annuler. Opaque et tiree au hasard : le
            // matricule ne peut pas servir ici, il est deja le sujet d'un
            // compteur anti-enumeration cote portail, et l'employer comme cle
            // de consultation rouvrirait l'oracle que ce compteur ferme.
            $table->string('reference', 32)->unique();

            // Exactement une des deux est renseignee. La contrainte est posee
            // plus bas, dans la base : un seul gardien pour cette porte.
            $table->foreignId('reinscription_demande_id')->nullable()
                ->constrained('esbtp_reinscription_demandes')->cascadeOnDelete();
            $table->foreignId('candidature_id')->nullable()
                ->constrained('esbtp_candidatures')->cascadeOnDelete();

            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires')->cascadeOnDelete();

            // Instantane fige de l'identite : voir l'en-tete.
            $table->string('nom', 100);
            $table->string('prenoms', 150);
            $table->string('telephone', 30);
            $table->string('email', 150)->nullable();

            // Le creneau, fige : voir l'en-tete.
            $table->dateTime('debut_at');
            $table->dateTime('fin_at');

            $table->string('statut', 20)->default('reserve');

            // Pointage au guichet. Vides tant que personne ne s'est presente —
            // et ce sont eux qui feront exister, a terme, la seule mesure du
            // temps reellement passe par famille. Aucun horodatage du depot ne
            // la donne aujourd'hui : `traite_at` est pose AVANT le travail,
            // pour reserver la ligne contre le double-clic, et il est remis a
            // null au moindre redepot.
            $table->timestamp('arrive_at')->nullable();
            $table->timestamp('termine_at')->nullable();
            $table->foreignId('pointe_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('annule_at')->nullable();
            $table->string('annule_motif', 255)->nullable();

            // Empreinte de l'adresse, jamais l'adresse : de quoi reperer un abus
            // sans conserver de donnee identifiante. Meme discipline que les
            // deux tables du portail.
            $table->string('ip_hash', 64)->nullable();

            $table->timestamps();

            // Pas de suppression douce, pour la raison deja retenue sur les deux
            // tables du portail : les index uniques ci-dessous ne porteraient pas
            // sur `deleted_at`, et un rendez-vous archive resterait invisible aux
            // requetes tout en continuant de bloquer l'insertion. Une annulation
            // se dit par le statut.

            // Un seul rendez-vous par dossier. Deplacer un rendez-vous met la
            // ligne a jour, il n'en cree pas une seconde — sans quoi le premier
            // creneau resterait occupe et le guichet attendrait quelqu'un qui a
            // change d'heure. MySQL admet autant de NULL que voulu dans un index
            // unique, donc l'autre canal n'est jamais gene.
            $table->unique('reinscription_demande_id', 'unique_rdv_demande');
            $table->unique('candidature_id', 'unique_rdv_candidature');

            // Le comptage d'occupation d'un creneau et la liste du jour, les
            // deux seules requetes chaudes.
            $table->index(['debut_at', 'statut'], 'idx_rdv_creneau_statut');

            // La recherche par telephone au guichet, quand la famille a perdu sa
            // reference — cas courant et sans gravite, l'agent a la personne en
            // face de lui.
            $table->index(['telephone', 'annee_universitaire_id'], 'idx_rdv_telephone_annee');
        });

        $this->poserLaContrainteDeCanalUnique();
        $this->semerLesReglages();
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_column($this->reglages(), 'key'))->delete();

        Schema::dropIfExists('esbtp_rendez_vous');
    }

    /**
     * Un rendez-vous appartient a UN dossier, jamais a zero ni a deux.
     *
     * Dans la base et non dans un observateur de modele. La regle a un seul
     * endroit ou l'oublier est impossible : toute ecriture y passe, y compris
     * celles qu'on ecrira dans deux ans sans relire ce fichier. Le depot a paye
     * l'inverse — deux gardiens d'une meme porte finissent par apprendre la
     * consigne separement.
     *
     * Seul MySQL la recoit. SQLite ignore silencieusement un ALTER TABLE de ce
     * type, et poser une contrainte qu'on croit active sans qu'elle le soit est
     * pire que ne pas en poser.
     */
    private function poserLaContrainteDeCanalUnique(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE esbtp_rendez_vous ADD CONSTRAINT chk_rdv_un_seul_canal CHECK ('
            .'(reinscription_demande_id IS NULL) <> (candidature_id IS NULL)'
            .')'
        );
    }

    private function semerLesReglages(): void
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
     * Les reglages du guichet.
     *
     * Le premier jour de reception n'est PAS ici : c'est
     * `inscriptions.physiques.debut`, qui existe deja et que le portail publie
     * pour repondre a « je viens quand ? ». Le doubler donnerait deux dates
     * pour une seule rentree, et la famille lirait deux instructions
     * contradictoires sur le meme ecran.
     *
     * Le nombre de personnes recues dans la journee n'est pas ici non plus, et
     * pour une raison de fond : les heures et la duree le determinent deja. Ce
     * que l'ecole declare, c'est `capacite_par_creneau` — combien de familles a
     * la fois — et son nombre par jour lui est RENDU, calcule sous les champs.
     * Une ecole qui annoncait « soixante par jour » sur une plage qui n'en
     * produit que vingt-huit ne se trompait pas : elle decrivait plusieurs
     * guichets en parallele sans le dire. C'est ce chiffre-la qu'on lui demande.
     *
     * Le canal est ferme par defaut, comme les deux autres. Les valeurs semees
     * decrivent une journee plausible pour que l'ecole ait quelque chose a
     * ajuster plutot qu'un formulaire vide — elles ne publient rien tant que
     * l'interrupteur n'est pas mis.
     *
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
                'key' => 'inscriptions.rdv.enabled',
                'value' => '0',
                'type' => 'boolean',
                'default_value' => '0',
                'description' => "Propose un rendez-vous aux familles apres leur depot en ligne, pour etaler la file au guichet. Desactive par defaut.",
                'sort_order' => 163,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.dernier_jour',
                'value' => '',
                'type' => 'string',
                'default_value' => '',
                'description' => "Dernier jour de reception au guichet (AAAA-MM-JJ). Le premier jour est celui des inscriptions physiques. Sans cette date, aucun rendez-vous n'est propose.",
                'sort_order' => 164,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.jours_ouverts',
                'value' => '1,2,3,4,5',
                'type' => 'string',
                'default_value' => '1,2,3,4,5',
                'description' => "Jours de la semaine ou le guichet recoit : 1 pour lundi jusqu'a 7 pour dimanche, separes par des virgules.",
                'sort_order' => 165,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.heure_ouverture',
                'value' => '08:00',
                'type' => 'string',
                'default_value' => '08:00',
                'description' => "Heure a laquelle le guichet commence a recevoir (HH:MM).",
                'sort_order' => 166,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.heure_fermeture',
                'value' => '16:00',
                'type' => 'string',
                'default_value' => '16:00',
                'description' => "Heure a laquelle le guichet cesse de recevoir (HH:MM).",
                'sort_order' => 167,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.pause_debut',
                'value' => '12:00',
                'type' => 'string',
                'default_value' => '12:00',
                'description' => "Debut de la pause (HH:MM). Laisser vide, avec la fin, pour une journee continue.",
                'sort_order' => 168,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.pause_fin',
                'value' => '13:00',
                'type' => 'string',
                'default_value' => '13:00',
                'description' => "Fin de la pause (HH:MM). Laisser vide, avec le debut, pour une journee continue.",
                'sort_order' => 169,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.duree_minutes',
                'value' => '15',
                'type' => 'integer',
                'default_value' => '15',
                'description' => "Temps approximatif passe avec une famille, en minutes. Determine le nombre de creneaux de la journee.",
                'sort_order' => 170,
            ],
            $commun + [
                'key' => 'inscriptions.rdv.capacite_par_creneau',
                'value' => '1',
                'type' => 'integer',
                'default_value' => '1',
                'description' => "Combien de familles peuvent etre recues en meme temps, autrement dit combien de guichets sont tenus en parallele. Le nombre de personnes recues dans la journee en decoule et s'affiche sous les champs.",
                'sort_order' => 171,
            ],
        ];
    }
};

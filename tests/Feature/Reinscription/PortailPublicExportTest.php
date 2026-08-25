<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\Setting;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Reinscription\PortailSignatureVerifier;
use App\Services\TenantScolariteSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * L'export de reinscription est la SEULE surface non authentifiee de
 * l'application, ouverte sur des bases de plus de 2000 etudiants.
 *
 * Le couple matricule + date de naissance est un facteur faible : il circule
 * entre camarades et figure sur d'anciens bulletins. La securite ne repose
 * donc pas sur la difficulte a franchir la porte, mais sur le fait qu'il n'y a
 * presque rien derriere, et qu'on ne peut pas s'en servir pour deviner qui
 * existe. Ces tests verrouillent exactement cela.
 */
class PortailPublicExportTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'un-secret-de-test-suffisamment-long-pour-passer';

    private ESBTPEtudiant $etudiant;

    private ESBTPAnneeUniversitaire $anneeCible;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.reinscription_portal.secret' => self::SECRET]);

        // CheckInstalled, middleware global, redirige vers l'assistant tant
        // qu'aucun superAdmin n'existe, et met son verdict en cache. En
        // production il y en a un ; sans ces lignes le test mesurerait cette
        // redirection au lieu de mesurer l'export.
        User::factory()->create(['id' => 1])
            ->assignRole(Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']));

        $this->ouvrirLeCanal();

        // Purge APRES avoir cree les reglages : SettingsHelper met en cache la
        // valeur lue, y compris l'absence de ligne. Purger avant laisserait le
        // canal ferme quoi qu'on ecrive ensuite.
        Cache::flush();

        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        $anneePassee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => false,
        ]);
        $this->anneeCible = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true,
        ]);

        $this->etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => 'DEMO-0001',
            'nom' => 'KOUASSI',
            'prenoms' => 'Ama Grace',
            'date_naissance' => '2004-03-15',
        ]);

        $classe = ESBTPClasse::factory()->create([
            'name' => 'BTS2 A', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true,
        ]);

        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $anneePassee->id,
            'status' => 'active',
        ]);
    }

    public function test_un_etudiant_connu_retrouve_sa_situation(): void
    {
        $reponse = $this->appeler('lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ]);

        $reponse->assertOk()
            ->assertJson(['trouve' => true, 'classe_actuelle' => 'BTS2 A', 'eligible' => true])
            ->assertJsonPath('prenom', 'Ama');
    }

    public function test_la_reponse_ne_contient_aucune_donnee_financiere(): void
    {
        $reponse = $this->appeler('lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ]);

        // Sans cette garde, le test passerait sur un refus : un corps d'erreur
        // ne contient evidemment aucune donnee financiere.
        $reponse->assertOk()->assertJson(['trouve' => true]);

        $corps = $reponse->json();

        foreach (['solde', 'montant', 'frais', 'reliquat', 'paiement', 'dette'] as $interdit) {
            $this->assertStringNotContainsStringIgnoringCase(
                $interdit,
                json_encode($corps),
                "L'export public ne doit jamais laisser filtrer d'information financiere."
            );
        }

        // Ni identite complete : le prenom seul suffit a confirmer « c'est moi ».
        $this->assertStringNotContainsString('KOUASSI', json_encode($corps));
        $this->assertStringNotContainsString('Grace', json_encode($corps));
    }

    public function test_matricule_inconnu_et_date_fausse_rendent_la_meme_reponse(): void
    {
        // C'est le coeur de la protection : distinguer les deux donnerait un
        // oracle pour enumerer les matricules valides.
        $inconnu = $this->appeler('lookup', [
            'matricule' => 'MATRICULE-QUI-N-EXISTE-PAS',
            'date_naissance' => '2004-03-15',
        ]);

        $mauvaiseDate = $this->appeler('lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '1999-01-01',
        ]);

        $this->assertSame($inconnu->status(), $mauvaiseDate->status());
        $this->assertSame($inconnu->json(), $mauvaiseDate->json());
        $this->assertFalse($inconnu->json('trouve'));
    }

    public function test_une_requete_non_signee_est_refusee(): void
    {
        $this->postJson('/api/public/reinscription/lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ])->assertStatus(401);
    }

    public function test_une_signature_falsifiee_est_refusee(): void
    {
        $this->postJson('/api/public/reinscription/lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ], [
            'X-Klassci-Signature' => str_repeat('a', 64),
            'X-Klassci-Timestamp' => (string) $this->maintenantMs(),
        ])->assertStatus(401);
    }

    public function test_une_signature_hors_fenetre_est_refusee(): void
    {
        $this->appeler('lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ], horodatage: $this->maintenantMs() - 3_600_000)->assertStatus(401);
    }

    public function test_une_requete_interceptee_ne_peut_pas_etre_rejouee(): void
    {
        // Rejeu au sens strict : le MEME octet, la meme signature, renvoyes.
        // Sans consommation a l'usage, ils passeraient pendant les cinq minutes
        // de tolerance.
        $horodatage = $this->maintenantMs();
        $chemin = 'api/public/reinscription/lookup';
        $corps = json_encode([
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
            'ip_client' => '41.66.10.24',
        ]);
        $entetes = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KLASSCI_SIGNATURE' => app(PortailSignatureVerifier::class)
                ->signature($corps, 'POST', $chemin, $horodatage),
            'HTTP_X_KLASSCI_TIMESTAMP' => (string) $horodatage,
        ];

        $this->call('POST', "/{$chemin}", [], [], [], $entetes, $corps)->assertOk();
        $this->call('POST', "/{$chemin}", [], [], [], $entetes, $corps)->assertStatus(401);
    }

    public function test_une_signature_emise_pour_lookup_ne_vaut_pas_pour_submit(): void
    {
        // La methode et le chemin entrent dans la charge signee : sinon une
        // signature de consultation autoriserait un depot.
        $donnees = [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
            'consentement' => '1',
            'ip_client' => '41.66.10.24',
        ];
        $horodatage = $this->maintenantMs();
        $corps = json_encode($donnees);

        $this->call('POST', '/api/public/reinscription/submit', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KLASSCI_SIGNATURE' => app(PortailSignatureVerifier::class)
                ->signature($corps, 'POST', 'api/public/reinscription/lookup', $horodatage),
            'HTTP_X_KLASSCI_TIMESTAMP' => (string) $horodatage,
        ], $corps)->assertStatus(401);

        $this->assertSame(0, ESBTPReinscriptionDemande::count());
    }

    public function test_une_requete_non_signee_ne_consomme_pas_le_quota_du_matricule(): void
    {
        // Tant que la limitation etait posee en `throttle:` sur la route,
        // Laravel la hissait AVANT ce garde — l'ordre ecrit dans le fichier de
        // routes n'est pas l'ordre d'execution. N'importe qui pouvait alors,
        // avec cinq signatures bidon, fermer le portail a un etudiant nomme
        // pendant un quart d'heure.
        for ($i = 0; $i < 8; $i++) {
            $this->postJson('/api/public/reinscription/lookup', [
                'matricule' => 'DEMO-0001',
                'date_naissance' => '2004-03-15',
                'ip_client' => '41.66.10.24',
            ], [
                'X-Klassci-Signature' => str_repeat('a', 64),
                'X-Klassci-Timestamp' => (string) $this->maintenantMs(),
            ])->assertStatus(401);
        }

        $this->appeler('lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ])->assertOk()->assertJson(['trouve' => true]);
    }

    public function test_un_refus_de_debit_ne_consomme_pas_le_quota_des_autres_seaux(): void
    {
        // Les trois seaux sont consultes avant qu'aucun ne soit incremente.
        // Sinon une requete refusee par le plafond global aurait deja brule un
        // jeton du matricule : pendant une inondation, chaque tentative d'un
        // etudiant legitime lui couterait un jeton sans rien lui servir, et le
        // verrouillerait pour un quart d'heure apres la fin de l'attaque.
        $cleMatricule = PortailReinscriptionService::cleDebitMatricule('DEMO-0001');
        RateLimiter::clear($cleMatricule);

        // Le plafond global est deja atteint : l'ecole subit une inondation.
        for ($i = 0; $i < 121; $i++) {
            RateLimiter::hit('rp-global', 60);
        }

        $this->appeler('lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ])->assertStatus(429);

        $this->assertSame(
            0,
            RateLimiter::attempts($cleMatricule),
            "Un refus du plafond global ne doit rien couter au quota de l'etudiant."
        );
    }

    public function test_une_demande_convertie_puis_annulee_peut_etre_redeposee(): void
    {
        // L'ecole convertit, puis annule l'inscription (erreur de classe,
        // paiement extourne). L'etudiant redevient eligible et redepose. Sans
        // reouverture, le portail repondait « bien transmise » alors que rien
        // n'atterrissait dans la corbeille.
        $donnees = [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
            'consentement' => '1',
        ];

        $this->appeler('submit', $donnees)->assertStatus(201);

        ESBTPReinscriptionDemande::firstOrFail()
            ->update(['statut' => ESBTPReinscriptionDemande::STATUT_CONVERTIE]);

        $this->appeler('submit', $donnees)->assertStatus(201);

        $this->assertSame(
            ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
            ESBTPReinscriptionDemande::firstOrFail()->statut
        );
    }

    public function test_une_demande_rejetee_peut_etre_redeposee(): void
    {
        // L'ecole rejette pour piece manquante, l'etudiant corrige et revient.
        // L'index unique rendait alors la ligne rejetee sans rien changer, et
        // le portail repondait quand meme « bien transmise » : la famille
        // attendait un traitement qui ne viendrait jamais.
        $donnees = [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
            'consentement' => '1',
        ];

        $this->appeler('submit', $donnees)->assertStatus(201);

        ESBTPReinscriptionDemande::firstOrFail()->update([
            'statut' => ESBTPReinscriptionDemande::STATUT_REJETEE,
            'motif_rejet' => 'Acte de naissance manquant.',
        ]);

        $this->appeler('submit', $donnees)->assertStatus(201);

        $demande = ESBTPReinscriptionDemande::firstOrFail();
        $this->assertSame(1, ESBTPReinscriptionDemande::count());
        $this->assertSame(
            ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
            $demande->statut,
            'Un nouveau depot doit reellement remettre la demande dans la corbeille.'
        );
        $this->assertNull($demande->motif_rejet);
    }

    public function test_le_canal_ferme_ne_consulte_rien(): void
    {
        Setting::where('key', TenantScolariteSettings::REINSCRIPTION_EN_LIGNE)->update(['value' => '0']);

        $this->appeler('lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ])->assertStatus(503)->assertJson(['ouvert' => false]);
    }

    public function test_hors_de_la_fenetre_de_dates_le_canal_est_ferme(): void
    {
        Setting::where('key', PortailReinscriptionService::REGLAGE_FERMETURE)
            ->update(['value' => now()->subDay()->toDateString()]);

        $this->appeler('lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ])->assertStatus(503);
    }

    public function test_le_depot_cree_une_demande_inerte_et_non_une_inscription(): void
    {
        $inscriptionsAvant = ESBTPInscription::count();

        $this->appeler('submit', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
            'consentement' => '1',
        ])->assertStatus(201)->assertJson(['enregistre' => true]);

        $this->assertSame(
            $inscriptionsAvant,
            ESBTPInscription::count(),
            "Le portail public ne doit JAMAIS creer d'inscription."
        );

        $demande = ESBTPReinscriptionDemande::firstOrFail();
        $this->assertSame(ESBTPReinscriptionDemande::STATUT_EN_ATTENTE, $demande->statut);
        $this->assertNotNull($demande->consentement_at);
    }

    public function test_le_depot_est_idempotent(): void
    {
        $donnees = ['matricule' => 'DEMO-0001', 'date_naissance' => '2004-03-15', 'consentement' => '1'];

        $this->appeler('submit', $donnees)->assertStatus(201);
        $this->appeler('submit', $donnees)->assertStatus(201);

        $this->assertSame(
            1,
            ESBTPReinscriptionDemande::count(),
            'Un double envoi ne doit pas encombrer la corbeille de la scolarite.'
        );
    }

    public function test_un_etudiant_deja_reinscrit_ne_peut_pas_deposer(): void
    {
        // Sans ce refus, la scolarite convertissait la demande, le flux
        // canonique creait une SECONDE inscription pour l'annee en cours, et
        // les souscriptions de frais de la premiere restaient actives : la
        // famille portait deux jeux de frais pour la meme annee. Le tout
        // declenchable depuis la seule surface non authentifiee.
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'annee_universitaire_id' => $this->anneeCible->id,
            'status' => 'active',
        ]);

        $this->appeler('lookup', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ])->assertOk()->assertJson(['trouve' => true, 'eligible' => false]);

        $this->appeler('submit', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
            'consentement' => '1',
        ])->assertOk()->assertJson(['trouve' => false]);

        $this->assertSame(0, ESBTPReinscriptionDemande::count());
    }

    public function test_le_depot_exige_un_consentement_explicite(): void
    {
        // Obligation de la loi ivoirienne 2013-450 sur les donnees personnelles.
        $this->appeler('submit', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
        ])->assertStatus(422);

        $this->assertSame(0, ESBTPReinscriptionDemande::count());
    }

    public function test_l_adresse_est_stockee_sous_forme_d_empreinte_et_non_en_clair(): void
    {
        $this->appeler('submit', [
            'matricule' => 'DEMO-0001',
            'date_naissance' => '2004-03-15',
            'consentement' => '1',
        ])->assertStatus(201);

        $demande = ESBTPReinscriptionDemande::firstOrFail();

        $this->assertNotNull($demande->ip_hash);
        $this->assertSame(64, strlen($demande->ip_hash), 'Une empreinte SHA-256 fait 64 caracteres.');
        $this->assertStringNotContainsString('41.66.10.24', $demande->ip_hash);

        // Et c'est bien l'adresse du VISITEUR qui est empreinte, pas celle du
        // site vitrine : cette derniere serait identique pour toute l'ecole et
        // ne permettrait de reperer aucun abus.
        $this->assertSame(
            hash_hmac('sha256', '41.66.10.24', (string) config('app.key')),
            $demande->ip_hash
        );
    }

    public function test_une_adresse_de_visiteur_absente_est_refusee(): void
    {
        // Sans elle, la limitation de debit retomberait sur l'adresse du site
        // vitrine : partagee par toute l'ecole, et renouvelee a chaque
        // demarrage a froid chez l'hebergeur. Autant dire aucune limite.
        $horodatage = $this->maintenantMs();
        $chemin = 'api/public/reinscription/lookup';
        $corps = json_encode(['matricule' => 'DEMO-0001', 'date_naissance' => '2004-03-15']);

        $this->call('POST', "/{$chemin}", [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KLASSCI_SIGNATURE' => app(PortailSignatureVerifier::class)
                ->signature($corps, 'POST', $chemin, $horodatage),
            'HTTP_X_KLASSCI_TIMESTAMP' => (string) $horodatage,
        ], $corps)->assertStatus(422)->assertJson(['trouve' => false]);
    }

    /**
     * L'horodatage voyage en MILLISECONDES : c'est ce qui distingue deux clics
     * rapides, et donc ce qui permet a la signature d'etre consommee a l'usage
     * sans casser un double clic legitime.
     */
    private function maintenantMs(): int
    {
        return (int) (microtime(true) * 1000);
    }

    private function ouvrirLeCanal(): void
    {
        foreach ([
            TenantScolariteSettings::REINSCRIPTION_EN_LIGNE => '1',
            PortailReinscriptionService::REGLAGE_OUVERTURE => '',
            PortailReinscriptionService::REGLAGE_FERMETURE => '',
        ] as $cle => $valeur) {
            Setting::updateOrCreate(['key' => $cle], [
                'value' => $valeur,
                'type' => $cle === TenantScolariteSettings::REINSCRIPTION_EN_LIGNE ? 'boolean' : 'string',
                'group' => 'scolarite',
                // Setting::get filtre sur is_active : sans ce drapeau, la ligne
                // existe mais reste invisible et le defaut s'applique.
                'is_active' => true,
            ]);
        }
    }
    /**
     * Appelle le portail comme le fera le site vitrine : corps JSON brut,
     * signe avec la methode et le chemin.
     *
     * @param  array<string, mixed>  $donnees
     */
    private function appeler(string $point, array $donnees, ?int $horodatage = null)
    {
        // L'adresse du visiteur voyage dans le corps SIGNE. Celle que verrait
        // Laravel serait celle du site vitrine, identique pour toute l'ecole.

        $donnees += ['ip_client' => '41.66.10.24'];

        $horodatage ??= $this->maintenantMs();
        $chemin = "api/public/reinscription/{$point}";
        // Le corps est construit UNE fois et sert a la fois a signer et a
        // envoyer : c'est exactement la discipline attendue du site vitrine.
        $corps = json_encode($donnees);

        return $this->call('POST', "/{$chemin}", [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KLASSCI_SIGNATURE' => app(PortailSignatureVerifier::class)
                ->signature($corps, 'POST', $chemin, $horodatage),
            'HTTP_X_KLASSCI_TIMESTAMP' => (string) $horodatage,
        ], $corps);
    }
}

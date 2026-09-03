<?php

namespace Tests\Unit\Services\LMD;

use App\Models\ESBTPLMDResultatUE;
use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMDBulletinService;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Verrouille la compensation entre unites d'enseignement.
 *
 * Une unite sous le seuil dont la moyenne generale de l'etudiant atteint le seuil est
 * « acquise par compensation » : ses credits sont capitalises et son statut passe a APC.
 * Ce marquage est une ecriture groupee en base, donc ce fichier monte une base SQLite
 * en memoire (aucune migration, aucune base de l'etablissement) pour verifier que le
 * statut est reellement persiste, et pas seulement que le total de credits est bon.
 */
class LMDBulletinServiceCompensationTest extends TestCase
{
    private Capsule $base;

    private ?ConnectionResolverInterface $resolveurInitial = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Capsule::setAsGlobal() pose un resolveur de connexion global sur Eloquent :
        // on garde le precedent pour le rendre intact aux autres tests du meme processus.
        $this->resolveurInitial = Model::getConnectionResolver();

        $conteneur = new Container();
        Container::setInstance($conteneur);
        Facade::setFacadeApplication($conteneur);
        $conteneur->instance('app', $conteneur);

        $this->base = new Capsule($conteneur);
        $this->base->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->base->setAsGlobal();
        $this->base->bootEloquent();

        $this->base->schema()->create('esbtp_lmd_resultats_ues', function (Blueprint $table) {
            $table->id();
            $table->string('statut')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        if ($this->resolveurInitial !== null) {
            Model::setConnectionResolver($this->resolveurInitial);
        } else {
            Model::unsetConnectionResolver();
        }

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    /** Construit le service avec les reglages d'une ecole donnee. */
    private function service(array $reglages = []): LMDBulletinService
    {
        $profil = new LmdAcademicRuleProfile(
            fn (string $cle, mixed $defaut = null): mixed => $reglages[$cle] ?? $defaut
        );

        return new LMDBulletinService($profil);
    }

    /**
     * Insere une ligne de resultat d'unite et renvoie l'objet que le service consomme.
     * Le statut initial est celui pose avant compensation (AQ ou NAQ).
     */
    private function resultatUe(int $id, ?float $moyenne, int $credit, string $statut): stdClass
    {
        $this->base->table('esbtp_lmd_resultats_ues')->insert([
            'id' => $id,
            'statut' => $statut,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
            'deleted_at' => null,
        ]);

        $resultat = new stdClass();
        $resultat->id = $id;
        $resultat->moyenne = $moyenne;
        $resultat->credit = $credit;

        return $resultat;
    }

    private function statutEnBase(int $id): ?string
    {
        return $this->base->table('esbtp_lmd_resultats_ues')->where('id', $id)->value('statut');
    }

    public function test_une_unite_faible_est_acquise_par_compensation_et_marquee_en_base(): void
    {
        $unites = [
            $this->resultatUe(1, 14.0, 6, ESBTPLMDResultatUE::STATUT_AQ),
            $this->resultatUe(2, 8.0, 4, ESBTPLMDResultatUE::STATUT_NAQ),
        ];

        // Moyenne generale ponderee : (14 x 6 + 8 x 4) / 10 = 11.6, au-dessus du seuil.
        $credits = $this->service()->appliquerCompensation($unites, 11.6);

        $this->assertSame(10, $credits, 'Les credits de l\'unite compensee sont capitalises.');
        $this->assertSame(ESBTPLMDResultatUE::STATUT_AQ, $this->statutEnBase(1));
        $this->assertSame(ESBTPLMDResultatUE::STATUT_APC, $this->statutEnBase(2));
    }

    public function test_plusieurs_unites_faibles_sont_compensees_en_une_seule_ecriture(): void
    {
        $unites = [
            $this->resultatUe(1, 15.0, 10, ESBTPLMDResultatUE::STATUT_AQ),
            $this->resultatUe(2, 9.0, 5, ESBTPLMDResultatUE::STATUT_NAQ),
            $this->resultatUe(3, 7.5, 5, ESBTPLMDResultatUE::STATUT_NAQ),
        ];

        $credits = $this->service()->appliquerCompensation($unites, 11.75);

        $this->assertSame(20, $credits);
        $this->assertSame(ESBTPLMDResultatUE::STATUT_APC, $this->statutEnBase(2));
        $this->assertSame(ESBTPLMDResultatUE::STATUT_APC, $this->statutEnBase(3));
    }

    public function test_sans_compensation_l_unite_faible_reste_non_acquise(): void
    {
        $unites = [
            $this->resultatUe(1, 14.0, 6, ESBTPLMDResultatUE::STATUT_AQ),
            $this->resultatUe(2, 8.0, 4, ESBTPLMDResultatUE::STATUT_NAQ),
        ];

        $service = $this->service(['lmd_compensation_inter_ue' => '0']);
        $credits = $service->appliquerCompensation($unites, 11.6);

        $this->assertSame(6, $credits);
        $this->assertSame(ESBTPLMDResultatUE::STATUT_NAQ, $this->statutEnBase(2));
    }

    public function test_une_moyenne_generale_sous_le_seuil_ne_compense_rien(): void
    {
        $unites = [
            $this->resultatUe(1, 9.5, 6, ESBTPLMDResultatUE::STATUT_NAQ),
            $this->resultatUe(2, 8.0, 4, ESBTPLMDResultatUE::STATUT_NAQ),
        ];

        $credits = $this->service()->appliquerCompensation($unites, 8.9);

        $this->assertSame(0, $credits);
        $this->assertSame(ESBTPLMDResultatUE::STATUT_NAQ, $this->statutEnBase(1));
        $this->assertSame(ESBTPLMDResultatUE::STATUT_NAQ, $this->statutEnBase(2));
    }

    public function test_relever_le_seuil_de_l_ecole_deplace_la_frontiere_de_la_compensation(): void
    {
        $unites = fn (): array => [
            $this->resultatUe(1, 11.0, 6, ESBTPLMDResultatUE::STATUT_AQ),
            $this->resultatUe(2, 9.0, 4, ESBTPLMDResultatUE::STATUT_NAQ),
        ];

        // Seuil par defaut (10) : moyenne generale 10.2, l'unite a 9 est compensee.
        $this->assertSame(10, $this->service()->appliquerCompensation($unites(), 10.2));
        $this->assertSame(ESBTPLMDResultatUE::STATUT_APC, $this->statutEnBase(2));

        // Meme dossier dans une ecole qui valide a 12 : plus rien n'est acquis.
        $this->base->table('esbtp_lmd_resultats_ues')->delete();
        $seuilReleve = $this->service(['lmd_validation_threshold' => '12']);

        $this->assertSame(0, $seuilReleve->appliquerCompensation($unites(), 10.2));
        $this->assertSame(ESBTPLMDResultatUE::STATUT_NAQ, $this->statutEnBase(2));
    }

    public function test_une_unite_sans_moyenne_n_est_ni_compensee_ni_touchee(): void
    {
        $unites = [
            $this->resultatUe(1, 14.0, 6, ESBTPLMDResultatUE::STATUT_AQ),
            $this->resultatUe(2, null, 4, ESBTPLMDResultatUE::STATUT_NAQ),
        ];

        $credits = $this->service()->appliquerCompensation($unites, 14.0);

        $this->assertSame(6, $credits, 'Une unite non evaluee n\'apporte aucun credit.');
        $this->assertSame(ESBTPLMDResultatUE::STATUT_NAQ, $this->statutEnBase(2));
    }

    public function test_une_unite_archivee_n_est_pas_remarquee_par_la_mise_a_jour_groupee(): void
    {
        $unites = [
            $this->resultatUe(1, 14.0, 6, ESBTPLMDResultatUE::STATUT_AQ),
            $this->resultatUe(2, 8.0, 4, ESBTPLMDResultatUE::STATUT_NAQ),
        ];

        $this->base->table('esbtp_lmd_resultats_ues')
            ->where('id', 2)
            ->update(['deleted_at' => '2026-02-01 00:00:00']);

        $credits = $this->service()->appliquerCompensation($unites, 11.6);

        // Le total de credits ignore l'archivage (il se calcule en memoire),
        // mais la ligne archivee n'est pas reecrite en base.
        $this->assertSame(10, $credits);
        $this->assertSame(ESBTPLMDResultatUE::STATUT_NAQ, $this->statutEnBase(2));
    }
}

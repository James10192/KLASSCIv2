<?php

namespace Tests\Unit\Inscription;

use App\Http\Controllers\ESBTP\ESBTPSettingsController;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Reinscription\PortailReinscriptionService;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

/**
 * On ne peut pas ouvrir les candidatures sans designer l'annee visee.
 *
 * Les deux canaux lisent ce champ differemment, et c'est voulu : vide, la
 * reinscription retombe sur l'annee courante (reconduire un dossier dans
 * l'annee en cours a un sens), la candidature refuse (ranger une cohorte de
 * nouveaux sous l'annee sortante n'en a aucun).
 *
 * Sans ce refus, l'asymetrie se decouvrait cote public : l'ecole cochait
 * l'interrupteur, laissait le selecteur vide, enregistrait — sauvegarde
 * reussie, interrupteur vert, aucun journal — et chaque bachelier lisait
 * « l'etablissement n'a pas termine la configuration de cette rentree ». Le
 * seul signal arrivait par telephone, en pleine rentree.
 *
 * Le refus est verifie sans base : la garde ne lit la base que pour retomber
 * sur un reglage deja enregistre, et les cas testes fournissent tous le champ
 * dans la requete.
 */
class OuvertureCandidaturesTest extends TestCase
{
    /** @return string|null le message de refus, null si la sauvegarde passe */
    private function refus(array $champs): ?string
    {
        $methode = new ReflectionMethod(ESBTPSettingsController::class, 'refuserCandidaturesSansAnnee');
        $methode->setAccessible(true);

        $reponse = $methode->invoke(
            $this->app->make(ESBTPSettingsController::class),
            Request::create('/esbtp/settings', 'POST', $champs)
        );

        if ($reponse === null) {
            return null;
        }

        // Le formulaire de reglages est un POST de navigateur : le refus est
        // une redirection portant le message en session.
        return (string) ($reponse->getSession()?->get('error') ?? 'refus');
    }

    /** Le cas qui aurait casse la rentree : canal ouvert, annee vide. */
    public function test_ouvrir_sans_annee_est_refuse(): void
    {
        $message = $this->refus([
            PortailCandidaturePublication::REGLAGE_ACTIF => '1',
            PortailReinscriptionService::REGLAGE_ANNEE_CIBLE => '',
        ]);

        $this->assertNotNull($message);
        $this->assertStringContainsString('année visée', $message);
    }

    public function test_ouvrir_avec_une_annee_passe(): void
    {
        $this->assertNull($this->refus([
            PortailCandidaturePublication::REGLAGE_ACTIF => '1',
            PortailReinscriptionService::REGLAGE_ANNEE_CIBLE => '3',
        ]));
    }

    /**
     * Canal ferme : l'annee vide reste le cas normal, c'est celui des ecoles
     * qui ne font que de la reinscription.
     */
    public function test_fermer_sans_annee_reste_permis(): void
    {
        $this->assertNull($this->refus([
            PortailReinscriptionService::REGLAGE_ANNEE_CIBLE => '',
        ]));
    }

    /**
     * Un formulaire qui ne presente pas la case n'est pas juge sur elle : les
     * reglages KLASSCI s'enregistrent onglet par onglet.
     */
    public function test_un_formulaire_sans_la_case_n_est_pas_juge(): void
    {
        $this->assertNull($this->refus(['bulletin_style' => 'yakro']));
    }
}

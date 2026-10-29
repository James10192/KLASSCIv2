<?php

namespace Tests\Feature\Emails;

use App\Http\Requests\Etudiants\ReglesEmailsEtudiant;
use App\Http\Requests\Inscription\StoreInscriptionRequest;
use App\Http\Requests\UpdateStudentReinscriptionFicheRequest;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Les formulaires etudiant refusent une adresse fabriquee ou fautive SAISIE,
 * mais laissent passer celle deja en base, renvoyee telle quelle a l'edition.
 */
class ReglesEmailsFormulairesTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_inscription_refuse_une_faute_et_une_adresse_de_parent_fabriquee(): void
    {
        $donnees = [
            'email_personnel' => 'awa@gmail.con',
            'parents' => [['type' => 'nouveau', 'nom' => 'KONE', 'prenoms' => 'Ali', 'telephone' => '0701020304', 'relation' => 'pere', 'email' => 'ali@esbtp.edu.ci']],
        ];
        $requete = StoreInscriptionRequest::create('/esbtp/inscriptions', 'POST', $donnees);

        $erreurs = Validator::make($donnees, $requete->rules())->errors();

        $this->assertSame('Vouliez-vous dire awa@gmail.com ?', $erreurs->first('email_personnel'));
        $this->assertTrue($erreurs->has('parents.0.email'));
    }

    public function test_la_fiche_accepte_les_adresses_deja_en_base_et_refuse_une_nouvelle_fabriquee(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create(['email_personnel' => 'm22-0521@esbtp.edu.ci']);
        $parent = ESBTPParent::create(['nom' => 'KONE', 'prenoms' => 'Ali', 'telephone' => '0701020304', 'email' => 'ali@gmail.con']);
        $etudiant->parents()->attach($parent->id, ['relation' => 'pere', 'is_tuteur' => true]);

        $inchangee = $this->erreursFiche($etudiant, ['email_personnel' => 'm22-0521@esbtp.edu.ci', 'parents' => [['email' => 'ali@gmail.con']]]);
        $this->assertFalse($inchangee->has('email_personnel'));
        $this->assertFalse($inchangee->has('parents.0.email'));

        $nouvelle = $this->erreursFiche($etudiant, ['email_personnel' => 'autre@esbtp.edu.ci']);
        $this->assertTrue($nouvelle->has('email_personnel'));
    }

    public function test_l_edition_de_l_ecran_etudiant_suit_la_meme_regle(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create(['email_personnel' => 'awa@gmail.con']);
        $regles = ReglesEmailsEtudiant::edition($etudiant);

        $this->assertFalse(Validator::make(['email_personnel' => 'awa@gmail.con'], $regles)->fails());
        $this->assertTrue(Validator::make(['email_personnel' => 'awa@yahoo.con'], $regles)->fails());
        $this->assertTrue(Validator::make(['new_parent' => ['email' => 'p@esbtp.edu']], $regles)->fails());
    }

    /** @param  array<string, mixed>  $donnees */
    private function erreursFiche(ESBTPEtudiant $etudiant, array $donnees)
    {
        $requete = UpdateStudentReinscriptionFicheRequest::create('/esbtp/etudiants/'.$etudiant->id.'/fiche', 'PUT', $donnees);
        $route = new Route('PUT', 'esbtp/etudiants/{etudiant}/fiche', []);
        $route->bind($requete);
        $route->setParameter('etudiant', $etudiant);
        $requete->setRouteResolver(fn () => $route);

        return Validator::make($donnees, $requete->rules())->errors();
    }
}

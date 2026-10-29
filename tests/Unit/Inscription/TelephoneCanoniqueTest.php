<?php

namespace Tests\Unit\Inscription;

use App\Http\Requests\Inscription\PortailCandidatureRequest;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Le telephone est la CLE d'unicite du canal public.
 *
 * Tout l'edifice des refus repose dessus : « une inscription existe deja pour
 * ce numero », « une candidature enregistree sous un autre nom utilise ce
 * numero », l'idempotence du redepot. Si deux ecritures du meme numero
 * produisent deux cles, aucun de ces refus ne se declenche — et la corbeille
 * propose de reinscrire quelqu'un qui l'est deja.
 *
 * Le cas n'a rien de theorique : le formulaire d'inscription de l'ecole
 * affiche « +225 XX XX XXX XXX » en exemple, quand le portail public voit
 * arriver « 0707121234 ». La meme famille ecrira les deux.
 *
 * D'ou la propriete que ce fichier verifie, dans les deux sens : ce qui entre
 * ressort canonique, et ce qui ne peut pas etre rendu canonique est REFUSE.
 * Un repli « on garde les chiffres » avait ete essaye : il ne rapprochait pas
 * « 27 20 30 10 20 » de « +225 27 20 30 10 20 », faute d'indicatif pays connu,
 * et rendait donc une cle canonique en apparence seulement.
 */
class TelephoneCanoniqueTest extends TestCase
{
    protected function tearDown(): void
    {
        // Le resolveur est un etat statique : le laisser regle sur le Benin
        // contaminerait les cas ivoiriens de la classe suivante. `null` rend
        // le plan livre, soit exactement l'etat d'une instance sans reglage.
        \App\Domain\Notifications\PhoneNormalizer::definirResolveurReglages(null);

        parent::tearDown();
    }

    private function canonique(string $saisie): string
    {
        $requete = new PortailCandidatureRequest;
        $requete->merge(['telephone' => $saisie]);

        $methode = new ReflectionMethod(PortailCandidatureRequest::class, 'canoniserLeTelephone');
        $methode->setAccessible(true);
        $methode->invoke($requete);

        return (string) $requete->input('telephone');
    }

    /** La regle de validation seule, sans base : elle n'interroge rien. */
    private function accepte(string $saisie): bool
    {
        $regles = (new PortailCandidatureRequest)->rules();

        return Validator::make(['telephone' => $saisie], ['telephone' => $regles['telephone']])->passes();
    }

    /**
     * Bascule l'instance sur le plan beninois : indicatif 229, prefixe `01` seul.
     *
     * Les DEUX reglages, jamais l'indicatif seul — c'est la combinaison que
     * l'ecran refuse desormais d'enregistrer, et la reproduire ici donnerait
     * une instance qui accepte des numeros qui n'existent pas.
     *
     * `tearDown()` remet le resolveur a null.
     */
    private function reglerSurLeBenin(): void
    {
        \App\Domain\Notifications\PhoneNormalizer::definirResolveurReglages(
            static fn (string $cle): ?string => match ($cle) {
                \App\Domain\Notifications\PhoneNormalizer::CLE_INDICATIF => '229',
                \App\Domain\Notifications\PhoneNormalizer::CLE_PREFIXES => '01',
                default => null,
            }
        );
    }

    /** Les six ecritures qu'un formulaire public voit vraiment passer. */
    public function test_toutes_les_ecritures_d_un_mobile_ivoirien_donnent_la_meme_cle(): void
    {
        $ecritures = [
            '0707121234',
            '07 07 12 12 34',
            '+2250707121234',
            '+225 07 07 12 12 34',
            '00225 07 07 12 12 34',
            ' 07-07-12-12-34 ',
        ];

        $cles = array_map(fn (string $e) => $this->canonique($e), $ecritures);

        $this->assertCount(1, array_unique($cles), 'Un seul numero doit donner une seule cle');
        $this->assertSame('+2250707121234', $cles[0]);

        foreach ($ecritures as $ecriture) {
            $this->assertTrue($this->accepte($ecriture), "« {$ecriture} » doit etre accepte");
        }
    }

    public function test_deux_numeros_differents_gardent_deux_cles(): void
    {
        $this->assertNotSame(
            $this->canonique('0707121234'),
            $this->canonique('0707121235'),
        );
    }

    /**
     * Ce qui ne peut pas devenir canonique est refuse, pas replie.
     *
     * Un fixe abidjanais et un mobile francais sont les deux cas que le repli
     * pretendait couvrir et ne couvrait pas : leurs deux ecritures usuelles
     * donnaient deux cles. Les refuser dit la verite au candidat au lieu de
     * lui laisser croire que sa candidature est identifiable.
     *
     * @dataProvider horsPerimetre
     */
    public function test_un_numero_hors_perimetre_est_refuse(string $saisie): void
    {
        $this->assertFalse($this->accepte($saisie), "« {$saisie} » ne doit pas etre accepte");
    }

    /** @return array<string, array{string}> */
    public static function horsPerimetre(): array
    {
        return [
            'fixe abidjanais, ecriture nationale' => ['27 20 30 10 20'],
            'fixe abidjanais, ecriture internationale' => ['+225 27 20 30 10 20'],
            'mobile francais, ecriture internationale' => ['+33 6 12 34 56 78'],
            'trop court' => ['0707'],
            'texte' => ['appelez ma mere'],
            'vide' => [''],
        ];

        // Volontairement ABSENT de cette liste : « 06 12 34 56 78 », un mobile
        // francais ecrit a la nationale. Dix chiffres commencant par 06, c'est
        // exactement la forme d'un mobile Orange ivoirien : aucun analyseur ne
        // peut les distinguer, et pretendre le contraire dans un test
        // figerait une exigence impossible a tenir. Un numero etranger doit
        // etre ecrit avec son indicatif pour etre reconnu comme tel — et il
        // est alors refuse, ce que la ligne du dessus verifie.
    }

    /**
     * La meme propriete, hors de Cote d'Ivoire.
     *
     * `ucao-benin` est la premiere instance etrangere, et le portail y refusait
     * TOUT : `+229…` parce qu'il rejouait le controle de prefixe ivoirien sur
     * un numero qui n'en portait pas, et son message parlait meme d'un « numero
     * mobile ivoirien ». L'ecriture nationale, elle, etait pire — elle passait,
     * en devenant un abonne Moov ivoirien joignable et tiers.
     *
     * Ce cas verifie donc les deux sens sur une instance beninoise : les cinq
     * ecritures d'un numero local donnent une seule cle, et un mobile ivoirien
     * n'est plus accepte.
     */
    public function test_les_ecritures_d_un_numero_beninois_donnent_la_meme_cle(): void
    {
        $this->reglerSurLeBenin();

        $ecritures = [
            '0142345678',
            '01 42 34 56 78',
            '+2290142345678',
            '+229 01 42 34 56 78',
            '00229 01 42 34 56 78',
        ];

        $cles = array_map(fn (string $e) => $this->canonique($e), $ecritures);

        $this->assertCount(1, array_unique($cles), 'Un seul numero doit donner une seule cle');
        $this->assertSame('+2290142345678', $cles[0]);

        foreach ($ecritures as $ecriture) {
            $this->assertTrue($this->accepte($ecriture), "« {$ecriture} » doit etre accepte au Benin");
        }

        // Et l'inverse, qui est le vrai degat repare : `0707121234` est un
        // mobile ivoirien valide. Au Benin il ne designe personne — la
        // renumerotation ARCEP du 30 novembre 2024 a prefixe `01` a tous les
        // numeros du pays. Avant, il devenait `+2290707121234` en silence.
        $this->assertFalse($this->accepte('0707121234'), 'Un mobile ivoirien ne doit pas passer au Benin');
    }

    /**
     * Le message de refus doit dire quoi taper.
     *
     * « Le champ telephone est invalide » enverrait un bachelier essayer des
     * variantes au hasard ; nommer la longueur et les prefixes attendus lui
     * donne la forme du premier coup.
     */
    public function test_le_refus_montre_la_forme_attendue(): void
    {
        $regles = (new PortailCandidatureRequest)->rules();
        $validateur = Validator::make(['telephone' => '27 20 30 10 20'], ['telephone' => $regles['telephone']]);

        $this->assertTrue($validateur->fails());

        $message = implode(' ', $validateur->errors()->get('telephone'));
        $this->assertStringContainsString('10 chiffres', $message);
        $this->assertStringContainsString('07', $message, 'Les prefixes ivoiriens doivent etre cites sur une instance ivoirienne');
    }

    /**
     * Le message de refus ne doit pas recommander la saisie qu'il refuse.
     *
     * C'etait le cas : l'exemple « 07 07 12 12 34 » etait ecrit en dur, donc le
     * portail beninois conseillait un mobile ivoirien — precisement la forme que
     * l'instance venait de rejeter, dans la phrase la plus lue de l'ecran.
     */
    public function test_le_refus_ne_conseille_pas_un_numero_que_l_instance_rejette(): void
    {
        $this->reglerSurLeBenin();

        $regles = (new PortailCandidatureRequest)->rules();
        $validateur = Validator::make(['telephone' => '27 20 30 10 20'], ['telephone' => $regles['telephone']]);

        $this->assertTrue($validateur->fails());

        $message = implode(' ', $validateur->errors()->get('telephone'));
        $this->assertStringContainsString('01', $message, 'Le prefixe declare par l\'instance doit etre cite');
        $this->assertStringNotContainsString(
            '07 07',
            $message,
            'Le message ne doit plus citer un mobile ivoirien sur une instance beninoise'
        );
    }
}

<?php

namespace Tests\Unit\Inscription;

use App\Enums\RefusCandidature;
use App\Exceptions\RefusCandidatureException;
use App\Http\Controllers\API\Public\CandidaturePortalController;
use App\Http\Requests\Inscription\PortailCandidatureRequest;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Inscription\PortailCandidatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Tests\TestCase;

/**
 * Ce que le portail repond quand il ne peut pas enregistrer.
 *
 * Chaque raison a son code et son message, et la difference n'est pas
 * cosmetique : « les inscriptions ne sont pas configurees » demande
 * d'appeler l'ecole, « reessayez dans quelques minutes » demande d'attendre.
 * Envoyer le second a quelqu'un qui doit faire le premier lui fait perdre sa
 * journee.
 *
 * Ils sont testes parce qu'ils se sont deja tus. Un `catch` dont la classe
 * n'est pas importee ne leve aucune erreur en PHP : il ne s'applique
 * simplement jamais, et l'exception traverse jusqu'au 500. La revue l'a
 * trouve sur ce fichier meme, apres qu'une verification navigateur l'ait
 * manque — /choix refuse l'annee sans passer par l'exception, seul /submit le
 * fait. Ce test-ci passe par la ou personne ne regardait.
 */
class PortailCandidatureRefusTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** Une requete deja validee, sans base de donnees. */
    private function requete(): PortailCandidatureRequest
    {
        $donnees = [
            'nom' => 'Kouassi',
            'prenoms' => 'Aya',
            'date_naissance' => '2007-03-15',
            'telephone' => '0707121234',
            'voeu_libre' => 'Genie civil',
            'consentement' => true,
            'ip_client' => '196.1.2.3',
        ];

        $requete = new PortailCandidatureRequest;
        $requete->merge($donnees);

        // Les vraies regles, pour que `validated()` rende vraiment les champs.
        // Aucune ne touche la base : les seules a porter un `exists` visent
        // filiere_id et niveau_id, absents ici — le voeu passe par le texte
        // libre, comme pour un bachelier qui ne connait pas le nom exact de
        // la formation.
        $validateur = Validator::make($donnees, $requete->rules());
        $this->assertTrue($validateur->passes(), 'la charge de test doit etre valide');
        $requete->setValidator($validateur);

        return $requete;
    }

    private function reponsePour(\Throwable $refus): JsonResponse
    {
        $service = Mockery::mock(PortailCandidatureService::class);
        $service->shouldReceive('deposer')->once()->andThrow($refus);

        // Les refus qui envoient SUR PLACE emportent la date, comme la
        // confirmation : la publication est donc sollicitee sur ce chemin
        // aussi, mais seulement pour ceux-la.
        $publication = Mockery::mock(PortailCandidaturePublication::class);
        $publication->shouldReceive('inscriptionsPhysiques')
            ->andReturn(['debut' => '2026-09-12', 'ouvertes' => false]);

        return (new CandidaturePortalController($publication, $service))->submit($this->requete());
    }

    /**
     * L'ecole a ouvert le canal sans designer d'annee : defaut de
     * parametrage de son cote, qui ne se resout pas en patientant.
     */
    public function test_l_annee_non_configuree_rend_un_503_qualifie(): void
    {
        $reponse = $this->reponsePour(
            new RefusCandidatureException(RefusCandidature::AnneeNonConfiguree, 'pas d annee')
        );

        $this->assertSame(503, $reponse->getStatusCode());
        $this->assertSame('annee_non_configuree', $reponse->getData(true)['code']);
    }

    /**
     * Le numero porte deja une inscription. 409 : la demande entre en conflit
     * avec l'etat de la ressource, elle ne reussira pas en la repetant.
     */
    public function test_un_numero_deja_inscrit_rend_un_409_qualifie(): void
    {
        $reponse = $this->reponsePour(
            new RefusCandidatureException(RefusCandidature::DejaInscrit, 'deja inscrit')
        );

        $this->assertSame(409, $reponse->getStatusCode());
        $this->assertSame('deja_inscrit', $reponse->getData(true)['code']);
    }

    /**
     * Le telephone porte une candidature REJETEE pour quelqu'un d'autre —
     * l'ainee refusee en septembre, le cadet qui depose en octobre sur le
     * telephone du foyer.
     *
     * Rejetee, et non « decidee » : une acceptation sous un autre nom part sur
     * `AccepteePourUnAutre` depuis que ce cas existe.
     *
     * Le message ne doit surtout pas parler d'inscription : dans le cas d'un
     * REFUS, aucune n'existe, et la famille en conclurait que la place est
     * prise. Elle appellerait alors une ecole incapable de reproduire ce que
     * le message decrit. Un message faux ne se contente pas de ne pas aider,
     * il oriente l'appel dans le vide.
     */
    public function test_une_candidature_pour_un_autre_ne_parle_pas_d_inscription(): void
    {
        $reponse = $this->reponsePour(
            new RefusCandidatureException(RefusCandidature::AutrePersonne, 'autre personne')
        );

        $this->assertSame(409, $reponse->getStatusCode());
        $this->assertSame('autre_personne', $reponse->getData(true)['code']);
        $this->assertStringNotContainsStringIgnoringCase(
            'inscription existe',
            $reponse->getData(true)['message'],
            "Aucune inscription n'existe dans ce cas : le dire serait faux"
        );
    }

    /**
     * La date des inscriptions sur place suit les refus qui y envoient, et
     * seulement ceux-la.
     *
     * Deux refus disent « presentez-vous sur place » : ils s'adressent a
     * quelqu'un dont le dossier est deja accepte, c'est-a-dire a l'audience
     * exacte de cette date. Sans elle, la famille se deplace le jour meme quand
     * le guichet ouvre dans trois semaines — et le meme serveur, sur la requete
     * d'a cote, savait le dire.
     *
     * Les autres ne l'emportent pas : joindre une date d'ouverture a « une
     * inscription existe deja » suggererait un deplacement a qui n'a rien a
     * venir chercher.
     */
    public function test_seuls_les_refus_qui_envoient_sur_place_portent_la_date(): void
    {
        foreach (RefusCandidature::cases() as $raison) {
            $corps = $this->reponsePour(
                new RefusCandidatureException($raison, 'peu importe')
            )->getData(true);

            $this->assertSame(
                $raison->renvoieSurPlace(),
                array_key_exists('inscriptions_physiques', $corps),
                "La date accompagne « {$raison->value} » alors qu'elle ne devrait pas, ou l'inverse"
            );
        }
    }

    /**
     * Chaque raison a SA phrase, et le `match` les couvre toutes.
     *
     * Le test parcourt l'enum plutot que de citer ses cas : une raison ajoutee
     * sans son message ferait lever `UnhandledMatchError` ici, et non chez un
     * bachelier en pleine rentree. C'est deja arrive deux fois — d'ou l'absence
     * de tout decompte dans cette phrase.
     */
    public function test_chaque_raison_a_sa_propre_phrase(): void
    {
        $messages = array_map(
            fn (RefusCandidature $raison) => $this->reponsePour(
                new RefusCandidatureException($raison, 'peu importe')
            )->getData(true)['message'],
            RefusCandidature::cases()
        );

        $this->assertCount(count(RefusCandidature::cases()), array_unique($messages));
    }
}

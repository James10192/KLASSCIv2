<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\Chatbot\Tools\EvolutionEncaissementsTool;
use App\Services\Chatbot\Tools\GetDashboardKpisTool;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Harnais de l'agent contre la base : historique rejoué avec ses appels
 * d'outil, comparaison d'année des indicateurs, encaissements mois par mois.
 */
class AgentHistoriqueEtOutilsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_l_historique_rejoue_les_appels_d_outil_au_lieu_d_un_repere(): void
    {
        $user = User::factory()->create();
        $conversation = ChatbotConversation::create(['user_id' => $user->id, 'session_id' => (string) Str::uuid(), 'is_active' => true, 'last_activity_at' => now()]);
        ChatbotMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => 'Qui doit le plus ?', 'display_type' => 'text']);
        ChatbotMessage::create([
            'conversation_id' => $conversation->id, 'role' => 'assistant', 'display_type' => 'cards',
            'content' => "Je regarde.\n\nKOUAKOU doit le plus.",
            'metadata' => [
                'trace' => [
                    ['role' => 'assistant', 'texte' => 'Je regarde.', 'appels' => [['id' => 'c1', 'nom' => 'search_debtors', 'arguments' => []]]],
                    ['role' => 'outil', 'id' => 'c1', 'nom' => 'search_debtors', 'resultat' => '{"nombre":1}'],
                ],
                'parties' => [
                    ['type' => 'texte', 'texte' => 'Je regarde.'],
                    ['type' => 'etape', 'id' => 'c1', 'nom' => 'search_debtors', 'etat' => 'termine'],
                    ['type' => 'widget', 'id' => 'c1', 'kind' => 'cards', 'data' => []],
                    ['type' => 'texte', 'texte' => 'KOUAKOU doit le plus.'],
                ],
            ],
        ]);

        $messages = app(ConstructeurDePrompt::class)->messages($conversation, 'Et le deuxième ?');

        $this->assertSame(['user', 'assistant', 'outil', 'assistant', 'user'], array_column($messages, 'role'));
        $this->assertSame('KOUAKOU doit le plus.', $messages[3]['texte'], 'le texte après le dernier outil, sans la phrase d\'avant');
        // Identifiants réécrits : ceux d'origine venaient du modèle de l'époque.
        $this->assertSame('h00000001', $messages[1]['appels'][0]['id']);
        $this->assertSame('h00000001', $messages[2]['id']);
        $this->assertStringNotContainsString('widget', json_encode($messages, JSON_UNESCAPED_UNICODE), 'plus aucun repère à recopier');
    }

    public function test_deux_reponses_passees_au_meme_identifiant_sont_renumerotees(): void
    {
        $user = User::factory()->create();
        $conversation = ChatbotConversation::create(['user_id' => $user->id, 'session_id' => (string) Str::uuid(), 'is_active' => true, 'last_activity_at' => now()]);
        foreach (['A', 'B'] as $lettre) {
            ChatbotMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => "Question {$lettre}", 'display_type' => 'text']);
            ChatbotMessage::create([
                'conversation_id' => $conversation->id, 'role' => 'assistant', 'display_type' => 'text', 'content' => "Réponse {$lettre}",
                'metadata' => [
                    'trace' => [
                        ['role' => 'assistant', 'texte' => '', 'appels' => [['id' => 'gemini_1', 'nom' => 'search_fees', 'arguments' => ['q' => $lettre]]]],
                        ['role' => 'outil', 'id' => 'gemini_1', 'nom' => 'search_fees', 'resultat' => '{}'],
                    ],
                    'parties' => [
                        ['type' => 'etape', 'id' => 'gemini_1', 'nom' => 'search_fees', 'etat' => 'termine'],
                        ['type' => 'texte', 'texte' => "Réponse {$lettre}"],
                    ],
                ],
            ]);
        }

        $messages = app(ConstructeurDePrompt::class)->messages($conversation, 'Et alors ?');
        $ids = collect($messages)->where('role', 'outil')->pluck('id')->all();

        $this->assertSame(['h00000001', 'h00000002'], $ids);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{9}$/', $ids[0], 'format accepté par Mistral');
    }

    public function test_une_reponse_coupee_apres_ses_outils_ne_rejoue_ni_trace_ni_repere(): void
    {
        $user = User::factory()->create();
        $conversation = ChatbotConversation::create(['user_id' => $user->id, 'session_id' => (string) Str::uuid(), 'is_active' => true, 'last_activity_at' => now()]);
        ChatbotMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => 'Question A', 'display_type' => 'text']);
        ChatbotMessage::create([
            'conversation_id' => $conversation->id, 'role' => 'assistant', 'display_type' => 'text', 'content' => '',
            'metadata' => [
                'trace' => [
                    ['role' => 'assistant', 'texte' => '', 'appels' => [['id' => 'x', 'nom' => 'search_fees', 'arguments' => []]]],
                    ['role' => 'outil', 'id' => 'x', 'nom' => 'search_fees', 'resultat' => '{}'],
                ],
                'parties' => [['type' => 'etape', 'id' => 'x', 'nom' => 'search_fees', 'etat' => 'termine']],
            ],
        ]);

        $messages = app(ConstructeurDePrompt::class)->messages($conversation, 'Et alors ?');

        // Pas de trace orpheline, pas de repère : les deux questions forment un seul tour.
        $this->assertSame(['user'], array_column($messages, 'role'));
        $this->assertSame("Question A\n\nEt alors ?", $messages[0]['texte']);
    }

    public function test_le_prompt_porte_l_environnement_et_la_page_ouverte(): void
    {
        $user = User::factory()->create(['name' => 'Awa Koné']);
        $systeme = app(ConstructeurDePrompt::class)->systeme($user, null, ['page_title' => 'Fiche étudiant', 'current_path' => '/esbtp/etudiants/2743']);

        $this->assertStringContainsString('<environnement>', $systeme);
        $this->assertStringContainsString('Awa Koné', $systeme);
        $this->assertStringContainsString("la fiche de l'étudiant n° 2743", $systeme);
        $this->assertStringContainsString(Carbon::now()->locale('fr')->isoFormat('YYYY'), $systeme);
        $this->assertStringNotContainsString('Ne génère JAMAIS de tableaux', $systeme);
    }

    public function test_les_indicateurs_comparent_avec_l_annee_precedente_et_donnent_des_cartes(): void
    {
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $avant = ESBTPAnneeUniversitaire::factory()->create(['name' => '2090-2091', 'start_date' => '2090-09-01', 'end_date' => '2091-07-31']);
        $courante = ESBTPAnneeUniversitaire::factory()->create(['name' => '2091-2092', 'start_date' => '2091-09-01', 'end_date' => '2092-07-31', 'is_current' => true]);
        ESBTPInscription::factory()->count(2)->create(['annee_universitaire_id' => $avant->id]);
        ESBTPInscription::factory()->count(3)->create(['annee_universitaire_id' => $courante->id]);

        $resultat = (new GetDashboardKpisTool())->execute(['focus' => 'general'], User::factory()->create());

        $this->assertSame(3, $resultat['kpis']['inscrits_annee_courante']);
        $this->assertSame(2, $resultat['kpis']['inscrits_annee_precedente']);
        $this->assertSame('kpis', $resultat['widget']['kind']);
        $inscrits = $resultat['widget']['elements'][0];
        $this->assertSame(3, $inscrits['valeur']);
        $this->assertStringContainsString('+50', $inscrits['repere']);
        $this->assertSame('succes', $inscrits['ton']);
        $this->assertNull($inscrits['url'], 'pas de lien vers une liste que la personne ne peut pas ouvrir');
    }

    public function test_la_repartition_compte_comme_le_tableau_de_bord(): void
    {
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2034-2035', 'start_date' => '2034-09-01', 'end_date' => '2035-07-31', 'is_current' => true]);
        // Deux classes du même nom : deux barres, distinguées par leur code.
        $classeA = \App\Models\ESBTPClasse::factory()->create(['name' => 'BATIMENT A']);
        $classeB = \App\Models\ESBTPClasse::factory()->create(['name' => 'BATIMENT A']);
        ESBTPInscription::factory()->count(3)->create(['annee_universitaire_id' => $annee->id, 'classe_id' => $classeA->id]);
        $double = ESBTPInscription::factory()->create(['annee_universitaire_id' => $annee->id, 'classe_id' => $classeB->id]);
        // Ne comptent pas, comme au tableau de bord : non validée, terminée, supprimée.
        ESBTPInscription::factory()->create(['annee_universitaire_id' => $annee->id, 'classe_id' => $classeB->id, 'workflow_step' => 'prospect']);
        ESBTPInscription::factory()->create(['annee_universitaire_id' => $annee->id, 'classe_id' => $classeB->id, 'status' => 'terminée']);
        ESBTPInscription::factory()->create(['annee_universitaire_id' => $annee->id, 'classe_id' => $classeB->id])->delete();
        // Même étudiant dans une seconde classe : une fois au total, une fois par groupe.
        $classeC = \App\Models\ESBTPClasse::factory()->create(['name' => 'TP 1']);
        ESBTPInscription::factory()->create(['annee_universitaire_id' => $annee->id, 'classe_id' => $classeC->id, 'etudiant_id' => $double->etudiant_id]);

        $r = (new \App\Services\Chatbot\Tools\RepartitionEffectifsTool())->execute(['par' => 'classe'], User::factory()->create());

        $parGroupe = array_column($r['results'], 'inscrits', 'classe');
        $this->assertSame(3, $parGroupe["BATIMENT A ({$classeA->code})"]);
        $this->assertSame(1, $parGroupe["BATIMENT A ({$classeB->code})"]);
        $this->assertSame(1, $parGroupe['TP 1']);
        $this->assertSame(4, $r['totaux']['inscrits']);
        $this->assertSame(app(\App\Domain\Students\StudentCountService::class)->inscritsDe($annee->id), $r['totaux']['inscrits']);
        $this->assertStringContainsString('plusieurs groupes', $r['remarque']);
        $this->assertSame('graphique', $r['widget']['kind']);
        $this->assertSame([3, 1, 1], $r['widget']['series'][0]['valeurs']);
    }

    public function test_une_inscription_sans_classe_reste_comptee(): void
    {
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2035-2036', 'start_date' => '2035-09-01', 'end_date' => '2036-07-31', 'is_current' => true]);
        ESBTPInscription::factory()->create(['annee_universitaire_id' => $annee->id, 'classe_id' => null]);

        $r = (new \App\Services\Chatbot\Tools\RepartitionEffectifsTool())->execute(['par' => 'filiere'], User::factory()->create());

        $this->assertSame([['filiere' => 'Non renseigné', 'inscrits' => 1, 'part' => '100 %']], $r['results']);
        $this->assertSame(1, $r['totaux']['inscrits']);
        $this->assertNull($r['remarque']);
    }

    public function test_les_encaissements_par_mois_ne_comptent_que_le_valide_et_comparent(): void
    {
        Carbon::setTestNow('2035-06-15 10:00:00');
        try {
            $inscription = ESBTPInscription::factory()->create();
            ESBTPPaiement::factory()->pour($inscription)->create(['montant' => 100000, 'date_paiement' => '2035-06-02']);
            ESBTPPaiement::factory()->pour($inscription)->create(['montant' => 50000, 'date_paiement' => '2035-05-10']);
            ESBTPPaiement::factory()->pour($inscription)->create(['montant' => 30000, 'date_paiement' => '2035-05-11', 'status' => 'en_attente']);
            ESBTPPaiement::factory()->pour($inscription)->create(['montant' => 40000, 'date_paiement' => '2035-04-20']);

            $r = (new EvolutionEncaissementsTool())->execute(['mois' => 2], User::factory()->create());

            $this->assertSame(['Mai 35', 'Juin 35'], array_map(fn ($l) => rtrim($l, '.'), $r['widget']['libelles']));
            $this->assertSame([50000.0, 100000.0], array_map('floatval', $r['widget']['series'][0]['valeurs']));
            $this->assertSame('150 000 FCFA', $r['totaux']['total_periode']);
            $this->assertSame('40 000 FCFA', $r['totaux']['total_periode_precedente']);
            $this->assertTrue($r['results'][1]['en_cours']);
        } finally {
            Carbon::setTestNow();
        }
    }
}

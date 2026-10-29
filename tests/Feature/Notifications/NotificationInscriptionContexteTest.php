<?php

namespace Tests\Feature\Notifications;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\Notification;
use App\Services\Notifications\NotificationPresenter;
use Tests\TestCase;

/**
 * Une notification liée à une inscription garde son propre message : la
 * fiche n'ajoute que le contexte (élève, classe, statut lisible).
 */
class NotificationInscriptionContexteTest extends TestCase
{
    public function test_le_message_d_origine_est_garde_et_l_eleve_vient_en_contexte(): void
    {
        $inscription = new ESBTPInscription(['status' => 'active', 'workflow_step' => 'etudiant_cree']);
        $inscription->id = 42;
        $inscription->setRelation('etudiant', new ESBTPEtudiant(['nom' => 'KOUAME', 'prenoms' => 'Awa']));
        $inscription->setRelation('classe', new ESBTPClasse(['name' => '1BTS GC A']));
        $inscription->setRelation('paiements', collect());

        $notification = new Notification([
            'message' => 'Paiement de 50 000 FCFA reçu. Statut: validé',
            'link' => '/esbtp/inscriptions/42',
        ]);

        $lire = new \ReflectionMethod(NotificationPresenter::class, 'readMessage');
        $lire->setAccessible(true);
        [$principal, $etiquettes] = $lire->invoke(new NotificationPresenter, $notification, collect([42 => $inscription]));

        $this->assertSame('Paiement de 50 000 FCFA reçu.', $principal);
        $parCle = collect($etiquettes)->keyBy('key');
        $this->assertSame('KOUAME Awa', $parCle['Étudiant']['value']);
        $this->assertSame('1BTS GC A', $parCle['Classe']['value']);
        // Le statut est lisible, et une seule fois.
        $this->assertSame('Active', $parCle['Statut']['value']);
        $this->assertSame(1, collect($etiquettes)->where('key', 'Statut')->count());
    }

    public function test_le_statut_brut_n_est_jamais_affiche(): void
    {
        $this->assertSame('En attente', NotificationPresenter::statutInscription('en_attente'));
        $this->assertSame('Annulée', NotificationPresenter::statutInscription('annulée'));
        $this->assertSame('Non défini', NotificationPresenter::statutInscription(null));
    }
}

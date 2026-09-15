<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Services\EtudiantAcademicJourneyPresenter;
use Tests\TestCase;

/**
 * La moyenne annuelle affichée sur la fiche étudiant, côté diagramme de parcours.
 *
 * Ce cas-ci, contrairement à `AgregatDeLaPeriodeTest`, couvre bien la
 * CORRECTION : `EtudiantAcademicJourneyPresenter::lmdMetrics()` portait sa
 * propre formule, avec le filtre `moyenne_generale > 0`. Exécuté sur le code
 * d'avant, il échoue en rendant 8.5.
 *
 * Sans base de données, malgré `Tests\TestCase` : `presentFromCollections()`
 * prend les collections qu'on lui donne et ne va rien chercher. Le cadre Laravel
 * n'est là que pour les façades dont le journal d'audit a besoin à la
 * construction d'un modèle ; aucune requête n'est émise — toutes les relations
 * lues par le présentateur sont épinglées plus bas. C'est ce qui rend ce contrôle
 * exécutable là où MySQL n'est pas disponible, donc réellement rejoué au lieu
 * d'être écrit et jamais lancé.
 */
class MoyenneAnnuelleDuParcoursTest extends TestCase
{
    private function classeLmd(int $id): ESBTPClasse
    {
        $classe = new ESBTPClasse;
        $classe->id = $id;
        $classe->name = 'Licence 1 Génie civil';
        $classe->systeme_academique = 'LMD';
        // Mêmes raisons que pour l'inscription : les libellés du diagramme lisent
        // le niveau et la filière de la classe. Sans relations posées, chacune
        // déclencherait une requête.
        $classe->setRelation('niveau', null);
        $classe->setRelation('filiere', null);
        $classe->setRelation('parcours', null);

        return $classe;
    }

    private function inscription(int $id, ESBTPClasse $classe, int $anneeId): ESBTPInscription
    {
        $inscription = new ESBTPInscription;
        $inscription->id = $id;
        $inscription->classe_id = $classe->id;
        $inscription->annee_universitaire_id = $anneeId;
        $inscription->status = 'active';
        $inscription->setRelation('classe', $classe);
        // Épinglée explicitement : le tri lit `anneeUniversitaire?->start_date`,
        // et sans relation posée Eloquent irait la chercher en base — ce que ce
        // cas, volontairement sans base, ne peut pas faire.
        $inscription->setRelation('anneeUniversitaire', null);

        return $inscription;
    }

    private function bulletin(int $id, int $classeId, int $anneeId, int $semestre, ?float $moyenne, ?int $credits): ESBTPLMDBulletin
    {
        $bulletin = new ESBTPLMDBulletin;
        $bulletin->id = $id;
        $bulletin->classe_id = $classeId;
        $bulletin->annee_universitaire_id = $anneeId;
        $bulletin->semestre = $semestre;
        $bulletin->moyenne_generale = $moyenne;
        $bulletin->credits_totaux = $credits;
        $bulletin->credits_capitalises = 0;

        return $bulletin;
    }

    /** @return array<string, mixed> */
    private function metriquesDeLAnnee(iterable $bulletins): array
    {
        $classe = $this->classeLmd(1);

        $rendu = (new EtudiantAcademicJourneyPresenter)->presentFromCollections(
            collect([$this->inscription(1, $classe, 7)]),
            null,
            collect($bulletins),
            null
        );

        return $rendu['items']->first()['metrics'];
    }

    public function test_un_semestre_a_zero_pese_dans_la_moyenne_de_l_annee(): void
    {
        // Le cas fondateur. Avant la correction, le filtre `> 0` retirait le
        // second bulletin, il n'en restait qu'un, et le diagramme annonçait
        // 8,50 comme moyenne de l'ANNÉE. La valeur juste est
        // (8,50 × 30 + 0 × 30) / 60 = 4,25.
        $metrics = $this->metriquesDeLAnnee([
            $this->bulletin(1, 1, 7, 1, 8.5, 30),
            $this->bulletin(2, 1, 7, 2, 0.0, 30),
        ]);

        $this->assertSame(4.25, $metrics['moyenne']);
        $this->assertSame('4,25 / 20', $metrics['moyenne_label']);
    }

    public function test_une_annee_dont_un_semestre_n_est_pas_calculable_n_affiche_pas_de_moyenne(): void
    {
        // Et non une moyenne plausible tirée de l'autre moitié : l'ancienne
        // formule rendait ici 12,00, présenté comme la moyenne de l'année.
        $metrics = $this->metriquesDeLAnnee([
            $this->bulletin(1, 1, 7, 1, 12.0, 30),
            $this->bulletin(2, 1, 7, 2, null, 30),
        ]);

        $this->assertNull($metrics['moyenne']);
        $this->assertNull($metrics['moyenne_label']);
    }

    public function test_un_seul_semestre_reste_sa_propre_moyenne(): void
    {
        // La borne qui protège le cas courant : en janvier, seul le S1 est en
        // base, et l'écran doit montrer ce qu'il montrait.
        $metrics = $this->metriquesDeLAnnee([
            $this->bulletin(1, 1, 7, 1, 11.37, 30),
        ]);

        $this->assertSame(11.37, $metrics['moyenne']);
    }
}

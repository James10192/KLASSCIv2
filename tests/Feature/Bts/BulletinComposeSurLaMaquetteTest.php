<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPResultatMatiere;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * Composition du bulletin depuis la maquette.
 *
 * Decision metier validee : quand la maquette est renseignee, le bulletin liste
 * TOUTES les matieres qu'elle prevoit au semestre, celles sans note portant le
 * symbole de trou. Une matiere qui manque n'est plus invisible : le lecteur voit
 * qu'elle etait prevue et qu'elle n'a pas ete notee.
 *
 * BTS uniquement.
 */
class BulletinComposeSurLaMaquetteTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;
    use SeedsConfiguredBulletin;

    /** Rattache une matiere au combo de la classe, avec son semestre valide. */
    private function inscrireAuReferentiel(int $matiereId, ?int $semestre, bool $valide = true): void
    {
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiereId,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'semestre' => $semestre,
            'semestre_renseigne' => $valide,
        ]);
    }

    /**
     * @test
     *
     * Le coeur de la decision : la matiere prevue mais non notee apparait, et
     * elle ne deplace pas la moyenne.
     */
    public function une_matiere_prevue_sans_note_apparait_avec_le_symbole_de_trou(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        $notee = $this->matiereConfiguree();
        $muette = $this->matiereConfiguree();

        $this->inscrireAuReferentiel((int) $notee->id, 1);
        $this->inscrireAuReferentiel((int) $muette->id, 1);

        // Seule la premiere a des notes.
        $this->noter($etudiant, $this->evaluationDe($notee), 14);

        $bulletin = $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [(int) $notee->id, (int) $muette->id]
        );

        $data = app(BulletinService::class)->genererDonneesBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $lignes = ESBTPResultatMatiere::query()->where('bulletin_id', $bulletin->id)->get()->keyBy('matiere_id');

        $this->assertCount(2, $lignes, 'La matière prévue et non notée doit figurer au bulletin.');

        $trou = $lignes[$muette->id];
        $this->assertSame(ESBTPResultatMatiere::STATUT_NON_NOTE, $trou->statut);
        $this->assertNull($trou->moyenne);
        $this->assertNull($trou->rang);
        $this->assertSame('—', $trou->moyenneLisible());

        // La moyenne reste celle de la seule matiere notee : le trou ne compte
        // pas comme un zero.
        $this->assertEqualsWithDelta(14.0, (float) $data['moyenneGlobale'], 0.001);
    }

    /**
     * @test
     *
     * Non-regression : tant que l'ecole n'a rien valide, le bulletin garde
     * exactement les lignes d'avant.
     */
    public function une_maquette_non_validee_ne_compose_rien(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        $notee = $this->matiereConfiguree();
        $muette = $this->matiereConfiguree();

        // Les deux sont au referentiel, mais AUCUNE n'est validee.
        $this->inscrireAuReferentiel((int) $notee->id, 1, valide: false);
        $this->inscrireAuReferentiel((int) $muette->id, 1, valide: false);

        $this->noter($etudiant, $this->evaluationDe($notee), 14);

        $bulletin = $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [(int) $notee->id, (int) $muette->id]
        );

        app(BulletinService::class)->genererDonneesBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $lignes = ESBTPResultatMatiere::query()->where('bulletin_id', $bulletin->id)->get();

        $this->assertCount(1, $lignes, 'Sans maquette validée, aucune ligne ne doit être ajoutée.');
        $this->assertSame((int) $notee->id, (int) $lignes->first()->matiere_id);
    }

    /**
     * @test
     *
     * Une matiere prevue a l'autre semestre n'a rien a faire sur ce bulletin.
     */
    public function une_matiere_du_second_semestre_n_apparait_pas_au_premier(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        $notee = $this->matiereConfiguree();
        $plusTard = $this->matiereConfiguree();

        $this->inscrireAuReferentiel((int) $notee->id, 1);
        $this->inscrireAuReferentiel((int) $plusTard->id, 2);

        $this->noter($etudiant, $this->evaluationDe($notee), 11);

        $bulletin = $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [(int) $notee->id]
        );

        app(BulletinService::class)->genererDonneesBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $matiereIds = ESBTPResultatMatiere::query()
            ->where('bulletin_id', $bulletin->id)
            ->pluck('matiere_id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains((int) $notee->id, $matiereIds);
        $this->assertNotContains((int) $plusTard->id, $matiereIds);
    }

    /**
     * @test
     *
     * Une matiere reellement notee mais absente de la maquette reste au
     * bulletin : la maquette ajoute des lignes, elle n'en retire jamais. Une
     * saisie de referentiel incomplete ne doit pas effacer un travail evalue.
     */
    public function la_maquette_n_efface_jamais_une_matiere_notee(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        $auReferentiel = $this->matiereConfiguree();
        $horsReferentiel = $this->matiereConfiguree();

        $this->inscrireAuReferentiel((int) $auReferentiel->id, 1);
        // `$horsReferentiel` n'est rattachee a aucun combo.

        $this->noter($etudiant, $this->evaluationDe($auReferentiel), 12);
        $this->noter($etudiant, $this->evaluationDe($horsReferentiel), 16);

        $bulletin = $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [(int) $auReferentiel->id, (int) $horsReferentiel->id]
        );

        app(BulletinService::class)->genererDonneesBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $ligne = ESBTPResultatMatiere::query()
            ->where('bulletin_id', $bulletin->id)
            ->where('matiere_id', $horsReferentiel->id)
            ->first();

        $this->assertNotNull($ligne, 'Une matière notée hors maquette doit rester au bulletin.');
        $this->assertSame(ESBTPResultatMatiere::STATUT_NOTE, $ligne->statut);
        $this->assertEqualsWithDelta(16.0, (float) $ligne->moyenne, 0.001);
    }
}

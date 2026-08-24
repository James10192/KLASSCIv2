<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * La liste des resultats d'une classe montre qui APPARTIENT a la classe.
 *
 * Elle unissait auparavant les inscriptions, les NOTES et les RESULTATS
 * persistes. Cette union ramenait les partis : une specialite corrigee garde
 * les notes prises avant la correction, et l'etudiant reapparaissait dans son
 * ANCIENNE classe en plus de la nouvelle. C'est le signalement de l'ESBTP
 * Yamoussoukro : « les etudiants dont on a corrige la specialite se retrouvent
 * dans les deux classes ».
 *
 * Les notes disent ou l'on a evalue ; la cohorte de phases dit a qui la classe
 * appartient. Ce ne sont pas la meme question.
 */
class ListeResultatsClasseTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
    }

    private function specialite(string $nom): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'name' => $nom,
            'filiere_id' => ESBTPFiliere::factory()->create([
                'is_tronc_commun' => false,
                'parent_id' => $this->filiere->id,
            ])->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
    }

    private function idsDeLaListe(ESBTPClasse $classe, string $periode, bool $inactifsInclus = true): array
    {
        return app(BulletinService::class)
            ->buildEtudiantsQuery($classe->id, $this->annee->id, $inactifsInclus, $periode)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    /** Tronc commun au semestre 1, puis specialite active au semestre 2. */
    private function poserLesPhases(ESBTPInscription $inscription, ESBTPClasse $specialite): void
    {
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $this->classe->id,
            'filiere_id' => $this->filiere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $specialite->id,
            'filiere_id' => $specialite->filiere_id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);
        $inscription->update(['classe_id' => $specialite->id]);
    }

    public function test_une_specialite_corrigee_ne_garde_pas_l_etudiant(): void
    {
        $ancienne = $this->specialite('SPECIALITE A');
        $nouvelle = $this->specialite('SPECIALITE B');

        $etudiant = $this->etudiantInscrit();
        $inscription = ESBTPInscription::where('etudiant_id', $etudiant->id)->firstOrFail();

        // Des notes ont ete prises dans l'ancienne specialite avant la correction.
        $matiere = $this->matiereConfiguree();
        $evaluation = $this->evaluationDe($matiere);
        $evaluation->update(['classe_id' => $ancienne->id, 'periode' => 'semestre2']);
        $this->noter($etudiant, $evaluation, 13, $ancienne);

        // Orientation, puis correction : l'ancienne phase est fermee, la
        // nouvelle est active.
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $this->classe->id,
            'filiere_id' => $this->filiere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $ancienne->id,
            'filiere_id' => $ancienne->filiere_id,
            'semestre_debut' => 2,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $nouvelle->id,
            'filiere_id' => $nouvelle->filiere_id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);
        $inscription->update(['classe_id' => $nouvelle->id]);

        $this->assertSame([(int) $etudiant->id], $this->idsDeLaListe($nouvelle, 'semestre2'),
            'La nouvelle specialite doit le montrer.');
        $this->assertSame([], $this->idsDeLaListe($ancienne, 'semestre2'),
            'L ancienne specialite ne doit plus le montrer, malgre les notes qui y restent.');
    }

    /**
     * Le cas que l'union par les notes servait a couvrir doit continuer de
     * marcher : au semestre 1, le tronc commun garde celui qu'il a porte.
     */
    public function test_le_tronc_commun_garde_son_etudiant_au_semestre_1(): void
    {
        $specialite = $this->specialite('SPECIALITE B');
        $etudiant = $this->etudiantInscrit();
        $this->poserLesPhases(ESBTPInscription::where('etudiant_id', $etudiant->id)->firstOrFail(), $specialite);

        $this->assertSame([(int) $etudiant->id], $this->idsDeLaListe($this->classe, 'semestre1'));
        $this->assertSame([(int) $etudiant->id], $this->idsDeLaListe($specialite, 'semestre2'));
    }

    /**
     * « Annuel » est l'ecran par defaut. Une classe de tronc commun doit y
     * montrer ceux qu'elle a portes au semestre 1 : sans cela, la page
     * s'ouvre vide pour toutes les classes de tronc commun des six ecoles.
     */
    public function test_l_annuel_du_tronc_commun_montre_encore_ses_etudiants(): void
    {
        $specialite = $this->specialite('SPECIALITE B');
        $etudiant = $this->etudiantInscrit();
        $this->poserLesPhases(ESBTPInscription::where('etudiant_id', $etudiant->id)->firstOrFail(), $specialite);

        $this->assertSame([(int) $etudiant->id], $this->idsDeLaListe($this->classe, 'annuel'),
            'En annuel, le tronc commun garde celui qu il a porte au semestre 1.');
        $this->assertSame([(int) $etudiant->id], $this->idsDeLaListe($specialite, 'annuel'));
    }

    /**
     * « Inclure les inscriptions inactives » doit tenir sa promesse, meme pour
     * un etudiant parti en specialite avant d'abandonner : son inscription
     * designe la specialite, seule une phase le rattache au tronc commun.
     *
     * ET la promesse s'arrete la : l'elargissement porte sur le PERIMETRE
     * d'inscriptions, jamais sur la regle d'appartenance. Au semestre 2, sa
     * phase de tronc commun est close -- le tronc commun ne le montre plus.
     * Une requete directe sur les phases repondait « cette classe a-t-elle un
     * jour ete mentionnee » et le rendait aux deux semestres.
     */
    public function test_une_inscription_inactive_reste_visible_par_sa_phase(): void
    {
        $specialite = $this->specialite('SPECIALITE B');
        $etudiant = $this->etudiantInscrit();
        $inscription = ESBTPInscription::where('etudiant_id', $etudiant->id)->firstOrFail();
        $this->poserLesPhases($inscription, $specialite);
        $inscription->update(['status' => 'annulée']);

        $this->assertContains((int) $etudiant->id, $this->idsDeLaListe($this->classe, 'semestre1'),
            'Bouton actif : un abandon doit rester visible sur la classe qui l a porte au semestre 1.');
        $this->assertSame([], $this->idsDeLaListe($this->classe, 'semestre2'),
            'Au semestre 2 sa phase de tronc commun est close : il n y est plus, meme bouton actif.');
        $this->assertSame([(int) $etudiant->id], $this->idsDeLaListe($specialite, 'semestre2'),
            'Au semestre 2 il appartient a sa specialite.');
        $this->assertSame([], $this->idsDeLaListe($this->classe, 'semestre1', inactifsInclus: false),
            'Bouton inactif : il disparait.');
    }

    /**
     * `terminee` n'est pas un cas limite : c'est le statut que l'orientation
     * ecrit sur l'inscription d'origine. Un etudiant oriente normalement ne
     * doit jamais apparaitre dans deux classes au meme semestre.
     */
    public function test_un_oriente_termine_n_apparait_que_dans_une_classe_par_semestre(): void
    {
        $specialite = $this->specialite('SPECIALITE B');
        $etudiant = $this->etudiantInscrit();
        $inscription = ESBTPInscription::where('etudiant_id', $etudiant->id)->firstOrFail();
        $this->poserLesPhases($inscription, $specialite);
        $inscription->update(['status' => 'terminée']);

        foreach (['semestre1', 'semestre2', 'annuel'] as $periode) {
            $tc = $this->idsDeLaListe($this->classe, $periode);
            $spe = $this->idsDeLaListe($specialite, $periode);

            if ($periode === 'semestre1') {
                $this->assertSame([(int) $etudiant->id], $tc, 'S1 : le tronc commun le porte.');
                $this->assertSame([], $spe, 'S1 : la specialite ne l a pas encore.');
            } elseif ($periode === 'semestre2') {
                $this->assertSame([], $tc, 'S2 : il a quitte le tronc commun.');
                $this->assertSame([(int) $etudiant->id], $spe, 'S2 : la specialite le porte.');
            } else {
                // En annuel chaque classe montre ce qu'elle a porte : les deux
                // le listent, c'est l'union assumee d'une liste. Mais jamais
                // deux classes au MEME semestre.
                $this->assertSame([(int) $etudiant->id], $tc);
                $this->assertSame([(int) $etudiant->id], $spe);
            }
        }
    }

    /**
     * Une specialite corrigee sur une inscription NON active ne reprend pas
     * l'etudiant : c'est le signalement de Yamoussoukro, sur la population que
     * l'orientation fabrique elle-meme.
     */
    public function test_une_specialite_corrigee_sur_inscription_terminee_ne_le_reprend_pas(): void
    {
        $ancienne = $this->specialite('SPECIALITE A');
        $nouvelle = $this->specialite('SPECIALITE B');

        $etudiant = $this->etudiantInscrit();
        $inscription = ESBTPInscription::where('etudiant_id', $etudiant->id)->firstOrFail();

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $this->classe->id,
            'filiere_id' => $this->filiere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $ancienne->id,
            'filiere_id' => $ancienne->filiere_id,
            'semestre_debut' => 2,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $nouvelle->id,
            'filiere_id' => $nouvelle->filiere_id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);
        $inscription->update(['classe_id' => $nouvelle->id, 'status' => 'terminée']);

        foreach (['semestre1', 'semestre2', 'annuel'] as $periode) {
            $this->assertSame([], $this->idsDeLaListe($ancienne, $periode),
                "Periode $periode : l ancienne specialite corrigee ne doit jamais le montrer.");
        }
        $this->assertSame([(int) $etudiant->id], $this->idsDeLaListe($nouvelle, 'semestre2'));
    }
}

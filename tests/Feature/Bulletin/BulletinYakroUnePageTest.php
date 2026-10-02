<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\BulletinService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gabarit Yakro : un bulletin BTS 2 a vingt matieres tient sur UNE page.
 *
 * Mesure en production (esbtp-yakro, octobre 2026) : BTS2 Travaux Publics
 * passait en deux pages aux deux semestres, BTS2 URBA et BTS2 Mines au
 * semestre 2 — la signature seule partait en page 2. La decision du conseil
 * et la signature montent desormais sous les statistiques, dans la colonne
 * de droite que les mentions laissaient vide.
 */
class BulletinYakroUnePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_bts2_a_vingt_matieres_tient_sur_une_page_aux_deux_semestres(): void
    {
        [$etudiant, $classe, $annee] = $this->monterUneClasseDeVingtMatieres();

        foreach (['semestre1', 'semestre2'] as $periode) {
            $this->assertSame(1, $this->pages($etudiant, $classe, $annee, $periode), "Bulletin du {$periode} sur deux pages.");
        }
    }

    public function test_sans_statistiques_la_decision_garde_sa_place_pleine_largeur(): void
    {
        [$etudiant, $classe, $annee] = $this->monterUneClasseDeVingtMatieres(2);
        \App\Helpers\SettingsHelper::setOrCreate('bulletin_show_statistics', '0');

        $html = view(app(BulletinService::class)->getBulletinTemplateView(), $this->donnees($etudiant, $classe, $annee, 'semestre2'))->render();

        $this->assertStringNotContainsString('class="conseil-a-droite"', $html);
        $this->assertStringContainsString('decision-container', $html);
    }

    private function pages(int $etudiant, ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, string $periode): int
    {
        $pdf = Pdf::loadView(app(BulletinService::class)->getBulletinTemplateView(), $this->donnees($etudiant, $classe, $annee, $periode))
            ->setPaper('a4', 'portrait')
            ->setOptions(['dpi' => 150, 'defaultFont' => 'DejaVu Sans', 'isPhpEnabled' => true, 'isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);
        $pdf->render();

        return $pdf->getDomPDF()->getCanvas()->get_page_count();
    }

    private function donnees(int $etudiant, ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, string $periode): array
    {
        $svc = app(BulletinService::class);
        $d = $svc->genererDonneesBulletinPreview($etudiant, $classe->id, $annee->id, $periode);
        $d['logoBase64'] = null;
        $d['photoEtudiantBase64'] = null;
        $d['isPdfExport'] = true;

        return $d;
    }

    /** @return array{0: int, 1: ESBTPClasse, 2: ESBTPAnneeUniversitaire} */
    private function monterUneClasseDeVingtMatieres(int $nbTechniques = 18): array
    {
        User::factory()->create(['id' => 1]);
        $this->actingAs(User::find(1));
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 2, 'type' => 'BTS', 'name' => 'Deuxième année BTS']);
        $filiere = ESBTPFiliere::factory()->create(['name' => 'GENIE CIVIL OPTION TRAVAUX PUBLICS']);
        $classe = ESBTPClasse::factory()->create(['name' => 'BTS2 Travaux Publics A', 'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id, 'systeme_academique' => 'BTS']);
        $techniques = array_slice(['DROIT DU TRAVAIL', 'ENTREPRENEURIAT', 'TOPOGRAPHIE', 'BARRAGES', 'BETON ARME', 'CIRCULATION ROUTIERE', 'COVADIS',
            'DESSIN BATIMENT', 'ENTRETIEN ROUTIER', 'METRE ET ETUDE DE PRIX', 'ORGANISATION ET GESTION DES CHANTIERS', "PROJET DE FIN D'ETUDE",
            'SIGNALISATION ROUTIERE', 'VOIRIES ET RESEAUX DIVERS', 'HYDRAULIQUE ROUTIERE', 'GEOTECHNIQUE ROUTIERE', 'MECANIQUE DES SOLS', "OUVRAGES D'ART"], 0, $nbTechniques);
        $etudiants = [];
        for ($i = 0; $i < 2; $i++) {
            $e = ESBTPEtudiant::factory()->create();
            ESBTPInscription::factory()->create(['etudiant_id' => $e->id, 'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id,
                'status' => 'active', 'workflow_step' => 'etudiant_cree']);
            $etudiants[] = $e;
        }
        $ids = ['generales' => [], 'techniques' => []];
        foreach (['generales' => ['MATHEMATIQUES', "TECHNIQUE DE RECHERCHE D'EMPLOI"], 'techniques' => $techniques] as $type => $noms) {
            foreach ($noms as $nom) {
                $m = ESBTPMatiere::factory()->create(['name' => $nom, 'unite_enseignement_id' => null, 'is_active' => true]);
                $ids[$type][] = $m->id;
                foreach (['semestre1', 'semestre2'] as $p) {
                    ESBTPConfigMatiere::create(['matiere_id' => $m->id, 'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id, 'periode' => $p,
                        'config' => ['type' => $type === 'generales' ? 'general' : 'technique', 'coefficient' => 3]]);
                    ESBTPMatiereCoefficient::create(['matiere_id' => $m->id, 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id,
                        'annee_universitaire_id' => $annee->id, 'periode' => $p, 'coefficient' => 3]);
                    $ev = ESBTPEvaluation::factory()->create(['matiere_id' => $m->id, 'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id,
                        'periode' => $p, 'status' => 'published', 'bareme' => 20, 'coefficient' => 1]);
                    foreach ($etudiants as $k => $e) {
                        ESBTPNote::create(['evaluation_id' => $ev->id, 'etudiant_id' => $e->id, 'matiere_id' => $m->id, 'classe_id' => $classe->id,
                            'note' => 6 + (($k * 3 + $m->id) % 13), 'is_absent' => false]);
                    }
                }
            }
        }
        foreach ($etudiants as $e) {
            foreach (['semestre1', 'semestre2'] as $p) {
                $b = ESBTPBulletin::create(['etudiant_id' => $e->id, 'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id,
                    'periode' => $p, 'config_matieres' => $ids]);
                $b->professeurs = json_encode(array_fill_keys(array_merge($ids['generales'], $ids['techniques']), "N'GUESSAN JACQUES"));
                $b->save();
            }
        }

        return [(int) $etudiants[0]->id, $classe, $annee];
    }
}

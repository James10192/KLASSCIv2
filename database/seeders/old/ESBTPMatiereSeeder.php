<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPClasse;

class ESBTPMatiereSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matieresByFiliere = [
            'BTS1-TC' => [
                [
                    'name' => 'Dessin Technique',
                    'description' => 'Matière : Dessin Technique',
                    'code' => 'DESSIN_TECHNIQUE',
                ],
                [
                    'name' => 'Mathématiques',
                    'description' => 'Matière : Mathématiques',
                    'code' => 'MATHEMATIQUES',
                ],
                [
                    'name' => 'Physique',
                    'description' => 'Matière : Physique',
                    'code' => 'PHYSIQUE',
                ],
                [
                    'name' => 'Chimie',
                    'description' => 'Matière : Chimie',
                    'code' => 'CHIMIE',
                ],
                [
                    'name' => 'Informatique',
                    'description' => 'Matière : Informatique',
                    'code' => 'INFORMATIQUE',
                ],
                [
                    'name' => 'Français',
                    'description' => 'Matière : Français',
                    'code' => 'FRANCAIS',
                ],
                [
                    'name' => 'Anglais',
                    'description' => 'Matière : Anglais',
                    'code' => 'ANGLAIS',
                ],
            ],
            'BTS1-BAT' => [
                [
                    'name' => 'Résistance des Matériaux',
                    'description' => 'Matière : Résistance des Matériaux',
                    'code' => 'RDM',
                ],
                [
                    'name' => 'Mécanique des Sols',
                    'description' => 'Matière : Mécanique des Sols',
                    'code' => 'MDS',
                ],
                [
                    'name' => 'Topographie',
                    'description' => 'Matière : Topographie',
                    'code' => 'TOPO',
                ],
                [
                    'name' => 'Construction Métallique',
                    'description' => 'Matière : Construction Métallique',
                    'code' => 'CM',
                ],
            ],
            'BTS1-GTP' => [
                [
                    'name' => 'Hydrologie',
                    'description' => 'Matière : Hydrologie',
                    'code' => 'HYDROLOGIE',
                ],
                [
                    'name' => 'Hydraulique',
                    'description' => 'Matière : Hydraulique',
                    'code' => 'HYDRAULIQUE',
                ],
                [
                    'name' => 'Géotechnique',
                    'description' => 'Matière : Géotechnique',
                    'code' => 'GEOTECHNIQUE',
                ],
                [
                    'name' => 'Technique des Engins',
                    'description' => 'Matière : Technique des Engins',
                    'code' => 'TECHNIQUE_ENGINS',
                ],
                [
                    'name' => 'IHH',
                    'description' => 'Matière : IHH',
                    'code' => 'IHH',
                ],
                [
                    'name' => 'RDM',
                    'description' => 'Matière : RDM',
                    'code' => 'RDM',
                ],
                [
                    'name' => 'Electricité',
                    'description' => 'Matière : Electricité',
                    'code' => 'ELECTRICITE',
                ],
                [
                    'name' => 'Sécurité',
                    'description' => 'Matière : Sécurité',
                    'code' => 'SECURITE',
                ],
                [
                    'name' => 'Matériaux',
                    'description' => 'Matière : Matériaux',
                    'code' => 'MATERIAUX',
                ],
                [
                    'name' => 'IGC',
                    'description' => 'Matière : IGC',
                    'code' => 'IGC',
                ],
                [
                    'name' => 'Informatique',
                    'description' => 'Matière : Informatique',
                    'code' => 'INFORMATIQUE',
                ],
                [
                    'name' => 'GRV',
                    'description' => 'Matière : GRV',
                    'code' => 'GRV',
                ],
            ],
            'BTS1-GGT' => [
                [
                    'name' => 'Mathématiques',
                    'description' => 'Matière : Mathématiques',
                    'code' => 'MATHEMATIQUES',
                ],
                [
                    'name' => 'Calcul Topo',
                    'description' => 'Matière : Calcul Topo',
                    'code' => 'CALCUL_TOPO',
                ],
                [
                    'name' => 'Topo Générale',
                    'description' => 'Matière : Topo Générale',
                    'code' => 'TOPO_GENERALE',
                ],
                [
                    'name' => 'Electricité',
                    'description' => 'Matière : Electricité',
                    'code' => 'ELECTRICITE',
                ],
                [
                    'name' => 'Sécurité',
                    'description' => 'Matière : Sécurité',
                    'code' => 'SECURITE',
                ],
                [
                    'name' => 'Informatique',
                    'description' => 'Matière : Informatique',
                    'code' => 'INFORMATIQUE',
                ],
                [
                    'name' => 'TP Topo',
                    'description' => 'Matière : TP Topo',
                    'code' => 'TP_TOPO',
                ],
            ],
            'BTS1-MGP' => [
                [
                    'name' => 'Electricité',
                    'description' => 'Matière : Electricité',
                    'code' => 'ELECTRICITE',
                ],
                [
                    'name' => 'Géochimie',
                    'description' => 'Matière : Géochimie',
                    'code' => 'GEOCHIMIE',
                ],
                [
                    'name' => 'Géologie Générale',
                    'description' => 'Matière : Géologie Générale',
                    'code' => 'GEOLOGIE_GENERALE',
                ],
                [
                    'name' => 'Géologie Historique',
                    'description' => 'Matière : Géologie Historique',
                    'code' => 'GEOLOGIE_HISTORIQUE',
                ],
                [
                    'name' => 'Hydrologie',
                    'description' => 'Matière : Hydrologie',
                    'code' => 'HYDROLOGIE',
                ],
                [
                    'name' => 'Informatique',
                    'description' => 'Matière : Informatique',
                    'code' => 'INFORMATIQUE',
                ],
                [
                    'name' => 'Mécanique des Sols',
                    'description' => 'Matière : Mécanique des Sols',
                    'code' => 'MECA_SOL',
                ],
                [
                    'name' => 'Mécanique des Roches',
                    'description' => 'Matière : Mécanique des Roches',
                    'code' => 'MECA_ROCHE',
                ],
                [
                    'name' => 'Minéralogie',
                    'description' => 'Matière : Minéralogie',
                    'code' => 'MINERALOGIE',
                ],
                [
                    'name' => 'Sécurité',
                    'description' => 'Matière : Sécurité',
                    'code' => 'SECURITE',
                ],
                [
                    'name' => 'Mécanique des Fluides',
                    'description' => 'Matière : Mécanique des Fluides',
                    'code' => 'MECA_FLUIDES',
                ],
                [
                    'name' => 'Topographie Minière',
                    'description' => 'Matière : Topographie Minière',
                    'code' => 'TOPO_MINIERE',
                ],
            ],
            'BTS1-URB' => [
                [
                    'name' => 'Architecture',
                    'description' => 'Matière : Architecture',
                    'code' => 'ARCHITECTURE',
                ],
                [
                    'name' => 'Démographie',
                    'description' => 'Matière : Démographie',
                    'code' => 'DEMOGRAPHIE',
                ],
                [
                    'name' => 'Dessin Bâtiment',
                    'description' => 'Matière : Dessin Bâtiment',
                    'code' => 'DESSIN_BATIMENT',
                ],
                [
                    'name' => 'Electricité',
                    'description' => 'Matière : Electricité',
                    'code' => 'ELECTRICITE',
                ],
                [
                    'name' => 'Géographie Urbaine',
                    'description' => 'Matière : Géographie Urbaine',
                    'code' => 'GEOGRAPHIE_URBAINE',
                ],
                [
                    'name' => 'IHH',
                    'description' => 'Matière : IHH',
                    'code' => 'IHH',
                ],
                [
                    'name' => 'Informatique',
                    'description' => 'Matière : Informatique',
                    'code' => 'INFORMATIQUE',
                ],
                [
                    'name' => 'Introduction à l\'urbanisme',
                    'description' => 'Matière : Introduction à l\'urbanisme',
                    'code' => 'INTRO_URBANISME',
                ],
                [
                    'name' => 'Lecture Photo',
                    'description' => 'Matière : Lecture Photo',
                    'code' => 'LECTURE_PHOTO',
                ],
                [
                    'name' => 'Métré et Etude de prix',
                    'description' => 'Matière : Métré et Etude de prix',
                    'code' => 'METRE_PRIX',
                ],
                [
                    'name' => 'Sécurité',
                    'description' => 'Matière : Sécurité',
                    'code' => 'SECURITE',
                ],
                [
                    'name' => 'Sociologie Urbaine',
                    'description' => 'Matière : Sociologie Urbaine',
                    'code' => 'SOCIOLOGIE_URBAINE',
                ],
                [
                    'name' => 'Technique Graphique',
                    'description' => 'Matière : Technique Graphique',
                    'code' => 'TECHNIQUE_GRAPHIQUE',
                ],
                [
                    'name' => 'Technologie du Bâtiment',
                    'description' => 'Matière : Technologie du Bâtiment',
                    'code' => 'TECHNO_BAT',
                ],
            ],
            'BTS2-BAT' => [
                [
                    'name' => 'Anglais',
                    'description' => 'Matière : Anglais',
                    'code' => 'ANGLAIS',
                ],
                [
                    'name' => 'Architecture',
                    'description' => 'Matière : Architecture',
                    'code' => 'ARCHITECTURE',
                ],
                [
                    'name' => 'Archicad',
                    'description' => 'Matière : Archicad',
                    'code' => 'ARCHICAD',
                ],
                [
                    'name' => 'Béton Armé',
                    'description' => 'Matière : Béton Armé',
                    'code' => 'BETON_ARME',
                ],
                [
                    'name' => 'CAO-DAO',
                    'description' => 'Matière : CAO-DAO',
                    'code' => 'CAO_DAO',
                ],
                [
                    'name' => 'Dessin Bâtiment',
                    'description' => 'Matière : Dessin Bâtiment',
                    'code' => 'DESSIN_BATIMENT',
                ],
                [
                    'name' => 'Droit',
                    'description' => 'Matière : Droit',
                    'code' => 'DROIT',
                ],
                [
                    'name' => 'Droit de la Construction',
                    'description' => 'Matière : Droit de la Construction',
                    'code' => 'DROIT_CONSTRUCTION',
                ],
                [
                    'name' => 'Entrepreneuriat',
                    'description' => 'Matière : Entrepreneuriat',
                    'code' => 'ENTREPRENEURIAT',
                ],
                [
                    'name' => 'Géotechnique',
                    'description' => 'Matière : Géotechnique',
                    'code' => 'GEOTECHNIQUE',
                ],
                [
                    'name' => 'Gestion',
                    'description' => 'Matière : Gestion',
                    'code' => 'GESTION',
                ],
                [
                    'name' => 'Informatique',
                    'description' => 'Matière : Informatique',
                    'code' => 'INFORMATIQUE',
                ],
                [
                    'name' => 'Matériaux',
                    'description' => 'Matière : Matériaux',
                    'code' => 'MATERIAUX',
                ],
                [
                    'name' => 'Mathématiques',
                    'description' => 'Matière : Mathématiques',
                    'code' => 'MATHEMATIQUES',
                ],
                [
                    'name' => 'Métré et Etude de Prix',
                    'description' => 'Matière : Métré et Etude de Prix',
                    'code' => 'METRE_PRIX_1',
                ],
                [
                    'name' => 'OGC',
                    'description' => 'Matière : OGC',
                    'code' => 'OGC_1',
                ],
                [
                    'name' => 'Optique',
                    'description' => 'Matière : Optique',
                    'code' => 'OPTIQUE',
                ],
                [
                    'name' => 'Projet',
                    'description' => 'Matière : Projet',
                    'code' => 'PROJET_1',
                ],
                [
                    'name' => 'Statique RDM',
                    'description' => 'Matière : Statique RDM',
                    'code' => 'STATIQUE_RDM_1',
                ],
                [
                    'name' => 'Technique de Recherche d\'emploi',
                    'description' => 'Matière : Technique de Recherche d\'emploi',
                    'code' => 'TRE_1',
                ],
                [
                    'name' => 'Technique d\'expression',
                    'description' => 'Matière : Technique d\'expression',
                    'code' => 'TECHNIQUE_EXPRESSION',
                ],
                [
                    'name' => 'Technologie du Bâtiment-Pathologie',
                    'description' => 'Matière : Technologie du Bâtiment-Pathologie',
                    'code' => 'TECHNO_BAT_PATHO',
                ],
                [
                    'name' => 'Topographie',
                    'description' => 'Matière : Topographie',
                    'code' => 'TOPOGRAPHIE',
                ],
                [
                    'name' => 'Urbanisme',
                    'description' => 'Matière : Urbanisme',
                    'code' => 'URBANISME',
                ],
                [
                    'name' => 'VRD',
                    'description' => 'Matière : VRD',
                    'code' => 'VRD',
                ],
            ],
            'BTS2-GTP' => [
                [
                    'name' => 'Béton Armé',
                    'description' => 'Matière : Béton Armé',
                    'code' => 'BA',
                ],
                [
                    'name' => 'Géotechnique',
                    'description' => 'Matière : Géotechnique',
                    'code' => 'GEO',
                ],
                [
                    'name' => 'Routes',
                    'description' => 'Matière : Routes',
                    'code' => 'ROUTES',
                ],
                [
                    'name' => 'Hydraulique',
                    'description' => 'Matière : Hydraulique',
                    'code' => 'HYDRO',
                ],
                [
                    'name' => 'Alimentation en Eau potable',
                    'description' => 'Matière : Alimentation en Eau potable',
                    'code' => 'AEP',
                ],
                [
                    'name' => 'Anglais',
                    'description' => 'Matière : Anglais',
                    'code' => 'ANGLAIS',
                ],
                [
                    'name' => 'Assainissement',
                    'description' => 'Matière : Assainissement',
                    'code' => 'ASSAINISSEMENT',
                ],
                [
                    'name' => 'CAO-DAO',
                    'description' => 'Matière : CAO-DAO',
                    'code' => 'CAO_DAO',
                ],
                [
                    'name' => 'COVADIS',
                    'description' => 'Matière : COVADIS',
                    'code' => 'COVADIS',
                ],
                [
                    'name' => 'Dessin',
                    'description' => 'Matière : Dessin',
                    'code' => 'DESSIN',
                ],
                [
                    'name' => 'Drainage',
                    'description' => 'Matière : Drainage',
                    'code' => 'DRAINAGE',
                ],
                [
                    'name' => 'Droit',
                    'description' => 'Matière : Droit',
                    'code' => 'DROIT',
                ],
                [
                    'name' => 'Entrepreneuriat',
                    'description' => 'Matière : Entrepreneuriat',
                    'code' => 'ENTREPRENEURIAT',
                ],
                [
                    'name' => 'Entretien Routier',
                    'description' => 'Matière : Entretien Routier',
                    'code' => 'ENTRETIEN_ROUTIER',
                ],
                [
                    'name' => 'Environnement',
                    'description' => 'Matière : Environnement',
                    'code' => 'ENVIRONNEMENT',
                ],
                [
                    'name' => 'Géométrie Routière et Voirie',
                    'description' => 'Matière : Géométrie Routière et Voirie',
                    'code' => 'GRV',
                ],
                [
                    'name' => 'Gestion',
                    'description' => 'Matière : Gestion',
                    'code' => 'GESTION',
                ],
                [
                    'name' => 'Informatique',
                    'description' => 'Matière : Informatique',
                    'code' => 'INFORMATIQUE',
                ],
                [
                    'name' => 'Matériaux',
                    'description' => 'Matière : Matériaux',
                    'code' => 'MATERIAUX',
                ],
                [
                    'name' => 'Mathématiques',
                    'description' => 'Matière : Mathématiques',
                    'code' => 'MATHEMATIQUES',
                ],
                [
                    'name' => 'Métré et étude de Prix',
                    'description' => 'Matière : Métré et étude de Prix',
                    'code' => 'METRE_PRIX_2',
                ],
                [
                    'name' => 'OGC',
                    'description' => 'Matière : OGC',
                    'code' => 'OGC_2',
                ],
                [
                    'name' => 'Projet',
                    'description' => 'Matière : Projet',
                    'code' => 'PROJET_2',
                ],
                [
                    'name' => 'Qualité et Traitement des Eaux',
                    'description' => 'Matière : Qualité et Traitement des Eaux',
                    'code' => 'QTE',
                ],
                [
                    'name' => 'Signalisation Routière',
                    'description' => 'Matière : Signalisation Routière',
                    'code' => 'SIGNALISATION',
                ],
                [
                    'name' => 'Statique RDM',
                    'description' => 'Matière : Statique RDM',
                    'code' => 'STATIQUE_RDM_2',
                ],
                [
                    'name' => 'Technique de Recherche d\'emploi',
                    'description' => 'Matière : Technique de Recherche d\'emploi',
                    'code' => 'TRE_2',
                ],
                [
                    'name' => 'Technique d\'expression',
                    'description' => 'Matière : Technique d\'expression',
                    'code' => 'TECHNIQUE_EXPRESSION',
                ],
                [
                    'name' => 'Techniques Routières',
                    'description' => 'Matière : Techniques Routières',
                    'code' => 'TECHNIQUES_ROUTIERES',
                ],
                [
                    'name' => 'Topographie',
                    'description' => 'Matière : Topographie',
                    'code' => 'TOPOGRAPHIE',
                ],
                [
                    'name' => 'VRD',
                    'description' => 'Matière : VRD',
                    'code' => 'VRD',
                ],
            ],
        ];

        foreach ($matieresByFiliere as $filiereCode => $matieres) {
            $filiere = ESBTPFiliere::where('code', $filiereCode)->first();
            if ($filiere) {
                foreach ($matieres as $matiereData) {
                    $matiere = ESBTPMatiere::firstOrCreate(
                        ['code' => $matiereData['code']],
                        $matiereData
                    );

                    if (!$filiere->matieres()->where('esbtp_matieres.id', $matiere->id)->exists()) {
                        $filiere->matieres()->attach($matiere->id);
                    }
                }
            }
        }

        // Create some basic subjects
        $basicSubjects = [
            [
                'name' => 'Mathématiques',
                'code' => 'MATH',
                'description' => 'Cours de mathématiques générales',
                'coefficient' => 3,
                'heures_cm' => 30,
                'heures_td' => 20,
            ],
            [
                'name' => 'Physique',
                'code' => 'PHYS',
                'description' => 'Cours de physique générale',
                'coefficient' => 3,
                'heures_cm' => 30,
                'heures_td' => 20,
            ],
            [
                'name' => 'Français',
                'code' => 'FR',
                'description' => 'Cours de français technique',
                'coefficient' => 2,
                'heures_cm' => 20,
                'heures_td' => 10,
            ],
            [
                'name' => 'Anglais Technique',
                'code' => 'ANG',
                'description' => 'Cours d\'anglais technique',
                'coefficient' => 2,
                'heures_cm' => 20,
                'heures_td' => 10,
            ],
        ];

        $classes = ESBTPClasse::where('is_active', true)->get();

        foreach ($basicSubjects as $subjectData) {
            $subjectData['is_active'] = true;
            $matiere = ESBTPMatiere::create($subjectData);

            // Associate with all active classes
            foreach ($classes as $classe) {
                $matiere->classes()->attach($classe->id, [
                    'coefficient' => $subjectData['coefficient'],
                    'total_heures' => $subjectData['heures_cm'] + $subjectData['heures_td'],
                    'is_active' => true,
                ]);

                // Also associate with filiere and niveau
                if ($classe->filiere_id) {
                    $matiere->filieres()->syncWithoutDetaching([$classe->filiere_id => ['is_active' => true]]);
                }
                if ($classe->niveau_etude_id) {
                    $matiere->niveaux()->syncWithoutDetaching([$classe->niveau_etude_id]);
                }
            }
        }
    }
}

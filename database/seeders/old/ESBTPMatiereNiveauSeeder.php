<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;

class ESBTPMatiereNiveauSeeder extends Seeder
{
    /**
     * Seeder pour créer les relations entre matières et niveaux d'études.
     *
     * @return void
     */
    public function run()
    {
        $niveauBTS1 = ESBTPNiveauEtude::where('code', 'BTS1')->first();
        $niveauBTS2 = ESBTPNiveauEtude::where('code', 'BTS2')->first();

        if (!$niveauBTS1 || !$niveauBTS2) {
            $this->command->error('Les niveaux d\'études BTS1 et BTS2 doivent être créés avant d\'exécuter ce seeder.');
            return;
        }

        // Matières de BTS1
        $matieresBTS1 = ESBTPMatiere::whereIn('code', [
            'DESSIN_TECHNIQUE', 'MATHEMATIQUES', 'PHYSIQUE', 'CHIMIE',
            'INFORMATIQUE', 'FRANCAIS', 'ANGLAIS', 'RDM', 'MDS',
            'TOPO', 'CM', 'HYDROLOGIE', 'HYDRAULIQUE', 'GEOTECHNIQUE',
            'TECHNIQUE_ENGINS', 'IHH', 'ELECTRICITE', 'SECURITE',
            'MATERIAUX', 'IGC', 'GRV', 'CALCUL_TOPO', 'TOPO_GENERALE',
            'TP_TOPO', 'GEOCHIMIE', 'GEOLOGIE_GENERALE', 'GEOLOGIE_HISTORIQUE',
            'MECA_SOL', 'MECA_ROCHE', 'MINERALOGIE', 'MECA_FLUIDES',
            'TOPO_MINIERE', 'ARCHITECTURE', 'DEMOGRAPHIE', 'DESSIN_BATIMENT',
            'GEOGRAPHIE_URBAINE', 'INTRO_URBANISME', 'LECTURE_PHOTO',
            'METRE_PRIX', 'SOCIOLOGIE_URBAINE', 'TECHNIQUE_GRAPHIQUE',
            'TECHNO_BAT'
        ])->get();

        foreach ($matieresBTS1 as $matiere) {
            if (!$niveauBTS1->matieres()->where('esbtp_matieres.id', $matiere->id)->exists()) {
                $niveauBTS1->matieres()->attach($matiere->id, [
                    'coefficient' => 1.0,
                    'heures_cours' => 30,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Matières de BTS2
        $matieresBTS2 = ESBTPMatiere::whereIn('code', [
            'BA', 'GEO', 'ROUTES', 'HYDRO', 'AEP', 'ANGLAIS',
            'ASSAINISSEMENT', 'CAO_DAO', 'COVADIS', 'DESSIN',
            'DRAINAGE', 'DROIT', 'ENTREPRENEURIAT', 'ENTRETIEN_ROUTIER',
            'ENVIRONNEMENT', 'GRV', 'GESTION', 'INFORMATIQUE',
            'MATERIAUX', 'MATHEMATIQUES', 'METRE_PRIX', 'OGC',
            'PROJET', 'QTE', 'SIGNALISATION', 'STATIQUE_RDM',
            'TRE', 'TECHNIQUE_EXPRESSION', 'TECHNIQUES_ROUTIERES',
            'TOPOGRAPHIE', 'VRD', 'ARCHICAD', 'BETON_ARME',
            'DESSIN_BATIMENT', 'DROIT_CONSTRUCTION', 'OPTIQUE',
            'TECHNO_BAT_PATHO', 'URBANISME'
        ])->get();

        foreach ($matieresBTS2 as $matiere) {
            if (!$niveauBTS2->matieres()->where('esbtp_matieres.id', $matiere->id)->exists()) {
                $niveauBTS2->matieres()->attach($matiere->id, [
                    'coefficient' => 1.0,
                    'heures_cours' => 30,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Les relations entre matières et niveaux d\'études ont été créées avec succès.');
    }
}

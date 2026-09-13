<?php

namespace Database\Seeders\Demo;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Les resultats de l'annee ecoulee, sans lesquels la reinscription ne peut
 * rien decider.
 *
 * La decision — passage, rattrapage, redoublement — se lit sur les notes de
 * l'annee que l'etudiant vient de terminer. Sans note, la moyenne vaut zero
 * et TOUT le monde ressort redoublant : l'ecran s'affiche, mais il ne dit
 * plus rien.
 *
 * Les baremes de repli, appliques faute de regle configuree, sont : passage
 * a partir de 12, rattrapage entre 8 et 12 A CONDITION que trois matieres au
 * plus restent sous 12, redoublement en dessous de 8. Les notes ci-dessous
 * sont choisies pour tomber franchement dans l'un des trois cas, condition
 * sur le nombre de matieres comprise — une moyenne de 10 obtenue avec cinq
 * matieres faibles donne un redoublement, pas un rattrapage.
 *
 * Le profil scolaire est independant du profil financier : on veut voir a
 * l'ecran l'etudiant a jour qui redouble, et celui qui passe en devant encore.
 */
class PromotionPrecedenteNotesDemoData
{
    /** Moyennes visees : passage 13.2 / rattrapage 10.4 (3 echecs) / redoublement 6.0 */
    private const BULLETINS = [
        'passage' => [15, 14, 13, 12, 12],
        'rattrapage' => [14, 14, 8, 8, 8],
        'redoublement' => [7, 6, 6, 5, 6],
    ];

    private const MATIERES = [
        'BTS-IG' => ['Algorithmique', 'Bases de donnees', 'Reseaux', 'Anglais technique', 'Economie generale'],
        'BTS-CG' => ['Comptabilite generale', 'Fiscalite', 'Droit des affaires', 'Anglais technique', 'Mathematiques financieres'],
        'L-GC' => ['Resistance des materiaux', 'Topographie', 'Beton arme', 'Anglais technique', 'Hydraulique appliquee'],
    ];

    private const MATIERES_DEFAUT = ['Mathematiques', 'Anglais technique', 'Expression francaise', 'Informatique', 'Economie generale'];

    public function __construct(private readonly ?Command $command = null) {}

    /** @return array{matieres: int, notes: int} */
    public function run(Collection $inscriptions, ESBTPAnneeUniversitaire $annee): array
    {
        $auteur = User::query()->min('id');
        $posees = 0;
        $matieres = 0;

        foreach ($inscriptions->groupBy('classe_id') as $classeId => $lot) {
            $premiere = $lot->first();
            $grille = $this->grilleDeLaClasse($premiere, $annee, $auteur);
            $matieres += $grille->count();

            foreach ($lot as $inscription) {
                $posees += $this->noterUnEtudiant($inscription, $grille, $annee, $auteur);
            }
        }

        $this->command?->line(sprintf('   • %d matieres · %d notes posees sur %s', $matieres, $posees, $annee->name));

        return ['matieres' => $matieres, 'notes' => $posees];
    }

    /**
     * Une matiere et son evaluation par couple (filiere, niveau). L'evaluation
     * porte l'annee ecoulee : c'est elle qui rattache le resultat a l'annee
     * terminee, et non a celle que l'etudiant s'apprete a rejoindre.
     *
     * @return Collection<int, array{matiere: ESBTPMatiere, evaluation: ESBTPEvaluation}>
     */
    private function grilleDeLaClasse($inscription, ESBTPAnneeUniversitaire $annee, ?int $auteur): Collection
    {
        $classe = $inscription->classe;
        $codeFiliere = $classe->filiere->code ?? '';
        $intitules = self::MATIERES[$codeFiliere] ?? self::MATIERES_DEFAUT;

        return collect($intitules)->values()->map(function (string $intitule, int $rang) use ($classe, $annee, $auteur, $codeFiliere) {
            $matiere = $this->poserMatiere($intitule, $codeFiliere, $rang, $classe, $auteur);

            $evaluation = ESBTPEvaluation::firstOrCreate(
                [
                    'matiere_id' => $matiere->id,
                    'classe_id' => $classe->id,
                    'annee_universitaire_id' => $annee->id,
                    'type' => 'examen',
                ],
                [
                    'titre' => 'Examen de fin d\'annee — ' . $intitule,
                    'date_evaluation' => Carbon::parse($annee->end_date)->subMonths(2)->toDateString(),
                    'coefficient' => 1.0,
                    'bareme' => 20.00,
                    'periode' => 'semestre2',
                    'status' => 'completed',
                    'is_published' => true,
                    'notes_published' => true,
                    'created_by' => $auteur,
                ]
            );

            return ['matiere' => $matiere, 'evaluation' => $evaluation];
        });
    }

    private function poserMatiere(string $intitule, string $codeFiliere, int $rang, $classe, ?int $auteur): ESBTPMatiere
    {
        $code = strtoupper(($codeFiliere !== '' ? $codeFiliere : 'GEN')) . '-' . str_pad((string) ($rang + 1), 2, '0', STR_PAD_LEFT);

        $matiere = ESBTPMatiere::firstOrCreate(
            ['code' => $code],
            [
                'name' => $intitule,
                'coefficient' => $rang < 2 ? 3 : 2,
                'filiere_id' => $classe->filiere_id,
                'niveau_etude_id' => $classe->niveau_etude_id,
                'is_active' => true,
                'created_by' => $auteur,
            ]
        );

        // Le rattachement canonique d'une matiere passe par le couple
        // filiere + niveau, jamais par le pivot classe-matiere hérité.
        ESBTPMatiereFilierNiveau::firstOrCreate(
            [
                'matiere_id' => $matiere->id,
                'filiere_id' => $classe->filiere_id,
                'niveau_etude_id' => $classe->niveau_etude_id,
            ],
            ['ordre_bulletin' => $rang + 1]
        );

        return $matiere;
    }

    private function noterUnEtudiant($inscription, Collection $grille, ESBTPAnneeUniversitaire $annee, ?int $auteur): int
    {
        $profil = array_keys(self::BULLETINS)[$inscription->id % 3];
        $bulletin = self::BULLETINS[$profil];
        $posees = 0;

        foreach ($grille as $rang => $ligne) {
            $note = ESBTPNote::firstOrCreate(
                [
                    'evaluation_id' => $ligne['evaluation']->id,
                    'etudiant_id' => $inscription->etudiant_id,
                ],
                [
                    'matiere_id' => $ligne['matiere']->id,
                    'classe_id' => $inscription->classe_id,
                    'semestre' => 'semestre2',
                    // Colonne CHAINE, distincte de la cle etrangere : c'est
                    // elle que la reinscription interroge.
                    'annee_universitaire' => $annee->name,
                    'note' => $bulletin[$rang % count($bulletin)],
                    'type_evaluation' => 'examen',
                    'is_absent' => false,
                    'created_by' => $auteur,
                ]
            );

            if ($note->wasRecentlyCreated) {
                $posees++;
            }
        }

        return $posees;
    }
}

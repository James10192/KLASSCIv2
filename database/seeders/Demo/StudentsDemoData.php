<?php

namespace Database\Seeders\Demo;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Étape 3 — étudiants + inscriptions distribués sur les classes.
 *
 * Cible ~85% du capacity de chaque classe pour avoir des classes
 * presque pleines (réaliste pour démos) sans saturer.
 */
class StudentsDemoData
{
    private const NOMS = [
        'KOUASSI', 'KOUAME', 'YAO', 'KONAN', 'BAMBA', 'TRAORE', 'OUATTARA', 'COULIBALY',
        'DIABATE', 'DIALLO', 'DIARRA', 'KONE', 'TOURE', 'CISSE', 'DOUMBIA', 'KOFFI',
        'N\'GUESSAN', 'AKA', 'ASSEMIEN', 'BROU', 'GBOGBO', 'YAPI', 'AMANI', 'ANGAMAN',
        'BOA', 'EBROTTIE', 'GNAMIEN', 'KIPRE', 'LOROUGNON', 'TANO',
    ];

    private const PRENOMS_M = [
        'JEAN', 'PATRICK', 'EMMANUEL', 'CHRIST', 'OLIVIER', 'KEVIN', 'YANNICK', 'IVAN',
        'ELYSEE', 'ARIEL', 'WILFRIED', 'FRANCK', 'SERGE', 'GUY-ALAIN', 'MARC', 'PAUL',
        'JOEL', 'AXEL', 'PRINCE', 'ROMUALD', 'DAVID', 'ENOCK', 'HABIB', 'YOUSSOUF',
    ];

    private const PRENOMS_F = [
        'MARIE', 'GRACE', 'CHANTAL', 'AUDREY', 'EUNICE', 'JEMIMA', 'AURORE', 'NISSI',
        'MAEVA', 'LESLIE', 'ANGE', 'DEBORAH', 'ANNE', 'MARTHE', 'EMILIE', 'AISSATA',
        'AMINATA', 'FATOU', 'KADY', 'MARIAM', 'AYA', 'AKISSI', 'YASMINE', 'CHRISTELLE',
    ];

    private const COMMUNES = ['Cocody', 'Yopougon', 'Abobo', 'Marcory', 'Plateau', 'Treichville', 'Adjamé', 'Koumassi'];

    public function __construct(private readonly ?Command $command = null) {}

    /**
     * @param  array{annee: \App\Models\ESBTPAnneeUniversitaire, filieres: Collection, niveaux: Collection, classes: Collection}  $academic
     * @return array{etudiants: Collection, inscriptions: Collection}
     */
    public function run(array $academic): array
    {
        $etudiants = collect();
        $inscriptions = collect();

        $tenantOrigin = (int) ($academic['annee']->id) * 1000;
        $counter = $tenantOrigin;

        foreach ($academic['classes'] as $classe) {
            $target = (int) round($classe->places_totales * 0.85);
            $existing = ESBTPInscription::query()
                ->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $academic['annee']->id)
                ->count();

            $toCreate = max(0, $target - $existing);
            for ($i = 0; $i < $toCreate; $i++) {
                $counter++;
                $etu = $this->createEtudiant($classe, $counter);
                $inscription = $this->createInscription($etu, $classe, $academic['annee']);
                $etudiants->push($etu);
                $inscriptions->push($inscription);
            }
        }

        $this->refreshClassePlacesOccupees($academic['classes']);

        $this->command?->line(sprintf('   • %d étudiants créés · %d inscriptions actives', $etudiants->count(), $inscriptions->count()));

        return ['etudiants' => $etudiants, 'inscriptions' => $inscriptions];
    }

    private function createEtudiant(ESBTPClasse $classe, int $counter): ESBTPEtudiant
    {
        $sexe = mt_rand(0, 1) ? 'M' : 'F';
        $nom = self::NOMS[array_rand(self::NOMS)];
        $prenom = $sexe === 'M'
            ? self::PRENOMS_M[array_rand(self::PRENOMS_M)]
            : self::PRENOMS_F[array_rand(self::PRENOMS_F)];

        $matricule = sprintf('DEMO%05d', $counter);
        $username  = strtolower($matricule);

        $user = User::firstOrCreate(
            ['username' => $username],
            [
                'name'       => $prenom . ' ' . $nom,
                'first_name' => $prenom,
                'last_name'  => $nom,
                'email'      => $username . '@demo.klassci.local',
                'password'   => Hash::make('demo'.Str::random(8)),
                'is_active'  => true,
                'phone'      => '+225 0' . mt_rand(1, 7) . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
            ]
        );

        return ESBTPEtudiant::firstOrCreate(
            ['matricule' => $matricule],
            [
                'user_id'                => $user->id,
                'classe_id'              => $classe->id,
                'annee_universitaire_id' => $classe->annee_universitaire_id,
                'nom'                    => $nom,
                'prenoms'                => $prenom,
                'sexe'                   => $sexe,
                'date_naissance'         => Carbon::now()->subYears(mt_rand(18, 26))->subDays(mt_rand(0, 365))->toDateString(),
                'lieu_naissance'         => self::COMMUNES[array_rand(self::COMMUNES)],
                'nationalite'            => 'Ivoirienne',
                'commune'                => self::COMMUNES[array_rand(self::COMMUNES)],
                'ville'                  => 'Abidjan',
                'telephone'              => $user->phone,
                'email'                  => $user->email,
                'statut'                 => 'actif',
            ]
        );
    }

    private function createInscription(ESBTPEtudiant $etu, ESBTPClasse $classe, $annee): ESBTPInscription
    {
        $dateInscription = Carbon::parse($annee->start_date)->addDays(mt_rand(0, 21));

        return ESBTPInscription::firstOrCreate(
            [
                'etudiant_id'            => $etu->id,
                'annee_universitaire_id' => $annee->id,
                'classe_id'              => $classe->id,
            ],
            [
                'filiere_id'         => $classe->filiere_id,
                'niveau_id'          => $classe->niveau_etude_id,
                'affectation_status' => 'affecté',
                'date_inscription'   => $dateInscription->toDateString(),
                'type_inscription'   => 'premiere',
                'status'             => 'active',
                'workflow_step'      => 'etudiant_cree',
                'date_validation'    => $dateInscription->toDateString(),
                'numero_recu'        => 'INS-' . $etu->matricule,
                // Champs requis par le schéma legacy (NOT NULL sans default)
                'montant_scolarite'  => 0,
                'frais_inscription'  => 0,
            ]
        );
    }

    private function refreshClassePlacesOccupees(Collection $classes): void
    {
        foreach ($classes as $classe) {
            $count = ESBTPInscription::where('classe_id', $classe->id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree')
                ->count();
            $classe->update(['places_occupees' => $count]);
        }
    }
}

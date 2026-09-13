<?php

namespace Database\Seeders\Demo;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * La promotion de l'annee ecoulee — celle que l'ecran de reinscription
 * est cense afficher.
 *
 * Le reste de la demo ne peuple que l'annee courante. Or la reinscription
 * ne regarde QUE l'annee precedente, et n'y retient que les etudiants qui
 * n'ont pas encore de dossier dans l'annee courante : sur une base ou tout
 * le monde est deja inscrit cette annee, l'ecran est vide, et rien de ce
 * qu'il calcule — solde, decision, blocage — ne peut etre verifie.
 *
 * Cette promotion est donc volontairement laissee SANS inscription dans
 * l'annee courante : c'est precisement ce qui la rend visible.
 *
 * Chaque etudiant est complet : compte utilisateur, fiche, inscription,
 * souscriptions de frais et versements. Le volet financier vit dans
 * PromotionPrecedenteFinanceDemoData.
 */
class PromotionPrecedenteDemoData
{
    /** Classes d'ou l'on peut monter d'un cran l'annee suivante. */
    private const CLASSES_CIBLES = ['1BTS IG A', '1BTS CG A', 'L3 Génie Civil'];

    private const PAR_CLASSE = 12;

    private const NOMS = [
        'ADJOUMANI', 'BEUGRE', 'DAGO', 'ESSIS', 'FOFANA', 'GNAHORE', 'HIEN', 'IRIE',
        'KACOU', 'LOUKOU', 'MEITE', 'NIANGORAN', 'OKOU', 'POKOU', 'SANGARE', 'TIEMOKO',
        'VANIE', 'WODIE', 'YEBOUA', 'ZADI',
    ];

    private const PRENOMS_M = ['ARMAND', 'BRICE', 'CEDRIC', 'DIDIER', 'EVRARD', 'FABRICE', 'GERALD', 'HERVE', 'ISMAEL', 'JUNIOR'];
    private const PRENOMS_F = ['ADELE', 'BERTHE', 'CLARISSE', 'DJENEBA', 'ESTHER', 'FLORE', 'GISELE', 'HORTENSE', 'IRENE', 'JOSIANE'];
    private const COMMUNES = ['Cocody', 'Yopougon', 'Abobo', 'Marcory', 'Plateau', 'Bingerville'];

    public function __construct(private readonly ?Command $command = null) {}

    /**
     * @param  array{annee: ESBTPAnneeUniversitaire, classes: Collection}  $academic
     * @return array{annee: ESBTPAnneeUniversitaire, inscriptions: Collection}
     */
    public function run(array $academic): array
    {
        $annee = $this->poserAnneePrecedente($academic['annee']);
        $this->reporterLeBaremeDeFrais($academic, $annee);

        $inscriptions = $this->inscrireLaPromotion($academic['classes'], $annee);
        $argent = (new PromotionPrecedenteFinanceDemoData($this->command))->run($inscriptions);
        (new PromotionPrecedenteNotesDemoData($this->command))->run($inscriptions, $annee);

        $this->command?->line(sprintf(
            '   • Annee %s · %d etudiants · %d souscriptions · %d versements',
            $annee->name,
            $inscriptions->count(),
            $argent['souscriptions'],
            $argent['versements']
        ));

        return ['annee' => $annee, 'inscriptions' => $inscriptions];
    }

    /**
     * L'annee precedente se reconnait a une seule chose, cote service : sa
     * date de fin tombe avant le debut de l'annee courante. Ni le nom ni
     * l'ordre des identifiants n'entrent en compte.
     */
    private function poserAnneePrecedente(ESBTPAnneeUniversitaire $courante): ESBTPAnneeUniversitaire
    {
        $debut = Carbon::parse($courante->start_date)->subYear();
        $fin = Carbon::parse($courante->end_date)->subYear();

        return ESBTPAnneeUniversitaire::updateOrCreate(
            ['name' => $debut->year . '-' . $fin->year],
            [
                'start_date' => $debut->toDateString(),
                'end_date' => $fin->toDateString(),
                'is_current' => false,
                'is_active' => true,
                'description' => "Annee ecoulee (demo) — promotion a reinscrire",
            ]
        );
    }

    /**
     * Sans bareme pose sur l'annee precedente, la souscription retombe sur
     * le montant par defaut de la categorie : le solde affiche ne serait
     * plus celui que l'ecole a reellement facture. On reporte donc le bareme
     * courant, minore de 5 % — les tarifs montent, ils ne baissent pas.
     *
     * @param  array{annee: ESBTPAnneeUniversitaire, classes: Collection}  $academic
     */
    private function reporterLeBaremeDeFrais(array $academic, ESBTPAnneeUniversitaire $annee): void
    {
        $courants = ESBTPFraisConfiguration::query()
            ->where('annee_universitaire_id', $academic['annee']->id)
            ->get();

        foreach ($courants as $config) {
            $montant = (float) round($config->amount * 0.95);

            ESBTPFraisConfiguration::updateOrCreate(
                [
                    'frais_category_id' => $config->frais_category_id,
                    'filiere_id' => $config->filiere_id,
                    'niveau_id' => $config->niveau_id,
                    'annee_universitaire_id' => $annee->id,
                ],
                [
                    'amount' => $montant,
                    'amount_affecte' => $montant,
                    'amount_reaffecte' => $montant,
                    'amount_non_affecte' => $montant,
                    'payment_deadline_days' => $config->payment_deadline_days,
                    'installments_allowed' => true,
                    'max_installments' => 3,
                    'min_installment_amount' => 50000,
                    'effective_date' => $annee->start_date,
                    'is_active' => true,
                ]
            );
        }
    }

    private function inscrireLaPromotion(Collection $classes, ESBTPAnneeUniversitaire $annee): Collection
    {
        $cibles = $classes->filter(fn ($c) => in_array($c->name, self::CLASSES_CIBLES, true))->values();
        if ($cibles->isEmpty()) {
            $cibles = $classes->take(3)->values();
        }

        $inscriptions = collect();
        $rang = 0;

        foreach ($cibles as $classe) {
            for ($i = 0; $i < self::PAR_CLASSE; $i++) {
                $rang++;
                $etudiant = $this->creerEtudiant($classe, $annee, $rang);
                $inscriptions->push($this->creerInscription($etudiant, $classe, $annee, $rang));
            }
        }

        return $inscriptions;
    }

    private function creerEtudiant(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, int $rang): ESBTPEtudiant
    {
        $sexe = $rang % 2 === 0 ? 'F' : 'M';
        $nom = self::NOMS[$rang % count(self::NOMS)];
        $prenom = $sexe === 'M'
            ? self::PRENOMS_M[$rang % count(self::PRENOMS_M)]
            : self::PRENOMS_F[$rang % count(self::PRENOMS_F)];

        $matricule = sprintf('DEMO9%04d', $rang);
        $identifiant = strtolower($matricule);

        $user = User::firstOrCreate(
            ['username' => $identifiant],
            [
                'name' => $prenom . ' ' . $nom,
                'first_name' => $prenom,
                'last_name' => $nom,
                'email' => $identifiant . '@demo.klassci.local',
                'password' => Hash::make('demo' . Str::random(10)),
                'is_active' => true,
                'phone' => '+225 07' . str_pad((string) (10000000 + $rang * 137), 8, '0', STR_PAD_LEFT),
            ]
        );

        return ESBTPEtudiant::firstOrCreate(
            ['matricule' => $matricule],
            [
                'user_id' => $user->id,
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $annee->id,
                'nom' => $nom,
                'prenoms' => $prenom,
                'sexe' => $sexe,
                'date_naissance' => Carbon::parse($annee->start_date)->subYears(19)->subDays($rang * 7)->toDateString(),
                'lieu_naissance' => self::COMMUNES[$rang % count(self::COMMUNES)],
                'nationalite' => 'Ivoirienne',
                'commune' => self::COMMUNES[($rang + 2) % count(self::COMMUNES)],
                'ville' => 'Abidjan',
                'telephone' => $user->phone,
                'email' => $user->email,
                'statut' => 'actif',
            ]
        );
    }

    private function creerInscription(
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        int $rang
    ): ESBTPInscription {
        $date = Carbon::parse($annee->start_date)->addDays($rang % 75);

        return ESBTPInscription::firstOrCreate(
            [
                'etudiant_id' => $etudiant->id,
                'annee_universitaire_id' => $annee->id,
                'classe_id' => $classe->id,
            ],
            [
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'affectation_status' => ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
                'date_inscription' => $date->toDateString(),
                'type_inscription' => 'première_inscription',
                'statut_etablissement' => ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU,
                'status' => 'active',
                'workflow_step' => 'etudiant_cree',
                'date_validation' => $date->toDateString(),
                'numero_recu' => 'INS-' . $etudiant->matricule,
                'montant_scolarite' => 0,
                'frais_inscription' => 0,
            ]
        );
    }
}

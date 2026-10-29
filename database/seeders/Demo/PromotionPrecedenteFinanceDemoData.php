<?php

namespace Database\Seeders\Demo;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\ESBTPInscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Ce que doit la promotion de l'annee ecoulee, et ce qu'elle a verse.
 *
 * L'ecran de reinscription affiche, pour chaque etudiant, le reste du de
 * l'annee qu'il vient de quitter, et n'autorise la reinscription que si ce
 * reste passe sous le seuil regle par l'etablissement. Une promotion ou
 * tout le monde serait a jour — ou personne — ne montrerait qu'une moitie
 * de l'ecran. D'ou trois profils.
 *
 * Les versements sont derives des souscriptions reellement posees, jamais
 * d'un montant choisi a part : c'est la seule facon que le solde affiche
 * soit vrai plutot que vraisemblable.
 */
class PromotionPrecedenteFinanceDemoData
{
    /** Part de chaque souscription reellement versee, par profil. */
    private const PROFILS = [
        ['cle' => 'solde', 'poids' => 40, 'part_inscription' => 1.0, 'part_reste' => 1.0],
        ['cle' => 'partiel', 'poids' => 35, 'part_inscription' => 1.0, 'part_reste' => 0.4],
        ['cle' => 'impaye', 'poids' => 25, 'part_inscription' => 0.0, 'part_reste' => 0.0],
    ];

    public function __construct(private readonly ?Command $command = null) {}

    /** @return array{souscriptions: int, versements: int} */
    public function run(Collection $inscriptions): array
    {
        return [
            'souscriptions' => $this->souscrireLesFrais($inscriptions),
            'versements' => $this->encaisser($inscriptions),
        ];
    }

    private function souscrireLesFrais(Collection $inscriptions): int
    {
        $service = app(ESBTPInscriptionService::class);
        $auteur = User::query()->min('id');
        $poses = 0;

        foreach ($inscriptions as $inscription) {
            if (ESBTPFraisSubscription::where('inscription_id', $inscription->id)->exists()) {
                continue;
            }

            $frais = $service->generateFeesForInscription($inscription, [], $inscription->affectation_status);

            foreach ($frais as $ligne) {
                if ((float) ($ligne['amount'] ?? 0) <= 0) {
                    continue;
                }
                ESBTPFraisSubscription::create([
                    'inscription_id' => $inscription->id,
                    'frais_category_id' => $ligne['category_id'],
                    'selected_option_id' => null,
                    'amount' => $ligne['amount'],
                    'is_active' => true,
                    'created_by' => $auteur,
                    'notes' => 'Promotion precedente (demo reinscription)',
                ]);
                $poses++;
            }
        }

        return $poses;
    }

    private function encaisser(Collection $inscriptions): int
    {
        $verses = 0;

        foreach ($inscriptions->values() as $rang => $inscription) {
            $profil = self::PROFILS[$this->profilPour($rang)];

            $souscriptions = ESBTPFraisSubscription::with('fraisCategory')
                ->where('inscription_id', $inscription->id)
                ->where('is_active', true)
                ->get();

            foreach ($souscriptions as $souscription) {
                $estInscription = ($souscription->fraisCategory->code ?? '') === 'INSCRIPTION';
                $part = $estInscription ? $profil['part_inscription'] : $profil['part_reste'];
                $montant = round((float) $souscription->amount * $part, 2);

                if ($montant <= 0) {
                    // Le profil ne verse rien sur cette ligne. Si un passage
                    // precedent y avait pose un versement, le laisser figerait
                    // l'ancienne repartition : la demo ne convergerait jamais
                    // vers ce qu'elle annonce.
                    $this->retirerVersement($inscription, $souscription);
                    continue;
                }

                $verses += $this->poserVersement($inscription, $souscription, $montant, $estInscription);
            }
        }

        return $verses;
    }

    /**
     * Repartition deterministe : un seed rejoue donne la meme demo.
     *
     * Le rang est d'abord disperse sur 0-99 par un multiplicateur premier avec
     * 100, sans quoi une promotion de 36 etudiants garderait des rangs tous
     * inferieurs a 40 et tomberait entierement dans le premier profil — ce qui
     * est arrive : trente-six etudiants tous soldes, et aucun impaye a montrer.
     */
    private function profilPour(int $rang): int
    {
        $disperse = ($rang * 37 + 11) % 100;

        return match (true) {
            $disperse < self::PROFILS[0]['poids'] => 0,
            $disperse < self::PROFILS[0]['poids'] + self::PROFILS[1]['poids'] => 1,
            default => 2,
        };
    }

    private function retirerVersement(ESBTPInscription $inscription, ESBTPFraisSubscription $souscription): void
    {
        ESBTPPaiement::query()
            ->where('inscription_id', $inscription->id)
            ->where('frais_category_id', $souscription->frais_category_id)
            ->where('motif', 'like', 'Promotion precedente —%')
            ->forceDelete();
    }

    private function poserVersement(
        ESBTPInscription $inscription,
        ESBTPFraisSubscription $souscription,
        float $montant,
        bool $estInscription
    ): int {
        $date = Carbon::parse($inscription->date_inscription)->addDays($estInscription ? 2 : 45);

        // updateOrCreate, et non firstOrCreate : le montant depend du profil,
        // et un profil qui change entre deux passages doit se voir a l'ecran.
        $paiement = ESBTPPaiement::updateOrCreate(
            [
                'inscription_id' => $inscription->id,
                'frais_category_id' => $souscription->frais_category_id,
                'motif' => 'Promotion precedente — ' . ($souscription->fraisCategory->name ?? 'frais'),
            ],
            [
                'etudiant_id' => $inscription->etudiant_id,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
                'type_paiement' => $estInscription ? 'inscription' : 'scolarite',
                'montant' => $montant,
                'mode_paiement' => 'especes',
                'date_paiement' => $date->toDateString(),
                'status' => 'validé',
                // Derive de la ligne, et non d'un compteur : un profil qui
                // passe a « rien verse » supprime des versements et decalerait
                // un compteur, donc reattribuerait des numeros deja emis.
                'numero_recu' => sprintf('REC-PROMO-%d-%d', $inscription->id, $souscription->frais_category_id),
                'reference_paiement' => 'PROMO-' . strtoupper(Str::random(8)),
            ]
        );

        return $paiement->exists ? 1 : 0;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\EmploiTemps;

use App\Models\ESBTPSeanceCours;
use App\Models\Setting;
use Carbon\Carbon;

/**
 * Les délais qui décident quand un enseignant peut émarger, et avec quel statut.
 *
 * Ils étaient écrits en dur dans `TeacherAttendanceController::sign()` : 20 min
 * pour « présent », 45 min pour « en retard », absent d'office au-delà, fenêtre
 * de fin de −20 à +30 min. Deux écoles n'ont aucune raison d'avoir les mêmes
 * (un cours de 4 h en amphi n'a pas la tolérance d'un TD de 1 h), donc ils sont
 * devenus des réglages d'instance. Les valeurs par défaut reproduisent à la
 * minute près l'ancien comportement : une école qui ne touche à rien ne voit
 * aucune différence.
 *
 * Le dépassement du délai de retard a deux conduites possibles, et c'est à
 * l'école de choisir laquelle (règle `rien-en-dur`) :
 *   - `absent` : l'ancien comportement, la séance n'est pas comptée ;
 *   - `justification` : l'émargement est accepté en retard, avec un motif
 *     obligatoire, et la coordination le voit.
 */
final class FenetresDEmargement
{
    public const CLE_AVANCE = 'emargement_minutes_avance';
    public const CLE_PRESENT = 'emargement_minutes_present';
    public const CLE_RETARD = 'emargement_minutes_retard';
    public const CLE_AVANT_FIN = 'emargement_minutes_avant_fin';
    public const CLE_APRES_FIN = 'emargement_minutes_apres_fin';
    public const CLE_DEPASSEMENT = 'emargement_depassement_retard';

    public const DEPASSEMENT_ABSENT = 'absent';
    public const DEPASSEMENT_JUSTIFICATION = 'justification';

    /** Valeurs livrées : exactement l'ancien comportement codé en dur. */
    public const DEFAUTS = [
        self::CLE_AVANCE => 0,
        self::CLE_PRESENT => 20,
        self::CLE_RETARD => 45,
        self::CLE_AVANT_FIN => 20,
        self::CLE_APRES_FIN => 30,
    ];

    private const LIBELLES = [
        self::CLE_AVANCE => 'Minutes pendant lesquelles un enseignant peut émarger AVANT le début du cours',
        self::CLE_PRESENT => 'Minutes après le début pendant lesquelles l’enseignant est compté présent',
        self::CLE_RETARD => 'Minutes après le début au-delà desquelles l’émargement de début est refusé',
        self::CLE_AVANT_FIN => 'Minutes avant la fin à partir desquelles l’émargement de fin est possible',
        self::CLE_APRES_FIN => 'Minutes après la fin pendant lesquelles l’émargement de fin reste possible',
    ];

    /**
     * Pose les réglages s'ils manquent, sans jamais écraser ce que l'école a réglé.
     * Sans ligne en base, la boucle générique de l'écran des réglages ignore le champ.
     */
    public function ensureDefaults(): void
    {
        $ordre = 170;
        foreach (self::DEFAUTS as $cle => $valeur) {
            Setting::firstOrCreate(['key' => $cle], [
                'value' => (string) $valeur,
                'type' => 'integer',
                'group' => 'emargement',
                'category' => 'emargement',
                'description' => self::LIBELLES[$cle],
                'is_required' => false,
                'default_value' => (string) $valeur,
                'validation_rules' => ['nullable', 'integer', 'min:0', 'max:240'],
                'sort_order' => $ordre++,
            ]);
        }

        Setting::firstOrCreate(['key' => self::CLE_DEPASSEMENT], [
            'value' => self::DEPASSEMENT_ABSENT,
            'type' => 'string',
            'group' => 'emargement',
            'category' => 'emargement',
            'description' => 'Conduite quand l’enseignant émarge après le délai de retard : absent d’office, ou retard accepté avec justification',
            'is_required' => false,
            'default_value' => self::DEPASSEMENT_ABSENT,
            'validation_rules' => ['nullable', 'in:'.self::DEPASSEMENT_ABSENT.','.self::DEPASSEMENT_JUSTIFICATION],
            'sort_order' => $ordre,
        ]);
    }

    /** @var array<string, mixed> valeurs brutes lues une fois par instance */
    private array $brut = [];

    public function minutes(string $cle): int
    {
        // Lu en BRUT, pas via `Setting::get()` : le cast `integer` y change ''
        // en 0, et une case laissée vide fermerait l'émargement à la minute
        // même où le cours commence.
        if (! array_key_exists($cle, $this->brut)) {
            try {
                $this->brut[$cle] = Setting::where('key', $cle)->where('is_active', true)->value('value');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Délais d’émargement illisibles, valeurs livrées utilisées.', ['cle' => $cle, 'erreur' => $e->getMessage()]);
                $this->brut[$cle] = null;
            }
        }
        $valeur = $this->brut[$cle];

        // Une valeur vide ou illisible ne doit pas fermer l'émargement à zéro
        // minute : on retombe sur le défaut livré, jamais sur 0.
        if ($valeur === null || $valeur === '' || ! is_numeric($valeur)) {
            return self::DEFAUTS[$cle] ?? 0;
        }

        return max(0, (int) $valeur);
    }

    public function exigeJustification(): bool
    {
        return Setting::get(self::CLE_DEPASSEMENT, self::DEPASSEMENT_ABSENT) === self::DEPASSEMENT_JUSTIFICATION;
    }

    public function ouvertureDebut(Carbon $heureDebut): Carbon
    {
        return $heureDebut->copy()->subMinutes($this->minutes(self::CLE_AVANCE));
    }

    public function limitePresent(Carbon $heureDebut): Carbon
    {
        return $heureDebut->copy()->addMinutes($this->minutes(self::CLE_PRESENT));
    }

    public function limiteRetard(Carbon $heureDebut): Carbon
    {
        // Un délai de retard plus court que le délai « présent » n'a pas de sens :
        // on prend le plus grand des deux plutôt que de refuser un enseignant à l'heure.
        $minutes = max($this->minutes(self::CLE_RETARD), $this->minutes(self::CLE_PRESENT));

        return $heureDebut->copy()->addMinutes($minutes);
    }

    public function ouvertureFin(Carbon $heureFin): Carbon
    {
        return $heureFin->copy()->subMinutes($this->minutes(self::CLE_AVANT_FIN));
    }

    public function fermetureFin(Carbon $heureFin): Carbon
    {
        return $heureFin->copy()->addMinutes($this->minutes(self::CLE_APRES_FIN));
    }

    /**
     * La seule décision sur un émargement de début : trop tôt, présent, en
     * retard, ou au-delà du délai. Tous les écrans et la tâche planifiée la
     * lisent ici, pour qu'un délai réglé par l'école vaille partout.
     */
    public function classerDebut(Carbon $maintenant, Carbon $heureDebut): MomentDEmargement
    {
        return match (true) {
            $maintenant->lt($this->ouvertureDebut($heureDebut)) => MomentDEmargement::TropTot,
            $maintenant->lte($this->limitePresent($heureDebut)) => MomentDEmargement::Present,
            $maintenant->lte($this->limiteRetard($heureDebut)) => MomentDEmargement::Retard,
            default => MomentDEmargement::Depasse,
        };
    }

    /**
     * Fenêtre de l'émargement de fin, prolongation accordée comprise.
     *
     * @return array{0: Carbon, 1: Carbon} ouverture, fermeture
     */
    public function fenetreDeFin(ESBTPSeanceCours $seance, ?Carbon $date = null): array
    {
        $fin = app(ProlongationDeSeance::class)->heureFinEffective($seance, $date);

        return [$this->ouvertureFin($fin), $this->fermetureFin($fin)];
    }

    /**
     * Au-delà du délai, l'école a-t-elle choisi l'absence d'office ? Sinon le
     * retard reste émargeable avec un motif, et rien ne doit marquer absent.
     */
    public function marqueAbsentDOffice(): bool
    {
        return ! $this->exigeJustification();
    }
}

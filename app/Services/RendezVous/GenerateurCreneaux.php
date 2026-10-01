<?php

namespace App\Services\RendezVous;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Services\Reinscription\PortailReinscriptionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GenerateurCreneaux
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly PortailReinscriptionService $portail,
    ) {
    }

    public function generer(?Carbon $maintenant = null): RapportGeneration
    {
        [$regle, $annee, $clesTheoriques] = $this->cadre($maintenant);
        $rapport = null;

        DB::transaction(function () use ($annee, $clesTheoriques, $regle, &$rapport) {
            $plan = $this->plan($this->existants($annee, $regle, true), $clesTheoriques, $regle->capacite);

            foreach ($plan['a_creer'] as $slot) {
                ESBTPRdvCreneau::create([
                    'annee_universitaire_id' => $annee->id,
                    'date' => $slot['date'],
                    'heure_debut' => $slot['heure_debut'],
                    'heure_fin' => $slot['heure_fin'],
                    'capacite' => $regle->capacite,
                    'ouvert' => true,
                ]);
            }
            foreach ($plan['a_mettre_a_jour'] as [$existant, $slot]) {
                $existant->update([
                    'heure_fin' => $slot['heure_fin'],
                    'capacite' => $regle->capacite,
                    'ouvert' => true,
                ]);
            }
            foreach ($plan['a_fermer'] as $creneau) {
                $creneau->update(['ouvert' => false]);
            }

            $rapport = $this->rapport($plan);
        });

        return $rapport;
    }

    /**
     * Ce que generer() ferait, sans rien ecrire : le meme plan, lu sans verrou.
     * Sert a montrer la generation avant de la faire (Nanan, « proposer puis
     * Valider ») ; generer() refait le plan sous verrou au moment d'ecrire.
     */
    public function simuler(?Carbon $maintenant = null): RapportGeneration
    {
        [$regle, $annee, $clesTheoriques] = $this->cadre($maintenant);

        return $this->rapport($this->plan($this->existants($annee, $regle, false), $clesTheoriques, $regle->capacite));
    }

    /** @return array{0: CreneauRegle, 1: ESBTPAnneeUniversitaire, 2: array<string, array{date: string, heure_debut: string, heure_fin: string}>} */
    private function cadre(?Carbon $maintenant): array
    {
        $regle = $this->reglages->pourGeneration();
        $maintenant = $maintenant?->copy() ?? Carbon::now();
        $annee = $this->anneeCible();

        $clesTheoriques = [];
        foreach (self::theoriques($regle, $maintenant) as $slot) {
            $clesTheoriques[$slot['date'].'|'.$slot['heure_debut']] = $slot;
        }

        return [$regle, $annee, $clesTheoriques];
    }

    private function existants(ESBTPAnneeUniversitaire $annee, CreneauRegle $regle, bool $verrou): Collection
    {
        return ESBTPRdvCreneau::query()
            ->where('annee_universitaire_id', $annee->id)
            ->whereDate('date', '>=', $regle->plancher->toDateString())
            ->whereDate('date', '<=', $regle->fermeture->toDateString())
            ->when($verrou, fn ($q) => $q->lockForUpdate())
            ->get()
            ->keyBy(fn (ESBTPRdvCreneau $c) => $c->date->toDateString().'|'.$c->heureDebutHi());
    }

    /**
     * Un creneau deja reserve n'est jamais retouche ; un creneau libre hors de la
     * regle est ferme, jamais supprime.
     *
     * @return array{a_creer: list<array>, a_mettre_a_jour: list<array{0: ESBTPRdvCreneau, 1: array}>, a_fermer: list<ESBTPRdvCreneau>, conserves: int, inchanges: int}
     */
    private function plan(Collection $existants, array $clesTheoriques, int $capacite): array
    {
        $plan = ['a_creer' => [], 'a_mettre_a_jour' => [], 'a_fermer' => [], 'conserves' => 0, 'inchanges' => 0];

        foreach ($clesTheoriques as $cle => $slot) {
            $existant = $existants->get($cle);
            if ($existant === null) {
                $plan['a_creer'][] = $slot;
            } elseif ($existant->estOccupe()) {
                $plan['conserves']++;
            } else {
                $plan['a_mettre_a_jour'][] = [$existant, $slot];
                // Deja conforme : « mis a jour » sans que rien change. Compte a
                // part, pour qu'un apercu puisse dire « deja a jour ».
                if ($existant->ouvert && (int) $existant->capacite === $capacite && $existant->heureFinHi() === $slot['heure_fin']) {
                    $plan['inchanges']++;
                }
            }
        }

        foreach ($existants as $cle => $creneau) {
            if (isset($clesTheoriques[$cle])) {
                continue;
            }
            if ($creneau->estOccupe()) {
                $plan['conserves']++;
            } elseif ($creneau->ouvert) {
                $plan['a_fermer'][] = $creneau;
            }
        }

        return $plan;
    }

    private function rapport(array $plan): RapportGeneration
    {
        return new RapportGeneration(count($plan['a_creer']), count($plan['a_mettre_a_jour']), count($plan['a_fermer']), $plan['conserves'], $plan['inchanges']);
    }

    /**
     * @return list<array{date: string, heure_debut: string, heure_fin: string}>
     */
    public static function theoriques(CreneauRegle $regle, Carbon $maintenant): array
    {
        $slots = [];
        $jour = $regle->plancher->copy()->startOfDay();
        $fin = $regle->fermeture->copy()->startOfDay();
        $aujourdHui = $maintenant->copy()->startOfDay();

        while ($jour->lte($fin)) {
            if (in_array((int) $jour->dayOfWeekIso, $regle->joursOuverts, true)) {
                foreach (self::horairesDuJour($regle) as [$debut, $finHeure]) {
                    if ($jour->lt($aujourdHui)) {
                        continue;
                    }
                    if ($jour->equalTo($aujourdHui)) {
                        $debutDuSlot = $jour->copy()->setTimeFromTimeString($debut.':00');
                        if ($debutDuSlot->lte($maintenant)) {
                            continue;
                        }
                    }

                    $slots[] = [
                        'date' => $jour->toDateString(),
                        'heure_debut' => $debut,
                        'heure_fin' => $finHeure,
                    ];
                }
            }

            $jour->addDay();
        }

        return $slots;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function horairesDuJour(CreneauRegle $regle): array
    {
        $horaires = [];
        $curseur = self::minutes($regle->heureDebut);
        $fin = self::minutes($regle->heureFin);
        $pauseDebut = $regle->pauseDebut !== null ? self::minutes($regle->pauseDebut) : null;
        $pauseFin = $regle->pauseFin !== null ? self::minutes($regle->pauseFin) : null;

        while ($curseur + $regle->dureeMinutes <= $fin) {
            $finSlot = $curseur + $regle->dureeMinutes;
            $chevauchePause = $pauseDebut !== null && $pauseFin !== null
                && $curseur < $pauseFin
                && $finSlot > $pauseDebut;

            if (! $chevauchePause) {
                $horaires[] = [self::formatMinutes($curseur), self::formatMinutes($finSlot)];
            }

            $curseur += $regle->dureeMinutes;
        }

        return $horaires;
    }

    private function anneeCible(): ESBTPAnneeUniversitaire
    {
        $annee = $this->portail->anneeCible();
        if ($annee === null) {
            throw new ReglagesRdvIncomplets([PortailReinscriptionService::REGLAGE_ANNEE_CIBLE]);
        }

        return $annee;
    }

    private static function minutes(string $heure): int
    {
        [$h, $m] = array_map('intval', explode(':', $heure));

        return ($h * 60) + $m;
    }

    private static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}

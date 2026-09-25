<?php

declare(strict_types=1);

namespace App\Domain\EmploiTemps;

use App\Models\ESBTPProlongationSeance;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rallonger un cours qui déborde, sans marcher sur le suivant.
 *
 * L'enseignant demande ; une personne habilitée décide. Le contrôle de conflit
 * n'est pas réécrit : c'est celui qui garde déjà la création d'une séance
 * (`ConflitsDUnCreneau`), appliqué au SEUL créneau ajouté — de l'ancienne fin à
 * la nouvelle. Il voit donc la séance suivante de la classe, la salle occupée
 * et un autre cours de l'enseignant ailleurs, sur tout emploi du temps actif.
 *
 * Une prolongation accordée déplace l'heure de fin effective de cette
 * occurrence. La fenêtre d'émargement de fin la lit via `heureFinEffective()`
 * (par `FenetresDEmargement::fenetreDeFin()`). Le décompte des heures payées,
 * lui, calcule sur toute une période et additionne les minutes accordées dans
 * `TeacherHoursService::minutesProlongees()` : même donnée, lecture groupée
 * pour ne pas interroger la base séance par séance.
 */
final class ProlongationDeSeance
{
    public const MINUTES_MAX = 240;

    public function demander(ESBTPSeanceCours $seance, User $demandeur, int $minutes, string $motif, ?Carbon $date = null): ESBTPProlongationSeance
    {
        $date ??= Carbon::today();
        $motif = trim($motif);

        if ($minutes < 5 || $minutes > self::MINUTES_MAX) {
            throw ValidationException::withMessages(['minutes' => 'La prolongation doit être comprise entre 5 et '.self::MINUTES_MAX.' minutes.']);
        }
        if (mb_strlen($motif) < 5) {
            throw ValidationException::withMessages(['motif' => 'Indiquez en quelques mots pourquoi le cours doit être prolongé.']);
        }

        $dejaEnAttente = ESBTPProlongationSeance::where('seance_cours_id', $seance->id)
            ->whereDate('date', $date)
            ->where('statut', ESBTPProlongationSeance::EN_ATTENTE)
            ->exists();
        if ($dejaEnAttente) {
            throw ValidationException::withMessages(['minutes' => 'Une demande de prolongation est déjà en attente pour ce cours.']);
        }

        $finActuelle = $this->heureFinEffective($seance, $date);

        return ESBTPProlongationSeance::create([
            'seance_cours_id' => $seance->id,
            'date' => $date->toDateString(),
            'heure_fin_initiale' => $finActuelle->format('H:i:s'),
            'heure_fin_demandee' => $finActuelle->copy()->addMinutes($minutes)->format('H:i:s'),
            'minutes' => $minutes,
            'motif' => mb_substr($motif, 0, 500),
            'statut' => ESBTPProlongationSeance::EN_ATTENTE,
            'demandee_par' => $demandeur->id,
        ]);
    }

    /**
     * Les conflits que la prolongation créerait, en phrases destinées à l'utilisateur.
     *
     * @return string[]
     */
    public function conflits(ESBTPProlongationSeance $prolongation): array
    {
        $seance = $prolongation->seance()->with('emploiTemps')->first();

        if (! $seance || ! $seance->emploiTemps) {
            return ['La séance n’est rattachée à aucun emploi du temps : les conflits n’ont pas pu être vérifiés.'];
        }

        if (substr((string) $prolongation->heure_fin_demandee, 0, 5) <= substr((string) $prolongation->heure_fin_initiale, 0, 5)) {
            return ['La prolongation dépasse minuit : elle ne peut pas être accordée.'];
        }

        return (new ConflitsDUnCreneau($seance->emploiTemps))->pourUneSeanceModifiee(
            $seance,
            $seance->jour,
            substr((string) $prolongation->heure_fin_initiale, 0, 5),
            substr((string) $prolongation->heure_fin_demandee, 0, 5),
            $seance->teacher_id ? (int) $seance->teacher_id : null,
            $seance->salle,
        );
    }

    public function accorder(ESBTPProlongationSeance $prolongation, User $decideur): ESBTPProlongationSeance
    {
        $this->exigerEnAttente($prolongation);

        $conflits = $this->conflits($prolongation);
        if ($conflits !== []) {
            // Le refus est enregistré avec ses raisons : la coordination voit
            // pourquoi, et l'enseignant aussi.
            $this->clore($prolongation, $decideur, ESBTPProlongationSeance::REFUSEE, 'Conflit : '.implode(' ', $conflits), $conflits);

            throw ValidationException::withMessages(['prolongation' => $conflits]);
        }

        return $this->clore($prolongation, $decideur, ESBTPProlongationSeance::ACCORDEE, null, []);
    }

    public function refuser(ESBTPProlongationSeance $prolongation, User $decideur, ?string $motif): ESBTPProlongationSeance
    {
        $this->exigerEnAttente($prolongation);

        return $this->clore($prolongation, $decideur, ESBTPProlongationSeance::REFUSEE, $motif ? trim($motif) : null, null);
    }

    /**
     * L'heure de fin réelle de l'occurrence du jour : planifiée, ou prolongée.
     */
    public function heureFinEffective(ESBTPSeanceCours $seance, ?Carbon $date = null): Carbon
    {
        $date ??= Carbon::today();
        $finPlanifiee = $this->surLaDate($seance->getAttributes()['heure_fin'] ?? null, $date)
            ?? $date->copy()->setTime(23, 59);

        $derniere = ESBTPProlongationSeance::where('seance_cours_id', $seance->id)
            ->whereDate('date', $date)
            ->where('statut', ESBTPProlongationSeance::ACCORDEE)
            ->orderByDesc('heure_fin_demandee')
            ->value('heure_fin_demandee');

        if ($derniere === null) {
            return $finPlanifiee;
        }

        $prolongee = $this->surLaDate($derniere, $date);

        return $prolongee && $prolongee->gt($finPlanifiee) ? $prolongee : $finPlanifiee;
    }

    /**
     * Une valeur de colonne `time` posée sur une date. Lue en brut : l'accesseur
     * de `ESBTPSeanceCours` rendrait un Carbon daté d'AUJOURD'HUI (piège #14).
     */
    private function surLaDate(mixed $heure, Carbon $date): ?Carbon
    {
        $texte = HeureDeSeance::hi($heure);
        if ($texte === null || ! preg_match('/^\d{2}:\d{2}$/', $texte)) {
            return null;
        }

        return $date->copy()->startOfDay()->setTimeFromTimeString($texte);
    }

    private function exigerEnAttente(ESBTPProlongationSeance $prolongation): void
    {
        if ($prolongation->statut !== ESBTPProlongationSeance::EN_ATTENTE) {
            throw ValidationException::withMessages(['prolongation' => 'Cette demande a déjà été traitée ('.$prolongation->libelleStatut().').']);
        }
    }

    private function clore(ESBTPProlongationSeance $prolongation, User $decideur, string $statut, ?string $motif, ?array $conflits): ESBTPProlongationSeance
    {
        return DB::transaction(function () use ($prolongation, $decideur, $statut, $motif, $conflits) {
            $prolongation->forceFill([
                'statut' => $statut,
                'decidee_par' => $decideur->id,
                'decidee_le' => now(),
                'motif_decision' => $motif ? mb_substr($motif, 0, 500) : null,
                'conflits' => $conflits,
            ])->save();

            return $prolongation;
        });
    }
}

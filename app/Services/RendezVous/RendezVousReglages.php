<?php

namespace App\Services\RendezVous;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Helpers\SettingsHelper;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Reinscription\PortailReinscriptionService;
use Carbon\Carbon;

class RendezVousReglages
{
    public const ENABLED = 'inscriptions.rdv.enabled';

    public const OUVERTURE = 'inscriptions.rdv.ouverture';

    public const FERMETURE = 'inscriptions.rdv.fermeture';

    public const JOURS = 'inscriptions.rdv.jours_ouverts';

    public const HEURE_DEBUT = 'inscriptions.rdv.heure_debut';

    public const HEURE_FIN = 'inscriptions.rdv.heure_fin';

    public const PAUSE_DEBUT = 'inscriptions.rdv.pause_debut';

    public const PAUSE_FIN = 'inscriptions.rdv.pause_fin';

    public const DUREE = 'inscriptions.rdv.duree_minutes';

    public const CAPACITE = 'inscriptions.rdv.capacite';

    public const DELAI_MIN = 'inscriptions.rdv.delai_min_heures';

    public const DELAI_MODIF = 'inscriptions.rdv.delai_modif_heures';

    /** Minutes apres le debut d'un creneau au-dela desquelles une famille attendue est « en retard ». */
    public const GRACE = 'inscriptions.rdv.grace_no_show_minutes';

    /** Proposer la convocation par WhatsApp, apres accord, aux familles que le courriel n'a pas atteintes. */
    public const WHATSAPP_RELAIS = 'inscriptions.rdv.whatsapp_relais';

    /** Le message de demande d'accord. Vide : TEXTE_ACCORD_DEFAUT. */
    public const WHATSAPP_TEXTE_ACCORD = 'inscriptions.rdv.whatsapp_texte_accord';

    /**
     * Nomme l'ecole ET KLASSCI : la famille recoit un message d'un numero
     * qu'elle ne connait pas, elle doit savoir qui lui ecrit et pour quoi.
     * Reperes : {ecole}, {candidat}, {date}, {heure}, {reference}.
     */
    public const TEXTE_ACCORD_DEFAUT = 'Bonjour, ici le service des inscriptions de {ecole}, via KLASSCI. '
        .'Nous avons un rendez-vous d\'inscription pour {candidat} le {date} à {heure}. '
        .'Acceptez-vous de recevoir la convocation sur WhatsApp ? Répondez OUI ou NON.';

    /** @return list<string> */
    public static function clesTexte(): array
    {
        return [
            self::OUVERTURE,
            self::FERMETURE,
            self::JOURS,
            self::HEURE_DEBUT,
            self::HEURE_FIN,
            self::PAUSE_DEBUT,
            self::PAUSE_FIN,
            self::DUREE,
            self::CAPACITE,
            self::DELAI_MIN,
            self::DELAI_MODIF,
            self::GRACE,
        ];
    }

    /** @return list<string> */
    public static function clesBascules(): array
    {
        return [self::ENABLED];
    }

    public function enabled(): bool
    {
        return $this->flag(self::ENABLED);
    }

    /**
     * Les reglages du relais WhatsApp, a part : ils n'existent qu'une fois leur
     * migration passee, et Setting::set() leve sur une cle absente.
     *
     * @return array{bascules: list<string>, textes: list<string>}
     */
    public static function clesWhatsapp(): array
    {
        return ['bascules' => [self::WHATSAPP_RELAIS], 'textes' => [self::WHATSAPP_TEXTE_ACCORD]];
    }

    public function whatsappRelais(): bool
    {
        return $this->flag(self::WHATSAPP_RELAIS);
    }

    public function texteAccordWhatsapp(): string
    {
        $texte = $this->valeur(self::WHATSAPP_TEXTE_ACCORD, '');

        return $texte !== '' ? $texte : self::TEXTE_ACCORD_DEFAUT;
    }

    public function pourGeneration(): CreneauRegle
    {
        $manquants = [];

        $ouverture = $this->dateObligatoire(self::OUVERTURE, $manquants);
        $fermeture = $this->dateObligatoire(self::FERMETURE, $manquants);
        $plancherPhysique = $this->dateObligatoire(PortailCandidaturePublication::REGLAGE_PHYSIQUES, $manquants);
        $heureDebut = $this->heureObligatoire(self::HEURE_DEBUT, $manquants);
        $heureFin = $this->heureObligatoire(self::HEURE_FIN, $manquants);
        $duree = $this->entierPositif(self::DUREE, $manquants);
        $capacite = $this->entierPositif(self::CAPACITE, $manquants);
        $jours = $this->joursOuverts($manquants);

        $pauseDebut = $this->heureOptionnelle(self::PAUSE_DEBUT);
        $pauseFin = $this->heureOptionnelle(self::PAUSE_FIN);

        if (($pauseDebut === null) !== ($pauseFin === null)) {
            $manquants[] = $pauseDebut === null ? self::PAUSE_DEBUT : self::PAUSE_FIN;
        }

        if ($heureDebut !== null && $heureFin !== null && $heureDebut >= $heureFin) {
            $manquants[] = self::HEURE_FIN;
        }

        if ($pauseDebut !== null && $pauseFin !== null && $pauseDebut >= $pauseFin) {
            $manquants[] = self::PAUSE_FIN;
        }

        if ($ouverture !== null && $fermeture !== null && $ouverture->gt($fermeture)) {
            $manquants[] = self::FERMETURE;
        }

        if ($manquants !== []) {
            throw new ReglagesRdvIncomplets(array_values(array_unique($manquants)));
        }

        $plancher = $ouverture->copy();
        if ($plancherPhysique->gt($plancher)) {
            $plancher = $plancherPhysique;
        }

        return new CreneauRegle(
            ouverture: $ouverture,
            fermeture: $fermeture,
            plancher: $plancher,
            heureDebut: $heureDebut,
            heureFin: $heureFin,
            dureeMinutes: $duree,
            capacite: $capacite,
            joursOuverts: $jours,
            pauseDebut: $pauseDebut,
            pauseFin: $pauseFin,
        );
    }

    /**
     * @return array{duree: int, capacite: int, minutes_utiles: int, creneaux_par_jour: int, personnes_par_jour: int}|null
     */
    public function debitJournalier(): ?array
    {
        try {
            $regle = $this->pourGeneration();
        } catch (ReglagesRdvIncomplets) {
            return null;
        }

        $debut = $this->minutesDepuisMinuit($regle->heureDebut);
        $fin = $this->minutesDepuisMinuit($regle->heureFin);
        $pause = 0;
        if ($regle->pauseDebut !== null && $regle->pauseFin !== null) {
            $pause = $this->minutesDepuisMinuit($regle->pauseFin)
                - $this->minutesDepuisMinuit($regle->pauseDebut);
        }

        $utiles = max(0, $fin - $debut - $pause);
        $parJour = intdiv($utiles, $regle->dureeMinutes);

        return [
            'duree' => $regle->dureeMinutes,
            'capacite' => $regle->capacite,
            'minutes_utiles' => $utiles,
            'creneaux_par_jour' => $parJour,
            'personnes_par_jour' => $parJour * $regle->capacite,
        ];
    }

    /**
     * @param  list<string>  $manquants
     */
    private function dateObligatoire(string $cle, array &$manquants): ?Carbon
    {
        $valeur = $this->texte($cle);
        if ($valeur === '') {
            $manquants[] = $cle;

            return null;
        }

        $date = PortailReinscriptionService::interpreterDateIso($valeur);
        if ($date === null) {
            $manquants[] = $cle;

            return null;
        }

        return $date->startOfDay();
    }

    /**
     * @param  list<string>  $manquants
     */
    private function heureObligatoire(string $cle, array &$manquants): ?string
    {
        $heure = $this->heureOptionnelle($cle);
        if ($heure === null) {
            $manquants[] = $cle;
        }

        return $heure;
    }

    private function heureOptionnelle(string $cle): ?string
    {
        $valeur = $this->texte($cle);
        if ($valeur === '') {
            return null;
        }

        return $this->interpreterHeure($valeur);
    }

    /**
     * @param  list<string>  $manquants
     * @return list<int>
     */
    private function joursOuverts(array &$manquants): array
    {
        $brut = $this->texte(self::JOURS);
        if ($brut === '') {
            $manquants[] = self::JOURS;

            return [];
        }

        $jours = [];
        foreach (preg_split('/[,\s]+/', $brut) as $morceau) {
            if ($morceau === '') {
                continue;
            }
            $n = (int) $morceau;
            if ($n < 1 || $n > 7) {
                $manquants[] = self::JOURS;

                return [];
            }
            $jours[] = $n;
        }

        $jours = array_values(array_unique($jours));
        sort($jours);

        if ($jours === []) {
            $manquants[] = self::JOURS;
        }

        return $jours;
    }

    /**
     * @param  list<string>  $manquants
     */
    private function entierPositif(string $cle, array &$manquants): int
    {
        $valeur = $this->texte($cle);
        if ($valeur === '' || ! ctype_digit($valeur) || (int) $valeur < 1) {
            $manquants[] = $cle;

            return 0;
        }

        return (int) $valeur;
    }

    public function interpreterHeure(string $valeur): ?string
    {
        foreach (['H:i', 'H:i:s'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $valeur);
            if ($date !== false && $date->format($format) === $valeur) {
                return $date->format('H:i');
            }
        }

        return null;
    }

    public function minutesDepuisMinuit(string $heure): int
    {
        [$h, $m] = array_map('intval', explode(':', $heure));

        return ($h * 60) + $m;
    }

    public function graceMinutes(): int
    {
        $valeur = $this->valeur(self::GRACE, '15');

        return ctype_digit($valeur) ? (int) $valeur : 15;
    }

    public function valeur(string $cle, string $defaut = ''): string
    {
        $valeur = SettingsHelper::get($cle, $defaut);

        return is_scalar($valeur) ? trim((string) $valeur) : '';
    }

    private function texte(string $cle): string
    {
        return $this->valeur($cle, '');
    }

    private function flag(string $cle): bool
    {
        $valeur = SettingsHelper::get($cle, '0');

        return $valeur === true || $valeur === 1 || $valeur === '1';
    }
}

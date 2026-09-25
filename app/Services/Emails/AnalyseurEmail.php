<?php

namespace App\Services\Emails;

use App\Enums\EtatEmail;

/**
 * Analyse une adresse SANS reseau : forme, domaine factice, faute de frappe.
 *
 * Meme algorithme que `lib/email/verifier-email.ts` du site vitrine, sur les
 * memes donnees (DomainesSuspects) :
 * - une faute CONNUE est certaine ;
 * - sinon le domaine se coupe en nom + extension ; l'extension ne se corrige
 *   que par la table explicite (con → com, fe → fr…), jamais en remplacant une
 *   extension de pays valide par une autre ;
 * - le NOM seul se compare aux messageries de reference de meme extension,
 *   par distance d'alignement optimal (une transposition compte pour 1) :
 *   au plus `distance_maximale`, c'est une faute probable ;
 * - un nom reel voisin (ymail, mail, email, gmx) n'est jamais corrige.
 */
class AnalyseurEmail
{
    public function __construct(private readonly DomainesSuspects $listes) {}

    public function analyser(?string $brut): AnalyseEmail
    {
        $email = trim((string) $brut);

        if ($email === '') {
            return new AnalyseEmail(EtatEmail::Vide);
        }

        // Un point final (`gmail.com.`) ou double (`gmail..com`) n'est jamais une vraie adresse.
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || str_ends_with($email, '.') || str_contains($email, '..')) {
            return new AnalyseEmail(EtatEmail::Invalide);
        }

        $arobase = strrpos($email, '@');
        $local = substr($email, 0, $arobase);
        $domaine = mb_strtolower(substr($email, $arobase + 1));

        if ($this->estFactice($domaine)) {
            return new AnalyseEmail(EtatEmail::Factice, $domaine);
        }

        $correction = $this->corriger($domaine);
        if ($correction === null) {
            return new AnalyseEmail(EtatEmail::Valide, $domaine);
        }

        [$corrige, $certaine] = $correction;

        return new AnalyseEmail($certaine ? EtatEmail::FauteDeFrappe : EtatEmail::FauteProbable, $domaine, $local.'@'.$corrige);
    }

    public function estFactice(string $domaine): bool
    {
        foreach ($this->listes->domainesFactices() as $factice) {
            if ($domaine === $factice || str_ends_with($domaine, '.'.$factice)) {
                return true;
            }
        }

        $extension = substr((string) strrchr('.'.$domaine, '.'), 1);

        return in_array($extension, $this->listes->extensionsReservees(), true);
    }

    /** @return array{0: string, 1: bool}|null le domaine corrige, et si la correction est certaine */
    private function corriger(string $domaine): ?array
    {
        $connue = $this->listes->correctionsConnues()[$domaine] ?? null;
        if (is_string($connue)) {
            return [$connue, true];
        }

        [$nom, $tld] = $this->decouper($domaine);
        $tldCorrige = $this->listes->correctionsTld()[$tld] ?? $tld;

        $meilleur = $this->nomVoisin($nom, $tldCorrige);
        if ($meilleur !== null) {
            return [$meilleur.'.'.$tldCorrige, false];
        }

        return $tldCorrige !== $tld ? [$nom.'.'.$tldCorrige, true] : null;
    }

    /**
     * Le nom de messagerie de reference le plus proche, a distance 1..max, de
     * meme extension. Deux references a la meme plus petite distance : aucune
     * suggestion, on ne choisit pas au hasard la messagerie de la famille.
     */
    private function nomVoisin(string $nom, string $tld): ?string
    {
        if (in_array($nom, $this->listes->nomsReelsVoisins(), true)) {
            return null;
        }

        $maximum = $this->listes->distanceMaximale();
        $meilleur = null;
        $meilleureDistance = PHP_INT_MAX;
        $egalite = false;

        foreach ($this->listes->domainesReference() as $reference) {
            [$refNom, $refTld] = $this->decouper($reference);
            if ($refTld !== $tld) {
                continue;
            }

            $distance = DistanceEdition::alignementOptimal($nom, $refNom);
            if ($distance > $maximum) {
                continue;
            }
            if ($distance < $meilleureDistance) {
                $meilleur = $refNom;
                $meilleureDistance = $distance;
                $egalite = false;
            } elseif ($distance === $meilleureDistance && $refNom !== $meilleur) {
                $egalite = true;
            }
        }

        return $meilleur !== null && $meilleureDistance > 0 && ! $egalite ? $meilleur : null;
    }

    /** @return array{0: string, 1: string} */
    private function decouper(string $domaine): array
    {
        $point = strrpos($domaine, '.');

        return $point === false ? [$domaine, ''] : [substr($domaine, 0, $point), substr($domaine, $point + 1)];
    }
}

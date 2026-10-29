<?php

namespace App\Domain\Admissions;

use App\Enums\StatutReservationRdv;
use Carbon\CarbonInterface;

/**
 * Le chemin d'un dossier, du depot a l'inscription, tel que les donnees le
 * prouvent. Aucune etape n'est supposee : une etape sans date n'est pas
 * franchie. « La prochaine » est la derniere, l'inscription ou la
 * reinscription, tant que le dossier est ouvert (voir marquerLaProchaine).
 *
 * C'est ce qui manquait aux deux corbeilles : l'agent voyait un statut, jamais
 * ce qui s'etait passe avant ni ce qui restait a faire.
 */
final class ParcoursDuDossier
{
    /**
     * @return list<array{titre: string, detail: string, fait: bool, prochaine: bool, ton: string}>
     */
    public static function pour(DemandeDInscription $demande): array
    {
        $m = $demande->modele;
        $rdv = $demande->rendezVous;
        $contact = $m->contact_confirme_at ?? $m->telephone_verifie_at ?? $m->email_verifie_at;

        $etapes = [
            self::etape('Déposée en ligne', $m->created_at, 'depuis le portail'),
            self::etape('Contact vérifié', $contact, match (true) {
                $contact === null => '',
                $m->contact_confirme_at !== null => 'confirmé par l\'école',
                default => 'confirmé par la famille',
            }),
            self::etape('Rendez-vous fixé', $rdv?->created_at,
                $rdv?->creneau ? ucfirst($rdv->creneau->date->translatedFormat('l j F')).' · '.$rdv->creneau->heureDebutHi() : ''),
            self::etape('Reçue au guichet', $rdv?->statut === StatutReservationRdv::Honoree ? $rdv->accueilli_at : null,
                $rdv?->accueilliPar ? 'par '.$rdv->accueilliPar->name : ''),
        ];

        if ($demande->estNouvelle()) {
            $acceptee = $demande->estAcceptee() || $demande->estInscrite();
            $etapes[] = self::etape('Acceptée', $acceptee ? ($m->traite_at ?? $m->updated_at) : null, $m->traitePar ? 'par '.$m->traitePar->name : '');
        }

        $etapes[] = $demande->estRejetee()
            ? self::etape('Rejetée', $m->traite_at, (string) $m->motif_rejet, 'echec')
            : self::etape($demande->estNouvelle() ? 'Inscrite' : 'Réinscrite', $demande->estInscrite() ? $m->traite_at : null,
                $demande->estInscrite() && $m->traitePar ? 'par '.$m->traitePar->name : 'doublons, classe, matricule, aperçu');

        return self::marquerLaProchaine($etapes, $demande->estOuverte());
    }

    /** @return array{titre: string, detail: string, fait: bool, prochaine: bool, ton: string, quand: ?CarbonInterface} */
    private static function etape(string $titre, ?CarbonInterface $quand, string $detail, string $ton = 'normal'): array
    {
        return [
            'titre' => $titre,
            'detail' => trim(($quand ? self::date($quand) : '').($quand && $detail !== '' ? ' · ' : '').$detail),
            'fait' => $quand !== null,
            'prochaine' => false,
            'ton' => $ton,
            'quand' => $quand,
        ];
    }

    /**
     * La prochaine etape est la DERNIERE, tant que le dossier est ouvert : les
     * etapes intermediaires (contact, rendez-vous) sont souhaitables, pas
     * obligatoires — une famille venue sans rendez-vous s'inscrit quand meme.
     *
     * @param  list<array<string, mixed>>  $etapes
     * @return list<array<string, mixed>>
     */
    private static function marquerLaProchaine(array $etapes, bool $ouverte): array
    {
        if ($ouverte) {
            $etapes[array_key_last($etapes)]['prochaine'] = true;
        }

        return $etapes;
    }

    private static function date(CarbonInterface $quand): string
    {
        return $quand->isToday() ? "aujourd'hui ".$quand->format('H:i') : $quand->translatedFormat('j M').' · '.$quand->format('H:i');
    }
}

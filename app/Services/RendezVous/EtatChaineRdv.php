<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Exceptions\ReglagesRdvIncomplets;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\MailPulse\MailPulseClient;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Reinscription\PortailSignatureVerifier;
use App\Support\ColonnesDeployees;

/**
 * Chaque maillon qui separe une famille de sa convocation, et son etat.
 *
 * Tous ces maillons echouaient EN SILENCE : canal ferme, reglage manquant,
 * aucune place, MailPulse coupe. L'ecran affichait pourtant des creneaux
 * « Ouvert ». Ce service est la seule source de ce diagnostic — l'ecran, la
 * commande artisan et l'API CLI le lisent tous trois, pour qu'ils ne puissent
 * pas se contredire.
 */
class EtatChaineRdv
{
    private const LIBELLES_REGLAGES = [
        RendezVousReglages::OUVERTURE => 'premier jour des rendez-vous',
        RendezVousReglages::FERMETURE => 'dernier jour',
        RendezVousReglages::JOURS => 'jours ouverts',
        RendezVousReglages::HEURE_DEBUT => 'ouverture du guichet',
        RendezVousReglages::HEURE_FIN => 'fermeture du guichet',
        RendezVousReglages::PAUSE_DEBUT => 'début de pause',
        RendezVousReglages::PAUSE_FIN => 'fin de pause',
        RendezVousReglages::DUREE => 'durée d\'un créneau',
        RendezVousReglages::CAPACITE => 'places par créneau',
        PortailCandidaturePublication::REGLAGE_PHYSIQUES => 'date d\'ouverture des inscriptions sur place (réglages Inscriptions)',
        PortailReinscriptionService::REGLAGE_ANNEE_CIBLE => 'année universitaire cible (réglages Inscriptions)',
    ];

    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly CatalogueCreneaux $catalogue,
        private readonly PortailReinscriptionService $saison,
        private readonly PortailSignatureVerifier $signature,
        private readonly MailPulseClient $mailpulse,
        private readonly PerimetreRdv $perimetre,
    ) {
    }

    /**
     * @return list<array{cle: string, ok: bool, titre: string, detail: string}>
     */
    public function maillons(): array
    {
        return [
            $this->canal(),
            $this->reglagesComplets(),
            $this->places(),
            $this->portail(),
            $this->messagerie(),
        ];
    }

    public function toutEstEnOrdre(): bool
    {
        foreach ($this->maillons() as $maillon) {
            if (! $maillon['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Les convocations par etat. `inconnu` : reservations actives anterieures au
     * suivi, dont on ne sait pas si le courriel est parti.
     *
     * @return array{en_attente: int, envoyee: int, echec: int, sans_email: int, inconnu: int}
     */
    public function convocations(): array
    {
        // Meme perimetre que les familles a recontacter et le diagnostic e-mails.
        $comptes = $this->perimetre->reservations()
            ->selectRaw('convocation_statut, COUNT(*) AS n')
            ->whereNotNull('convocation_statut')
            ->groupBy('convocation_statut')
            ->pluck('n', 'convocation_statut');

        $resultat = [];
        foreach (StatutConvocationRdv::cases() as $statut) {
            $resultat[$statut->value] = (int) ($comptes[$statut->value] ?? 0);
        }
        $resultat['inconnu'] = $this->perimetre->reservations()->occupantes()->whereNull('convocation_statut')->count();
        // « Envoyee » veut dire « acceptee par MailPulse ». La remise, elle, n'est
        // connue qu'apres synchronisation (inscriptions:synchroniser-convocations-rdv).
        $resultat['delivrees'] = ColonnesDeployees::existe('esbtp_rdv_reservations', 'convocation_delivree_at')
            ? $this->perimetre->reservations()->whereNotNull('convocation_delivree_at')->count()
            : 0;

        return $resultat;
    }

    private function canal(): array
    {
        $ok = $this->reglages->enabled();

        return [
            'cle' => 'canal',
            'ok' => $ok,
            'titre' => $ok ? 'Prise de rendez-vous ouverte aux familles' : 'Prise de rendez-vous fermée',
            'detail' => $ok
                ? 'Le portail public propose les créneaux libres.'
                : 'Le portail répond « pas ouverte » à toutes les familles, même si des créneaux existent. Cochez « Ouvrir la prise de rendez-vous » dans les réglages.',
        ];
    }

    private function reglagesComplets(): array
    {
        $manquants = [];
        try {
            $this->reglages->pourGeneration();
        } catch (ReglagesRdvIncomplets $e) {
            $manquants = $e->cles;
        }
        if ($this->saison->anneeCible() === null) {
            $manquants[] = PortailReinscriptionService::REGLAGE_ANNEE_CIBLE;
        }

        $libelles = array_map(fn (string $cle) => self::LIBELLES_REGLAGES[$cle] ?? $cle, array_values(array_unique($manquants)));

        return [
            'cle' => 'reglages',
            'ok' => $libelles === [],
            'titre' => $libelles === [] ? 'Réglages complets' : 'Réglages incomplets',
            'detail' => $libelles === []
                ? 'Horaires, jours et capacité sont renseignés.'
                : 'À renseigner : '.implode(', ', $libelles).'.',
        ];
    }

    private function places(): array
    {
        $libres = $this->catalogue->placesLibres();
        $total = array_sum($libres);

        return [
            'cle' => 'places',
            'ok' => $total > 0,
            'titre' => $total > 0 ? $total.' places libres à venir' : 'Aucune place libre à venir',
            'detail' => $total > 0
                ? 'Sur '.count($libres).' créneaux ouverts.'
                : 'Les familles ne voient aucun créneau. Générez les créneaux, ou rouvrez-en.',
        ];
    }

    private function portail(): array
    {
        $ok = $this->signature->estConfigure();

        return [
            'cle' => 'portail',
            'ok' => $ok,
            'titre' => $ok ? 'Liaison avec le site klassci.com active' : 'Liaison avec le site klassci.com non configurée',
            'detail' => $ok
                ? 'Les demandes du site sont signées et acceptées.'
                : 'Sans secret partagé, le site ne peut ni lire les créneaux ni réserver. À régler par le support KLASSCI.',
        ];
    }

    private function messagerie(): array
    {
        $actif = filter_var($this->mailpulse->getSetting('mailpulse_enabled', 'enabled', '1'), FILTER_VALIDATE_BOOLEAN);
        $cle = (bool) ($this->mailpulse->apiKeyDiagnostics()['configured'] ?? false);
        $ok = $actif && $cle;

        return [
            'cle' => 'messagerie',
            'ok' => $ok,
            'titre' => $ok ? 'Envoi des convocations par MailPulse actif' : 'Envoi des convocations impossible',
            'detail' => match (true) {
                $ok => 'Les convocations partent par e-mail.',
                ! $actif => 'MailPulse est désactivé sur cette instance : aucune convocation ne peut partir.',
                default => 'La clé MailPulse est absente : aucune convocation ne peut partir.',
            },
        ];
    }
}

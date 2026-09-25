<?php

namespace App\Services\Portail;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\Emails\AnalyseurEmail;
use App\Services\MailPulse\MailPulseClient;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Vitrine\IdentitePublique;
use App\Support\ColonnesDeployees;
use App\Support\SeauDeDebit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * « J'ai perdu ma reference » : la famille donne l'adresse e-mail de son
 * dossier et sa date de naissance, la reference part A CETTE ADRESSE.
 *
 * Jamais affichee a l'ecran : qui connait une adresse et une date de naissance
 * ne doit pas obtenir l'acces au rendez-vous d'un autre. Et la reponse est la
 * meme que le dossier existe ou non : on ne sonde pas qui est inscrit.
 */
class ReferenceOubliee
{
    public function __construct(
        private readonly ReferencePublique $references,
        private readonly PortailReinscriptionService $reinscriptions,
        private readonly AnalyseurEmail $emails,
        private readonly MailPulseClient $mailpulse,
        private readonly IdentitePublique $identite,
    ) {
    }

    public static function seau(string $email): SeauDeDebit
    {
        return SeauDeDebit::parIdentifiant('ref-oubliee:'.hash('sha256', Str::lower(trim($email))), 3, 3600);
    }

    /** @return int le nombre de dossiers retrouves (0 ou 1 en pratique) */
    public function envoyer(string $email, string $dateNaissance): int
    {
        $email = Str::lower(trim($email));
        $naissance = PortailReinscriptionService::interpreterDateIso($dateNaissance)?->toDateString();
        if ($naissance === null || ! $this->emails->analyser($email)->joignable()) {
            return 0;
        }

        $references = $this->retrouver($email, $naissance);
        if ($references === []) {
            return 0;
        }

        // Apres la reponse : l'appel a MailPulse prendrait sinon plus de temps
        // quand un dossier existe, et la duree trahirait ce que le texte tait.
        // Une seule fois : un processus qui sert plusieurs requetes (tests,
        // Octane) rejouerait sinon l'envoi a chaque fin de requete.
        $fait = false;
        app()->terminating(function () use (&$fait, $email, $references) {
            if (! $fait) {
                $fait = true;
                $this->expedier($email, $references);
            }
        });

        return count($references);
    }

    /** @param  list<array{reference: string, type: string}>  $references */
    private function expedier(string $email, array $references): void
    {
        $donnees = $this->donnees($references);
        $resultat = $this->mailpulse->sendEmailMessage([
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $email],
            'content' => ['type' => 'text', 'text' => $this->texte($references)],
            'metadata' => [
                'source' => 'klassci',
                'workflow_event' => 'reference_dossier',
                'subject' => $donnees['sujet'],
                'email_html' => View::make('esbtp.emails.reference-dossier', $donnees)->render(),
            ],
        ]);

        if (! $resultat->ok) {
            Log::warning('Reference oubliee : courriel non parti', ['code' => $resultat->errorCode]);
        }
    }

    /** @return list<array{reference: string, type: string}> */
    private function retrouver(string $email, string $naissance): array
    {
        $trouves = [];

        $candidature = ESBTPCandidature::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereDate('date_naissance', $naissance)
            ->latest('id')
            ->first();
        if ($candidature !== null) {
            $trouves[] = ['reference' => $this->references->formater($candidature->assurerReferencePublique()), 'type' => 'inscription'];
        }

        $annee = $this->reinscriptions->anneeCible();
        if ($annee !== null) {
            $demande = ESBTPReinscriptionDemande::query()
                ->where('annee_universitaire_id', $annee->id)
                ->whereHas('etudiant', fn (Builder $q) => $q->whereDate('date_naissance', $naissance))
                ->where(function (Builder $q) use ($email) {
                    if (ColonnesDeployees::existe('esbtp_reinscription_demandes', 'email_contact')) {
                        $q->whereRaw('LOWER(email_contact) = ?', [$email]);
                    }
                    $q->orWhereHas('etudiant', fn (Builder $e) => $e->whereRaw('LOWER(email) = ?', [$email])
                        ->orWhereRaw('LOWER(email_personnel) = ?', [$email]));
                })
                ->latest('id')
                ->first();
            if ($demande !== null) {
                $trouves[] = ['reference' => $this->references->formater($demande->assurerReferencePublique()), 'type' => 'reinscription'];
            }
        }

        return $trouves;
    }

    /** @param  list<array{reference: string, type: string}>  $references */
    private function texte(array $references): string
    {
        $lignes = array_map(fn ($r) => ($r['type'] === 'inscription' ? 'Inscription' : 'Réinscription').' : '.$r['reference'], $references);

        return $this->donnees($references)['sujet']."\n\n".implode("\n", $lignes)
            ."\n\nSuivre votre dossier : ".$this->donnees($references)['lien'];
    }

    /**
     * @param  list<array{reference: string, type: string}>  $references
     * @return array<string, mixed>
     */
    private function donnees(array $references): array
    {
        $ecole = SettingsHelper::getSchoolInfo();
        $identite = $this->identite->decrire();
        $nom = trim((string) ($ecole['name'] ?? '')) ?: (trim((string) ($identite['nom'] ?? '')) ?: 'votre établissement');
        $logo = $identite['logo']['url'] ?? null;
        $code = strtolower(trim((string) config('app.tenant_code', '')));

        return [
            'sujet' => 'Votre référence de dossier · '.$nom,
            'references' => $references,
            'lien' => config('verification_contact.url_portail_public').'/inscription/universite/'.rawurlencode($code).'/suivi',
            'schoolName' => $nom,
            'schoolLogoUrl' => is_string($logo) ? $logo : null,
            'emailPrimaryColor' => SettingsHelper::getPdfSettings()['primary_color'] ?? '#0453cb',
        ];
    }
}

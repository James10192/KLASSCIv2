<?php

namespace App\Mail;

use App\Domain\Bulletins\Taches\BulletinTache;
use App\Helpers\SettingsHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * « Votre travail sur les bulletins est terminé. »
 *
 * Pas de pièce jointe : un PDF de soixante-dix bulletins dépasse ce qu'un
 * serveur de messagerie accepte, et le document contient des notes. Le lien
 * ramène dans l'application, derrière la connexion.
 */
class TacheBulletinsTermineeMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly BulletinTache $tache,
        public readonly ?string $lien,
    ) {}

    public function envelope(): Envelope
    {
        $reussie = $this->tache->statut === BulletinTache::TERMINEE;

        return new Envelope(
            subject: ($reussie ? 'Terminé · ' : 'Échec · ').$this->tache->libelle().' · '.$this->nomEcole(),
        );
    }

    public function content(): Content
    {
        $pdf = SettingsHelper::getPdfSettings();

        return new Content(
            view: 'esbtp.emails.tache-bulletins-terminee',
            with: [
                'schoolName' => $this->nomEcole(),
                'emailPrimaryColor' => $pdf['primary_color'] ?? '#0453cb',
                'reussie' => $this->tache->statut === BulletinTache::TERMINEE,
                'libelle' => $this->tache->libelle(),
                'message' => (string) $this->tache->message,
                'lien' => $this->lien,
                'libelleLien' => match (true) {
                    $this->tache->statut !== BulletinTache::TERMINEE => 'Revenir aux bulletins',
                    $this->tache->type === BulletinTache::TYPE_GENERATION => 'Voir les bulletins',
                    $this->tache->parametre('mode') === 'apercu' => "Ouvrir l'aperçu",
                    default => 'Télécharger le PDF',
                },
                'conservationHeures' => BulletinTache::CONSERVATION_HEURES,
                'estExport' => $this->tache->type === BulletinTache::TYPE_EXPORT,
                'prenom' => trim((string) ($this->tache->user->name ?? '')),
            ],
        );
    }

    private function nomEcole(): string
    {
        return trim((string) (SettingsHelper::getSchoolInfo()['name'] ?? '')) ?: 'KLASSCI';
    }
}

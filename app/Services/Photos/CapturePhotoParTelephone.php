<?php

namespace App\Services\Photos;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPCapturePhoto;
use App\Models\ESBTPEtudiant;
use App\Support\CodeQr;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Le pont entre l'écran du guichet et le téléphone qui photographie.
 *
 * Toutes les décisions de cette fonctionnalité vivent ici : la durée du lien,
 * l'endroit où dort une photo pas encore acceptée, ce qu'on fait d'un lien
 * expiré. Les contrôleurs ne font que traduire des requêtes.
 */
class CapturePhotoParTelephone
{
    /** Le dossier privé où attend une photo que personne n'a encore acceptée. */
    public const DOSSIER_PROVISOIRE = 'captures-photo';

    /** Le disque privé : `storage/app`, que le serveur web ne sert pas. */
    public const DISQUE = 'local';

    public const REGLAGE_ACTIF = 'capture_photo.telephone_actif';
    public const REGLAGE_DUREE = 'capture_photo.duree_minutes';
    public const REGLAGE_CONFIRMER = 'capture_photo.confirmer_remplacement';

    /**
     * Bornes de la durée du lien.
     *
     * Elles n'existent pas pour brider l'école mais pour arrêter la faute de
     * frappe : une durée de zéro rendrait la fonctionnalité inutilisable dès le
     * premier scan, et une durée d'un jour laisserait un code QR ouvrir un envoi
     * le lendemain matin, sur un écran que plus personne ne regarde.
     */
    private const DUREE_MIN = 1;
    private const DUREE_MAX = 120;
    private const DUREE_REPLI = 10;

    public function __construct(private StockagePhoto $photos, private CodeQr $codes)
    {
    }

    /**
     * Ouvre une capture et rend de quoi l'afficher.
     *
     * Les captures encore ouvertes pour le même étudiant sont abandonnées : deux
     * codes QR valides en même temps pour un seul élève, c'est la garantie qu'un
     * jour la photo du second arrive alors que le guichet regarde le premier.
     *
     * @return array{capture: ESBTPCapturePhoto, url: string, qr: string|null}
     */
    public function ouvrir(ESBTPEtudiant $etudiant, ?int $userId): array
    {
        ESBTPCapturePhoto::query()
            ->where('etudiant_id', $etudiant->id)
            ->where('etat', ESBTPCapturePhoto::EN_ATTENTE)
            ->update([
                'etat' => ESBTPCapturePhoto::ABANDONNEE,
                'decidee_at' => now(),
                'decidee_par' => $userId,
            ]);

        $capture = ESBTPCapturePhoto::create([
            // 48 caracteres tires du generateur cryptographique de Laravel : le
            // jeton EST la serrure de cette adresse publique.
            'jeton' => Str::random(48),
            'etudiant_id' => $etudiant->id,
            'ouverte_par' => $userId,
            'expire_at' => now()->addMinutes($this->dureeMinutes()),
            'etat' => ESBTPCapturePhoto::EN_ATTENTE,
        ]);

        $url = route('photo-capture.montrer', ['jeton' => $capture->jeton]);

        return [
            'capture' => $capture,
            'url' => $url,
            // Peut etre nul si la bibliotheque manque sur l'instance. L'ecran
            // montre alors l'adresse en clair, a recopier : c'est moins commode,
            // mais la fonctionnalite reste utilisable.
            'qr' => $this->codes->svg($url, 190),
        ];
    }

    /**
     * Le téléphone envoie sa photo.
     *
     * Elle atterrit sur le disque privé et n'écrase RIEN : la photo de
     * l'étudiant ne change qu'au moment où quelqu'un l'accepte, plus bas.
     */
    public function recevoir(ESBTPCapturePhoto $capture, UploadedFile $fichier, ?string $ip): ESBTPCapturePhoto
    {
        if (! $capture->estOuverte()) {
            throw new RuntimeException('Ce lien de prise de vue n’est plus valable.');
        }

        $nom = 'capture-'.$capture->id.'-'.Str::random(16).'.'.($fichier->getClientOriginalExtension() ?: 'jpg');
        $chemin = $fichier->storeAs(self::DOSSIER_PROVISOIRE, $nom, self::DISQUE);

        if ($chemin === false || $chemin === '') {
            throw new RuntimeException("La photo n'a pas pu être enregistrée sur le serveur.");
        }

        $capture->update([
            'etat' => ESBTPCapturePhoto::RECUE,
            'fichier_provisoire' => $chemin,
            'recue_at' => now(),
            'ip_capture' => $ip,
        ]);

        return $capture;
    }

    /**
     * Le guichet accepte la photo : elle devient celle de l'étudiant.
     *
     * L'ancienne photo n'est effacée qu'après que la nouvelle est écrite. Dans
     * l'autre ordre, une écriture qui échoue — quota, permissions — laisserait
     * l'étudiant sans aucune photo, et l'ancienne serait déjà perdue.
     */
    public function accepter(ESBTPCapturePhoto $capture, ?int $userId): string
    {
        if ($capture->etat !== ESBTPCapturePhoto::RECUE || ! $capture->fichier_provisoire) {
            throw new RuntimeException('Aucune photo reçue pour cette prise de vue.');
        }

        $source = Storage::disk(self::DISQUE)->path($capture->fichier_provisoire);

        if (! is_file($source)) {
            throw new RuntimeException('La photo reçue est introuvable sur le serveur.');
        }

        $etudiant = $capture->etudiant;
        $ancienne = $etudiant->photo;

        $nom = 'etudiant-'.$etudiant->id.'-'.Str::random(16).'.'.(pathinfo($source, PATHINFO_EXTENSION) ?: 'jpg');
        $destination = StockagePhoto::DOSSIER.'/'.$nom;

        $flux = fopen($source, 'r');

        if ($flux === false) {
            throw new RuntimeException('La photo reçue est illisible.');
        }

        try {
            $ecrit = Storage::disk('public')->put($destination, $flux);
        } finally {
            fclose($flux);
        }

        if ($ecrit === false) {
            throw new RuntimeException("La photo n'a pas pu être enregistrée.");
        }

        $etudiant->update(['photo' => $destination]);

        $this->photos->supprimer($ancienne);
        Storage::disk(self::DISQUE)->delete($capture->fichier_provisoire);

        $capture->update([
            'etat' => ESBTPCapturePhoto::VALIDEE,
            'fichier_provisoire' => null,
            'decidee_at' => now(),
            'decidee_par' => $userId,
        ]);

        return $destination;
    }

    /**
     * Le guichet refuse la photo, ou renonce.
     *
     * Le fichier provisoire part tout de suite. Une photo d'élève dont personne
     * ne veut n'a aucune raison de rester sur le serveur en attendant un ménage.
     */
    public function ecarter(ESBTPCapturePhoto $capture, ?int $userId, string $etat = ESBTPCapturePhoto::REFUSEE): void
    {
        if ($capture->fichier_provisoire) {
            Storage::disk(self::DISQUE)->delete($capture->fichier_provisoire);
        }

        $capture->update([
            'etat' => $etat,
            'fichier_provisoire' => null,
            'decidee_at' => now(),
            'decidee_par' => $userId,
        ]);
    }

    /** L'école propose-t-elle la prise de vue par téléphone ? */
    public function actif(): bool
    {
        return $this->booleen(SettingsHelper::get(self::REGLAGE_ACTIF, true), true);
    }

    /** Faut-il confirmer avant de remplacer une photo existante ? */
    public function confirmerLeRemplacement(): bool
    {
        return $this->booleen(SettingsHelper::get(self::REGLAGE_CONFIRMER, true), true);
    }

    public function dureeMinutes(): int
    {
        $valeur = (int) SettingsHelper::get(self::REGLAGE_DUREE, self::DUREE_REPLI);

        if ($valeur < self::DUREE_MIN || $valeur > self::DUREE_MAX) {
            return self::DUREE_REPLI;
        }

        return $valeur;
    }

    /**
     * Les réglages sont stockés en texte : « 0 », « false » et « non » valent
     * non. Toute autre valeur renseignée vaut oui, l'absence vaut le défaut.
     */
    private function booleen(mixed $valeur, bool $defaut): bool
    {
        if ($valeur === null || $valeur === '') {
            return $defaut;
        }

        if (is_bool($valeur)) {
            return $valeur;
        }

        if (is_int($valeur)) {
            return $valeur !== 0;
        }

        return ! in_array(mb_strtolower(trim((string) $valeur)), ['0', 'false', 'non', 'no', 'off'], true);
    }
}

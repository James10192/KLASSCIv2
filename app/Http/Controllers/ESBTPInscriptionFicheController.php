<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPInscription;
use App\Services\Documents\CodeQrDocument;
use App\Services\CataloguePiecesDossier;
use App\Services\DossierPiecesEtudiant;
use App\Services\Photos\StockagePhoto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ESBTPInscriptionFicheController extends Controller
{
    use Concerns\RespondsWithInlinePdf;

    /**
     * Au-dela, la photo n'est plus embarquee.
     *
     * Le document part en base64 dans le HTML que DomPDF avale, et il en porte
     * DEUX exemplaires : une photo d'appareil non redimensionnee gonflerait la
     * fiche de plusieurs megaoctets et ferait expirer le rendu. La fiche sans
     * photo reste imprimable, avec son cadre a coller ; une fiche qui ne sort
     * pas ne l'est pas.
     */
    private const PHOTO_OCTETS_MAX = 1_500_000;

    public function preview(
        Request $request,
        ESBTPInscription $inscription,
        StockagePhoto $photos,
        CodeQrDocument $codesQr
    ) {
        $inscription->load([
            'etudiant.parents',
            'classe.filiere',
            'classe.niveau',
            'classe.parcours.mention.domaine',
            'filiere',
            'niveau',
            'anneeUniversitaire',
        ]);
        $school = SettingsHelper::getSchoolInfo();
        $photo = $this->photoEmbarquee($inscription, $photos);

        // Les pieces du dossier, quand l'ecole en reclame. Elles figurent sur la
        // fiche pour que la famille voie d'un coup d'oeil ce qu'il reste a
        // apporter — c'est justement le papier qu'elle emporte.
        //
        // Les MOTIFS n'y sont pas : celui d'un refus et celui d'une piece
        // ecartee sont ecrits « pour la personne qui lira ce dossier apres
        // vous ». Ce sont des notes de service, pas des messages a la famille.
        $dossiers = app(DossierPiecesEtudiant::class);
        $pieces = $dossiers->estConfigure()
            ? $dossiers->pourInscription($inscription)
            : collect();

        // Le code QR ramene le papier au dossier : la fiche part au guichet,
        // revient signee, et il faut alors retrouver l'eleve. Il ouvre sa fiche
        // directement. Il n'expose rien — l'adresse mene a l'application, qui
        // demande de s'identifier ; qui scanne sans compte voit un ecran de
        // connexion.
        $qr = $inscription->etudiant
            ? $codesQr->pour(route('esbtp.etudiants.show', $inscription->etudiant))
            : null;

        $pdf = Pdf::loadView('esbtp.inscriptions.pdf.fiche-double', compact('inscription', 'school', 'photo', 'qr', 'pieces'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);
        $filename = 'fiche-inscription-'.Str::slug($inscription->etudiant?->nom_complet ?? 'inscription').'.pdf';

        return $this->respondWithPdf($pdf, $filename, $request);
    }

    /**
     * La fiche a remplir a la main, en salle d'attente.
     *
     * Ce n'est pas la fiche d'inscription sans ses valeurs : c'est un autre
     * document, pour un autre geste. L'une se relit et se signe ; l'autre
     * s'ecrit au stylo puis se ressaisit au clavier. D'ou un gabarit distinct,
     * dont l'ordre des champs suit celui de l'ecran de saisie pour que la
     * secretaire recopie sans chercher.
     *
     * Elle ne demande NI CLASSE, NI NIVEAU, NI FILIERE. Les demander reviendrait
     * a retenir l'eleve pour lui demander ou il veut aller, alors que le papier
     * est fait pour etre rempli sans personne en face ; l'ecole l'affectera
     * ensuite. Et pas de matricule, de photo, de code QR ni de signature de
     * l'administration : ils n'existent qu'APRES la saisie, et les pre-imprimer
     * vides inviterait quelqu'un a inventer un matricule.
     *
     * Le papier ne remplace pas le portail de candidature en ligne : il le
     * double, pour les familles qui n'y ont pas acces.
     */
    public function vierge(Request $request, CataloguePiecesDossier $catalogue)
    {
        $school = SettingsHelper::getSchoolInfo();

        // Les pieces demandees a TOUT LE MONDE. Sans classe ni filiere, on ne
        // peut pas resoudre une portee : afficher les pieces d'une filiere
        // particuliere sur un formulaire qu'on distribue a l'aveugle ferait
        // reclamer des documents a des eleves que cela ne concerne pas.
        $pieces = $catalogue->estConfigure()
            ? $catalogue->pourScope(null, null)
            : collect();

        $annee = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->value('name');

        $pdf = Pdf::loadView('esbtp.inscriptions.pdf.fiche-vierge', compact('school', 'pieces', 'annee'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);

        return $this->respondWithPdf($pdf, 'fiche-de-renseignements-a-remplir.pdf', $request);
    }

    /**
     * La photo de l'etudiant en donnee embarquee, ou null.
     *
     * `isRemoteEnabled` est a false, et c'est voulu : DomPDF n'ira chercher
     * aucune URL. La photo doit donc voyager DANS le document, ou pas du tout.
     */
    private function photoEmbarquee(ESBTPInscription $inscription, StockagePhoto $photos): ?string
    {
        $chemin = $photos->chemin($inscription->etudiant?->photo);

        if ($chemin === null) {
            return null;
        }

        $absolu = Storage::disk('public')->path($chemin);
        $taille = @filesize($absolu);

        if ($taille === false || $taille > self::PHOTO_OCTETS_MAX) {
            return null;
        }

        $binaire = @file_get_contents($absolu);

        if ($binaire === false) {
            return null;
        }

        // Le type vient du fichier lui-meme, pas de son extension : une photo
        // renommee a la main donnerait un type faux, et l'image ne s'afficherait
        // pas sans que rien ne le dise.
        $type = @mime_content_type($absolu);

        if (! is_string($type) || ! str_starts_with($type, 'image/')) {
            return null;
        }

        return 'data:'.$type.';base64,'.base64_encode($binaire);
    }
}

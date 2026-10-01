<?php

namespace App\Domain\Assistant\Actions\Reglages;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Assistant\Pieces\PiecesJointes;
use App\Domain\Reglages\ImageDeReglage;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

/**
 * Propose de remplacer le logo, le favicon, le filigrane ou la signature par
 * une image que la personne a jointe à la conversation.
 *
 * Le serveur relit la pièce lui-même (PiecesJointes : seule la personne qui
 * l'a déposée la lit), la revalide comme l'écran (JPEG, PNG, GIF ou WebP, 2 Mo
 * au plus, pas de SVG), et l'écrit par ImageDeReglage, le chemin du CLI.
 */
class PoserImageReglage extends ActionAgent
{
    public function __construct(private ImageDeReglage $images, private PiecesJointes $pieces)
    {
    }

    public function cle(): string
    {
        return 'image_reglage';
    }

    public function description(): string
    {
        return "PROPOSE de remplacer une image de l'établissement (".implode(', ', array_keys(ImageDeReglage::DOSSIERS)).') par une image JOINTE à la conversation (piece_id). '
            .'La personne dit laquelle : ne choisis jamais entre logo et signature à sa place. Rien n\'est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cle' => ['type' => 'string', 'enum' => array_keys(ImageDeReglage::DOSSIERS), 'description' => 'Le réglage d\'image à remplacer.'],
                'piece_id' => ['type' => 'string', 'description' => 'Identifiant de l\'image jointe (piece_id).'],
            ],
            'required' => ['cle', 'piece_id'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Remplacer une image de l\'établissement';
        $cle = trim((string) ($args['cle'] ?? ''));
        $manques = [];
        if ($cle === '') {
            $manques[] = 'Quelle image remplacer : '.implode(', ', array_keys(ImageDeReglage::DOSSIERS)).' ?';
        } elseif (($refus = $this->images->refusCle($cle)) !== null) {
            $manques[] = $refus;
        }

        $piece = $this->pieces->pour((int) $user->id, (string) ($args['piece_id'] ?? ''));
        if (($piece['type'] ?? null) !== 'image' || empty($piece['base64'])) {
            $manques[] = 'Joignez l\'image à la conversation (JPEG, PNG ou WebP) : elle est introuvable ou a expiré.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $octets = (string) base64_decode((string) $piece['base64'], true);
        $examen = $this->images->examinerOctets($octets);
        if ($examen['refus'] !== null) {
            return new Proposition(titre: $titre, resume: '', manques: [$examen['refus']]);
        }

        $actuel = Setting::where('key', $cle)->value('value');

        return new Proposition(
            titre: $titre,
            resume: sprintf('« %s » sera remplacé par « %s » (%d × %d px).', $cle, $piece['nom'], $examen['largeur'], $examen['hauteur']),
            tableau: [
                'colonnes' => ['Réglage', 'Image actuelle', 'Nouvelle image', 'Dimensions', 'Taille'],
                'lignes' => [[$cle, $actuel ? basename((string) $actuel) : '(aucune)', (string) $piece['nom'],
                    $examen['largeur'].' × '.$examen['hauteur'].' px', number_format(strlen($octets) / 1024, 0, ',', ' ').' Ko']],
            ],
            avertissements: ['L\'image actuelle est supprimée une fois la nouvelle en place : elle apparaîtra sur tous les documents générés ensuite.'],
            donnees: ['cle' => $cle, 'piece_id' => (string) $args['piece_id'], 'empreinte_image' => hash('sha256', $octets), 'extension' => $examen['extension']],
            etat: ['valeur' => $actuel],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('system.manage')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de modifier les réglages.");
        }
        $d = $proposition->donnees;
        $piece = $this->pieces->pour((int) $user->id, $d['piece_id']);
        $octets = (string) base64_decode((string) ($piece['base64'] ?? ''), true);
        if ($octets === '' || hash('sha256', $octets) !== $d['empreinte_image']) {
            throw new PropositionPerimee('L\'image jointe a expiré ou a changé.');
        }
        if (Setting::where('key', $d['cle'])->value('value') !== $proposition->etat['valeur']) {
            throw new PropositionPerimee('Cette image a été remplacée entre-temps.');
        }

        $pose = $this->images->poser($d['cle'], $octets, $d['extension'], (int) $user->id);

        return [
            'message' => "Image « {$d['cle']} » remplacée.",
            'lien' => Route::has('esbtp.settings.index') ? route('esbtp.settings.index', [], false) : null,
            'model_type' => Setting::class,
            'model_id' => Setting::where('key', $d['cle'])->value('id'),
            'details' => $pose,
        ];
    }
}

<?php

namespace App\Domain\Assistant\Actions\Reglages;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Reglages\ModificationDeReglages;
use App\Domain\Reglages\ReglageModifieEntreTemps;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

/**
 * Propose de changer des réglages de l'établissement (nom, adresse, couleurs et
 * mise en page des PDF, textes du bulletin, seuils LMD, fenêtre d'inscription,
 * indicatif téléphonique…), comme l'écran des paramètres.
 *
 * Toute la règle vit dans ModificationDeReglages : liste fermée des clés que
 * Nanan peut toucher, type et règles de validation du réglage, contrôles
 * croisés de l'écran (couleurs, dates, année visée, pays et préfixes, parcours
 * d'inscription), écriture avec sauvegarde. Les secrets, l'envoi des e-mails,
 * les barèmes JSON, les droits des rôles et les images restent hors de portée.
 */
class ModifierReglages extends ActionAgent
{
    public function __construct(private ModificationDeReglages $reglages)
    {
    }

    public function cle(): string
    {
        return 'modification_reglages';
    }

    public function description(): string
    {
        return "PROPOSE de changer un ou plusieurs réglages de l'établissement (au plus ".ModificationDeReglages::MAX."), par leur clé EXACTE lue avec lire_reglages. "
            .'La valeur vient de la personne ou d\'un document qu\'elle a joint : ne la devine jamais. '
            .'Indicatif pays et préfixes mobiles se changent ENSEMBLE. Les secrets, l\'envoi des e-mails, les barèmes, les droits des rôles sont refusés : renvoie vers l\'écran. Rien n\'est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reglages' => [
                    'type' => 'array',
                    'description' => 'Les changements : clé exacte et nouvelle valeur (texte ; « 1 »/« 0 » pour oui/non, « AAAA-MM-JJ » pour une date).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'cle' => ['type' => 'string'],
                            'valeur' => ['type' => 'string'],
                        ],
                        'required' => ['cle', 'valeur'],
                    ],
                ],
            ],
            'required' => ['reglages'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Modifier des réglages';
        $changements = [];
        foreach ((array) ($args['reglages'] ?? []) as $ligne) {
            $cle = trim((string) ($ligne['cle'] ?? ''));
            if ($cle === '' || ! array_key_exists('valeur', (array) $ligne)) {
                return new Proposition(titre: $titre, resume: '', manques: ['Chaque réglage demande sa clé exacte et sa nouvelle valeur.']);
            }
            if (array_key_exists($cle, $changements)) {
                return new Proposition(titre: $titre, resume: '', manques: ["« {$cle} » apparaît deux fois : quelle valeur garder ?"]);
            }
            $changements[$cle] = $ligne['valeur'];
        }

        $examen = $this->reglages->examiner($changements);
        if ($examen['refus'] !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_values(array_unique($examen['refus'])));
        }

        $avertissements = [];
        $cles = array_keys($examen['ecritures']);
        if (array_filter($cles, fn ($c) => str_starts_with($c, 'lmd_') && ! str_starts_with($c, 'lmd_bulletin_')) !== []) {
            $avertissements[] = 'Les seuils et pondérations LMD changent le calcul des résultats et des décisions à la prochaine génération.';
        }
        if (array_filter($cles, fn ($c) => str_starts_with($c, 'inscriptions.') || str_starts_with($c, 'reinscriptions.')) !== []) {
            $avertissements[] = 'Ces réglages changent ce que voient les familles sur le portail d\'inscription.';
        }
        if ($examen['inchangees'] !== []) {
            $avertissements[] = 'Déjà à la valeur demandée, rien à faire : '.implode(', ', $examen['inchangees']).'.';
        }

        $affiche = fn (string $v) => $v === '' ? '(vide)' : mb_strimwidth($v, 0, 80, '…');

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d réglage(s) modifié(s).', count($examen['ecritures'])),
            tableau: [
                'colonnes' => ['Réglage', 'Clé', 'Valeur actuelle', 'Nouvelle valeur'],
                'lignes' => array_map(fn ($l) => [$l['libelle'], $l['cle'], $affiche($l['avant']), $affiche($l['apres'])], $examen['lignes']),
            ],
            avertissements: $avertissements,
            donnees: ['ecritures' => $examen['ecritures']],
            etat: $examen['etat'],
            risque: $avertissements !== [] ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('system.manage')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de modifier les réglages.");
        }

        $ecritures = $proposition->donnees['ecritures'];
        try {
            $this->reglages->appliquer($ecritures, $proposition->etat, (int) $user->id, 'nanan');
        } catch (ReglageModifieEntreTemps $e) {
            throw new PropositionPerimee($e->getMessage());
        }

        return [
            'message' => count($ecritures).' réglage(s) enregistré(s) : '.implode(', ', array_keys($ecritures)).'.',
            'lien' => Route::has('esbtp.settings.index') ? route('esbtp.settings.index', [], false) : null,
            'model_type' => Setting::class,
            'model_id' => Setting::where('key', array_key_first($ecritures))->value('id'),
            'details' => ['ecritures' => $ecritures, 'avant' => $proposition->etat],
        ];
    }
}

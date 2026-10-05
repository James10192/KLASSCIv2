<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Exceptions\ReglagesRdvIncomplets;
use App\Models\ESBTPRdvReservation;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\RendezVous\AccueilRdv;
use App\Services\RendezVous\ReprogrammateurRdvAdministratif;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * Fermeture administrative d'un jour futur + déplacement atomique de toutes les
 * familles encore attendues vers les prochains créneaux conformes aux réglages.
 */
class ReprogrammerRendezVous extends ActionAgent
{
    public function __construct(private readonly ReprogrammateurRdvAdministratif $reprogrammateur)
    {
    }

    public function cle(): string
    {
        return 'reprogrammation_rdv';
    }

    public function description(): string
    {
        return 'PROPOSE de reprogrammer EN MASSE tous les rendez-vous actifs d’une date future qui ne doit plus recevoir de familles. '
            .'La simulation ferme les créneaux source de ce jour, répartit les familles sur les prochains créneaux ouverts conformes aux jours de réception actuels, puis replannifie leur convocation « déplacée ». '
            .'Utilise cette action quand l’utilisateur dit par exemple « j’ai fermé vendredi 9 octobre, reprogramme les familles » ou « ferme les rendez-vous de vendredi et déplace tout le monde ». '
            .'Ne prétends jamais qu’un déplacement manuel est nécessaire si cette action est disponible. Rien n’est modifié avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date_source' => [
                    'type' => 'string',
                    'description' => 'Date ISO AAAA-MM-JJ du jour de rendez-vous à supprimer du planning.',
                ],
            ],
            'required' => ['date_source'],
        ];
    }

    /** Cette action reste explicitement bornée au même droit que l’écran de gestion des rendez-vous. */
    public function isAvailableFor($user): bool
    {
        return (bool) config('assistant.actions.actives', true)
            && $user !== null
            && $user->can('inscriptions.rdv.manage');
    }

    public function libelle(): string
    {
        return 'Préparation de la reprogrammation des rendez-vous…';
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Reprogrammer les rendez-vous d’un jour fermé';
        $brut = trim((string) ($args['date_source'] ?? ''));
        $jour = PortailReinscriptionService::interpreterDateIso($brut);
        if ($jour === null) {
            return new Proposition(titre: $titre, resume: '', manques: ['Quelle date faut-il reprogrammer ? Donnez-la au format AAAA-MM-JJ.']);
        }
        $jour = $jour->startOfDay();
        if (! $jour->isFuture()) {
            return new Proposition(titre: $titre, resume: '', manques: [
                'La reprogrammation administrative en masse vise un jour futur. Pour une date passée, utilisez le traitement des non-venues de l’accueil.',
            ]);
        }

        try {
            $simulation = $this->reprogrammateur->simuler($jour);
        } catch (ReglagesRdvIncomplets $e) {
            return new Proposition(titre: $titre, resume: '', manques: [
                $e->getMessage().' Complétez d’abord les réglages des rendez-vous afin que Nanan sache quels jours et créneaux sont encore autorisés.',
            ]);
        }

        if ($simulation['reservations'] === 0) {
            return Proposition::sansObjet($titre, 'aucun rendez-vous actif n’est encore attaché à cette date.');
        }
        if ($simulation['sans_place'] > 0) {
            return new Proposition(titre: $titre, resume: '', manques: [sprintf(
                '%d famille(s) sur %d n’ont pas de place disponible après le %s. Générez ou ouvrez d’abord suffisamment de créneaux : aucun déplacement partiel ne sera proposé.',
                $simulation['sans_place'],
                $simulation['reservations'],
                $jour->format('d/m/Y')
            )]);
        }

        $jourLisible = ucfirst($jour->translatedFormat('l j F Y'));
        $tableau = [
            'colonnes' => ['Nouveau jour', 'Créneau', 'Familles déplacées'],
            'lignes' => array_map(fn (array $cible) => [
                ucfirst(Carbon::parse($cible['date'])->translatedFormat('l j F Y')),
                $cible['heure'],
                (string) $cible['affectees'],
            ], $simulation['cibles']),
        ];

        $avertissements = [
            'Chaque déplacement est journalisé avec l’ancien et le nouveau créneau. La convocation est replannifiée avec l’action « déplacé ».',
            'La messagerie actuelle choisit l’e-mail en priorité et utilise WhatsApp lorsqu’il n’y a pas d’e-mail valide ou en fallback selon le résultat MailPulse.',
        ];
        if ($simulation['creneaux_source_ouverts'] > 0) {
            $avertissements[] = sprintf(
                '%d créneau(x) du jour source sont encore ouverts : ils seront fermés dans la même transaction avant le déplacement du lot.',
                $simulation['creneaux_source_ouverts']
            );
        }

        return new Proposition(
            titre: $titre,
            resume: sprintf(
                '%d rendez-vous du %s seront déplacés vers %d nouveau(x) créneau(x) ; %d créneau(x) source seront fermés et %d nouvelle(s) convocation(s) seront replannifiées.',
                $simulation['reservations'],
                $jourLisible,
                count($simulation['cibles']),
                count($simulation['creneaux_source']),
                $simulation['reservations']
            ),
            tableau: $tableau,
            avertissements: $avertissements,
            donnees: [
                'date_source' => $simulation['date'],
                'assignations' => $simulation['assignations'],
                'creneaux_source' => $simulation['creneaux_source'],
            ],
            etat: [
                'reservations' => $simulation['reservations'],
                'cibles' => $simulation['cibles'],
                'creneaux_source_ouverts' => $simulation['creneaux_source_ouverts'],
            ],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.rdv.manage')) {
            throw new PropositionPerimee("Vous n’avez plus le droit de gérer les rendez-vous.");
        }

        try {
            $r = $this->reprogrammateur->executer(
                (array) ($proposition->donnees['assignations'] ?? []),
                (array) ($proposition->donnees['creneaux_source'] ?? []),
                (int) $user->id,
            );
        } catch (RuntimeException $e) {
            throw new PropositionPerimee(AccueilRdv::message($e->getMessage()));
        }

        return [
            'message' => sprintf(
                '%d rendez-vous reprogrammé(s), %d créneau(x) source fermé(s). %d nouvelle(s) convocation(s) sont en file d’envoi.',
                $r['faites'], $r['creneaux_fermes'], $r['convocations']
            ),
            'lien' => Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null,
            'model_type' => ESBTPRdvReservation::class,
            'details' => $r + ['date_source' => $proposition->donnees['date_source'] ?? null],
        ];
    }
}

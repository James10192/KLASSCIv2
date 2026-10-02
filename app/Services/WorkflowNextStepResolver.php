<?php

namespace App\Services;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Notifications\WorkflowNextStepNotification;
use Illuminate\Support\Collection;

/**
 * Résout l'étape suivante d'un workflow inscription.
 *
 * Mapping event → permission requise pour l'étape d'après. Anti-self-notif :
 * si l'acteur courant a déjà la permission, le système le rend visible côté UI
 * (modal "tu peux maintenant ...") au lieu d'envoyer une notif.
 */
class WorkflowNextStepResolver
{
    /**
     * Étapes suivantes par type d'event de workflow.
     *
     * Forme : [
     *     <event_type> => [
     *         'permission' => string|null,  // permission requise pour l'étape suivante
     *         'label'      => string|null,  // label humain
     *         'route'      => string|null,  // nom de route Laravel
     *         'param_key'  => string|null,  // clé recherchée dans le context (avec fallback sans `_id`)
     *     ],
     * ]
     */
    private const NEXT_STEPS = [
        'inscription.created' => [
            'permission' => 'paiements.create',
            'label'      => 'Encaisser un paiement pour cette inscription',
            'route'      => 'esbtp.paiements.create',
            'param_key'  => 'inscription_id',
        ],
        'paiement.created' => [
            'permission' => 'paiements.validate',
            'label'      => 'Valider ce paiement',
            'route'      => 'esbtp.paiements.show',
            'param_key'  => 'paiement',
        ],
        'paiement.validated' => [
            'permission' => 'inscriptions.validate',
            'label'      => 'Valider l\'inscription',
            'route'      => 'esbtp.inscriptions.show',
            'param_key'  => 'inscription',
        ],
        'inscription.validated' => [
            'permission' => null,
            'label'      => null,
            'route'      => null,
            'param_key'  => null,
        ],
    ];

    /**
     * Permission requise pour l'étape suivante du workflow donné.
     */
    public function nextPermission(string $eventType): ?string
    {
        return self::NEXT_STEPS[$eventType]['permission'] ?? null;
    }

    /**
     * Label humain de l'étape suivante.
     */
    public function nextLabel(string $eventType): ?string
    {
        return self::NEXT_STEPS[$eventType]['label'] ?? null;
    }

    /**
     * URL absolue de l'action suivante (déjà préfixée avec le contexte).
     */
    public function nextActionUrl(string $eventType, array $context): ?string
    {
        $step = self::NEXT_STEPS[$eventType] ?? null;
        if (!$step || !$step['route'] || !$step['param_key']) {
            return null;
        }

        $key = $step['param_key'];
        $fallbackKey = str_replace('_id', '', $key);
        $value = $context[$key] ?? $context[$fallbackKey] ?? null;
        if ($value === null) {
            return null;
        }

        return route($step['route'], [$key => $value]);
    }

    /**
     * Liste des destinataires de la notif (users avec la permission, hors actor).
     *
     * @return Collection<int, User>
     */
    public function recipients(string $eventType, ?int $actorId): Collection
    {
        $perm = $this->nextPermission($eventType);
        if (!$perm) {
            return collect();
        }

        return User::permission($perm)
            ->where('is_active', true)
            ->when($actorId, fn ($q) => $q->where('id', '!=', $actorId))
            ->get();
    }

    /**
     * True si l'acteur a lui-même la permission de l'étape suivante.
     * Dans ce cas le FE doit afficher un modal "tu peux maintenant X" plutôt
     * que d'envoyer une notif (anti-self-notif).
     */
    public function actorCanDoNextStep(string $eventType, User $actor): bool
    {
        $perm = $this->nextPermission($eventType);
        return $perm !== null && $actor->can($perm);
    }

    /** L'identifiant de l'objet que vise l'etape suivante, lu dans le contexte. */
    public function objetVise(string $eventType, array $context): ?int
    {
        $key = self::NEXT_STEPS[$eventType]['param_key'] ?? null;
        if (! $key) {
            return null;
        }
        $value = $context[$key] ?? $context[str_replace('_id', '', $key)] ?? null;

        return $value === null ? null : (int) $value;
    }

    /**
     * Clot les avis « etape suivante » de cette personne dont l'etape est deja
     * faite, et rend le nombre d'avis encore a faire.
     *
     * Lu dans l'ETAT des objets, pas dans les evenements : valider en masse,
     * valider rapidement, rejeter, valider definitivement… ne declenchent pas
     * tous un evenement, et l'avis, envoye par la file d'attente, peut arriver
     * apres que l'etape a ete faite. Sans cela, /messages et le badge du menu
     * montraient « Valider ce paiement » pour un paiement deja valide.
     */
    public function clotureLesEtapesFaites(User $personne): int
    {
        $avis = $personne->unreadNotifications()
            ->where('type', WorkflowNextStepNotification::class)
            ->latest()
            ->limit(200)
            ->get();
        if ($avis->isEmpty()) {
            return 0;
        }

        $etat = $this->etatDes($avis);
        $faits = $avis->filter(fn ($a) => $this->etapeFaite((array) $a->data, $etat));
        if ($faits->isNotEmpty()) {
            $personne->unreadNotifications()->whereIn('id', $faits->pluck('id'))->update(['read_at' => now()]);
        }

        return $avis->count() - $faits->count();
    }

    /**
     * L'etat des objets vises par ces avis, en trois requetes au plus.
     *
     * @return array{paiements: array<int,string>, inscriptions: array<int,object>, payees: array<int,bool>}
     */
    private function etatDes(Collection $avis): array
    {
        $ids = ['paiement.created' => [], 'inscription.created' => [], 'paiement.validated' => []];
        foreach ($avis as $a) {
            $type = (string) ($a->data['type'] ?? '');
            $id = $this->objetVise($type, (array) ($a->data['context'] ?? []));
            if ($id !== null && isset($ids[$type])) {
                $ids[$type][] = $id;
            }
        }
        $inscriptions = array_unique(array_merge($ids['inscription.created'], $ids['paiement.validated']));

        return [
            'paiements' => $ids['paiement.created']
                ? ESBTPPaiement::whereIn('id', $ids['paiement.created'])->pluck('status', 'id')->all() : [],
            'inscriptions' => $inscriptions
                ? ESBTPInscription::whereIn('id', $inscriptions)->get(['id', 'status', 'workflow_step'])->keyBy('id')->all() : [],
            'payees' => $ids['inscription.created']
                // Un paiement rejete n'a rien encaisse : l'etape reste a faire.
                ? array_fill_keys(ESBTPPaiement::whereIn('inscription_id', $ids['inscription.created'])
                    ->where('status', '!=', 'rejeté')->distinct()->pluck('inscription_id')->all(), true) : [],
        ];
    }

    /** L'etape que demande cet avis est-elle deja faite ? Un objet disparu compte pour fait. */
    private function etapeFaite(array $data, array $etat): bool
    {
        $type = (string) ($data['type'] ?? '');
        $id = $this->objetVise($type, (array) ($data['context'] ?? []));
        if ($id === null) {
            return false;
        }

        return match ($type) {
            // « Valider ce paiement » : fait des qu'il n'est plus en attente (valide, rejete) ou n'existe plus.
            'paiement.created' => ($etat['paiements'][$id] ?? null) !== 'en_attente',
            // « Encaisser un paiement » : fait des qu'un paiement existe, ou que l'inscription est validee.
            'inscription.created' => ! isset($etat['inscriptions'][$id])
                || isset($etat['payees'][$id])
                || $etat['inscriptions'][$id]->workflow_step === 'etudiant_cree',
            // « Valider l'inscription » : fait quand elle l'est, ou qu'elle n'existe plus.
            'paiement.validated' => ! isset($etat['inscriptions'][$id])
                || $etat['inscriptions'][$id]->workflow_step === 'etudiant_cree',
            default => false,
        };
    }
}

<?php

namespace App\Services\Notifications;

use App\Models\ESBTPInscription;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Prepare une notification pour la page /notifications : de quoi parle-t-elle,
 * ou mene-t-elle, et dans quel groupe de dates elle se range.
 *
 * Tout ce qui etait calcule dans la vue (lecture du message, pastilles, bouton
 * selon le role) vit ici, une seule fois, pour la page complete comme pour les
 * lignes chargees ensuite en AJAX.
 */
class NotificationPresenter
{
    public const GROUPES = [
        'aujourdhui' => "Aujourd'hui",
        'hier' => 'Hier',
        'semaine' => 'Cette semaine',
        'ancien' => 'Plus ancien',
    ];

    private const ETIQUETTES = 'Statut:|Étape:|Paiement:|Référence:|Numéro de reçu:';

    /**
     * @param  Collection<int, Notification>  $notifications
     * @return Collection<int, Notification>
     */
    public function decorateAll(Collection $notifications, User $user): Collection
    {
        // Une requete pour toutes les inscriptions citees, au lieu d'une par ligne.
        $inscriptionIds = $notifications
            ->map(fn (Notification $n) => $this->inscriptionId($n->link))
            ->filter()
            ->unique()
            ->values();

        $inscriptions = $inscriptionIds->isEmpty()
            ? collect()
            : ESBTPInscription::with(['etudiant', 'classe.filiere', 'paiements'])
                ->whereIn('id', $inscriptionIds)
                ->get()
                ->keyBy('id');

        $context = [
            'coordinate' => $user->can('identity.coordinate'),
            'student' => $user->can('identity.student'),
        ];

        return $notifications->map(
            fn (Notification $n) => $this->decorate($n, $inscriptions, $context)
        );
    }

    public static function groupKey(?Carbon $date): string
    {
        if (! $date) {
            return 'ancien';
        }
        if ($date->isToday()) {
            return 'aujourdhui';
        }
        if ($date->isYesterday()) {
            return 'hier';
        }
        if ($date->greaterThanOrEqualTo(now()->startOfWeek())) {
            return 'semaine';
        }

        return 'ancien';
    }

    /**
     * Type ramene a quatre familles, chacune avec son icone et son libelle.
     *
     * @return array{key: string, label: string, icon: string}
     */
    public static function typeOf(?string $type): array
    {
        return match (Str::lower((string) $type)) {
            'success' => ['key' => 'success', 'label' => 'Succès', 'icon' => 'fa-circle-check'],
            'warning' => ['key' => 'warning', 'label' => 'À surveiller', 'icon' => 'fa-triangle-exclamation'],
            'error', 'danger' => ['key' => 'alerte', 'label' => 'Alertes', 'icon' => 'fa-circle-exclamation'],
            default => ['key' => 'info', 'label' => 'Informations', 'icon' => 'fa-circle-info'],
        };
    }

    private function decorate(Notification $notification, Collection $inscriptions, array $context): Notification
    {
        $type = self::typeOf($notification->type);
        $notification->display_type = $type;
        $notification->display_group = self::groupKey($notification->created_at);
        $notification->display_url = $this->safeUrl($notification->link);

        [$primary, $labels] = $this->readMessage($notification, $inscriptions);
        $notification->display_primary = $primary;
        $notification->display_labels = $labels;

        $notification->display_action = $this->action($notification, $context);

        return $notification;
    }

    /**
     * @return array{0: string, 1: array<int, array{key: string, value: string, icon: string, tone: string}>}
     */
    private function readMessage(Notification $notification, Collection $inscriptions): array
    {
        $inscription = $inscriptions->get($this->inscriptionId($notification->link));

        if ($inscription) {
            $nom = trim(($inscription->etudiant->nom ?? '').' '.($inscription->etudiant->prenoms ?? ''));
            $filiere = $inscription->classe?->filiere?->name;
            $primary = "L'étudiant {$nom} s'est inscrit".($filiere ? " en {$filiere}" : '').'.';

            $raw = [];
            if ($inscription->classe?->name) {
                $raw[] = ['Classe', $inscription->classe->name];
            }
            $raw[] = ['Statut', $inscription->status ?? 'Non défini'];
            $raw[] = ['Étape', $inscription->workflow_step_label ?? $inscription->workflow_step ?? 'Non définie'];
            $dernier = $inscription->paiements?->sortByDesc('created_at')->first();
            $raw[] = ['Paiement', $dernier && $dernier->status
                ? Str::ucfirst(str_replace('_', ' ', $dernier->status))
                : 'Non renseigné'];

            return [$primary, array_map(fn ($l) => $this->pill($l[0], $l[1]), $raw)];
        }

        // Les messages ne portent pas de HTML a afficher : le texte seul est lu.
        $texte = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $notification->message)));
        $primary = trim(preg_split('/('.self::ETIQUETTES.'|Cliquez)/iu', $texte)[0] ?? '');

        $labels = [];
        if (preg_match_all(
            '/('.self::ETIQUETTES.')\s*((?:(?!'.self::ETIQUETTES.'|Cliquez)[^|\n])*)/iu',
            $texte,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $labels[] = $this->pill(rtrim($match[1], ':'), trim($match[2], " \t.;-"));
            }
        }

        return [$primary !== '' ? $primary : $texte, $labels];
    }

    /**
     * @return array{key: string, value: string, icon: string, tone: string}
     */
    private function pill(string $key, string $value): array
    {
        $k = Str::lower(Str::ascii($key));
        $v = Str::lower(Str::ascii($value));
        $icon = 'fa-tag';
        $tone = 'neutral';

        if ($k === 'classe') {
            $icon = 'fa-school';
        } elseif ($k === 'statut' || $k === 'paiement' || $k === 'etape') {
            $icon = ['statut' => 'fa-circle-info', 'paiement' => 'fa-money-bill-wave', 'etape' => 'fa-list-check'][$k];
            if (Str::contains($v, ['rejet', 'refus', 'annul'])) {
                $tone = 'danger';
            } elseif (Str::contains($v, ['attente', 'pending', 'validation'])) {
                $tone = 'warning';
            } elseif (Str::contains($v, ['valid', 'active', 'paye', 'regle'])) {
                $tone = 'success';
            }
        } elseif ($k === 'reference') {
            $icon = 'fa-hashtag';
        } elseif (Str::contains($k, 'recu')) {
            $icon = 'fa-receipt';
        }

        return ['key' => $key, 'value' => $value, 'icon' => $icon, 'tone' => $tone];
    }

    /**
     * Le bouton principal : ouvrir ce dont parle la notification. Sans lien, les
     * anciens raccourcis par role restent proposes (emargements, presences...).
     *
     * @return array{label: string, icon: string, url: string}|null
     */
    private function action(Notification $notification, array $context): ?array
    {
        $titre = Str::lower((string) $notification->title);
        $url = $notification->display_url;

        $regles = [];
        if ($context['coordinate']) {
            $regles = [
                ['émargement', 'Voir les émargements', 'fa-signature', 'esbtp.teacher-attendance.report'],
                ['appel', 'Voir les présences', 'fa-users', 'esbtp.attendances.index'],
                ['clôturé', 'Voir les séances', 'fa-check', 'esbtp.attendances.index'],
                ['retard', 'Vérifier les retards', 'fa-clock', 'esbtp.teacher-attendance.report'],
                ['récapitulatif', 'Voir le rapport', 'fa-chart-line', 'esbtp.teacher-attendance.report'],
            ];
        } elseif ($context['student']) {
            $regles = [['absence', "Justifier l'absence", 'fa-file-lines', 'esbtp.mes-absences.index']];
        }

        foreach ($regles as [$mot, $label, $icon, $route]) {
            // Mot entier : « Rappel » ne doit pas se lire comme un « appel ».
            if (preg_match('/(?<![\p{L}])'.preg_quote($mot, '/').'(?![\p{L}])/u', $titre)) {
                $cible = $url ?? (Route::has($route) ? route($route) : null);

                return $cible ? ['label' => $label, 'icon' => $icon, 'url' => $cible] : null;
            }
        }

        if (! $url) {
            return null;
        }

        if ($this->inscriptionId($url)) {
            return ['label' => 'Ouvrir le dossier', 'icon' => 'fa-folder-open', 'url' => $url];
        }

        return ['label' => 'Ouvrir', 'icon' => 'fa-arrow-right', 'url' => $url];
    }

    private function inscriptionId(?string $link): ?int
    {
        return $link && preg_match('/inscriptions\/(\d+)/', $link, $m) ? (int) $m[1] : null;
    }

    /**
     * Un lien de notification ne sort jamais de l'application : chemin relatif,
     * ou URL absolue sur le meme hote. Tout le reste (javascript:, autre domaine)
     * est ignore.
     */
    private function safeUrl(?string $link): ?string
    {
        $link = trim((string) $link);
        if ($link === '') {
            return null;
        }
        if (str_starts_with($link, '/') && ! str_starts_with($link, '//')) {
            return $link;
        }
        $parts = parse_url($link);
        if (! $parts || ! in_array(Str::lower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        return Str::lower($parts['host'] ?? '') === Str::lower(request()->getHost()) ? $link : null;
    }
}

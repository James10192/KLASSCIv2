<?php

namespace App\Services\Usage;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lit la table audits en agregats seulement : jamais une ligne d'audit
 * complete en memoire, jamais old_values / new_values, jamais d'adresse IP.
 *
 * Chaque requete sort l'origine de l'action :
 *  - outil   : URL de nos outils (CLI) ;
 *  - systeme : commande serveur, file d'attente, ou action sans compte ;
 *  - web     : une personne connectee dans l'application.
 */
class AuditActivityQuery
{
    public const ORIGIN_TOOL = 'outil';
    public const ORIGIN_SYSTEM = 'systeme';
    public const ORIGIN_WEB = 'web';

    public function __construct(private readonly UsageWindow $window)
    {
    }

    /** Lignes (origin, user_id, day, event, total). */
    public function perUserDayEvent(): Collection
    {
        return $this->base()
            ->selectRaw($this->originSql() . ' as origin', $this->originBindings())
            ->selectRaw('user_id, DATE(created_at) as day, event, COUNT(*) as total')
            ->groupByRaw('origin, user_id, DATE(created_at), event')
            ->get();
    }

    /** Lignes (user_id, hour, dow, total) pour les ecritures faites dans l'application. */
    public function webWritesPerHourAndWeekday(): Collection
    {
        return $this->webWrites()
            ->selectRaw('user_id, HOUR(created_at) as hour, DAYOFWEEK(created_at) as dow, COUNT(*) as total')
            ->groupByRaw('user_id, HOUR(created_at), DAYOFWEEK(created_at)')
            ->get();
    }

    /** Lignes (user_id, auditable_type, day, total) pour les ecritures faites dans l'application. */
    public function webWritesPerModelDay(): Collection
    {
        return $this->webWrites()
            ->selectRaw('user_id, auditable_type, DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('user_id, auditable_type, DATE(created_at)')
            ->get();
    }

    /** Lignes (origin, auditable_type, total) : ecritures de toutes origines. */
    public function writesPerOriginAndModel(): Collection
    {
        return $this->writes()
            ->selectRaw($this->originSql() . ' as origin', $this->originBindings())
            ->selectRaw('auditable_type, COUNT(*) as total')
            ->groupByRaw('origin, auditable_type')
            ->get();
    }

    private function base(): Builder
    {
        return DB::table('audits')->whereBetween('created_at', [$this->window->from, $this->window->to]);
    }

    private function writes(): Builder
    {
        return $this->base()->whereIn('event', config('usage_report.write_events'));
    }

    private function webWrites(): Builder
    {
        $query = $this->writes()->whereNotNull('user_id')->where('url', 'like', 'http%');
        foreach ($this->internalFragments() as $fragment) {
            $query->where('url', 'not like', '%' . $fragment . '%');
        }

        return $query;
    }

    private function originSql(): string
    {
        $tool = implode(' OR ', array_fill(0, count($this->internalFragments()), 'url LIKE ?')) ?: '1 = 0';

        return "CASE WHEN ({$tool}) THEN '" . self::ORIGIN_TOOL . "'"
            . " WHEN user_id IS NULL OR url IS NULL OR url NOT LIKE 'http%' THEN '" . self::ORIGIN_SYSTEM . "'"
            . " ELSE '" . self::ORIGIN_WEB . "' END";
    }

    private function originBindings(): array
    {
        return array_map(fn ($f) => '%' . $f . '%', $this->internalFragments());
    }

    private function internalFragments(): array
    {
        return config('usage_report.internal_url_fragments', []);
    }
}

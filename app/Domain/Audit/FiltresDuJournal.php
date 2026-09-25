<?php

namespace App\Domain\Audit;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

/**
 * Ce que l'agent a choisi de voir dans le journal : un onglet, une personne,
 * une periode, une recherche, et s'il veut les taches automatiques.
 *
 * Lu par la liste, par sa suite au defilement et par les exports : la meme
 * vue partout, sans qu'un export ramene autre chose que l'ecran.
 */
final class FiltresDuJournal
{
    /** Periodes proposees, en jours. `tout` : aucune borne. */
    public const PERIODES = ['1' => "Aujourd'hui", '7' => '7 derniers jours', '30' => '30 derniers jours', '90' => '3 derniers mois', 'tout' => 'Depuis le début'];

    private function __construct(
        public readonly string $theme,
        public readonly ?int $personne,
        public readonly string $periode,
        public readonly string $recherche,
        public readonly bool $automatiques,
        public readonly ?string $typeObjet,
        public readonly ?int $idObjet,
        public readonly ?Carbon $du = null,
        public readonly ?Carbon $au = null,
        public readonly bool $aucunOnglet = false,
        public readonly bool $argentVisible = true,
    ) {
    }

    /** @param  list<string>  $themes  les onglets ouverts a l'agent */
    public static function depuis(Request $request, array $themes): self
    {
        $theme = (string) $request->query('theme', '');
        $periode = (string) $request->query('periode', '7');

        return new self(
            theme: in_array($theme, $themes, true) ? $theme : ($themes[0] ?? ThemesDuJournal::TOUT),
            personne: $request->filled('user_id') ? (int) $request->query('user_id') : null,
            periode: array_key_exists($periode, self::PERIODES) ? $periode : '7',
            recherche: mb_substr(trim((string) $request->query('q', '')), 0, 80),
            automatiques: $request->boolean('auto'),
            // L'historique d'un objet precis, depuis sa fiche (x-entity-history).
            typeObjet: $request->filled('model_type') ? (string) $request->query('model_type') : null,
            idObjet: $request->filled('objet_id') ? (int) $request->query('objet_id') : null,
            // Une plage libre, venue de l'activite des personnes : elle prime sur la periode.
            du: self::date($request->query('date_from'))?->startOfDay(),
            au: self::date($request->query('date_to'))?->endOfDay(),
            // Aucun onglet ouvert : aucune ligne, jamais tout le journal par defaut.
            aucunOnglet: $themes === [],
            // Sans l'acces sensible, la recherche ne fouille pas les montants :
            // taper « 150000 » ne doit pas retrouver les paiements de ce montant.
            argentVisible: (bool) $request->user()?->can('comptabilite.sensitive.access'),
        );
    }

    /** Une date Y-m-d saisie, ou null si elle n'en est pas une (jamais d'erreur 500). */
    public static function date(mixed $valeur): ?Carbon
    {
        if (! is_string($valeur) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valeur)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $valeur);
        } catch (\Throwable) {
            return null;
        }
    }

    /** La requete de l'ecran, hors taches automatiques si elles sont masquees. */
    public function requete(): Builder
    {
        return $this->base()->when(! $this->automatiques && $this->idObjet === null, fn (Builder $q) => $q->whereNotNull('user_id'));
    }

    /** La meme vue, sans le choix sur les taches automatiques : pour les compter. */
    public function base(): Builder
    {
        $requete = ThemesDuJournal::appliquer(Audit::query(), $this->theme)
            ->when($this->aucunOnglet, fn (Builder $q) => $q->whereRaw('0 = 1'))
            ->when($this->depuisLe(), fn (Builder $q, Carbon $d) => $q->where('created_at', '>=', $d))
            ->when($this->au, fn (Builder $q, Carbon $d) => $q->where('created_at', '<=', $d))
            ->when($this->personne, fn (Builder $q, int $id) => $q->where('user_id', $id))
            ->when($this->typeObjet, fn (Builder $q, string $t) => $q->where('auditable_type', $t))
            ->when($this->idObjet, fn (Builder $q, int $id) => $q->where('auditable_id', $id));

        return $this->recherche === '' ? $requete : $requete->where(fn (Builder $q) => $this->chercher($q));
    }

    public function depuisLe(): ?Carbon
    {
        if ($this->du) {
            return $this->du;
        }

        return $this->periode === 'tout' ? null : now()->subDays((int) $this->periode - 1)->startOfDay();
    }

    /** « du 01/08/2026 au 15/08/2026 », quand une plage libre remplace la periode. */
    public function plage(): ?string
    {
        if (! $this->du && ! $this->au) {
            return null;
        }

        return trim(($this->du ? 'du '.$this->du->format('d/m/Y') : '').($this->au ? ' au '.$this->au->format('d/m/Y') : ''));
    }

    /** @return array<string, string|int> les filtres, pour les liens et l'export */
    public function enParametres(): array
    {
        return array_filter([
            'theme' => $this->theme,
            'user_id' => $this->personne,
            'periode' => $this->periode === '7' ? null : $this->periode,
            'q' => $this->recherche,
            'auto' => $this->automatiques ? 1 : null,
            'model_type' => $this->typeObjet,
            'objet_id' => $this->idObjet,
            'date_from' => $this->du?->format('Y-m-d'),
            'date_to' => $this->au?->format('Y-m-d'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * « Un étudiant, une classe, un reçu, une personne » : le nom d'un auteur,
     * le nom ou le matricule d'un etudiant (et ce qui le vise par son
     * identifiant), ou un texte present dans les valeurs (reçu, classe).
     */
    private function chercher(Builder $q): void
    {
        $like = '%'.addcslashes($this->recherche, '%_\\').'%';
        $auteurs = User::where('name', 'like', $like)->limit(20)->pluck('id');
        $etudiants = ESBTPEtudiant::query()->where(fn ($e) => $e->where('matricule', 'like', $like)
            ->orWhereRaw("CONCAT(nom, ' ', prenoms) LIKE ?", [$like])
            ->orWhereRaw("CONCAT(prenoms, ' ', nom) LIKE ?", [$like]))
            ->limit(20)->pluck('id');
        $inscriptions = $etudiants->isEmpty() ? collect() : ESBTPInscription::whereIn('etudiant_id', $etudiants)->limit(100)->pluck('id');

        $q->where(fn (Builder $v) => $v->where(fn (Builder $w) => $w->where('old_values', 'like', $like)->orWhere('new_values', 'like', $like))
                ->when(! $this->argentVisible, fn (Builder $w) => $w->whereNotIn('auditable_type', ThemesDuJournal::ARGENT)))
            ->when($auteurs->isNotEmpty(), fn ($w) => $w->orWhereIn('user_id', $auteurs))
            ->when($etudiants->isNotEmpty(), fn ($w) => $w
                ->orWhere(fn ($o) => $o->where('auditable_type', ESBTPEtudiant::class)->whereIn('auditable_id', $etudiants))
                ->orWhere(fn ($o) => $this->visePar($o, 'etudiant_id', $etudiants->all())))
            ->when($inscriptions->isNotEmpty(), fn ($w) => $w->orWhere(fn ($o) => $this->visePar($o, 'inscription_id', $inscriptions->all())));
    }

    /** Une ligne dont les valeurs portent `"cle":id` pour l'un de ces identifiants. */
    private function visePar(Builder $q, string $cle, array $ids): void
    {
        $motif = '"'.$cle.'":"?('.implode('|', array_map('intval', $ids)).')[,}"]';
        $q->where('new_values', 'regexp', $motif)->orWhere('old_values', 'regexp', $motif);
    }
}

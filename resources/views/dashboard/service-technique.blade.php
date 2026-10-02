@extends('layouts.app')

@section('title', 'Service technique - KLASSCI')

@push('styles')
<style>
.dst { --dst-primary:#0453cb; --dst-primary-d:#033a8e; --dst-dark:#0f172a; --dst-text:#1e293b; --dst-muted:#64748b; --dst-border:#e2e8f0; --dst-surface:#f8fafc; color:var(--dst-text); max-width:1320px; margin:0 auto; padding:clamp(1rem,2.5vw,1.5rem) clamp(.75rem,2.5vw,1.5rem) 2rem; overflow-x:clip; }
.dst *, .dst *::before, .dst *::after { box-sizing:border-box; }
.dst-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:clamp(1.25rem,3vw,2rem) clamp(1rem,3vw,2.5rem) clamp(1.1rem,2.5vw,1.5rem); color:#fff; margin-bottom:1.25rem; box-shadow:0 8px 30px rgba(4,83,203,.18); }
.dst-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.dst-hero-left { display:flex; align-items:center; gap:1rem; min-width:0; flex:1 1 auto; }
.dst-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
.dst-hero h1 { font-size:clamp(1.15rem,2.6vw,1.45rem); font-weight:700; color:#fff; margin:0; line-height:1.25; }
.dst-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:.2rem 0 0; }
.dst-hero-actions { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
.dst-pill { display:inline-flex; align-items:center; gap:.4rem; font-size:.74rem; font-weight:600; padding:.32rem .7rem; border-radius:999px; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); white-space:nowrap; }
.dst-pill-dot { width:8px; height:8px; border-radius:50%; background:#fff; }
.dst-pill--ok .dst-pill-dot { background:#6ee7b7; }
.dst-pill--ko .dst-pill-dot { background:#fca5a5; }
.dst-btn { display:inline-flex; align-items:center; justify-content:center; gap:.45rem; border-radius:10px; padding:.55rem 1rem; font-size:.82rem; font-weight:600; border:1px solid transparent; text-decoration:none; transition:all .2s ease; white-space:nowrap; }
.dst-btn--white { background:#fff; color:var(--dst-primary); }
.dst-btn--white:hover { background:#eef4fd; color:var(--dst-primary-d); }
.dst-btn--glass { background:rgba(255,255,255,.15); color:#fff; border-color:rgba(255,255,255,.22); }
.dst-btn--glass:hover { background:rgba(255,255,255,.24); color:#fff; }
.dst-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr)); gap:.75rem; margin-top:1.4rem; }
.dst-kpi { display:block; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.85rem 1rem; color:#fff; text-decoration:none; min-width:0; transition:background .2s ease; }
a.dst-kpi:hover { background:rgba(255,255,255,.18); color:#fff; }
.dst-kpi-label { font-size:.7rem; letter-spacing:.04em; text-transform:uppercase; color:rgba(255,255,255,.7); font-weight:600; }
.dst-kpi-value { font-size:clamp(1.05rem,2.6vw,1.45rem); font-weight:700; margin-top:.25rem; line-height:1.2; }
.dst-kpi-sub { font-size:.74rem; color:rgba(255,255,255,.75); margin-top:.15rem; line-height:1.35; }
.dst-layout { display:grid; grid-template-columns:minmax(0,1.15fr) minmax(0,1fr); gap:1rem; align-items:start; }
.dst-card { background:#fff; border:1px solid var(--dst-border); border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04),0 1px 2px rgba(15,23,42,.06); padding:1.1rem; min-width:0; }
.dst-card + .dst-card { margin-top:1rem; }
.dst-card-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; margin-bottom:.85rem; }
.dst-card-title { display:flex; align-items:center; gap:.6rem; min-width:0; }
.dst-card-title h2 { font-size:.98rem; font-weight:700; color:var(--dst-dark); margin:0; }
.dst-card-title span { display:block; font-size:.76rem; color:var(--dst-muted); }
.dst-icon { width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; }
.dst-link { font-size:.8rem; font-weight:600; color:var(--dst-primary); text-decoration:none; white-space:nowrap; }
.dst-link:hover { color:var(--dst-primary-d); text-decoration:underline; }
.dst-queue { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.55rem; }
.dst-queue li { display:flex; align-items:flex-start; gap:.7rem; padding:.7rem .8rem; border:1px solid var(--dst-border); border-radius:11px; background:var(--dst-surface); }
.dst-queue-dot { width:10px; height:10px; border-radius:50%; margin-top:.35rem; flex-shrink:0; background:var(--dst-primary); }
.dst-queue-dot--danger { background:#dc2626; }
.dst-queue-dot--warning { background:#f59e0b; }
.dst-queue-body { flex:1; min-width:0; font-size:.84rem; line-height:1.4; }
.dst-queue-body strong { color:var(--dst-dark); display:block; }
.dst-queue-body small { color:var(--dst-muted); }
.dst-queue li .dst-link { margin-left:auto; align-self:center; }
.dst-empty { display:flex; align-items:center; gap:.6rem; padding:.85rem; border-radius:11px; background:rgba(16,185,129,.06); border:1px solid rgba(16,185,129,.25); font-size:.85rem; }
.dst-empty i { color:#10b981; }
.dst-trend { display:flex; align-items:flex-end; gap:.5rem; height:140px; padding-top:.5rem; }
.dst-trend-col { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%; min-width:0; gap:.3rem; }
.dst-trend-bar { width:100%; max-width:42px; border-radius:7px 7px 3px 3px; background:linear-gradient(180deg,#3b7ddb,#0453cb); min-height:3px; }
.dst-trend-val { font-size:.74rem; font-weight:700; color:var(--dst-dark); white-space:nowrap; }
.dst-trend-lbl { font-size:.7rem; color:var(--dst-muted); white-space:nowrap; }
.dst-usage { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,140px),1fr)); gap:.6rem; }
.dst-usage-item { border:1px solid var(--dst-border); border-radius:11px; padding:.7rem .8rem; min-width:0; }
.dst-usage-label { font-size:.76rem; color:var(--dst-muted); font-weight:600; }
.dst-usage-value { font-size:1.05rem; font-weight:700; color:var(--dst-dark); white-space:nowrap; margin-top:.15rem; }
.dst-usage-value small { font-size:.74rem; color:var(--dst-muted); font-weight:600; }
.dst-gauge { height:6px; border-radius:999px; background:#eef2f7; margin-top:.45rem; overflow:hidden; }
.dst-gauge > div { height:100%; border-radius:999px; background:linear-gradient(90deg,#0453cb,#3b7ddb); }
.dst-gauge > div.is-warning { background:#f59e0b; }
.dst-gauge > div.is-danger { background:#dc2626; }
.dst-codes { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.45rem; }
.dst-codes li { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.3rem .75rem; font-size:.8rem; padding:.55rem .7rem; border:1px solid var(--dst-border); border-radius:10px; }
.dst-mono { font-family:'Courier New',ui-monospace,monospace; color:var(--dst-primary-d); overflow-wrap:anywhere; word-break:break-all; min-width:0; }
.dst-quick { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:.6rem; }
.dst-quick a { display:flex; align-items:center; gap:.65rem; padding:.75rem .85rem; border:1px solid var(--dst-border); border-radius:11px; text-decoration:none; color:var(--dst-text); transition:border-color .2s ease, box-shadow .2s ease; min-width:0; }
.dst-quick a:hover { border-color:#c7d4e5; box-shadow:0 6px 20px rgba(4,83,203,.08); color:var(--dst-text); }
.dst-quick a i { width:32px; height:32px; border-radius:9px; background:rgba(4,83,203,.08); color:var(--dst-primary); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dst-quick strong { display:block; font-size:.84rem; color:var(--dst-dark); }
.dst-quick small { font-size:.74rem; color:var(--dst-muted); line-height:1.3; overflow-wrap:anywhere; }
.dst-quick span { min-width:0; }
@media (max-width:992px) { .dst-layout { grid-template-columns:minmax(0,1fr); } }
/* Sur deux colonnes, une cinquième jauge seule : elle prend la rangée entière plutôt que de rester orpheline. */
@media (max-width:575.98px) { .dst-usage > :last-child:nth-child(odd) { grid-column:1 / -1; } }
@media (max-width:576px) {
    .dst-hero-actions { width:100%; }
    .dst-hero-actions .dst-btn, .dst-pill { flex:1 1 100%; justify-content:center; }
    .dst-queue li { flex-wrap:wrap; }
    .dst-queue li .dst-link { margin-left:1.4rem; }
}
</style>
@endpush

@section('content')
@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $abo = $abonnement['abonnement'];
    $estMaster = $abonnement['source'] === 'master';
    $routePaywall = route('esbtp.paywall-config.index');

    // File de travail : ce qui attend le service technique, du plus urgent au moins urgent.
    $file = [];
    if (! $abonnement['master_configure']) {
        $file[] = ['niveau' => 'warning', 'titre' => 'adminKlassci non configuré', 'detail' => 'Les limites lues sont les réglages locaux de secours.', 'lien' => $routePaywall, 'action' => 'Voir'];
    } elseif (! $estMaster) {
        $file[] = ['niveau' => 'danger', 'titre' => 'adminKlassci injoignable', 'detail' => $abonnement['erreur_master'] ?? 'Aucune réponse du master.', 'lien' => $routePaywall, 'action' => 'Réessayer'];
    }
    foreach ($abonnement['statut']['reasons'] as $raison) {
        $file[] = ['niveau' => 'danger', 'titre' => $raison, 'detail' => $abonnement['paywall_actif'] ? 'L\'école est bloquée.' : 'Paywall non appliqué : rien n\'est bloqué.', 'lien' => $abonnement['fiche_url'] ?: $routePaywall, 'action' => $abonnement['fiche_url'] ? 'Régler dans adminKlassci' : 'Voir'];
    }
    foreach ($abonnement['statut']['warnings'] as $alerte) {
        $file[] = ['niveau' => 'warning', 'titre' => $alerte, 'detail' => 'À anticiper avec l\'école.', 'lien' => $abonnement['fiche_url'] ?: $routePaywall, 'action' => 'Voir'];
    }
    if ($activeCodes->isNotEmpty()) {
        $file[] = ['niveau' => 'info', 'titre' => $activeCodes->count() . ' code(s) d\'urgence en cours', 'detail' => 'Le premier expire à ' . $activeCodes->first()->expires_at->format('H:i') . '.', 'lien' => $routePaywall, 'action' => 'Voir'];
    }

    $maxTendance = max(1, (int) $tendanceInscriptions->max('total'));
    $moisCourant = $tendanceInscriptions->last()['total'] ?? 0;
    $moisPrecedent = $tendanceInscriptions->count() > 1 ? $tendanceInscriptions[$tendanceInscriptions->count() - 2]['total'] : 0;
    $usageInscriptions = $abonnement['usages']['inscriptions'];
@endphp

<div class="dst">
    <div class="dst-hero">
        <div class="dst-hero-top">
            <div class="dst-hero-left">
                <div class="dst-hero-icon"><i class="fas fa-screwdriver-wrench"></i></div>
                <div style="min-width:0">
                    <h1>Service technique</h1>
                    <p>Bonjour {{ auth()->user()->name }} · {{ now()->format('d/m/Y') }} · instance {{ $abonnement['code'] ?? '—' }}</p>
                </div>
            </div>
            <div class="dst-hero-actions">
                <span class="dst-pill {{ $estMaster ? 'dst-pill--ok' : ($abonnement['master_configure'] ? 'dst-pill--ko' : '') }}">
                    <span class="dst-pill-dot"></span>{{ $estMaster ? 'adminKlassci connecté' : ($abonnement['master_configure'] ? 'adminKlassci injoignable' : 'adminKlassci non configuré') }}
                </span>
                @if($abonnement['fiche_url'])
                    <a href="{{ $abonnement['fiche_url'] }}" target="_blank" rel="noopener" class="dst-btn dst-btn--white">
                        <i class="fas fa-arrow-up-right-from-square"></i><span>Fiche adminKlassci</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="dst-kpis">
            <a class="dst-kpi" href="{{ $routePaywall }}">
                <div class="dst-kpi-label">Abonnement</div>
                <div class="dst-kpi-value">
                    @if(! $abo['fin'])
                        Sans échéance
                    @elseif($abo['expire'])
                        Expiré
                    @else
                        {{ $fmt($abo['jours_restants']) }} j
                    @endif
                </div>
                <div class="dst-kpi-sub">{{ $abonnement['plan_label'] ?: 'Plan non renseigné' }}@if($abo['fin']) · fin le {{ $abo['fin']->format('d/m/Y') }}@endif</div>
            </a>
            <a class="dst-kpi" href="{{ $routePaywall }}">
                <div class="dst-kpi-label">Inscriptions de l'année</div>
                <div class="dst-kpi-value">{{ $fmt($stats['total_inscriptions_year']) }}</div>
                <div class="dst-kpi-sub">
                    @if($usageInscriptions['illimite'])
                        Sans limite sur ce plan
                    @elseif($usageInscriptions['max'] !== null)
                        sur {{ $fmt($usageInscriptions['max']) }} autorisées
                    @else
                        Limite non renseignée
                    @endif
                </div>
            </a>
            @php
                $routeEtudiants = \Illuminate\Support\Facades\Route::has('esbtp.etudiants.index') ? route('esbtp.etudiants.index') : null;
            @endphp
            @if($routeEtudiants)<a class="dst-kpi" href="{{ $routeEtudiants }}">@else<div class="dst-kpi">@endif
                <div class="dst-kpi-label">Étudiants inscrits</div>
                <div class="dst-kpi-value">{{ $fmt($stats['total_students']) }}</div>
                <div class="dst-kpi-sub">+{{ $fmt($recentActivity['new_students_this_month']) }} ce mois · {{ $fmt($recentActivity['new_students_last_month']) }} le mois dernier</div>
            @if($routeEtudiants)</a>@else</div>@endif
            <div class="dst-kpi">
                <div class="dst-kpi-label">Comptes utilisateurs</div>
                <div class="dst-kpi-value">{{ $fmt($stats['total_users']) }}</div>
                <div class="dst-kpi-sub">+{{ $fmt($recentActivity['new_users_this_month']) }} ce mois · {{ $fmt($recentActivity['new_users_last_month']) }} le mois dernier</div>
            </div>
        </div>
    </div>

    <div class="dst-layout">
        <div>
            <div class="dst-card">
                <div class="dst-card-head">
                    <div class="dst-card-title">
                        <div class="dst-icon"><i class="fas fa-list-check"></i></div>
                        <div><h2>À traiter</h2><span>Du plus urgent au moins urgent</span></div>
                    </div>
                </div>
                @if(count($file) > 0)
                    <ul class="dst-queue">
                        @foreach($file as $ligne)
                            <li>
                                <span class="dst-queue-dot {{ $ligne['niveau'] !== 'info' ? 'dst-queue-dot--' . $ligne['niveau'] : '' }}"></span>
                                <div class="dst-queue-body">
                                    <strong>{{ $ligne['titre'] }}</strong>
                                    <small>{{ $ligne['detail'] }}</small>
                                </div>
                                <a class="dst-link" href="{{ $ligne['lien'] }}" @if(str_starts_with($ligne['lien'], 'http') && $ligne['lien'] !== $routePaywall) target="_blank" rel="noopener" @endif>{{ $ligne['action'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="dst-empty">
                        <i class="fas fa-circle-check"></i>
                        <span>Rien en attente. Dernier contrôle à {{ now()->format('H:i') }}.</span>
                    </div>
                @endif
            </div>

            <div class="dst-card">
                <div class="dst-card-head">
                    <div class="dst-card-title">
                        <div class="dst-icon"><i class="fas fa-gauge-high"></i></div>
                        <div><h2>Limites de l'abonnement</h2><span>{{ $estMaster ? 'Lues dans adminKlassci' : 'Réglages locaux de secours' }}</span></div>
                    </div>
                    <a class="dst-link" href="{{ $routePaywall }}">Détail</a>
                </div>
                <div class="dst-usage">
                    @foreach($abonnement['usages'] as $u)
                        @php
                            $niveau = $u['depasse'] ? 'is-danger' : (($u['pct'] ?? 0) >= \App\Services\Master\AbonnementDeLInstance::SEUIL_ALERTE_PCT ? 'is-warning' : '');
                        @endphp
                        <div class="dst-usage-item">
                            <div class="dst-usage-label">{{ $u['libelle'] }}</div>
                            @if($u['actuel'] === null)
                                <div class="dst-usage-value"><small>Non mesuré</small></div>
                            @else
                                <div class="dst-usage-value">{{ $fmt($u['actuel']) }} <small>/ {{ $u['illimite'] ? 'illimité' : ($u['max'] !== null ? $fmt($u['max']) : '—') }}</small></div>
                                @if($u['pct'] !== null)
                                    <div class="dst-gauge"><div class="{{ $niveau }}" style="width: {{ min(100, $u['pct']) }}%"></div></div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div>
            <div class="dst-card">
                <div class="dst-card-head">
                    <div class="dst-card-title">
                        <div class="dst-icon"><i class="fas fa-chart-column"></i></div>
                        <div>
                            <h2>Inscriptions créées</h2>
                            <span>Six derniers mois · {{ $fmt($moisCourant) }} ce mois, {{ $fmt($moisPrecedent) }} le mois dernier</span>
                        </div>
                    </div>
                </div>
                <div class="dst-trend" role="img" aria-label="Inscriptions créées par mois sur six mois">
                    @foreach($tendanceInscriptions as $point)
                        <div class="dst-trend-col" title="{{ ucfirst($point['mois']->translatedFormat('F Y')) }} : {{ $fmt($point['total']) }} inscription(s)">
                            <span class="dst-trend-val">{{ $fmt($point['total']) }}</span>
                            <div class="dst-trend-bar" style="height: {{ max(2, round($point['total'] / $maxTendance * 100)) }}%"></div>
                            <span class="dst-trend-lbl">{{ $point['mois']->translatedFormat('M') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="dst-card">
                <div class="dst-card-head">
                    <div class="dst-card-title">
                        <div class="dst-icon"><i class="fas fa-key"></i></div>
                        <div><h2>Codes d'urgence actifs</h2><span>Accès d'une heure, à usage unique</span></div>
                    </div>
                    <a class="dst-link" href="{{ $routePaywall }}">Générer</a>
                </div>
                @if($activeCodes->isNotEmpty())
                    <ul class="dst-codes">
                        @foreach($activeCodes as $code)
                            <li>
                                <span class="dst-mono">{{ $code->code }}</span>
                                <span>{{ $code->created_by }} · expire à {{ $code->expires_at->format('H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="dst-kpi-sub" style="color:var(--dst-muted);margin:0">Aucun code en cours de validité.</p>
                @endif
            </div>

            <div class="dst-card">
                <div class="dst-card-head">
                    <div class="dst-card-title">
                        <div class="dst-icon"><i class="fas fa-bolt"></i></div>
                        <div><h2>Accès rapides</h2><span>Réglages réservés au service technique</span></div>
                    </div>
                </div>
                <div class="dst-quick">
                    <a href="{{ $routePaywall }}"><i class="fas fa-shield-alt"></i><span><strong>Abonnement</strong><small>Limites, échéance, accès d'urgence</small></span></a>
                    <a href="{{ route('esbtp.matricule-config.index') }}"><i class="fas fa-id-card"></i><span><strong>Matricules</strong><small>Mode et nomenclature</small></span></a>
                    <a href="{{ route('esbtp.roles-permissions.index') }}"><i class="fas fa-user-shield"></i><span><strong>Rôles et permissions</strong><small>Droits par rôle</small></span></a>
                    <a href="{{ route('esbtp.bulletin-style.index') }}"><i class="fas fa-file-lines"></i><span><strong>Style des bulletins</strong><small>Présentation des PDF</small></span></a>
                    @if(config('app.support_email'))
                        <a href="mailto:{{ config('app.support_email') }}"><i class="fas fa-envelope"></i><span><strong>Support KLASSCI</strong><small>{{ config('app.support_email') }}</small></span></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

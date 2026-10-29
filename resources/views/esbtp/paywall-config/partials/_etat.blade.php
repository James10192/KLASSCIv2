{{-- Bloc d'etat de l'abonnement (hero + jauges). Rendu par la page et rendu
     de nouveau par l'action « Actualiser depuis adminKlassci », qui remplace
     #pwc-etat sans recharger la page. Aucun attribut Alpine ici : le bloc est
     injecte en HTML brut, les boutons passent par data-pwc-action. --}}
@php
    $abo = $etat['abonnement'];
    $fmt = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $estMaster = $etat['source'] === 'master';
    if ($estMaster) {
        $sourceClasse = 'pwc-source--ok';
        $sourceTexte = 'Synchronisé avec adminKlassci';
    } elseif ($etat['master_configure']) {
        $sourceClasse = 'pwc-source--ko';
        $sourceTexte = 'adminKlassci injoignable';
    } else {
        $sourceClasse = '';
        $sourceTexte = 'adminKlassci non configuré';
    }
    $bloque = $etat['statut']['is_blocked'];
    // Paywall non appliqué : les limites dépassées restent dites, mais
    // l'école est ouverte. Ne jamais afficher « Bloqué » dans ce cas.
    $bloqueEnFait = $bloque && $etat['paywall_actif'];
@endphp

<div class="pwc-hero">
    <div class="pwc-hero-top">
        <div class="pwc-hero-left">
            <div class="pwc-hero-icon"><i class="fas fa-shield-alt"></i></div>
            <div style="min-width:0">
                <h1>Abonnement de l'instance</h1>
                <p>
                    {{ $etat['nom'] ?? ($etablissement->nom ?? 'Établissement') }}
                    @if($etat['code'])
                        · code <span class="pwc-nowrap">{{ $etat['code'] }}</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="pwc-hero-actions">
            <span class="pwc-source {{ $sourceClasse }}"><span class="pwc-source-dot"></span>{{ $sourceTexte }}</span>
            @if($etat['master_configure'])
                <button type="button" class="pwc-btn pwc-btn--glass" data-pwc-action="actualiser">
                    <i class="fas fa-rotate"></i><span>Actualiser depuis adminKlassci</span>
                </button>
            @endif
            @if($etat['fiche_url'])
                <a href="{{ $etat['fiche_url'] }}" target="_blank" rel="noopener" class="pwc-btn pwc-btn--white">
                    <i class="fas fa-arrow-up-right-from-square"></i><span>Ouvrir dans adminKlassci</span>
                </a>
            @endif
        </div>
    </div>

    <div class="pwc-kpis">
        <div class="pwc-kpi">
            <div class="pwc-kpi-label">Plan</div>
            <div class="pwc-kpi-value">{{ $etat['plan_label'] ?: 'Non renseigné' }}</div>
            <div class="pwc-kpi-sub">
                @if($etat['tarif_mensuel'] !== null)
                    <span class="pwc-nowrap">{{ $fmt($etat['tarif_mensuel']) }} FCFA</span> / mois
                @else
                    Tarif non renseigné
                @endif
            </div>
        </div>
        <div class="pwc-kpi">
            <div class="pwc-kpi-label">Échéance</div>
            <div class="pwc-kpi-value">
                @if(! $abo['fin'])
                    Sans échéance
                @elseif($abo['expire'])
                    Expiré
                @else
                    <span class="pwc-nowrap">{{ $fmt($abo['jours_restants']) }} j</span> restants
                @endif
            </div>
            <div class="pwc-kpi-sub">
                @if($abo['fin'])
                    {{ $abo['expire'] ? 'Depuis le' : 'Jusqu\'au' }} <span class="pwc-nowrap">{{ $abo['fin']->format('d/m/Y') }}</span>
                    @if($abo['pct_ecoule'] !== null && ! $abo['expire'])
                        · {{ $abo['pct_ecoule'] }} % écoulé
                    @endif
                @else
                    Aucune date de fin posée
                @endif
            </div>
        </div>
        <div class="pwc-kpi">
            <div class="pwc-kpi-label">Accès de l'école</div>
            <div class="pwc-kpi-value">{{ $bloqueEnFait ? 'Bloqué' : 'Ouvert' }}</div>
            <div class="pwc-kpi-sub">
                Paywall {{ $etat['paywall_actif'] ? 'appliqué' : 'non appliqué' }} sur cette instance
                @if($etat['statut_tenant_label'])
                    · fiche {{ mb_strtolower($etat['statut_tenant_label'], 'UTF-8') }}
                @endif
            </div>
        </div>
        <div class="pwc-kpi">
            <div class="pwc-kpi-label">Dernière lecture</div>
            <div class="pwc-kpi-value">
                @if($etat['lu_a'])
                    <span class="pwc-nowrap">{{ $etat['lu_a']->format('H:i') }}</span>
                @else
                    —
                @endif
            </div>
            <div class="pwc-kpi-sub">
                @if($etat['lu_a'])
                    le {{ $etat['lu_a']->format('d/m/Y') }} · gardée 5 min
                @elseif($etat['master_configure'])
                    Aucune réponse d'adminKlassci
                @else
                    Valeurs locales uniquement
                @endif
            </div>
        </div>
    </div>
</div>

@if(! $etat['master_configure'])
    <div class="pwc-alert pwc-alert--info">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>adminKlassci n'est pas configuré sur cette instance.</strong>
            Les valeurs ci-dessous sont les réglages locaux de secours. Renseignez
            <span class="pwc-nowrap">MASTER_API_URL</span>, <span class="pwc-nowrap">MASTER_API_TOKEN</span> et
            <span class="pwc-nowrap">TENANT_CODE</span> pour lire la fiche du tenant.
        </div>
    </div>
@elseif(! $estMaster)
    <div class="pwc-alert pwc-alert--warning">
        <i class="fas fa-plug-circle-xmark"></i>
        <div>
            <strong>Valeurs locales de secours.</strong>
            {{ $etat['erreur_master'] ?? 'adminKlassci ne répond pas' }}@if($etat['echec_a']) à {{ $etat['echec_a']->format('H:i') }}@endif.
            Le paywall applique les réglages locaux tant que la fiche est injoignable ;
            une nouvelle tentative a lieu au plus tard dans une minute.
        </div>
    </div>
@endif

@if($bloque)
    <div class="pwc-alert {{ $bloqueEnFait ? 'pwc-alert--danger' : 'pwc-alert--warning' }}">
        <i class="fas {{ $bloqueEnFait ? 'fa-ban' : 'fa-triangle-exclamation' }}"></i>
        <div>
            <strong>{{ $bloqueEnFait ? 'L\'école est bloquée.' : 'Limites dépassées. Le paywall n\'est pas appliqué sur cette instance : l\'école reste ouverte.' }}</strong>
            <ul>
                @foreach($etat['statut']['reasons'] as $raison)
                    <li>{{ $raison }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(count($etat['statut']['warnings']) > 0)
    <div class="pwc-alert pwc-alert--warning">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
            <strong>À surveiller</strong>
            <ul>
                @foreach($etat['statut']['warnings'] as $alerte)
                    <li>{{ $alerte }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(! $bloque && count($etat['statut']['warnings']) === 0)
    <div class="pwc-alert pwc-alert--success">
        <i class="fas fa-circle-check"></i>
        <div><strong>Rien à signaler.</strong> Aucune limite atteinte, aucune échéance proche.</div>
    </div>
@endif

<div class="pwc-section-title">
    <div class="pwc-section-icon"><i class="fas fa-gauge-high"></i></div>
    <div>
        <h2>Consommation et limites</h2>
        <span>{{ $estMaster ? 'Compteurs relevés par adminKlassci' : 'Comptés sur cette instance' }}</span>
    </div>
</div>

<div class="pwc-grid">
    @foreach($etat['usages'] as $cle => $u)
        @php
            $niveau = $u['depasse'] ? 'danger' : (($u['pct'] ?? 0) >= \App\Services\Master\AbonnementDeLInstance::SEUIL_ALERTE_PCT ? 'warning' : '');
        @endphp
        <div class="pwc-card" data-usage="{{ $cle }}">
            <div class="pwc-usage-head">
                <div class="pwc-usage-icon"><i class="fas {{ $u['icone'] }}"></i></div>
                <div class="pwc-usage-label">{{ $u['libelle'] }}</div>
            </div>
            @if($u['actuel'] === null)
                <div class="pwc-usage-value pwc-usage-value--muted">Non mesuré</div>
                <div class="pwc-usage-foot">{{ $estMaster ? 'adminKlassci n\'a pas encore relevé ce compteur.' : 'Mesuré seulement par adminKlassci.' }}</div>
            @else
                <div class="pwc-usage-value">
                    {{ $fmt($u['actuel']) }}
                    <small>/ {{ $u['illimite'] ? 'illimité' : ($u['max'] !== null ? $fmt($u['max']) : '—') }}{{ $u['unite'] ? ' ' . $u['unite'] : '' }}</small>
                </div>
                @if($u['pct'] !== null)
                    <div class="pwc-gauge" role="progressbar" aria-valuenow="{{ min(100, $u['pct']) }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $u['libelle'] }}">
                        <div class="pwc-gauge-bar {{ $niveau ? 'pwc-gauge-bar--' . $niveau : '' }}" style="width: {{ min(100, $u['pct']) }}%"></div>
                    </div>
                    <div class="pwc-usage-foot {{ $niveau ? 'pwc-usage-foot--' . $niveau : '' }}">
                        @if($u['depasse'])
                            Limite dépassée de {{ $fmt($u['actuel'] - $u['max']) }}
                        @else
                            {{ str_replace('.', ',', (string) $u['pct']) }} % de la limite · reste {{ $fmt(max(0, $u['max'] - $u['actuel'])) }}
                        @endif
                    </div>
                @elseif($u['illimite'])
                    <div class="pwc-usage-foot">Aucune limite sur ce plan</div>
                @else
                    <div class="pwc-usage-foot">Aucune limite définie</div>
                @endif
            @endif
        </div>
    @endforeach
</div>

@if(count($etat['fonctionnalites_bloquees']) > 0)
    <div class="pwc-section-title">
        <div class="pwc-section-icon"><i class="fas fa-flag"></i></div>
        <div>
            <h2>Limites signalées par adminKlassci</h2>
            <span>Atteintes ou dépassées. Seul un dépassement bloque l'école.</span>
        </div>
    </div>
    <div class="pwc-chips">
        @foreach($etat['fonctionnalites_bloquees'] as $fonction)
            <span class="pwc-chip"><i class="fas fa-flag"></i>{{ $fonction }}</span>
        @endforeach
    </div>
@endif

<div class="pwc-meta">
    @if($estMaster)
        <span><i class="fas fa-clock-rotate-left"></i>Compteurs relevés
            {{ $etat['releve_a'] ? 'le ' . $etat['releve_a']->format('d/m/Y à H:i') : '— jamais relevés par adminKlassci' }}</span>
    @endif
    @if($abo['debut'])
        <span><i class="fas fa-calendar"></i>Abonnement depuis le {{ $abo['debut']->format('d/m/Y') }}</span>
    @endif
</div>

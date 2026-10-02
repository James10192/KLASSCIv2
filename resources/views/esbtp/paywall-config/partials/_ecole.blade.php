{{-- Page vue par une ecole bloquee (blocked, upgrade). Elle dit pourquoi,
     d'apres la fiche adminKlassci, et qui contacter. Aucun lien vers le
     panneau adminKlassci : ce n'est pas un ecran du service technique. --}}
@php
    $abo = $etat['abonnement'];
    $fmt = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $motifs = $reasons;
    $usagesMesures = collect($etat['usages'])->filter(fn ($u) => $u['actuel'] !== null && ! $u['illimite'] && $u['max'] !== null);
    $sujet = rawurlencode('Abonnement KLASSCI — ' . ($etablissement->nom ?? ($etat['code'] ?? 'mon établissement')));
@endphp
<div class="pwc">
    <div class="pwc-hero">
        <div class="pwc-hero-top">
            <div class="pwc-hero-left">
                <div class="pwc-hero-icon"><i class="fas {{ $icone }}"></i></div>
                <div style="min-width:0">
                    <h1>{{ $titre }}</h1>
                    <p>{{ $intro }}</p>
                </div>
            </div>
            <div class="pwc-hero-actions">
                @if($contactEmail)
                    <a href="mailto:{{ $contactEmail }}?subject={{ $sujet }}" class="pwc-btn pwc-btn--white">
                        <i class="fas fa-envelope"></i><span>Écrire à l'équipe KLASSCI</span>
                    </a>
                @endif
                <a href="tel:{{ $contactTelephone }}" class="pwc-btn pwc-btn--glass">
                    <i class="fas fa-phone"></i><span>Appeler</span>
                </a>
            </div>
        </div>

        <div class="pwc-kpis">
            <div class="pwc-kpi">
                <div class="pwc-kpi-label">Plan actuel</div>
                <div class="pwc-kpi-value">{{ $etat['plan_label'] ?: 'Non renseigné' }}</div>
                <div class="pwc-kpi-sub">{{ $etablissement->nom ?? ($etat['nom'] ?? '') }}</div>
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
                    @else
                        Aucune date de fin
                    @endif
                </div>
            </div>
            <div class="pwc-kpi">
                <div class="pwc-kpi-label">Motifs de blocage</div>
                <div class="pwc-kpi-value"><span class="pwc-nowrap">{{ count($motifs) }}</span></div>
                <div class="pwc-kpi-sub">{{ count($motifs) > 0 ? 'à régulariser' : 'aucun motif en cours' }}</div>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="pwc-alert pwc-alert--danger">
            <i class="fas fa-ban"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(count($motifs) > 0)
        <div class="pwc-alert pwc-alert--danger">
            <i class="fas fa-circle-exclamation"></i>
            <div>
                <strong>Ce qui bloque l'accès</strong>
                <ul>
                    @foreach($motifs as $motif)
                        <li>{{ $motif }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @elseif(! session('error'))
        <div class="pwc-alert pwc-alert--success">
            <i class="fas fa-circle-check"></i>
            <div><strong>Aucun blocage en cours.</strong> L'accès est rétabli ; vous pouvez revenir à votre tableau de bord.</div>
        </div>
    @endif

    @if($usagesMesures->isNotEmpty())
        <div class="pwc-section-title">
            <div class="pwc-section-icon"><i class="fas fa-gauge-high"></i></div>
            <div><h2>Votre consommation</h2><span>Comparée aux limites de votre plan</span></div>
        </div>
        <div class="pwc-grid">
            @foreach($usagesMesures as $u)
                @php
                    $niveau = $u['depasse'] ? 'danger' : (($u['pct'] ?? 0) >= \App\Services\Master\AbonnementDeLInstance::SEUIL_ALERTE_PCT ? 'warning' : '');
                @endphp
                <div class="pwc-card">
                    <div class="pwc-usage-head">
                        <div class="pwc-usage-icon"><i class="fas {{ $u['icone'] }}"></i></div>
                        <div class="pwc-usage-label">{{ $u['libelle'] }}</div>
                    </div>
                    <div class="pwc-usage-value">{{ $fmt($u['actuel']) }} <small>/ {{ $fmt($u['max']) }}{{ $u['unite'] ? ' ' . $u['unite'] : '' }}</small></div>
                    @if($u['pct'] !== null)
                        <div class="pwc-gauge"><div class="pwc-gauge-bar {{ $niveau ? 'pwc-gauge-bar--' . $niveau : '' }}" style="width: {{ min(100, $u['pct']) }}%"></div></div>
                        <div class="pwc-usage-foot {{ $niveau ? 'pwc-usage-foot--' . $niveau : '' }}">
                            {{ $u['depasse'] ? 'Limite dépassée' : str_replace('.', ',', (string) $u['pct']) . ' % de la limite' }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="pwc-section-title">
        <div class="pwc-section-icon"><i class="fas fa-route"></i></div>
        <div><h2>Rétablir l'accès</h2><span>Trois étapes, sans rien réinstaller</span></div>
    </div>
    <div class="pwc-grid">
        <div class="pwc-card">
            <div class="pwc-usage-head"><div class="pwc-usage-icon"><i class="fas fa-comments"></i></div><div class="pwc-usage-label">1. Contactez l'équipe KLASSCI</div></div>
            <p class="pwc-usage-foot">Par courriel ou par téléphone, en précisant le nom de votre établissement.</p>
        </div>
        <div class="pwc-card">
            <div class="pwc-usage-head"><div class="pwc-usage-icon"><i class="fas fa-file-invoice"></i></div><div class="pwc-usage-label">2. Choisissez le plan ou le renouvellement</div></div>
            <p class="pwc-usage-foot">L'équipe met à jour votre abonnement : échéance, plan et limites.</p>
        </div>
        <div class="pwc-card">
            <div class="pwc-usage-head"><div class="pwc-usage-icon"><i class="fas fa-unlock"></i></div><div class="pwc-usage-label">3. L'accès revient de lui-même</div></div>
            <p class="pwc-usage-foot">Dans les cinq minutes qui suivent la mise à jour, sans action de votre part.</p>
        </div>
    </div>
</div>

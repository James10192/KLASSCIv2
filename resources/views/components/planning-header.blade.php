{{-- Composant Header/Slider pour les pages de planning --}}
@props(['title' => 'Planning Général', 'subtitle' => '', 'activeTab' => 'overview', 'anneeSelectionnee' => null, 'annees' => collect(), 'stats' => null])

<style>
    /* ══════════════════════════════════════════════
       Planning Header — Premium v2
       Prefix: ph- (planning-header)
       ══════════════════════════════════════════════ */

    .ph-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem;
        color: #fff; margin-bottom: 1.25rem;
        animation: ph-fadeDown .5s ease-out;
    }
    @keyframes ph-fadeDown { from { opacity:0; transform:translateY(-15px); } to { opacity:1; transform:translateY(0); } }

    .ph-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem; position: relative; z-index: 5;
    }
    .ph-hero-left { display: flex; align-items: center; gap: 1rem; }
    .ph-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0;
    }
    .ph-hero-info h1 {
        font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem;
        color: #fff; letter-spacing: -.02em;
    }
    .ph-hero-info p { margin: 0; opacity: .8; font-size: .88rem; }

    .ph-hero-actions {
        display: flex; gap: .5rem; align-items: center; flex-wrap: wrap;
        position: relative; z-index: 10;
    }
    .ph-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .5rem 1rem; border-radius: 10px; font-size: .82rem;
        font-weight: 600; text-decoration: none; transition: all .2s;
        border: 1px solid rgba(255,255,255,.2); cursor: pointer;
    }
    .ph-btn--glass {
        background: rgba(255,255,255,.15); color: #fff;
    }
    .ph-btn--glass:hover { background: rgba(255,255,255,.2); color: #fff; }
    .ph-btn--white {
        background: #fff; color: #0453cb; border-color: transparent;
    }
    .ph-btn--white:hover { background: #f0f4ff; color: #0453cb; }

    .ph-dropdown-menu {
        background: #fff; border: 1px solid #e8ecf1; border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,.12); padding: .35rem; min-width: 220px;
        z-index: 1050;
    }
    .ph-dropdown-menu .dropdown-item {
        color: #1e293b; padding: .5rem .85rem; border-radius: 8px;
        font-size: .85rem; transition: all .15s;
    }
    .ph-dropdown-menu .dropdown-item:hover { background: #f1f5f9; }

    /* KPIs in hero */
    .ph-kpis {
        display: flex; gap: .75rem; margin-top: 1.5rem;
        position: relative; z-index: 1; flex-wrap: wrap;
        pointer-events: none;
    }
    .ph-kpi { pointer-events: auto; }
    .ph-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15); border-radius: 12px;
        padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem;
        transition: background .2s; pointer-events: auto;
    }
    .ph-kpi:hover { background: rgba(255,255,255,.15); }
    .ph-kpi-icon {
        width: 38px; height: 38px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; flex-shrink: 0;
    }
    .ph-kpi--seances .ph-kpi-icon  { background: rgba(255,255,255,.18); color: #fff; }
    .ph-kpi--heures .ph-kpi-icon   { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .ph-kpi--classes .ph-kpi-icon  { background: rgba(251,191,36,.25); color: #fcd34d; }
    .ph-kpi--matieres .ph-kpi-icon { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .ph-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1; color: #fff; }
    .ph-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* Infos année */
    .ph-info-bar {
        display: flex; gap: 1.5rem; flex-wrap: wrap;
        margin-top: 1rem; padding-top: .85rem;
        border-top: 1px solid rgba(255,255,255,.1);
        position: relative; z-index: 1;
    }
    .ph-info-item {
        display: flex; align-items: center; gap: .4rem;
        font-size: .8rem; color: rgba(255,255,255,.7);
    }
    .ph-info-item i { font-size: .7rem; opacity: .6; }
    .ph-info-val { color: #fff; font-weight: 600; }

    /* ── Tabs navigation ── */
    .ph-tabs {
        background: #fff; border-radius: 14px; padding: .4rem;
        margin-bottom: 1.25rem;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        display: flex; gap: .25rem; overflow-x: auto;
        scrollbar-width: none; -ms-overflow-style: none;
    }
    .ph-tabs::-webkit-scrollbar { display: none; }

    .ph-tab {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .55rem 1rem; border-radius: 10px;
        font-size: .82rem; font-weight: 500;
        color: #64748b; text-decoration: none;
        white-space: nowrap; transition: all .2s;
        border: none; background: transparent;
    }
    .ph-tab:hover { color: #0453cb; background: #f0f4ff; }
    .ph-tab.active {
        background: #0453cb; color: #fff;
        box-shadow: 0 2px 8px rgba(4,83,203,.25);
        font-weight: 600;
    }
    .ph-tab.active:hover { background: #0347b0; color: #fff; }
    .ph-tab i { font-size: .78rem; }

    @media (max-width: 768px) {
        .ph-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
        .ph-hero-top { flex-direction: column; }
        .ph-kpis { flex-direction: column; }
        .ph-kpi { min-width: unset; }
    }
</style>

<!-- Hero premium -->
<div class="ph-hero">
    <div class="ph-hero-top">
        <div class="ph-hero-left">
            <div class="ph-hero-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="ph-hero-info">
                <h1>{{ $title }}</h1>
                <p>{{ $subtitle ?: 'Vue d\'ensemble du planning académique et organisation des cours' }}</p>
            </div>
        </div>
        <div class="ph-hero-actions">
            <div class="btn-group">
                <button type="button" class="ph-btn ph-btn--glass dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-calendar-alt"></i>
                    {{ $anneeSelectionnee ? $anneeSelectionnee->name : 'Sélectionner une année' }}
                </button>
                <ul class="dropdown-menu ph-dropdown-menu dropdown-menu-end">
                    @foreach($annees as $annee)
                        <li>
                            <a class="dropdown-item" href="{{ request()->url() }}?annee_id={{ $annee->id }}">
                                {{ $annee->name }}
                                @if(optional($annee)->is_current)
                                    <span class="badge bg-primary ms-2" style="font-size:.65rem;">En cours</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @canany(['manage-planning', 'view-all-timetables'])
            <a href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee?->id]) }}" class="ph-btn ph-btn--glass">
                <i class="fas fa-cogs"></i>Gestion Planning
            </a>
            <a href="{{ route('esbtp.enseignants.index') }}" class="ph-btn ph-btn--white">
                <i class="fas fa-users"></i>Enseignants
            </a>
            @endcanany
        </div>
    </div>

    @if($stats)
    <div class="ph-kpis">
        <div class="ph-kpi ph-kpi--seances">
            <div class="ph-kpi-icon"><i class="fas fa-clock"></i></div>
            <div>
                <div class="ph-kpi-value">{{ number_format($stats['total_seances']) }}</div>
                <div class="ph-kpi-label">Séances programmées</div>
            </div>
        </div>
        <div class="ph-kpi ph-kpi--heures">
            <div class="ph-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <div class="ph-kpi-value">{{ number_format($stats['total_heures'], 0) }}h</div>
                <div class="ph-kpi-label">Heures de cours</div>
            </div>
        </div>
        <div class="ph-kpi ph-kpi--classes">
            <div class="ph-kpi-icon"><i class="fas fa-users"></i></div>
            <div>
                <div class="ph-kpi-value">{{ $stats['total_classes'] }}</div>
                <div class="ph-kpi-label">Classes actives</div>
            </div>
        </div>
        <div class="ph-kpi ph-kpi--matieres">
            <div class="ph-kpi-icon"><i class="fas fa-book"></i></div>
            <div>
                <div class="ph-kpi-value">{{ $stats['total_matieres'] }}</div>
                <div class="ph-kpi-label">Matières enseignées</div>
            </div>
        </div>
    </div>

    {{-- Infos année --}}
    @if($anneeSelectionnee)
    <div class="ph-info-bar">
        <div class="ph-info-item">
            <i class="fas fa-calendar-day"></i>
            Période : <span class="ph-info-val">{{ \Carbon\Carbon::parse($anneeSelectionnee->start_date)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($anneeSelectionnee->end_date)->format('d/m/Y') }}</span>
        </div>
        <div class="ph-info-item">
            <i class="fas fa-circle" style="font-size:.45rem; color:{{ optional($anneeSelectionnee)->is_current ? '#6ee7b7' : '#94a3b8' }};"></i>
            {{ optional($anneeSelectionnee)->is_current ? 'Année en cours' : 'Année archivée' }}
        </div>
        <div class="ph-info-item">
            <i class="fas fa-chalkboard-teacher"></i>
            <span class="ph-info-val">{{ $stats['total_enseignants'] ?? 0 }}</span> enseignants actifs
        </div>
        <div class="ph-info-item">
            <i class="fas fa-chart-bar"></i>
            Charge : <span class="ph-info-val">{{ number_format($stats['total_heures'], 0) }}h</span> planifiées
        </div>
    </div>
    @endif
    @endif
</div>

<!-- Tabs navigation -->
<div class="ph-tabs">
    <a class="ph-tab {{ $activeTab === 'overview' ? 'active' : '' }}"
       href="{{ route('esbtp.planning-general.index', ['annee_id' => $anneeSelectionnee?->id]) }}">
        <i class="fas fa-th-large"></i>Vue d'ensemble
    </a>
    <a class="ph-tab {{ $activeTab === 'annuel' ? 'active' : '' }}"
       href="{{ route('esbtp.planning-general.annuel', ['annee_id' => $anneeSelectionnee?->id]) }}">
        <i class="fas fa-calendar"></i>Planning Annuel
    </a>
    <a class="ph-tab {{ $activeTab === 'repartition' ? 'active' : '' }}"
       href="{{ route('esbtp.planning-general.repartition-matieres', ['annee_id' => $anneeSelectionnee?->id]) }}">
        <i class="fas fa-layer-group"></i>Charge par classe
    </a>
    <a class="ph-tab {{ $activeTab === 'evenements' ? 'active' : '' }}"
       href="{{ route('esbtp.evenements-academiques.index', ['annee_id' => $anneeSelectionnee?->id]) }}">
        <i class="fas fa-calendar-check"></i>Événements
    </a>
    <a class="ph-tab {{ $activeTab === 'emargement' ? 'active' : '' }}"
       href="{{ route('esbtp.planning-general.emargement', ['annee_id' => $anneeSelectionnee?->id]) }}">
        <i class="fas fa-qrcode"></i>Émargement
    </a>
    @canany(['manage-planning', 'view-all-timetables'])
    <a class="ph-tab {{ $activeTab === 'coordinateur' ? 'active' : '' }}"
       href="{{ route('esbtp.planning-general.coordinateur', ['annee_id' => $anneeSelectionnee?->id]) }}">
        <i class="fas fa-user-tie"></i>Coordinateur
    </a>
    @endcanany
</div>

{{-- Tabs AJAX navigation --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.ph-tab');
    const container = document.getElementById('pg-tab-content');
    if (!container || !tabs.length) return;

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (!href || href === '#') return;

            e.preventDefault();

            // Marquer le tab actif
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Loading state
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';
            container.style.transition = 'opacity .2s';

            // Mettre à jour l'URL sans reload
            history.pushState(null, '', href);

            // Fetch la page cible
            fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    // Parser le HTML de la page complète
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('pg-tab-content');

                    if (newContent) {
                        container.innerHTML = newContent.innerHTML;
                    } else {
                        // Fallback : extraire le contenu principal
                        const mainContent = doc.querySelector('.main-content');
                        if (mainContent) {
                            const allChildren = mainContent.children;
                            let afterTabs = false;
                            let contentHtml = '';
                            for (let i = 0; i < allChildren.length; i++) {
                                if (afterTabs) {
                                    contentHtml += allChildren[i].outerHTML;
                                }
                                if (allChildren[i].classList && allChildren[i].classList.contains('ph-tabs')) {
                                    afterTabs = true;
                                }
                            }
                            if (contentHtml) {
                                container.innerHTML = contentHtml;
                            } else {
                                window.location.href = href;
                                return;
                            }
                        } else {
                            window.location.href = href;
                            return;
                        }
                    }

                    // Injecter les styles de la page fetchée (CSS dans <head>)
                    // Supprimer les anciens styles injectés par tab
                    document.querySelectorAll('style[data-ph-tab-style]').forEach(s => s.remove());
                    document.querySelectorAll('link[data-ph-tab-style]').forEach(s => s.remove());

                    // Extraire et injecter les <style> inline du <head> et du <body>
                    doc.querySelectorAll('style').forEach(function(style) {
                        // Ignorer les styles du planning-header (déjà présents dans la page)
                        if (style.textContent.includes('ph-hero') || style.textContent.includes('ph-tab{') || style.textContent.includes('.ph-tab ')) return;
                        // Ignorer les styles déjà dans la page courante (vérifier un snippet unique)
                        const snippet = style.textContent.substring(0, 80).trim();
                        let alreadyExists = false;
                        document.querySelectorAll('style:not([data-ph-tab-style])').forEach(function(existing) {
                            if (existing.textContent.substring(0, 80).trim() === snippet) alreadyExists = true;
                        });
                        if (alreadyExists) return;

                        const newStyle = document.createElement('style');
                        newStyle.textContent = style.textContent;
                        newStyle.setAttribute('data-ph-tab-style', '1');
                        document.head.appendChild(newStyle);
                    });

                    // Extraire et injecter les <link stylesheet> spécifiques
                    doc.querySelectorAll('head link[rel="stylesheet"]').forEach(function(link) {
                        const href = link.getAttribute('href');
                        // Ne pas re-injecter les CSS déjà présentes
                        if (!document.querySelector('link[href="' + href + '"]')) {
                            const newLink = document.createElement('link');
                            newLink.rel = 'stylesheet';
                            newLink.href = href;
                            newLink.setAttribute('data-ph-tab-style', '1');
                            document.head.appendChild(newLink);
                        }
                    });

                    // Supprimer les anciens scripts injectés par tab
                    document.querySelectorAll('script[data-ph-tab-script]').forEach(s => s.remove());

                    // Extraire et exécuter les scripts de la page
                    doc.querySelectorAll('script').forEach(function(script) {
                        // Ignorer les scripts externes déjà chargés (jQuery, Bootstrap, etc.)
                        if (script.src && document.querySelector('script[src="' + script.src + '"]')) return;
                        // Ignorer le script du planning-header tabs
                        if (script.textContent.includes('ph-tab') && script.textContent.includes('pg-tab-content')) return;
                        // Ignorer les scripts très courts ou vides
                        if (!script.src && script.textContent.trim().length < 10) return;

                        const newScript = document.createElement('script');
                        newScript.setAttribute('data-ph-tab-script', '1');
                        if (script.src) {
                            newScript.src = script.src;
                        } else {
                            newScript.textContent = script.textContent;
                        }
                        document.body.appendChild(newScript);
                    });

                    // Restaurer l'affichage
                    container.style.opacity = '1';
                    container.style.pointerEvents = '';
                })
                .catch(function() {
                    // En cas d'erreur, fallback au comportement normal
                    window.location.href = href;
                });
        });
    });

    // Gérer le bouton retour du navigateur
    window.addEventListener('popstate', function() {
        window.location.reload();
    });
});
</script>

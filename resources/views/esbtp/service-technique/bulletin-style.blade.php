@extends('layouts.app')

@section('title', 'Style des bulletins - KLASSCI')

@push('styles')
<style>
.bst { --bst-primary:#0453cb; --bst-primary-d:#033a8e; --bst-dark:#0f172a; --bst-text:#1e293b; --bst-muted:#64748b; --bst-border:#e2e8f0; --bst-surface:#f8fafc; --bst-success:#10b981; color:var(--bst-text); max-width:1320px; margin:0 auto; padding:clamp(1rem,2.5vw,1.5rem) clamp(.75rem,2.5vw,1.5rem) 2rem; overflow-x:clip; }
.bst *, .bst *::before, .bst *::after { box-sizing:border-box; }
.bst-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:clamp(1.25rem,3vw,2rem) clamp(1rem,3vw,2.5rem) clamp(1.1rem,2.5vw,1.5rem); color:#fff; margin-bottom:1.25rem; box-shadow:0 8px 30px rgba(4,83,203,.18); }
.bst-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.bst-hero-left { display:flex; align-items:center; gap:1rem; min-width:0; }
.bst-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
.bst-hero h1 { font-size:clamp(1.15rem,2.6vw,1.45rem); font-weight:700; color:#fff; margin:0; line-height:1.25; }
.bst-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:.2rem 0 0; }
.bst-btn { display:inline-flex; align-items:center; gap:.45rem; border-radius:10px; padding:.5rem 1rem; font-size:.82rem; font-weight:600; border:1px solid transparent; cursor:pointer; text-decoration:none; transition:all .2s ease; white-space:nowrap; }
.bst-btn--glass { background:rgba(255,255,255,.15); color:#fff; border-color:rgba(255,255,255,.2); }
.bst-btn--glass:hover { background:rgba(255,255,255,.24); color:#fff; }
.bst-btn--primary { background:var(--bst-primary); color:#fff; }
.bst-btn--primary:hover:not(:disabled) { background:var(--bst-primary-d); color:#fff; }
.bst-btn--ghost { background:#fff; color:var(--bst-primary); border-color:rgba(4,83,203,.25); }
.bst-btn--ghost:hover:not(:disabled) { background:rgba(4,83,203,.06); }
.bst-btn:disabled { opacity:.55; cursor:not-allowed; }
.bst-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr)); gap:.75rem; margin-top:1.4rem; }
.bst-kpi { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.85rem 1rem; min-width:0; }
.bst-kpi-label { font-size:.7rem; letter-spacing:.04em; text-transform:uppercase; color:rgba(255,255,255,.7); font-weight:600; }
.bst-kpi-value { font-size:clamp(1.05rem,2.4vw,1.35rem); font-weight:700; margin-top:.25rem; line-height:1.25; }
.bst-kpi-sub { font-size:.74rem; color:rgba(255,255,255,.75); margin-top:.15rem; line-height:1.35; }
.bst-card { background:#fff; border:1px solid var(--bst-border); border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04),0 1px 2px rgba(15,23,42,.06); padding:1.1rem; min-width:0; margin-bottom:1rem; }
.bst-card-title { display:flex; align-items:center; gap:.6rem; margin-bottom:.9rem; }
.bst-card-title h2 { font-size:.98rem; font-weight:700; color:var(--bst-dark); margin:0; }
.bst-card-title span { display:block; font-size:.76rem; color:var(--bst-muted); }
.bst-icon { width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; }
.bst-choix { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr)); gap:1rem; }
.bst-option { position:relative; display:flex; flex-direction:column; border:2px solid var(--bst-border); border-radius:14px; background:var(--bst-surface); cursor:pointer; min-width:0; margin:0; transition:border-color .2s ease, box-shadow .2s ease; }
.bst-option:hover { border-color:#c7d4e5; box-shadow:0 8px 26px rgba(4,83,203,.08); }
.bst-option:focus-within { outline:3px solid rgba(4,83,203,.25); outline-offset:2px; }
.bst-option.is-choisi { border-color:var(--bst-primary); background:#fff; box-shadow:0 8px 30px rgba(4,83,203,.12); }
.bst-option input { position:absolute; opacity:0; pointer-events:none; }
.bst-apercu { display:block; background:#e9eef6; border-radius:12px 12px 0 0; padding:.9rem .9rem 0; overflow:hidden; height:clamp(220px,32vw,330px); }
.bst-apercu img { display:block; width:100%; height:auto; border-radius:6px 6px 0 0; box-shadow:0 4px 18px rgba(15,23,42,.12); background:#fff; }
.bst-option-corps { padding:.9rem 1rem 1rem; display:flex; flex-direction:column; gap:.45rem; }
.bst-option-tete { display:flex; align-items:center; justify-content:space-between; gap:.6rem; flex-wrap:wrap; }
.bst-option-nom { font-size:.95rem; font-weight:700; color:var(--bst-dark); }
.bst-pastille { display:inline-flex; align-items:center; gap:.35rem; font-size:.72rem; font-weight:700; padding:.2rem .55rem; border-radius:999px; white-space:nowrap; }
.bst-pastille[hidden] { display:none; }
.bst-pastille--actif { background:rgba(16,185,129,.1); color:#047857; border:1px solid rgba(16,185,129,.3); }
.bst-pastille--choisi { background:rgba(4,83,203,.08); color:var(--bst-primary); border:1px solid rgba(4,83,203,.25); }
.bst-option ul { margin:0; padding-left:1.05rem; font-size:.82rem; color:var(--bst-muted); line-height:1.5; }
.bst-option-radio { display:flex; align-items:center; gap:.5rem; font-size:.82rem; font-weight:600; color:var(--bst-dark); margin-top:.25rem; }
.bst-rond { width:18px; height:18px; border-radius:50%; border:2px solid #94a3b8; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; transition:border-color .2s ease; }
.bst-option.is-choisi .bst-rond { border-color:var(--bst-primary); }
.bst-option.is-choisi .bst-rond::after { content:''; width:8px; height:8px; border-radius:50%; background:var(--bst-primary); }
.bst-barre { display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; margin-top:1rem; padding-top:1rem; border-top:1px solid var(--bst-border); }
.bst-barre-texte { font-size:.82rem; color:var(--bst-muted); min-width:0; }
.bst-barre-texte strong { color:var(--bst-dark); }
.bst-note { display:flex; gap:.7rem; align-items:flex-start; padding:.85rem 1rem; border-radius:12px; background:rgba(4,83,203,.04); border:1px solid rgba(4,83,203,.2); font-size:.84rem; line-height:1.45; }
.bst-note i { color:var(--bst-primary); margin-top:.15rem; flex-shrink:0; }
.bst-note > div { min-width:0; overflow-wrap:anywhere; }
.bst-note a { color:var(--bst-primary); font-weight:600; }
@media (max-width:576px) {
    .bst-hero-top .bst-btn { width:100%; justify-content:center; }
    .bst-barre .bst-btn { width:100%; justify-content:center; }
}
</style>
@endpush

@section('content')
@php
    $libelleActif = $styles[$currentStyle]['nom'] ?? $styles['yakro']['nom'];
@endphp
<div class="bst" id="bst-page" data-update-url="{{ route('esbtp.bulletin-style.update') }}" data-actif="{{ $currentStyle }}">
    <div class="bst-hero">
        <div class="bst-hero-top">
            <div class="bst-hero-left">
                <div class="bst-hero-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <h1>Style des bulletins</h1>
                    <p>Gabarit des bulletins PDF et de leurs aperçus sur cette instance</p>
                </div>
            </div>
            @if(Route::has('esbtp.bulletins.configuration'))
                <a href="{{ route('esbtp.bulletins.configuration') }}" class="bst-btn bst-btn--glass">
                    <i class="fas fa-sliders-h"></i>Couleurs, police et textes
                </a>
            @endif
        </div>
        <div class="bst-kpis">
            <div class="bst-kpi">
                <div class="bst-kpi-label">Gabarit actif</div>
                <div class="bst-kpi-value" id="bst-kpi-actif">{{ $libelleActif }}</div>
                <div class="bst-kpi-sub">Bulletins, aperçus et génération en masse</div>
            </div>
            <div class="bst-kpi">
                <div class="bst-kpi-label">Gabarits disponibles</div>
                <div class="bst-kpi-value">{{ count($styles) }}</div>
                <div class="bst-kpi-sub">Aperçus réels ci-dessous, données de démonstration</div>
            </div>
            <div class="bst-kpi">
                <div class="bst-kpi-label">Dernière modification</div>
                <div class="bst-kpi-value" id="bst-kpi-modif">{{ $modifieLe ? $modifieLe->format('d/m/Y') : '—' }}</div>
                <div class="bst-kpi-sub" id="bst-kpi-modif-sub">{{ $modifieLe ? $modifieLe->format('H:i') : 'Jamais modifié' }}@if($modifiePar) · {{ $modifiePar }}@endif</div>
            </div>
        </div>
    </div>

    <div class="bst-card">
        <div class="bst-card-title">
            <div class="bst-icon"><i class="fas fa-swatchbook"></i></div>
            <div>
                <h2>Choisir le gabarit</h2>
                <span>Le changement s'applique au prochain bulletin généré ou prévisualisé</span>
            </div>
        </div>

        <form id="bst-form" method="POST" action="{{ route('esbtp.bulletin-style.update') }}">
            @csrf
            <div class="bst-choix" role="radiogroup" aria-label="Gabarit des bulletins">
                @foreach($styles as $cle => $style)
                    <label class="bst-option {{ $currentStyle === $cle ? 'is-choisi' : '' }}" data-style="{{ $cle }}">
                        <input type="radio" name="bulletin_style" id="bulletin_style_{{ $cle }}" value="{{ $cle }}" {{ $currentStyle === $cle ? 'checked' : '' }}>
                        <span class="bst-apercu">
                            <img src="{{ asset($style['image']) }}" alt="Aperçu du {{ $style['nom'] }}, avec des données de démonstration" loading="lazy" width="910" height="1287">
                        </span>
                        <span class="bst-option-corps">
                            <span class="bst-option-tete">
                                <span class="bst-option-nom">{{ $style['nom'] }}</span>
                                <span class="bst-pastille bst-pastille--actif" data-pastille-actif @if($currentStyle !== $cle) hidden @endif><i class="fas fa-check"></i>Actif</span>
                            </span>
                            <ul>
                                @foreach($style['points'] as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                            <span class="bst-option-radio"><span class="bst-rond" aria-hidden="true"></span>Utiliser ce gabarit</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="bst-barre">
                <div class="bst-barre-texte" id="bst-etat" aria-live="polite">Gabarit en place : <strong>{{ $libelleActif }}</strong></div>
                <button type="submit" class="bst-btn bst-btn--primary" id="bst-enregistrer" disabled>
                    <i class="fas fa-check"></i><span>Appliquer ce gabarit</span>
                </button>
            </div>
        </form>
    </div>

    <div class="bst-note">
        <i class="fas fa-info-circle"></i>
        <div>
            Les couleurs, la police, les textes de l'en-tête et le titre du conseil restent ceux de l'école, quel que soit le gabarit.
            @if(Route::has('esbtp.bulletins.configuration'))
                Ils se règlent dans <a href="{{ route('esbtp.bulletins.configuration') }}">la configuration des bulletins</a>.
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('partials._klassci_toast')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const page = document.getElementById('bst-page');
    const form = document.getElementById('bst-form');
    if (!page || !form) { return; }

    const bouton = document.getElementById('bst-enregistrer');
    const etat = document.getElementById('bst-etat');
    const options = Array.from(page.querySelectorAll('.bst-option'));
    const noms = Object.fromEntries(options.map((o) => [o.dataset.style, o.querySelector('.bst-option-nom').textContent.trim()]));
    const toast = (type, message) => (window.klassciToast ? window.klassciToast(type, message) : alert(message));
    let actif = page.dataset.actif;

    function choisi() {
        const radio = form.querySelector('input[name="bulletin_style"]:checked');
        return radio ? radio.value : actif;
    }

    function rafraichir() {
        const valeur = choisi();
        options.forEach((o) => {
            o.classList.toggle('is-choisi', o.dataset.style === valeur);
            o.querySelector('[data-pastille-actif]').hidden = o.dataset.style !== actif;
        });
        bouton.disabled = valeur === actif;
        etat.innerHTML = valeur === actif
            ? 'Gabarit en place : <strong></strong>'
            : 'Gabarit choisi : <strong></strong>, pas encore appliqué';
        etat.querySelector('strong').textContent = noms[valeur] || valeur;
    }

    form.addEventListener('change', rafraichir);

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const valeur = choisi();
        if (valeur === actif) { return; }
        bouton.disabled = true;
        try {
            const res = await fetch(page.dataset.updateUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ bulletin_style: valeur }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.message || (data.errors && Object.values(data.errors)[0][0]) || 'Une erreur est survenue.');
            }
            actif = data.style;
            page.dataset.actif = actif;
            document.getElementById('bst-kpi-actif').textContent = noms[actif] || actif;
            const [jour, reste] = String(data.modifie || '').split(' à ');
            document.getElementById('bst-kpi-modif').textContent = jour || '—';
            document.getElementById('bst-kpi-modif-sub').textContent = reste || '';
            toast('success', data.message);
        } catch (err) {
            toast('error', err.message);
        } finally {
            rafraichir();
        }
    });

    rafraichir();
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'Rendez-vous d\'inscription - KLASSCI')

@push('styles')
<style>
.rdv-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.rdv-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.rdv-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.rdv-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: .35rem 0 0; }
.rdv-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.rdv-kpi {
    flex: 1; min-width: 150px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: .9rem 1rem;
}
.rdv-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
.rdv-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
.rdv-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; text-decoration: none;
}
.rdv-btn--white { background: #fff; color: #0453cb; }
.rdv-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.rdv-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 1.25rem 1.5rem; margin-bottom: 1rem;
}
.rdv-jour-titre { font-size: .95rem; font-weight: 700; color: #1e293b; margin-bottom: .75rem; }
.rdv-slot {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    padding: .7rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
}
.rdv-slot + .rdv-slot { margin-top: .45rem; }
.rdv-slot--ferme { background: #f8fafc; color: #64748b; }
.rdv-heure { font-variant-numeric: tabular-nums; font-weight: 600; color: #1e293b; }
.rdv-badge { font-size: .72rem; font-weight: 600; padding: .15rem .5rem; border-radius: 999px; }
.rdv-badge--ouvert { background: #d1fae5; color: #065f46; }
.rdv-badge--ferme { background: #e2e8f0; color: #475569; }
.rdv-nav { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem; }
.rdv-empty { text-align: center; padding: 2rem 1rem; color: #64748b; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="rdv-hero">
        <div class="rdv-hero-top">
            <div>
                <h1>Rendez-vous d'inscription</h1>
                <p>Planning des créneaux au guichet. La réservation par les familles arrive ensuite.</p>
            </div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                @if($peutConfigurer)
                <a class="rdv-btn rdv-btn--glass" href="#reglages">Réglages des horaires</a>
                @endif
                @if($peutGerer)
                <form method="POST" action="{{ route('esbtp.rendez-vous.generer') }}">
                    @csrf
                    <button type="submit" class="rdv-btn rdv-btn--white">
                        <i class="fas fa-calendar-plus"></i> Générer les créneaux
                    </button>
                </form>
                <form method="POST" action="{{ route('esbtp.rendez-vous.placer') }}"
                      onsubmit="return confirm('Placer les candidatures et demandes en attente sur les créneaux libres, puis envoyer la convocation (mail + PDF) ?');">
                    @csrf
                    <button type="submit" class="rdv-btn rdv-btn--white">
                        <i class="fas fa-envelope-open-text"></i> Placer et convoquer
                    </button>
                </form>
                @endif
            </div>
        </div>
        <div class="rdv-kpis">
            <div class="rdv-kpi">
                <div class="rdv-kpi-value">
                    @if($debit)
                        {{ $debit['personnes_par_jour'] }}
                    @else
                        —
                    @endif
                </div>
                <div class="rdv-kpi-label">
                    @if($debit)
                        {{ $debit['duree'] }} min × {{ $debit['capacite'] }} places × {{ $debit['creneaux_par_jour'] }} créneaux = {{ $debit['personnes_par_jour'] }} personnes / jour
                    @else
                        Remplissez les réglages ci-dessous, puis générez les créneaux
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($peutConfigurer)
    <div class="rdv-card" id="reglages">
        <div class="rdv-jour-titre">Réglages des créneaux</div>
        <p style="color:#64748b;font-size:.88rem;margin:0 0 1rem;">Jours, horaires et nombre de familles par créneau. Enregistrez, puis cliquez sur « Générer les créneaux ».</p>
        <form method="POST" action="{{ route('esbtp.rendez-vous.reglages') }}">
            @csrf
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label" for="rdv-ouv">Premier jour des rendez-vous</label>
                    <input type="date" class="form-control" id="rdv-ouv" name="{{ $rdv::OUVERTURE }}"
                           value="{{ $rdv->valeur($rdv::OUVERTURE) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="rdv-fer">Dernier jour</label>
                    <input type="date" class="form-control" id="rdv-fer" name="{{ $rdv::FERMETURE }}"
                           value="{{ $rdv->valeur($rdv::FERMETURE) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="rdv-hd">Ouverture du guichet</label>
                    <input type="time" class="form-control" id="rdv-hd" name="{{ $rdv::HEURE_DEBUT }}"
                           value="{{ $rdv->valeur($rdv::HEURE_DEBUT) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="rdv-hf">Fermeture</label>
                    <input type="time" class="form-control" id="rdv-hf" name="{{ $rdv::HEURE_FIN }}"
                           value="{{ $rdv->valeur($rdv::HEURE_FIN) }}">
                </div>
                <div class="col-12" style="margin-top:.5rem;">
                    <span class="form-label d-block">Jours ouverts</span>
                    @foreach($rdvJours as $_n => $_lib)
                        <label style="margin-right:.85rem;">
                            <input type="checkbox" name="inscriptions_rdv_jours_ouverts[]" value="{{ $_n }}"
                                   {{ in_array((string) $_n, $rdvJoursChoisis, true) ? 'checked' : '' }}>
                            {{ $_lib }}
                        </label>
                    @endforeach
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="rdv-dur">Durée (min)</label>
                    <input type="number" min="5" class="form-control" id="rdv-dur" name="{{ $rdv::DUREE }}"
                           value="{{ $rdv->valeur($rdv::DUREE, '30') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="rdv-cap">Places / créneau</label>
                    <input type="number" min="1" class="form-control" id="rdv-cap" name="{{ $rdv::CAPACITE }}"
                           value="{{ $rdv->valeur($rdv::CAPACITE, '10') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="rdv-pd">Pause début</label>
                    <input type="time" class="form-control" id="rdv-pd" name="{{ $rdv::PAUSE_DEBUT }}"
                           value="{{ $rdv->valeur($rdv::PAUSE_DEBUT) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="rdv-pf">Pause fin</label>
                    <input type="time" class="form-control" id="rdv-pf" name="{{ $rdv::PAUSE_FIN }}"
                           value="{{ $rdv->valeur($rdv::PAUSE_FIN) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="rdv-min">Délai min (h)</label>
                    <input type="number" min="0" class="form-control" id="rdv-min" name="{{ $rdv::DELAI_MIN }}"
                           value="{{ $rdv->valeur($rdv::DELAI_MIN, '12') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="rdv-mod">Modif. jusqu'à (h)</label>
                    <input type="number" min="0" class="form-control" id="rdv-mod" name="{{ $rdv::DELAI_MODIF }}"
                           value="{{ $rdv->valeur($rdv::DELAI_MODIF, '12') }}">
                </div>
                <div class="col-12" style="margin-top:.6rem;">
                    <label>
                        <input type="checkbox" name="{{ $rdv::ENABLED }}" value="1"
                               {{ $rdv->valeur($rdv::ENABLED, '0') === '1' ? 'checked' : '' }}>
                        Ouvrir la prise de rendez-vous aux familles
                    </label>
                </div>
                <div class="col-12" style="margin-top:.75rem;">
                    <button type="submit" class="rdv-btn rdv-btn--white" style="background:#0453cb;color:#fff;">
                        Enregistrer les réglages
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif

    <div class="rdv-nav">
        <a class="rdv-btn rdv-btn--glass" style="background:#fff;color:#0453cb;border-color:#e2e8f0;"
           href="{{ route('esbtp.rendez-vous.index', ['debut' => $semainePrecedente]) }}">Semaine précédente</a>
        <strong>{{ $debut->translatedFormat('j F') }} — {{ $fin->translatedFormat('j F Y') }}</strong>
        <a class="rdv-btn rdv-btn--glass" style="background:#fff;color:#0453cb;border-color:#e2e8f0;"
           href="{{ route('esbtp.rendez-vous.index', ['debut' => $semaineSuivante]) }}">Semaine suivante</a>
    </div>

    @foreach($jours as $jour)
        <div class="rdv-card">
            <div class="rdv-jour-titre">{{ $jour['libelle'] }}</div>
            @forelse($jour['creneaux'] as $creneau)
                <div class="rdv-slot {{ $creneau->ouvert ? '' : 'rdv-slot--ferme' }}">
                    <div>
                        <span class="rdv-heure">{{ $creneau->heureDebutHi() }} – {{ $creneau->heureFinHi() }}</span>
                        <span class="rdv-badge {{ $creneau->ouvert ? 'rdv-badge--ouvert' : 'rdv-badge--ferme' }}">
                            {{ $creneau->ouvert ? 'Ouvert' : 'Fermé' }}
                        </span>
                        <span style="font-size:.8rem;color:#64748b;margin-left:.4rem;">{{ $creneau->capacite }} places</span>
                    </div>
                    @if($peutGerer)
                        @if($creneau->ouvert)
                            <form method="POST" action="{{ route('esbtp.rendez-vous.fermer', $creneau) }}">
                                @csrf
                                <button type="submit" class="rdv-btn" style="background:#fff;color:#475569;border-color:#e2e8f0;">Fermer</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('esbtp.rendez-vous.ouvrir', $creneau) }}">
                                @csrf
                                <button type="submit" class="rdv-btn rdv-btn--white">Ouvrir</button>
                            </form>
                        @endif
                    @endif
                </div>
            @empty
                <div class="rdv-empty">Aucun créneau ce jour.</div>
            @endforelse
        </div>
    @endforeach
</div>
@endsection

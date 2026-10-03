@extends('layouts.app')

@section('title', 'Professeurs du bulletin LMD')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="lpf-hero">
            <div>
                <div class="lpf-kicker">Bulletin LMD · source planning</div>
                <h1>Professeurs du semestre</h1>
                <p>{{ $classe->name }} · S{{ $semestre }} · {{ $annee->name }}</p>
            </div>
            <a class="lpf-back" href="{{ route('esbtp.lmd.bulletins.index', ['classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id, 'semestre' => $semestre]) }}">
                <i class="fas fa-arrow-left"></i> Bulletins
            </a>
        </div>

        <div class="lpf-note">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong>Une seule affectation officielle.</strong>
                Le professeur affiché ici est enregistré dans la planification LMD de l'ECUE. Les nouvelles évaluations le reprennent automatiquement et les bulletins brouillons actualisent leur snapshot. Un bulletin déjà publié reste figé.
            </div>
        </div>

        <form id="lpf-form" action="{{ route('esbtp.lmd.bulletins.professeurs.save') }}" method="POST">
            @csrf
            <input type="hidden" name="classe_id" value="{{ $classe->id }}">
            <input type="hidden" name="annee_universitaire_id" value="{{ $annee->id }}">
            <input type="hidden" name="semestre" value="{{ $semestre }}">

            <div class="lpf-card">
                <div class="lpf-head">
                    <span>Élément de la maquette</span>
                    <span>Enseignant officiel</span>
                    <span>Source</span>
                </div>
                @forelse($elements as $element)
                    <div class="lpf-row">
                        <div>
                            <div class="lpf-code">{{ $element['code'] ?: 'ECUE' }}</div>
                            <div class="lpf-name">{{ $element['name'] }}</div>
                            <div class="lpf-ue">{{ $element['ue'] }}</div>
                        </div>
                        <div>
                            @if($canEdit && $element['assignable_rapidement'])
                                <select class="form-select" name="professeurs[{{ $element['matiere_id'] }}]">
                                    <option value="">— Choisir un enseignant —</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" @selected((int) $element['enseignant_id'] === (int) $teacher->id)>
                                            {{ $teacher->name }}@if($teacher->email) · {{ $teacher->email }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            @elseif(!$element['assignable_rapidement'])
                                <div class="lpf-missing">La ligne de planification n'existe pas encore.</div>
                                <a class="lpf-link" href="{{ route('esbtp.lmd.planning.index', ['parcours_id' => $classe->parcours_id, 'niveau_id' => $classe->niveau_etude_id, 'semestre' => $semestre]) }}">Configurer le planning</a>
                            @else
                                <div class="lpf-current">{{ $element['enseignant_nom'] ?: 'Non affecté' }}</div>
                            @endif
                        </div>
                        <div>
                            @if($element['source_enseignant'] === 'planning')
                                <span class="lpf-badge lpf-badge--ok">Planning LMD</span>
                            @elseif($element['source_enseignant'] === 'evaluations')
                                <span class="lpf-badge">Repli évaluations</span>
                            @else
                                <span class="lpf-badge lpf-badge--missing">À affecter</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="lpf-empty">Aucun ECUE dans la maquette pour S{{ $semestre }}.</div>
                @endforelse
            </div>

            @if($canEdit)
                <div class="lpf-actions">
                    <div id="lpf-status" role="status"></div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Enregistrer les affectations</button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.lpf-hero{background:linear-gradient(135deg,#063f9c,#0453cb);color:#fff;border-radius:18px;padding:1.5rem 1.75rem;margin-bottom:1rem;display:flex;justify-content:space-between;gap:1rem;align-items:center}.lpf-kicker{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;opacity:.75}.lpf-hero h1{color:#fff;font-size:1.45rem;margin:.15rem 0}.lpf-hero p{margin:0;opacity:.8}.lpf-back{color:#fff;text-decoration:none;background:rgba(255,255,255,.13);padding:.6rem .85rem;border-radius:10px}.lpf-note{display:flex;gap:.75rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:1rem;color:#1e3a5f;margin-bottom:1rem}.lpf-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden}.lpf-head,.lpf-row{display:grid;grid-template-columns:minmax(260px,1.4fr) minmax(260px,1fr) 150px;gap:1rem;align-items:center}.lpf-head{padding:.75rem 1rem;background:#f8fafc;font-size:.75rem;font-weight:700;text-transform:uppercase;color:#64748b}.lpf-row{padding:1rem;border-top:1px solid #eef2f7}.lpf-code{color:#0453cb;font-size:.72rem;font-weight:800}.lpf-name{font-weight:700;color:#0f172a}.lpf-ue{font-size:.78rem;color:#64748b;margin-top:.2rem}.lpf-badge{display:inline-flex;padding:.3rem .55rem;border-radius:999px;background:#f1f5f9;color:#475569;font-size:.72rem;font-weight:700}.lpf-badge--ok{background:#ecfdf5;color:#047857}.lpf-badge--missing{background:#fef2f2;color:#b91c1c}.lpf-missing{font-size:.82rem;color:#b91c1c}.lpf-link{font-size:.78rem;color:#0453cb;font-weight:700}.lpf-current{font-weight:600}.lpf-empty{padding:2rem;text-align:center;color:#64748b}.lpf-actions{display:flex;justify-content:flex-end;align-items:center;gap:1rem;padding:1rem 0}.lpf-actions #lpf-status{color:#047857;font-size:.84rem}@media(max-width:900px){.lpf-head{display:none}.lpf-row{grid-template-columns:1fr}.lpf-hero{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@push('scripts')
<script>
(function(){
    const form=document.getElementById('lpf-form'); if(!form) return;
    form.addEventListener('submit',async function(e){
        e.preventDefault(); const button=form.querySelector('[type="submit"]'); const status=document.getElementById('lpf-status');
        button.disabled=true; status.textContent='Enregistrement…';
        try{
            const res=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
            const json=await res.json(); if(!res.ok) throw new Error(json.message||Object.values(json.errors||{})[0]?.[0]||'Enregistrement impossible');
            status.textContent=json.message; setTimeout(()=>location.reload(),500);
        }catch(err){status.textContent=err.message; status.style.color='#b91c1c';}finally{button.disabled=false;}
    });
})();
</script>
@endpush

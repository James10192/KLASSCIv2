@extends('layouts.app')

@section('title', 'Professeurs LMD par classe')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="lpf-hero">
            <div>
                <div class="lpf-kicker">Planning LMD · résolution par classe</div>
                <h1>Professeurs du semestre</h1>
                <p>{{ $classe->name }} · S{{ $semestre }} · {{ $annee->name }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="lpf-back" href="{{ route('esbtp.lmd.planning.index', ['parcours_id' => $classe->parcours_id, 'niveau_id' => $classe->niveau_etude_id, 'semestre' => $semestre]) }}">
                    <i class="fas fa-calendar-alt"></i> Planning LMD
                </a>
                <a class="lpf-back" href="{{ route('esbtp.lmd.bulletins.index', ['classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id, 'semestre' => $semestre]) }}">
                    <i class="fas fa-arrow-left"></i> Bulletins
                </a>
            </div>
        </div>

        <div class="lpf-note">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong>Le planning porte un pool, la classe porte le professeur réel.</strong>
                Un même ECUE peut avoir plusieurs professeurs disponibles parce qu'un parcours comporte plusieurs classes. KLASSCI regarde ensuite les évaluations et les séances de <strong>{{ $classe->name }}</strong> pour reconnaître le professeur réel. En cas de contradiction, vous confirmez une seule fois et les traces de cette classe sont harmonisées.
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
                    <span>Pool du planning</span>
                    <span>Professeur de cette classe</span>
                    <span>État</span>
                </div>
                @forelse($elements as $element)
                    <div class="lpf-row" data-lpf-row data-matiere-id="{{ $element['matiere_id'] }}" data-planification-id="{{ $element['planification_id'] ?? '' }}">
                        <div>
                            <div class="lpf-code">{{ $element['code'] ?: 'ECUE' }}</div>
                            <div class="lpf-name">{{ $element['name'] }}</div>
                            <div class="lpf-ue">{{ $element['ue'] }}</div>
                        </div>

                        <div>
                            <div class="lpf-pool" data-lpf-pool-label>
                                @forelse($element['pool'] as $prof)
                                    <span class="lpf-prof-chip"><i class="fas fa-user"></i>{{ $prof['name'] }}</span>
                                @empty
                                    <span class="lpf-missing">Aucun professeur prévu</span>
                                @endforelse
                            </div>
                            @if($canEdit && $element['assignable_rapidement'])
                                <button type="button" class="lpf-link-btn mt-2"
                                    data-lpf-pool-edit
                                    data-pool='@json($element['pool_ids'])'
                                    data-label="{{ $element['code'] ?: $element['name'] }}">
                                    <i class="fas fa-users-cog"></i> Modifier le pool
                                </button>
                            @elseif(!$element['assignable_rapidement'])
                                <a class="lpf-link" href="{{ route('esbtp.lmd.planning.index', ['parcours_id' => $classe->parcours_id, 'niveau_id' => $classe->niveau_etude_id, 'semestre' => $semestre]) }}">Créer la ligne de planning</a>
                            @endif
                        </div>

                        <div>
                            @if($canEdit && $element['assignable_rapidement'])
                                <select class="form-select" name="professeurs[{{ $element['matiere_id'] }}]">
                                    <option value="">— À confirmer —</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" @selected((int) $element['enseignant_id'] === (int) $teacher->id)>
                                            {{ $teacher->name }}@if($teacher->email) · {{ $teacher->email }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="lpf-help">{{ $element['message'] }}</div>
                            @else
                                <div class="lpf-current">{{ $element['enseignant_nom'] ?: 'À confirmer' }}</div>
                            @endif
                        </div>

                        <div>
                            @if($element['conflit'])
                                <span class="lpf-badge lpf-badge--danger"><i class="fas fa-triangle-exclamation"></i> Conflit</span>
                            @elseif(!$element['confirmation_requise'] && $element['source_enseignant'] === 'classe')
                                <span class="lpf-badge lpf-badge--ok"><i class="fas fa-check"></i> Confirmé par la classe</span>
                            @elseif(!$element['confirmation_requise'] && $element['source_enseignant'] === 'planning_unique')
                                <span class="lpf-badge"><i class="fas fa-wand-magic-sparkles"></i> Auto · pool unique</span>
                            @elseif($element['source_enseignant'] === 'planning_pool')
                                <span class="lpf-badge lpf-badge--warn"><i class="fas fa-users"></i> Choix requis</span>
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
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check-double me-1"></i> Confirmer les professeurs de la classe</button>
                </div>
            @endif
        </form>
    </div>
</div>

@if($canEdit)
<div class="modal fade" id="lpfPoolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><div><h5 class="modal-title mb-0">Professeurs possibles dans le planning</h5><div class="small text-muted" id="lpfPoolContext"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="small text-muted">Cochez tous les professeurs susceptibles d'assurer cet ECUE dans les différentes classes du parcours. Le professeur exact de chaque classe sera confirmé séparément.</p>
            <input type="search" class="form-control mb-3" id="lpfPoolSearch" placeholder="Rechercher un enseignant…">
            <div class="lpf-check-list" id="lpfPoolList">
                @foreach($teachers as $teacher)
                    <label class="lpf-check-item" data-search="{{ mb_strtolower($teacher->name.' '.$teacher->email, 'UTF-8') }}">
                        <input type="checkbox" value="{{ $teacher->id }}">
                        <span><strong>{{ $teacher->name }}</strong>@if($teacher->email)<small>{{ $teacher->email }}</small>@endif</span>
                    </label>
                @endforeach
            </div>
            <div class="alert alert-danger d-none mt-3" id="lpfPoolError"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><button type="button" class="btn btn-primary" id="lpfPoolSave"><i class="fas fa-save me-1"></i> Enregistrer le pool</button></div>
    </div></div>
</div>
@endif
@endsection

@push('styles')
<style>
.lpf-hero{background:linear-gradient(135deg,#063f9c,#0453cb);color:#fff;border-radius:18px;padding:1.5rem 1.75rem;margin-bottom:1rem;display:flex;justify-content:space-between;gap:1rem;align-items:center}.lpf-kicker{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;opacity:.75}.lpf-hero h1{color:#fff;font-size:1.45rem;margin:.15rem 0}.lpf-hero p{margin:0;opacity:.8}.lpf-back{color:#fff;text-decoration:none;background:rgba(255,255,255,.13);padding:.6rem .85rem;border-radius:10px}.lpf-note{display:flex;gap:.75rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:1rem;color:#1e3a5f;margin-bottom:1rem}.lpf-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden}.lpf-head,.lpf-row{display:grid;grid-template-columns:minmax(210px,1.2fr) minmax(220px,1fr) minmax(250px,1fr) 160px;gap:1rem;align-items:center}.lpf-head{padding:.75rem 1rem;background:#f8fafc;font-size:.72rem;font-weight:700;text-transform:uppercase;color:#64748b}.lpf-row{padding:1rem;border-top:1px solid #eef2f7}.lpf-code{color:#0453cb;font-size:.72rem;font-weight:800}.lpf-name{font-weight:700;color:#0f172a}.lpf-ue,.lpf-help{font-size:.76rem;color:#64748b;margin-top:.25rem}.lpf-pool{display:flex;gap:.35rem;flex-wrap:wrap}.lpf-prof-chip{display:inline-flex;align-items:center;gap:.3rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:999px;padding:.25rem .5rem;font-size:.72rem;font-weight:600}.lpf-badge{display:inline-flex;gap:.3rem;align-items:center;padding:.3rem .55rem;border-radius:999px;background:#f1f5f9;color:#475569;font-size:.7rem;font-weight:700}.lpf-badge--ok{background:#ecfdf5;color:#047857}.lpf-badge--warn{background:#fffbeb;color:#b45309}.lpf-badge--danger,.lpf-badge--missing{background:#fef2f2;color:#b91c1c}.lpf-missing{font-size:.82rem;color:#b91c1c}.lpf-link,.lpf-link-btn{font-size:.78rem;color:#0453cb;font-weight:700}.lpf-link-btn{border:0;background:transparent;padding:0}.lpf-current{font-weight:600}.lpf-empty{padding:2rem;text-align:center;color:#64748b}.lpf-actions{display:flex;justify-content:flex-end;align-items:center;gap:1rem;padding:1rem 0}.lpf-actions #lpf-status{color:#047857;font-size:.84rem}.lpf-check-list{max-height:330px;overflow:auto;border:1px solid #e2e8f0;border-radius:12px}.lpf-check-item{display:flex;gap:.75rem;align-items:center;padding:.75rem 1rem;border-bottom:1px solid #eef2f7;cursor:pointer}.lpf-check-item:last-child{border-bottom:0}.lpf-check-item:hover{background:#f8fafc}.lpf-check-item span{display:flex;flex-direction:column}.lpf-check-item small{color:#64748b}@media(max-width:1050px){.lpf-head{display:none}.lpf-row{grid-template-columns:1fr}.lpf-hero{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@push('scripts')
<script>
(function(){
    const form=document.getElementById('lpf-form'); if(!form) return;
    const csrf=form.querySelector('[name="_token"]').value;
    form.addEventListener('submit',async function(e){
        e.preventDefault(); const button=form.querySelector('[type="submit"]'); const status=document.getElementById('lpf-status');
        button.disabled=true; status.style.color=''; status.textContent='Confirmation…';
        try{
            const res=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
            const json=await res.json(); if(!res.ok) throw new Error(json.message||Object.values(json.errors||{})[0]?.[0]||'Enregistrement impossible');
            status.textContent=json.message; setTimeout(()=>location.reload(),500);
        }catch(err){status.textContent=err.message; status.style.color='#b91c1c';}finally{button.disabled=false;}
    });

    const modalEl=document.getElementById('lpfPoolModal'); if(!modalEl) return;
    const modal=bootstrap.Modal.getOrCreateInstance(modalEl); const list=document.getElementById('lpfPoolList'); const search=document.getElementById('lpfPoolSearch'); const error=document.getElementById('lpfPoolError'); let activeRow=null;
    document.querySelectorAll('[data-lpf-pool-edit]').forEach(btn=>btn.addEventListener('click',()=>{
        activeRow=btn.closest('[data-lpf-row]'); const ids=JSON.parse(btn.dataset.pool||'[]').map(String); document.getElementById('lpfPoolContext').textContent=btn.dataset.label||'';
        list.querySelectorAll('input[type="checkbox"]').forEach(c=>c.checked=ids.includes(c.value)); search.value=''; list.querySelectorAll('.lpf-check-item').forEach(i=>i.style.display=''); error.classList.add('d-none'); modal.show();
    }));
    search.addEventListener('input',()=>{const q=search.value.trim().toLocaleLowerCase('fr');list.querySelectorAll('.lpf-check-item').forEach(i=>i.style.display=!q||i.dataset.search.includes(q)?'':'none');});
    document.getElementById('lpfPoolSave').addEventListener('click',async function(){
        if(!activeRow)return; this.disabled=true; error.classList.add('d-none'); const ids=Array.from(list.querySelectorAll('input:checked')).map(i=>i.value);
        try{
            const res=await fetch('{{ route('esbtp.lmd.evaluation-teacher.pool') }}',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({classe_id:{{ (int)$classe->id }},annee_universitaire_id:{{ (int)$annee->id }},periode:'S{{ (int)$semestre }}',matiere_id:activeRow.dataset.matiereId,enseignant_ids:ids})});
            const json=await res.json(); if(!res.ok)throw new Error(json.message||Object.values(json.errors||{})[0]?.[0]||'Enregistrement impossible'); modal.hide(); location.reload();
        }catch(e){error.textContent=e.message;error.classList.remove('d-none');}finally{this.disabled=false;}
    });
})();
</script>
@endpush

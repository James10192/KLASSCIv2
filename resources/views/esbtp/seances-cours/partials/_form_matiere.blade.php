{{-- Picker matière conditionnel LMD vs BTS pour /esbtp/seances-cours/create --}}
{{-- Recoit : $matieres (collection planificationData), $emploiTemps --}}
{{-- LMD : <x-sce-ecue-picker> avec recherche + groupement par UE (premium custom) --}}
{{-- BTS : <select> natif stylé .sce-matiere-select (preserve JS legacy data-* hooks) --}}
@php
    $isLmd = ($emploiTemps->classe->systeme_academique ?? '') === 'LMD'
        || in_array($emploiTemps->classe->niveau->type ?? '', \App\Models\ESBTPNiveauEtude::CYCLES_LMD, true);
@endphp

<div class="form-group">
    <label for="matiere_id" class="sce-form-label">
        Matière <span class="text-danger">*</span>
        @if($isLmd)
            <span class="sce-form-label-chip"><i class="fas fa-university"></i>LMD — ECUE enrichie</span>
        @endif
    </label>

    @if($isLmd)
        <x-sce-ecue-picker
            name="matiere_id"
            :matieres="$matieres"
            :value="old('matiere_id', isset($seancesCour) ? $seancesCour->matiere_id : null)"
            :required="true"
            placeholder="Rechercher une ECUE par code, nom ou UE…"
            onchange-js="updateTeachersForSubject();" />
    @else
        <div class="sce-select-wrap">
            <i class="fas fa-graduation-cap sce-select-icon"></i>
            <select name="matiere_id" id="matiere_id"
                    class="sce-matiere-select @error('matiere_id') is-invalid @enderror"
                    onchange="updateTeachersForSubject()"
                    required>
                <option value="">Sélectionner une matière...</option>
                @foreach($matieres as $matiere)
                    @php
                        $m = $matiere['matiere'];
                        $heuresRestantes = $matiere['heures_restantes_formatted'] ?? $matiere['heures_restantes'];
                        $volumeTotal = $matiere['volume_horaire_total_formatted'] ?? $matiere['volume_horaire_total'];
                        $optLabel = $m->name . '   (' . $heuresRestantes . ' restantes / ' . $volumeTotal . ')';
                    @endphp
                    <option value="{{ $m->id }}"
                            data-heures-restantes="{{ $matiere['heures_restantes'] }}"
                            data-heures-restantes-formatted="{{ $matiere['heures_restantes_formatted'] ?? $matiere['heures_restantes'] }}"
                            data-volume-total="{{ $matiere['volume_horaire_total'] }}"
                            data-volume-total-formatted="{{ $matiere['volume_horaire_total_formatted'] ?? $matiere['volume_horaire_total'] }}"
                            data-enseignants="{{ ($matiere['enseignants_selectables'] ?? collect())->pluck('id')->toJson() }}"
                            data-planification-id="{{ $matiere['planification_id'] ?? '' }}"
                            {{ (string) old('matiere_id', isset($seancesCour) ? $seancesCour->matiere_id : '') === (string) $m->id ? 'selected' : '' }}>
                        {{ $optLabel }}
                    </option>
                @endforeach
            </select>
            <i class="fas fa-chevron-down sce-select-caret"></i>
        </div>
    @endif

    <div id="matiere-info" class="sce-form-info" style="display: none;">
        <i class="fas fa-clock"></i>
        <span id="heures-restantes-text"></span>
    </div>
    @error('matiere_id')
        <div class="form-error">{{ $message }}</div>
    @enderror
</div>

@if($isLmd)
{{-- Assistant professeur LMD : injecté depuis ce partial pour ne pas dupliquer le
     gros formulaire de séance ni toucher au flux BTS. --}}
<div id="lmd-session-teacher-helper"
     data-context-url="{{ route('esbtp.lmd.session-teacher.context') }}"
     data-classe-id="{{ $emploiTemps->classe_id }}"
     data-annee-id="{{ $emploiTemps->annee_universitaire_id }}"
     style="display:none"></div>

@push('scripts')
<script>
(function(){
    if(window.__lmdSessionTeacherHelper) return;
    window.__lmdSessionTeacherHelper=true;
    const helper=document.getElementById('lmd-session-teacher-helper'); if(!helper)return;
    const form=document.getElementById('sessionForm'); const matiere=form?.querySelector('[name="matiere_id"]');
    const teacher=form?.querySelector('[name="teacher_id"]'); const csrf=form?.querySelector('[name="_token"]')?.value;
    if(!form||!matiere||!teacher)return;
    let ctx=null, timer=null;

    const box=document.createElement('div'); box.className='alert alert-info mt-2 mb-0'; box.style.display='none';
    teacher.closest('.form-group')?.appendChild(box);

    function esc(v){const d=document.createElement('div');d.textContent=v==null?'':String(v);return d.innerHTML;}
    function norm(v){return String(v||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/\s+/g,' ').trim();}
    function setTeacher(profileId,name){
        if(!profileId)return false;
        let option=Array.from(teacher.options).find(o=>String(o.value)===String(profileId));
        if(!option){option=new Option(name||'Enseignant confirmé',profileId);teacher.add(option);}
        teacher.value=String(profileId); teacher.dispatchEvent(new Event('change',{bubbles:true})); return true;
    }

    async function load(){
        const matiereId=matiere.value; if(!matiereId){box.style.display='none';ctx=null;return;}
        try{
            const p=new URLSearchParams({classe_id:helper.dataset.classeId,annee_universitaire_id:helper.dataset.anneeId,matiere_id:matiereId});
            const r=await fetch(helper.dataset.contextUrl+'?'+p,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
            ctx=await r.json(); if(!r.ok||!ctx.is_lmd)throw new Error(ctx.message||'Contexte professeur indisponible'); render();
        }catch(e){box.className='alert alert-warning mt-2 mb-0';box.innerHTML='<i class="fas fa-triangle-exclamation me-1"></i>'+esc(e.message);box.style.display='block';}
    }

    function render(){
        box.className=ctx.conflit?'alert alert-warning mt-2 mb-0':'alert alert-info mt-2 mb-0'; box.style.display='block';
        if(ctx.teacher_id&&!ctx.confirmation_requise){
            setTimeout(()=>setTeacher(ctx.teacher_id,ctx.enseignant_nom),50);
            box.innerHTML='<i class="fas fa-user-check me-1"></i><strong>'+esc(ctx.enseignant_nom)+'</strong> · '+esc(ctx.message||'professeur résolu pour cette classe'); return;
        }
        const action=ctx.can_assign?'<button type="button" class="btn btn-sm btn-outline-primary ms-2" data-lst-open><i class="fas fa-user-check me-1"></i>Choisir / confirmer</button>':'';
        box.innerHTML='<i class="fas fa-users me-1"></i>'+esc(ctx.message||'Professeur à confirmer pour cette classe.')+action;
        box.querySelector('[data-lst-open]')?.addEventListener('click',openModal);
    }

    function modal(){
        let el=document.getElementById('lmdSessionTeacherModal'); if(el)return el;
        el=document.createElement('div');el.className='modal fade';el.id='lmdSessionTeacherModal';el.tabIndex=-1;
        el.innerHTML='<div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title mb-0">Professeur de cette classe</h5><div class="small text-muted" data-lst-context></div></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div data-lst-candidates class="d-grid gap-2 mb-3"></div><div class="border-top pt-3"><label class="form-label fw-semibold">Rechercher un enseignant existant ou saisir une nouvelle personne</label><input class="form-control" type="search" data-lst-search placeholder="Nom de l’enseignant…"><div class="list-group mt-2 d-none" data-lst-results></div><div class="mt-2 d-none" data-lst-create><div class="row g-2"><div class="col-md-7"><input class="form-control" data-lst-name placeholder="Nom complet"></div><div class="col-md-5"><input class="form-control" type="email" data-lst-email placeholder="E-mail (optionnel)"></div></div><button type="button" class="btn btn-outline-primary btn-sm mt-2" data-lst-create-btn><i class="fas fa-user-plus me-1"></i>C’est une autre personne : créer</button></div></div><div class="alert alert-danger d-none mt-3" data-lst-error></div></div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button></div></div></div>';
        document.body.appendChild(el); return el;
    }

    function openModal(){
        if(!ctx)return; const el=modal(); const candidates=(ctx.candidats?.length?ctx.candidats:ctx.pool)||[];
        el.querySelector('[data-lst-context]').textContent=ctx.classe.name+' · S'+ctx.semestre;
        el.querySelector('[data-lst-candidates]').innerHTML=candidates.length?candidates.map(u=>'<button type="button" class="btn btn-outline-primary text-start" data-user="'+u.id+'" data-name="'+esc(u.name)+'"><strong>'+esc(u.name)+'</strong>'+(u.email?'<span class="small text-muted ms-2">'+esc(u.email)+'</span>':'')+'</button>').join(''):'<div class="text-muted small">Aucun professeur proposé dans le planning.</div>';
        el.querySelectorAll('[data-user]').forEach(b=>b.onclick=()=>confirmUser(b.dataset.user,b.dataset.name,el));
        const search=el.querySelector('[data-lst-search]'), results=el.querySelector('[data-lst-results]'), create=el.querySelector('[data-lst-create]'); search.value='';results.classList.add('d-none');create.classList.add('d-none');
        search.oninput=()=>{clearTimeout(timer);const q=search.value.trim();if(q.length<2){results.classList.add('d-none');create.classList.add('d-none');return;}timer=setTimeout(()=>searchExisting(q,el),300);};
        el.querySelector('[data-lst-create-btn]').onclick=()=>quickCreate(el);
        bootstrap.Modal.getOrCreateInstance(el).show();
    }

    async function searchExisting(q,el){
        const results=el.querySelector('[data-lst-results]'), create=el.querySelector('[data-lst-create]');
        try{const u=new URL(ctx.duplicate_search_url,location.origin);u.searchParams.set('name',q);const r=await fetch(u,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});const j=await r.json();const d=j.duplicates||[];
            results.innerHTML=d.map(x=>'<button type="button" class="list-group-item list-group-item-action" data-name="'+esc(x.name)+'"><strong>'+esc(x.name)+'</strong>'+(x.email?'<small class="d-block text-muted">'+esc(x.email)+'</small>':'')+'</button>').join('');results.classList.toggle('d-none',!d.length);
            results.querySelectorAll('button').forEach(b=>b.onclick=()=>{const m=(ctx.teachers||[]).find(x=>norm(x.name)===norm(b.dataset.name));if(m)confirmUser(m.id,m.name,el);});
        }catch(e){results.classList.add('d-none');}
        if(ctx.can_create_teacher){create.classList.remove('d-none');el.querySelector('[data-lst-name]').value=q;}
    }

    async function quickCreate(el){
        const name=el.querySelector('[data-lst-name]').value.trim(), email=el.querySelector('[data-lst-email]').value.trim();if(!name)return;
        const error=el.querySelector('[data-lst-error]');try{const r=await fetch(ctx.quick_create_url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({name,email:email||null,specialization:'Enseignement LMD'})});const j=await r.json();if(!r.ok||!j.success)throw new Error(j.message||'Création impossible');if(!j.teacher?.user_id)throw new Error('Compte utilisateur enseignant introuvable');await confirmUser(j.teacher.user_id,j.teacher.user?.name||name,el);}catch(e){error.textContent=e.message;error.classList.remove('d-none');}}

    async function confirmUser(userId,name,el){
        const error=el.querySelector('[data-lst-error]');error.classList.add('d-none');try{const r=await fetch(ctx.assign_url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({classe_id:helper.dataset.classeId,annee_universitaire_id:helper.dataset.anneeId,periode:'S'+ctx.semestre,matiere_id:matiere.value,enseignant_id:userId})});const j=await r.json();if(!r.ok)throw new Error(j.message||Object.values(j.errors||{})[0]?.[0]||'Confirmation impossible');if(!j.teacher_profile_id)throw new Error('La personne est confirmée mais sa fiche enseignant doit être complétée avant de programmer une séance.');setTeacher(j.teacher_profile_id,j.enseignant_name||name);bootstrap.Modal.getOrCreateInstance(el).hide();await load();}catch(e){error.textContent=e.message;error.classList.remove('d-none');}}

    matiere.addEventListener('change',()=>setTimeout(load,0));
    if(matiere.value) setTimeout(load,100);
})();
</script>
@endpush
@endif
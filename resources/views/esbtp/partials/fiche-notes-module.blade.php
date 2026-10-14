{{-- Composant léger chargé par AJAX dans les fiches étudiant / inscription. --}}
<section class="fng-module" data-fng-module data-url="{{ $url }}">
    <div class="fng-loading" role="status"><i class="fas fa-circle-notch fa-spin"></i> Chargement des évaluations et notes...</div>
</section>
<style>
.fng-module{--fng-blue:#0453cb;--fng-ink:#17243b;--fng-muted:#64748b;margin:16px 0;font-family:inherit}
.fng-module *{box-sizing:border-box}
.fng-loading,.fng-empty{padding:26px;text-align:center;color:#718096;font-size:14px}
.fng-loading i,.fng-empty i{margin-right:8px;color:var(--fng-blue)}
.fng-head{padding:9px 3px 13px;color:#17243b;display:flex;justify-content:space-between;gap:12px;align-items:center;border-bottom:1px solid #e2e8f0}
.fng-head h3{margin:3px 0 4px;font-size:17px;font-weight:800;color:#162841;letter-spacing:-.35px}
.fng-head p{margin:0;color:#64748b;font-size:12px}
.fng-eyebrow{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#0453cb}
.fng-pill{border-radius:99px;background:#eef5ff;color:#2257a5;padding:6px 10px;white-space:nowrap;font-weight:700;font-size:11px}
.fng-block{border:1px solid #e5eaf3;border-radius:12px;background:#fff;box-shadow:none;overflow:hidden;margin-top:10px}
.fng-block-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;padding:19px 20px;border-bottom:1px solid #e9eef5}
.fng-block-head strong{display:block;color:#17243b;font-size:16px;font-weight:800}
.fng-block-head small{display:block;color:#64748b;font-size:12px;margin-top:5px}
.fng-actions{display:flex;gap:6px;align-items:center;justify-content:flex-end;flex-wrap:wrap}
.fng-btn,.fng-icon-btn{background:#f8faff;color:#325176;border:1px solid #e2e8f2;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:7px;font-weight:700;font-size:12px;text-decoration:none;cursor:pointer;transition:all .15s}
.fng-btn{padding:9px 11px}.fng-btn:hover,.fng-icon-btn:hover{border-color:#adc7ed;background:#edf4ff;color:#0453cb}
.fng-btn-primary{background:#0453cb;border-color:#0453cb;color:#fff}.fng-btn-primary:hover{background:#003b9e;color:white}
.fng-icon-btn{width:33px;height:33px}.fng-danger:hover{color:#dc2626;border-color:#fecaca;background:#fff1f2}
.fng-scroll{width:100%;overflow-x:auto}.fng-table{border-collapse:collapse;width:100%;min-width:710px;text-align:left}
.fng-table th{padding:12px 14px;background:#f7f9fd;color:#748399;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px}
.fng-table td{padding:13px 14px;border-top:1px solid #edf0f5;vertical-align:middle;color:#243047;font-size:12px}
.fng-table td strong{font-size:12px;font-weight:750}.fng-table td small{display:block;margin-top:5px;color:#8593a7}
.fng-table tbody tr:hover{background:#fafcff}.fng-right{text-align:right}
.fng-score{font-weight:800;color:#0453cb;font-size:14px;font-variant-numeric:tabular-nums}
.fng-muted{color:#96a3b5}.fng-status{display:inline-block;background:#fff4e6;color:#ae650e;padding:4px 8px;border-radius:30px;font-size:11px;font-weight:700}
.fng-status-ok{color:#0b8064;background:#e8f8f3}
.fng-notice{border-radius:12px;padding:12px 15px;margin:12px 0;font-size:13px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8}
.fng-notice-error{border-color:#fecaca;background:#fff1f2;color:#be123c}
.fng-dialog{border:none;border-radius:20px;box-shadow:0 30px 100px rgba(13,33,65,.35);width:min(490px,calc(100vw - 24px));max-height:85vh;overflow:auto;color:#17243b;padding:0}
.fng-dialog::backdrop{background:rgba(8,25,51,.55);backdrop-filter:blur(3px)}
.fng-dialog--document{width:min(1050px,96vw);max-height:96vh}
.fng-document-body{padding:12px;height:min(78vh,900px)}
.fng-document-body iframe{width:100%;height:100%;border:0;border-radius:12px;background:#f8fafc}
.fng-document-alt{padding:0 24px 14px;font-size:12px;text-align:right}
.fng-document-alt a{color:#0453cb;font-weight:700}
.fng-modal-head{padding:20px 24px;border-bottom:1px solid #edf1f6;display:flex;justify-content:space-between;align-items:center}
.fng-modal-head h3{font-size:17px;font-weight:800;margin:0}
.fng-modal-head button{border:0;background:transparent;color:#64748b;font-size:19px;cursor:pointer}
.fng-dialog form{padding:20px 24px}.fng-dialog label{display:block;font-size:12px;font-weight:700;margin-bottom:14px;color:#41516a}
.fng-dialog input,.fng-dialog select,.fng-dialog textarea{display:block;width:100%;border:1px solid #dce3ef;background:#fff;border-radius:10px;padding:10px 12px;outline:0;font-size:14px;margin-top:5px;color:#162841}
.fng-dialog input:focus,.fng-dialog select:focus,.fng-dialog textarea:focus{border-color:#0453cb;box-shadow:0 0 0 3px rgba(4,83,203,.09)}
.fng-dialog label.fng-check{display:flex;align-items:center;gap:8px}
.fng-picker{position:relative;min-width:0;margin-top:5px}
.fng-picker:focus-within{z-index:40}
.fng-dialog .fng-picker-native{position:absolute;opacity:0;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);pointer-events:none;top:0;left:0;margin:0;padding:0}
.fng-picker-trigger{display:flex;width:100%;align-items:center;justify-content:space-between;gap:12px;min-height:43px;border:1px solid #dce3ef;background:#fff;color:#17243b;border-radius:10px;padding:10px 12px;text-align:left;font:inherit;font-weight:600;cursor:pointer}
.fng-picker-trigger:hover{border-color:#92b5ef}.fng-picker-trigger[aria-expanded=true],.fng-picker-trigger:focus-visible{outline:none;border-color:#0453cb;box-shadow:0 0 0 3px rgba(4,83,203,.09)}
.fng-picker-trigger i{color:#6f83a1}
.fng-picker-menu{position:absolute;left:0;right:0;top:calc(100% + 4px);z-index:55;padding:5px;background:white;border:1px solid #dce3ef;border-radius:12px;box-shadow:0 16px 44px rgba(15,43,86,.2);max-height:220px;overflow-y:auto}
.fng-picker-option{width:100%;display:block;text-align:left;padding:9px 10px;background:white;color:#253d61;border:0;border-radius:7px;font:inherit;font-size:13px;cursor:pointer}
.fng-picker-option:hover,.fng-picker-option:focus-visible,.fng-picker-option[aria-selected=true]{background:#eef5ff;color:#0453cb;outline:none}
.fng-picker-menu[hidden]{display:none!important}.fng-dialog label.fng-check input{width:auto;margin:0}
.fng-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.fng-modal-footer{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}.fng-modal-error{color:#b91c1c;font-size:12px;margin:8px 0;min-height:17px}
@media(max-width:650px){.fng-head{padding:10px 0 12px;align-items:flex-start}.fng-head h3{font-size:19px}.fng-pill{display:none}.fng-block-head{padding:14px}.fng-grid{grid-template-columns:1fr}}
</style>
<script>
(() => {
  const module = document.currentScript?.previousElementSibling?.previousElementSibling;
  const panel = module?.matches?.('[data-fng-module]') ? module : document.querySelector('[data-fng-module]:not([data-fng-bound])');
  if (!panel || panel.dataset.fngBound) return;
  panel.dataset.fngBound = '1';
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  let busy = false;
  let notice = '';
  let noticeError = false;
  async function reload() {
    try {
      const response = await fetch(panel.dataset.url, {headers:{'Accept':'text/html','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
      if (!response.ok) throw new Error(response.status === 403 ? 'Permission de consultation des notes refusée.' : 'Impossible de charger les notes.');
      panel.innerHTML = (notice ? '<div class="fng-notice '+(noticeError ? 'fng-notice-error' : '')+'" role="status">'+escape(notice)+'</div>' : '')+await response.text();
      notice='';
    } catch(error) {
      panel.innerHTML = '<div class="fng-notice fng-notice-error" role="alert">'+escape(error.message)+'</div>';
    }
  }
  async function send(url,method,form) {
    // PHP only parses multipart/form-data natively for POST. Spoof other
    // verbs so Laravel receives every field instead of an empty request.
    if(method !== 'POST'){
      form.set('_method',method);
      method='POST';
    }
    const response = await fetch(url,{method,credentials:'same-origin',
      headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':token},
      body:form});
    let data={};
    try {data=await response.json()} catch(_) {}
    if (!response.ok || !data.success) {
      const first=Object.values(data.errors || {}).flat().find(Boolean);
      throw new Error(first || data.message || 'Opération refusée. Vérifiez les permissions et les données.');
    }
    return data;
  }
  function enhanceSelectors(dlg) {
    for (const select of dlg.querySelectorAll('select.fng-select')) {
      const picker=document.createElement('div');
      picker.className='fng-picker';
      const trigger=document.createElement('button');
      trigger.type='button';
      trigger.className='fng-picker-trigger';
      trigger.setAttribute('aria-haspopup','listbox');
      trigger.setAttribute('aria-expanded','false');
      const menu=document.createElement('div');
      menu.className='fng-picker-menu';
      menu.setAttribute('role','listbox');
      menu.hidden=true;
      const options=[...select.options].filter(option=>!option.disabled);
      const close=()=>{menu.hidden=true;trigger.setAttribute('aria-expanded','false');};
      const renderLabel=()=>{
        const option=select.selectedOptions[0];
        trigger.innerHTML='<span>'+escape(option?.textContent?.trim()||'Sélectionner…')+'</span><i class="fas fa-chevron-down" aria-hidden="true"></i>';
        menu.querySelectorAll('[role="option"]').forEach(el=>{
          el.setAttribute('aria-selected',String(el.dataset.value===select.value));
        });
      };
      for (const option of options) {
        const item=document.createElement('button');
        item.type='button';
        item.className='fng-picker-option';
        item.dataset.value=option.value;
        item.setAttribute('role','option');
        item.textContent=option.textContent;
        item.addEventListener('click',()=>{
          select.value=option.value;
          select.dispatchEvent(new Event('change',{bubbles:true}));
          renderLabel();close();trigger.focus();
        });
        menu.appendChild(item);
      }
      trigger.addEventListener('click',()=>{
        const visible=menu.hidden;
        dlg.querySelectorAll('.fng-picker-menu').forEach(el=>el.hidden=true);
        dlg.querySelectorAll('.fng-picker-trigger').forEach(el=>el.setAttribute('aria-expanded','false'));
        menu.hidden=!visible;trigger.setAttribute('aria-expanded',String(visible));
        if(visible)menu.querySelector('[aria-selected=true]')?.scrollIntoView({block:'nearest'});
      });
      trigger.addEventListener('keydown',e=>{
        if(['ArrowDown','ArrowUp','Enter',' '].includes(e.key)){
          e.preventDefault();
          if(menu.hidden)trigger.click();
          menu.querySelector('[aria-selected=true]')?.focus();
        }
      });
      picker.addEventListener('keydown',e=>{
        if(e.key==='Escape'){close();trigger.focus();e.stopPropagation();}
        if(['ArrowDown','ArrowUp'].includes(e.key) && !menu.hidden){
          e.preventDefault();
          const all=[...menu.querySelectorAll('[role=option]')];
          let idx=all.indexOf(document.activeElement);
          if(idx<0)idx=all.findIndex(el=>el.getAttribute('aria-selected')==='true');
          all[(idx+(e.key==='ArrowDown'?1:all.length-1))%all.length]?.focus();
        }
      });
      select.classList.add('fng-picker-native');
      select.after(picker);
      picker.append(trigger,menu);
      renderLabel();
    }
    dlg.addEventListener('click',e=>{
      if(e.target.closest('.fng-picker'))return;
      dlg.querySelectorAll('.fng-picker-menu').forEach(el=>el.hidden=true);
      dlg.querySelectorAll('.fng-picker-trigger').forEach(el=>el.setAttribute('aria-expanded','false'));
    });
  }
  function modal(title, body, onSubmit, danger=false) {
    document.querySelector('.fng-dialog')?.remove();
    const dlg=document.createElement('dialog');
    dlg.className='fng-dialog';
    dlg.innerHTML='<div class="fng-modal-head"><h3>'+escape(title)+'</h3><button type="button" data-fng-close aria-label="Fermer">×</button></div>'
      +'<form><div class="fng-modal-fields">'+body+'</div><p class="fng-modal-error" role="alert"></p>'
      +'<div class="fng-modal-footer"><button type="button" data-fng-close class="fng-btn">Annuler</button>'
      +'<button type="submit" class="fng-btn '+(danger ? 'fng-danger' : 'fng-btn-primary')+'">'+(danger ? 'Confirmer la suppression' : 'Enregistrer')+'</button></div></form>';
    document.body.appendChild(dlg);
    dlg.addEventListener('click', e => {if(e.target.closest('[data-fng-close]'))dlg.close()});
    dlg.addEventListener('close',()=>dlg.remove(),{once:true});
    dlg.querySelector('form').addEventListener('submit',async e=>{
      e.preventDefault();
      if(busy) return;
      busy=true;
      const button=dlg.querySelector('[type=submit]');button.disabled=true;
      try {
        const data=await onSubmit(new FormData(e.currentTarget));
        dlg.close();
        notice=data.message || 'Modification enregistrée.';
        noticeError=false;
        await reload();
      }catch(error){
        dlg.querySelector('.fng-modal-error').textContent=error.message;
      }finally{busy=false;button.disabled=false}
    });
    enhanceSelectors(dlg);
    const absence=dlg.querySelector('input[name="is_absent"]');
    const score=dlg.querySelector('input[name="note"]');
    if(absence && score){
      const toggle=()=>{score.required=!absence.checked;score.disabled=absence.checked;};
      absence.addEventListener('change',toggle);toggle();
    }
    dlg.showModal();
    return dlg;
  }
  panel.addEventListener('click', async e => {
    const documentLink=e.target.closest('a[data-fng-document]');
    if(documentLink){
      // Keep the normal new-tab link as an accessible fallback.
      const url=new URL(documentLink.href,window.location.href);
      if(url.origin !== window.location.origin)return;
      e.preventDefault();
      document.querySelector('.fng-dialog--document')?.close();
      const dlg=document.createElement('dialog');
      dlg.className='fng-dialog fng-dialog--document';
      const title=escape(documentLink.textContent.trim());
      dlg.innerHTML='<div class="fng-modal-head"><h3>'+title+'</h3><button type="button" data-fng-close aria-label="Fermer">×</button></div>'
        +'<div class="fng-document-body"><iframe title="'+title+'" loading="lazy"></iframe></div>'
        +'<div class="fng-document-alt"><a target="_blank" rel="noopener">Ouvrir le document dans un nouvel onglet ↗</a></div>';
      dlg.querySelector('iframe').src=url.href;
      dlg.querySelector('.fng-document-alt a').href=url.href;
      dlg.querySelector('[data-fng-close]').addEventListener('click',()=>dlg.close());
      dlg.addEventListener('close',()=>dlg.remove(),{once:true});
      document.body.appendChild(dlg);
      dlg.showModal();
      return;
    }
    const btn=e.target.closest('button');
    if(!btn) return;
    const block=btn.closest('[data-fng-inscription]');if(!block)return;
    if(btn.hasAttribute('data-fng-note')){
      const field='<label>Note / '+escape(btn.dataset.bareme)+'<input name="note" type="number" required min="0" max="'+escape(btn.dataset.bareme)+'" step="0.01" value="'+escape(btn.dataset.note)+'"></label>'
        +'<label class="fng-check"><input type="checkbox" name="is_absent" value="1" '+(btn.dataset.absent==='1'?'checked':'')+'> Étudiant absent</label>'
        +'<label>Commentaire <textarea name="commentaire" rows="2">'+escape(btn.dataset.comment)+'</textarea></label>';
      modal('Note · '+btn.dataset.title,field, fd=>{
        fd.set('evaluation_id',btn.dataset.evaluation);
        fd.set('etudiant_id',block.dataset.student);
        if(fd.has('is_absent'))fd.set('note','0');
        return send(block.dataset.save,'POST',fd);
      });
    }else if(btn.hasAttribute('data-fng-publish')){
      modal(btn.dataset.published==='1'?'Masquer cette évaluation':'Activer cette évaluation',
        '<p>Confirmer la modification du statut de publication ? Une évaluation masquée ne peut plus recevoir de nouvelles notes.</p>',
        ()=>send(block.dataset.quickBase+'/'+encodeURIComponent(btn.dataset.id)+'/toggle-published','PATCH',new FormData()));
    }else if(btn.hasAttribute('data-fng-edit-eval')){
      const body='<label>Titre<input name="titre" required maxlength="255" value="'+escape(btn.dataset.title)+'"></label>'
        +'<div class="fng-grid"><label>Barème<input type="number" name="bareme" required step="0.5" min="1" max="100" value="'+escape(btn.dataset.bareme)+'"></label>'
        +'<label>Coefficient<input type="number" name="coefficient" required step="0.1" min="0.1" max="100" value="'+escape(btn.dataset.coefficient)+'"></label></div>';
      modal('Modifier l’évaluation',body,fd=>send(block.dataset.quickBase+'/'+encodeURIComponent(btn.dataset.id)+'/quick-update','PATCH',fd));
    }else if(btn.hasAttribute('data-fng-new-eval')){
      const tpl=block.querySelector('[data-fng-eval-fields]');
      if(tpl)modal('Nouvelle évaluation',tpl.innerHTML,fd=>send(block.dataset.createEvaluation,'POST',fd));
    }else if(btn.hasAttribute('data-fng-delete')){
      modal('Supprimer la note', '<p>Supprimer la note de l’évaluation <strong>'+escape(btn.dataset.title)+'</strong> ? Les résultats seront recalculés.</p>',
        ()=>send(btn.dataset.deleteUrl,'DELETE',new FormData()),true);
    }
  });
  // etudiants.show has several heavy tabs: avoid querying every evaluation
  // when the administrative user never visits the Academic tab.
  // The inscription page is visible immediately, so it loads normally.
  if ('IntersectionObserver' in window) {
    const observer=new IntersectionObserver(entries=>{
      if (!entries.some(entry=>entry.isIntersecting)) return;
      observer.disconnect();
      reload();
    },{rootMargin:'120px 0px'});
    observer.observe(panel);
  } else {
    reload();
  }
})();
</script>

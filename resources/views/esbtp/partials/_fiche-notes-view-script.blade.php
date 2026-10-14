  // Conserver la préférence de lecture de chaque inscription après les mutations AJAX.
  const fngViewStates = new Map();
  const cleanSearch = value => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('fr').trim();
  function renderFngEvaluations(block) {
    const tbody=block.querySelector('.fng-table tbody');
    if(!tbody) return;
    const rows=[...tbody.querySelectorAll('[data-fng-eval-row]')];
    if(!rows.length) return;
    const id=block.dataset.fngInscription;
    const state=fngViewStates.get(id) || {mode:'matiere',search:'',collapsed:new Set(),searchCollapsed:new Set()};
    fngViewStates.set(id,state);
    block.dataset.fngDisplay=state.mode;
    block.querySelectorAll('[data-fng-view]').forEach(button=>{
      button.setAttribute('aria-pressed',String(button.dataset.fngView===state.mode));
    });
    const search=block.querySelector('[data-fng-search]');
    if(search && search.value!==state.search) search.value=state.search;
    const ordered=rows.sort((a,b)=>Number(a.dataset.fngOrder)-Number(b.dataset.fngOrder));
    const matches=ordered.filter(row=>cleanSearch(row.dataset.fngMatiere+' '+row.dataset.fngTitre).includes(cleanSearch(state.search)));
    const matched=new Set(matches);
    const count=block.querySelector('[data-fng-visible-count]');
    if(count) count.textContent=matches.length===ordered.length
      ? ordered.length+' évaluation'+(ordered.length>1?'s':'')
      : matches.length+' / '+ordered.length+' évaluation'+(ordered.length>1?'s':'');
    const fragment=document.createDocumentFragment();
    if(state.mode==='matiere' || state.mode==='periode'){
      const groups=new Map();
      for(const row of matches){
        const isSubject=state.mode==='matiere';
        const value=isSubject ? (row.dataset.fngMatiereId || row.dataset.fngMatiere) : (row.dataset.fngPeriode || 'non_precisee');
        const key=(isSubject ? 'matiere:' : 'periode:')+value;
        const rawPeriod=row.dataset.fngPeriode || '';
        const periodName=rawPeriod==='semestre1' || rawPeriod==='S1' ? 'Semestre 1'
          : rawPeriod==='semestre2' || rawPeriod==='S2' ? 'Semestre 2'
          : rawPeriod==='annuel' ? 'Annuel' : (rawPeriod || 'Période non précisée');
        const name=isSubject ? row.dataset.fngMatiere : periodName;
        if(!groups.has(key))groups.set(key,{key,name,rows:[]});
        groups.get(key).rows.push(row);
      }
      const sorted=[...groups.values()].sort((a,b)=>a.name.localeCompare(b.name,'fr',{sensitivity:'base',numeric:true}));
      for(const group of sorted){
        const header=document.createElement('tr');
        header.className='fng-group-heading';
        const cell=document.createElement('td');
        cell.colSpan=6;
        const button=document.createElement('button');
        button.type='button';
        button.className='fng-group-toggle';
        button.dataset.fngGroupToggle=group.key;
        // Les groupes de recherche restent indépendants des replis habituels.
        const collapsedSet=state.search.trim() ? state.searchCollapsed : state.collapsed;
        const expanded=!collapsedSet.has(group.key);
        button.setAttribute('aria-expanded',String(expanded));
        const title=document.createElement('span');
        title.className='fng-group-title';
        title.textContent=group.name;
        const subtitle=document.createElement('span');
        subtitle.className='fng-group-count';
        subtitle.textContent=group.rows.length+' évaluation'+(group.rows.length>1?'s':'');
        const chevron=document.createElement('i');
        chevron.className='fas fa-chevron-down';
        chevron.setAttribute('aria-hidden','true');
        button.append(title,subtitle,chevron);
        cell.appendChild(button);
        header.appendChild(cell);
        fragment.appendChild(header);
        for(const row of group.rows){
          row.hidden=!expanded;
          fragment.appendChild(row);
        }
      }
      for(const row of ordered){
        if(!matched.has(row)){row.hidden=true;fragment.appendChild(row);}
      }
    }else{
      for(const row of ordered){row.hidden=!matched.has(row);fragment.appendChild(row);}
    }
    if(!matches.length){
      const empty=document.createElement('tr');
      empty.className='fng-no-match';
      const td=document.createElement('td');
      td.colSpan=6;
      td.textContent='Aucune évaluation pour cette recherche.';
      empty.appendChild(td);
      fragment.appendChild(empty);
    }
    tbody.replaceChildren(fragment);
  }
  function initializeFngEvaluations(){
    panel.querySelectorAll('[data-fng-inscription]').forEach(renderFngEvaluations);
  }
  panel.addEventListener('click',e=>{
    const button=e.target.closest('[data-fng-view],[data-fng-group-toggle]');
    if(!button || !panel.contains(button))return;
    const block=button.closest('[data-fng-inscription]');
    if(!block)return;
    const state=fngViewStates.get(block.dataset.fngInscription);
    if(!state)return;
    if(button.hasAttribute('data-fng-view')){
      state.mode=button.dataset.fngView;
    }else{
      const key=button.dataset.fngGroupToggle;
      const collapsedSet=state.search.trim() ? state.searchCollapsed : state.collapsed;
      if(collapsedSet.has(key))collapsedSet.delete(key);
      else collapsedSet.add(key);
    }
    const focusedGroup=button.dataset.fngGroupToggle;
    renderFngEvaluations(block);
    if(focusedGroup!==undefined){
      const replacement=[...block.querySelectorAll('[data-fng-group-toggle]')].find(b=>b.dataset.fngGroupToggle===focusedGroup);
      replacement?.focus({preventScroll:true});
    }
  });
  panel.addEventListener('input',e=>{
    if(!e.target.matches('[data-fng-search]'))return;
    const block=e.target.closest('[data-fng-inscription]');
    const state=block && fngViewStates.get(block.dataset.fngInscription);
    if(!state)return;
    state.search=e.target.value;
    state.searchCollapsed.clear();
    renderFngEvaluations(block);
  });

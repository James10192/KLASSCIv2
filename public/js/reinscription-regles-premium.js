/* KLASSCI academic rules page; server controls validation, scopes and permissions. */
window.reglesPremium = function (element) {
    const initial = JSON.parse(element.dataset.regles || '[]');
    const endpoint = element.dataset.endpoint;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const emptyForm = () => ({ niveau:'', filiere:'', moyenne_passage:'', moyenne_rattrapage:'', max_matieres_rattrapage:'', max_redoublements:'', autoriser_redoublement:true, actif:true, conditions_speciales:'' });
    return {
        rules:initial, search:'', open:false, confirmDelete:false, loading:false,
        action:'create', selected:null, form:emptyForm(), errors:{}, feedback:'',
        get activeCount() {return this.rules.filter(rule=>Boolean(rule.actif)).length;},
        get filteredRules() {
            const term=this.search.trim().toLocaleLowerCase();
            return this.rules.filter(rule=>!term || [rule.niveau,rule.filiere,rule.conditions_speciales].some(value=>String(value||'').toLocaleLowerCase().includes(term)));
        },
        create() {this.action='create';this.selected=null;this.form=emptyForm();this.errors={};this.open=true;this.$nextTick(()=>this.$refs.passage?.focus());},
        edit(rule) {this.action='edit';this.selected=rule;this.form={...rule,autoriser_redoublement:Boolean(rule.autoriser_redoublement),actif:Boolean(rule.actif)};this.errors={};this.open=true;this.$nextTick(()=>this.$refs.passage?.focus());},
        dismiss() {if(this.loading)return;this.open=false;this.confirmDelete=false;this.errors={};},
        askDelete(rule) {this.selected=rule;this.confirmDelete=true;this.errors={};},
        async request(url,method,payload) {
            const response=await fetch(url,{
                method,
                headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token,'X-Requested-With':'XMLHttpRequest'},
                body:payload?JSON.stringify(payload):undefined
            });
            const data=await response.json().catch(()=>({message:'Réponse du serveur illisible.'}));
            if(!response.ok){const error=new Error(data.message||'Échec de la demande.');error.fields=data.errors||{};throw error;}
            return data;
        },
        fieldError(key) {return this.errors[key]?.[0]||'';},
        async save() {
            if(this.loading)return;
            this.loading=true;this.errors={};
            try {
                const url=this.action==='create'?endpoint:endpoint+'/'+encodeURIComponent(this.selected.id);
                const payload={...this.form,autoriser_redoublement:this.form.autoriser_redoublement?1:0,actif:this.form.actif?1:0};
                const data=await this.request(url,this.action==='create'?'POST':'PUT',payload);
                const idx=this.rules.findIndex(r=>r.id===data.regle.id);
                if(idx<0)this.rules.unshift(data.regle);
                else this.rules.splice(idx,1,data.regle);
                this.open=false;this.feedback=data.message||'Règle enregistrée.';
            }catch(error){this.errors=error.fields||{};if(!Object.keys(this.errors).length)this.errors.general=[error.message];}
            finally{this.loading=false;}
        },
        async destroy() {
            if(this.loading||!this.selected)return;
            this.loading=true;
            try {
                const id=this.selected.id;
                const data=await this.request(endpoint+'/'+encodeURIComponent(id),'DELETE');
                this.rules=this.rules.filter(rule=>rule.id!==id);
                this.confirmDelete=false;this.feedback=data.message||'Règle supprimée.';
            }catch(error){this.errors.general=[error.message];}
            finally{this.loading=false;}
        }
    };
};

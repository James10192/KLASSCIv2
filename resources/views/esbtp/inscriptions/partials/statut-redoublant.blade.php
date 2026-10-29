{{-- Statut redoublant de l'inscription : lu, confirmé ou corrigé sur place (namespace rsr-*). --}}
@php
    $_statutRedoublant = app(\App\Domain\Inscriptions\StatutRedoublant::class)->pourAffichage($inscription);
    $_peutEtablir = auth()->user()?->can(\App\Domain\Inscriptions\StatutRedoublant::PERMISSION) ?? false;
@endphp
<div class="is-info-row rsr-ligne"
     x-data="statutRedoublantFiche()"
     data-statut='@json($_statutRedoublant)'
     data-url="{{ route('esbtp.inscriptions.redoublant.etablir', $inscription) }}"
     data-motif-minimum="{{ \App\Domain\Inscriptions\StatutRedoublant::MOTIF_MINIMUM }}">
    <span class="is-info-lbl">Redoublant</span>
    <span class="is-info-val rsr-valeur">
        <span class="is-badge" :class="statut.valeur ? 'primary' : 'secondary'" x-text="statut.valeur ? 'Oui' : 'Non'">{{ $_statutRedoublant['valeur'] ? 'Oui' : 'Non' }}</span>
        <span class="rsr-etat" :class="'rsr-etat--' + statut.etat" x-show="statut.etat !== 'deduit'">
            <i class="fas" :class="{ 'fa-hourglass-half': statut.etat === 'a_confirmer', 'fa-check': statut.etat === 'confirme', 'fa-pen': statut.etat === 'corrige' }"></i>
            <span x-text="{ a_confirmer: 'À confirmer', confirme: 'Confirmé', corrige: 'Corrigé' }[statut.etat] || ''"></span>
        </span>
    </span>
    <span class="rsr-detail" x-text="statut.detail">{{ $_statutRedoublant['detail'] }}</span>
    <span class="rsr-detail" x-show="statut.motif" x-cloak>Motif : <span x-text="statut.motif"></span></span>
    <span class="rsr-alerte" x-show="statut.incoherence" x-cloak><i class="fas fa-exclamation-triangle"></i> <span x-text="statut.incoherence"></span></span>

    @if($_peutEtablir)
    <span class="rsr-actions" x-show="!edition">
        <button type="button" class="rsr-btn rsr-btn--plein" x-show="statut.a_confirmer" x-on:click="confirmer()" :disabled="envoi">
            <i class="fas fa-check"></i> Confirmer
        </button>
        <button type="button" class="rsr-btn" x-on:click="ouvrir()" :disabled="envoi">
            <i class="fas fa-pen"></i> Corriger
        </button>
    </span>
    <form class="rsr-form" x-show="edition" x-cloak x-on:submit.prevent="enregistrer()">
        <div class="rsr-choix">
            <label class="rsr-option" :class="{ 'rsr-option--actif': choix === '1' }"><input type="radio" value="1" x-model="choix"> Oui, il redouble</label>
            <label class="rsr-option" :class="{ 'rsr-option--actif': choix === '0' }"><input type="radio" value="0" x-model="choix"> Non</label>
        </div>
        <textarea class="rsr-motif" rows="2" x-model="motif" maxlength="500"
                  :placeholder="change() ? 'Pourquoi le statut change-t-il ? (obligatoire)' : 'Motif (facultatif)'"></textarea>
        <span class="rsr-erreur" x-show="erreur" x-text="erreur"></span>
        <span class="rsr-actions">
            <button type="submit" class="rsr-btn rsr-btn--plein" :disabled="envoi">
                <span x-show="!envoi">Enregistrer</span><span x-show="envoi" x-cloak>Enregistrement…</span>
            </button>
            <button type="button" class="rsr-btn" x-on:click="edition = false; erreur = ''">Annuler</button>
        </span>
    </form>
    @endif
</div>

<style>
    .rsr-ligne { grid-column: 1 / -1; gap: 4px; }
    .rsr-valeur { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .rsr-etat { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 700; padding: 2px 9px; border-radius: 999px; }
    .rsr-etat--a_confirmer { background: rgba(245,158,11,.1); color: #b45309; border: 1px solid rgba(245,158,11,.25); }
    .rsr-etat--confirme { background: rgba(16,185,129,.1); color: #047857; border: 1px solid rgba(16,185,129,.25); }
    .rsr-etat--corrige { background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.2); }
    .rsr-detail { font-size: .78rem; color: #64748b; }
    .rsr-alerte { font-size: .78rem; color: #b45309; }
    .rsr-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
    .rsr-btn { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; border: 1px solid #c7d4e5; background: #fff; color: #0453cb; font-size: .78rem; font-weight: 600; cursor: pointer; transition: all .2s ease; }
    .rsr-btn:hover:not(:disabled) { background: rgba(4,83,203,.06); }
    .rsr-btn--plein { background: #0453cb; border-color: #0453cb; color: #fff; }
    .rsr-btn--plein:hover:not(:disabled) { background: #033a8e; }
    .rsr-btn:disabled { opacity: .6; cursor: wait; }
    .rsr-form { display: flex; flex-direction: column; gap: 8px; margin-top: 6px; max-width: 520px; }
    .rsr-choix { display: flex; gap: 8px; flex-wrap: wrap; }
    .rsr-option { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: .82rem; font-weight: 600; color: #334155; cursor: pointer; }
    .rsr-option input { accent-color: #0453cb; }
    .rsr-option--actif { border-color: #0453cb; background: rgba(4,83,203,.06); color: #0453cb; }
    .rsr-motif { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: .85rem; }
    .rsr-erreur { font-size: .78rem; color: #dc2626; }
</style>

<script>
    if (typeof window.statutRedoublantFiche !== 'function') {
        window.statutRedoublantFiche = function () {
            return {
                statut: {},
                edition: false,
                envoi: false,
                choix: '0',
                motif: '',
                erreur: '',
                init() {
                    try { this.statut = JSON.parse(this.$root.dataset.statut || '{}'); } catch (e) { this.statut = {}; }
                },
                change() {
                    return (this.choix === '1') !== Boolean(this.statut.valeur);
                },
                ouvrir() {
                    this.choix = this.statut.valeur ? '1' : '0';
                    this.motif = '';
                    this.erreur = '';
                    this.edition = true;
                },
                confirmer() {
                    this.choix = this.statut.valeur ? '1' : '0';
                    this.motif = '';
                    this.enregistrer();
                },
                async enregistrer() {
                    const minimum = parseInt(this.$root.dataset.motifMinimum || '10', 10);
                    if (this.change() && this.motif.trim().length < minimum) {
                        this.erreur = 'Dites en quelques mots pourquoi le statut change (au moins ' + minimum + ' caractères).';
                        return;
                    }
                    this.envoi = true;
                    this.erreur = '';
                    try {
                        const reponse = await fetch(this.$root.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ valeur: this.choix, motif: this.motif }),
                        });
                        const donnees = await reponse.json().catch(() => ({}));
                        if (!reponse.ok) {
                            const premiere = donnees.errors ? Object.values(donnees.errors)[0] : null;
                            throw new Error((premiere && premiere[0]) || donnees.message || 'Enregistrement impossible.');
                        }
                        this.statut = donnees.statut;
                        this.edition = false;
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Statut redoublant enregistré.' } }));
                    } catch (e) {
                        this.erreur = e.message;
                    } finally {
                        this.envoi = false;
                    }
                },
            };
        };
    }
</script>

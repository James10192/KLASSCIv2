@extends('layouts.app')
@section('title', 'Notes de seconde session — ' . $session->libelle)

@push('styles')
<style>
[x-cloak]{display:none !important;}
.rtn-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;padding:2rem 2.5rem;color:#fff;margin-bottom:1.25rem;}
.rtn-hero-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;}
.rtn-hero h1{margin:0;font-size:1.45rem;font-weight:700;color:#fff;}
.rtn-hero p{margin:.3rem 0 0;color:rgba(255,255,255,.72);font-size:.88rem;}
.rtn-kpis{display:flex;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap;}
.rtn-kpi{flex:1;min-width:150px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:.9rem 1rem;}
.rtn-kpi-value{font-size:1.35rem;font-weight:700;color:#fff;}
.rtn-kpi-label{font-size:.72rem;color:rgba(255,255,255,.65);margin-top:.15rem;}
.rtn-btn{padding:.55rem 1rem;border-radius:9px;font-size:.82rem;font-weight:600;border:1px solid;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;}
.rtn-btn--glass{background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.2);}
.rtn-btn--white{background:#fff;color:#0453cb;border-color:transparent;}
.rtn-btn--primary{background:#0453cb;color:#fff;border-color:#0453cb;}
.rtn-btn--secondary{background:#f1f5f9;color:#475569;border-color:#e2e8f0;}
.rtn-btn:disabled{opacity:.55;cursor:not-allowed;}
.rtn-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem;margin-bottom:1rem;}
.rtn-regle{display:flex;align-items:flex-start;gap:.6rem;background:rgba(4,83,203,.06);border:1px solid rgba(4,83,203,.18);border-radius:10px;padding:.75rem .9rem;margin-bottom:1.25rem;font-size:.84rem;color:#1e293b;}
.rtn-regle i{color:#0453cb;margin-top:.15rem;}
.rtn-etu{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding-bottom:.75rem;margin-bottom:.75rem;border-bottom:1px solid #f1f5f9;}
.rtn-etu-nom{font-size:.98rem;font-weight:700;color:#1e293b;}
.rtn-etu-meta{font-size:.75rem;color:#64748b;margin-top:.1rem;}
.rtn-table{width:100%;border-collapse:collapse;font-size:.84rem;}
.rtn-table th{text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;font-weight:700;padding:.4rem .5rem;border-bottom:1px solid #e2e8f0;}
.rtn-table td{padding:.5rem;border-bottom:1px solid #f8fafc;vertical-align:middle;}
.rtn-table tr:last-child td{border-bottom:none;}
.rtn-matiere{font-weight:600;color:#1e293b;}
.rtn-code{font-family:'Courier New',monospace;font-size:.7rem;color:#0453cb;background:rgba(4,83,203,.08);padding:.1rem .4rem;border-radius:4px;margin-left:.4rem;}
.rtn-input{width:96px;padding:.4rem .55rem;border:1px solid #cbd5e1;border-radius:8px;font-size:.85rem;font-weight:600;color:#1e293b;text-align:center;}
.rtn-input:focus{outline:none;border-color:#0453cb;box-shadow:0 0 0 3px rgba(4,83,203,.12);}
.rtn-input--sale{border-color:#0453cb;background:rgba(4,83,203,.04);}
.rtn-val{font-weight:700;color:#1e293b;}
.rtn-val--vide{color:#94a3b8;font-weight:500;}
.rtn-trace{font-size:.7rem;color:#94a3b8;}
.rtn-vide{padding:2rem;text-align:center;color:#94a3b8;font-size:.88rem;}
.rtn-vide i{font-size:1.6rem;display:block;margin-bottom:.6rem;color:#cbd5e1;}
.rtn-bar{position:sticky;bottom:0;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:.85rem 1.1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;box-shadow:0 -4px 16px rgba(15,23,42,.06);}
.rtn-statut{font-size:.82rem;color:#475569;}
.rtn-statut--ok{color:#047857;}
/* Enregistre, mais une classe n'a pas suivi : ni le vert qui dit « tout va bien »,
   ni le rouge qui dirait que la saisie a echoue — elle est bien en base. Meme ton
   ambre que .rtn-alerte, la couleur du « a surveiller » du systeme de design. */
.rtn-statut--attention{color:#92400e;}
.rtn-statut--ko{color:#b91c1c;}
.rtn-alerte{display:flex;align-items:flex-start;gap:.6rem;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:10px;padding:.85rem 1rem;font-size:.85rem;color:#92400e;}
@media (max-width: 768px){
    .rtn-hero{padding:1.5rem 1.25rem;}
    .rtn-table thead{display:none;}
    .rtn-table td{display:block;border:none;padding:.25rem .5rem;}
    .rtn-table tr{display:block;border-bottom:1px solid #f1f5f9;padding:.5rem 0;}
}
</style>
@endpush

@section('content')
@php
    $_lignes = [];
    foreach ($groupes as $_ligneGroupe) {
        foreach ($_ligneGroupe as $_l) {
            $_lignes[] = [
                'resultat_id' => (int) $_l->id,
                'note_rattrapage' => $_l->note_rattrapage === null ? '' : (string) (float) $_l->note_rattrapage,
                'note_finale' => $_l->note_finale === null ? null : (float) $_l->note_finale,
            ];
        }
    }
    $_nbEtudiants = $groupes->count();
    $_nbLignes = count($_lignes);
    $_nbSaisies = collect($_lignes)->filter(fn ($l) => $l['note_rattrapage'] !== '')->count();
@endphp

<div x-data="saisieRattrapage()" x-init="init()">

<div class="rtn-hero">
    <div class="rtn-hero-top">
        <div>
            <h1>Notes de seconde session</h1>
            <p>
                {{ $session->libelle }} ·
                {{ $session->parcours->name ?? 'Tous parcours' }} ·
                {{ $session->semestre ? 'Semestre ' . $session->semestre : 'Semestre non precise' }}
            </p>
        </div>
        <a href="{{ route('esbtp.lmd.rattrapage.show', $session) }}" class="rtn-btn rtn-btn--glass">
            <i class="fas fa-arrow-left"></i> Retour a la session
        </a>
    </div>

    <div class="rtn-kpis">
        <div class="rtn-kpi">
            <div class="rtn-kpi-value">{{ $_nbEtudiants }}</div>
            <div class="rtn-kpi-label">Etudiants concernes</div>
        </div>
        <div class="rtn-kpi">
            <div class="rtn-kpi-value">{{ $_nbLignes }}</div>
            <div class="rtn-kpi-label">Enseignements a repasser</div>
        </div>
        <div class="rtn-kpi">
            <div class="rtn-kpi-value" x-text="nbSaisies">{{ $_nbSaisies }}</div>
            <div class="rtn-kpi-label">Notes deja saisies</div>
        </div>
    </div>
</div>

@if($messageBloquant)
    <div class="rtn-card">
        <div class="rtn-alerte">
            <i class="fas fa-triangle-exclamation"></i>
            <div>{{ $messageBloquant }}</div>
        </div>
    </div>
@elseif($groupes->isEmpty())
    <div class="rtn-card">
        <div class="rtn-vide">
            <i class="fas fa-user-check"></i>
            Aucun etudiant n'est inscrit a cette session de rattrapage.<br>
            Depuis la fiche de la session, lancez d'abord « Inscrire eligibles » pour ouvrir la saisie.
        </div>
    </div>
@else
    <div class="rtn-regle">
        <i class="fas fa-scale-balanced"></i>
        <div>
            @if($remplace)
                <strong>Regle de l'etablissement : la note de seconde session remplace celle de la premiere.</strong>
                La note retenue sera donc celle saisie ici, meme si elle est inferieure.
            @else
                <strong>Regle de l'etablissement : la meilleure des deux sessions est retenue.</strong>
                La note retenue sera le maximum entre la premiere et la seconde session.
            @endif
            <div style="margin-top:.25rem;color:#64748b;">
                Laissez une case vide pour effacer une note de seconde session deja saisie.
            </div>
        </div>
    </div>

    @foreach($groupes as $etudiantId => $lignes)
        @php $_etudiant = $lignes->first()->etudiant; @endphp
        <div class="rtn-card">
            <div class="rtn-etu">
                <div>
                    <div class="rtn-etu-nom">{{ optional($_etudiant)->nom }} {{ optional($_etudiant)->prenoms }}</div>
                    <div class="rtn-etu-meta">
                        Matricule {{ optional($_etudiant)->matricule ?? '—' }}
                        @if(optional($lignes->first()->bulletin)->classe)
                            · {{ $lignes->first()->bulletin->classe->name }}
                        @endif
                    </div>
                </div>
                <div class="rtn-etu-meta">{{ $lignes->count() }} enseignement(s)</div>
            </div>

            <table class="rtn-table">
                <thead>
                    <tr>
                        <th>Enseignement</th>
                        <th style="width:130px;">Premiere session</th>
                        <th style="width:150px;">Seconde session</th>
                        <th style="width:130px;">Note retenue</th>
                        <th style="width:200px;">Derniere saisie</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td>
                            <span class="rtn-matiere">{{ optional($ligne->matiere)->name ?? 'Enseignement' }}</span>
                            @if(optional($ligne->matiere)->code)
                                <span class="rtn-code">{{ $ligne->matiere->code_affiche }}</span>
                            @endif
                        </td>
                        <td>
                            @if($ligne->note_session_normale !== null)
                                <span class="rtn-val">{{ number_format((float) $ligne->note_session_normale, 2, ',', ' ') }}</span>
                            @else
                                <span class="rtn-val rtn-val--vide">—</span>
                            @endif
                        </td>
                        <td>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   class="rtn-input"
                                   :class="estModifiee({{ (int) $ligne->id }}) ? 'rtn-input--sale' : ''"
                                   x-model="notes[{{ (int) $ligne->id }}]"
                                   @if(! $peutSaisir) disabled @endif
                                   aria-label="Note de seconde session">
                        </td>
                        <td>
                            <template x-if="finales[{{ (int) $ligne->id }}] !== null">
                                <span class="rtn-val" x-text="formate(finales[{{ (int) $ligne->id }}])"></span>
                            </template>
                            <template x-if="finales[{{ (int) $ligne->id }}] === null">
                                <span class="rtn-val rtn-val--vide">—</span>
                            </template>
                        </td>
                        <td class="rtn-trace">
                            @if($ligne->updated_by && $ligne->updatedBy)
                                {{ $ligne->updatedBy->name }}
                                @if($ligne->updated_at)
                                    · {{ $ligne->updated_at->format('d/m/Y H:i') }}
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="rtn-bar">
        <div class="rtn-statut"
             :class="statutClasse"
             x-text="statut || (nbModifiees > 0 ? nbModifiees + ' modification(s) non enregistree(s).' : 'Aucune modification en attente.')"></div>
        <div style="display:flex;gap:.5rem;">
            <button type="button" class="rtn-btn rtn-btn--secondary" @click="annuler()" :disabled="enregistrement || nbModifiees === 0">
                <i class="fas fa-rotate-left"></i> Annuler les modifications
            </button>
            @if($peutSaisir)
            <button type="button" class="rtn-btn rtn-btn--primary" @click="enregistrer()" :disabled="enregistrement || nbModifiees === 0">
                <i class="fas fa-floppy-disk"></i>
                <span x-text="enregistrement ? 'Enregistrement…' : 'Enregistrer les notes'"></span>
            </button>
            @endif
        </div>
    </div>
@endif

</div>

@push('scripts')
<script>
function saisieRattrapage() {
    const initiales = @json($_lignes);

    return {
        notes: {},
        initial: {},
        finales: {},
        enregistrement: false,
        statut: '',
        // Trois issues, pas deux : enregistre / enregistre mais une classe
        // n'a pas pu etre reagregee / echec. Un booleen forcait le cas du
        // milieu a se peindre en vert.
        statutNiveau: 'ok',

        init() {
            initiales.forEach((ligne) => {
                this.notes[ligne.resultat_id] = ligne.note_rattrapage;
                this.initial[ligne.resultat_id] = ligne.note_rattrapage;
                this.finales[ligne.resultat_id] = ligne.note_finale;
            });
        },

        normalise(valeur) {
            return valeur === null || valeur === undefined ? '' : String(valeur).trim();
        },

        estModifiee(id) {
            return this.normalise(this.notes[id]) !== this.normalise(this.initial[id]);
        },

        get modifiees() {
            return Object.keys(this.notes).filter((id) => this.estModifiee(id));
        },

        get nbModifiees() {
            return this.modifiees.length;
        },

        get nbSaisies() {
            return Object.keys(this.notes).filter((id) => this.normalise(this.notes[id]) !== '').length;
        },

        get statutClasse() {
            if (!this.statut) return '';
            return 'rtn-statut--' + this.statutNiveau;
        },

        formate(valeur) {
            if (valeur === null || valeur === undefined) return '—';
            return Number(valeur).toFixed(2).replace('.', ',');
        },

        annuler() {
            Object.keys(this.initial).forEach((id) => {
                this.notes[id] = this.initial[id];
            });
            this.statut = 'Modifications annulees.';
            this.statutNiveau = 'ok';
        },

        async enregistrer() {
            const aEnvoyer = this.modifiees.map((id) => {
                const brut = this.normalise(this.notes[id]);
                return { resultat_id: Number(id), note: brut === '' ? null : Number(brut) };
            });

            if (aEnvoyer.length === 0) return;

            this.enregistrement = true;
            this.statut = '';

            try {
                const reponse = await fetch('{{ route('esbtp.lmd.rattrapage.notes.enregistrer', $session) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ notes: aEnvoyer }),
                });

                const donnees = await reponse.json().catch(() => ({}));
                if (!reponse.ok) {
                    throw new Error(donnees.message || ('Erreur ' + reponse.status));
                }

                (donnees.lignes || []).forEach((ligne) => {
                    const valeur = ligne.note_rattrapage === null ? '' : String(ligne.note_rattrapage);
                    this.notes[ligne.resultat_id] = valeur;
                    this.initial[ligne.resultat_id] = valeur;
                    this.finales[ligne.resultat_id] = ligne.note_finale;
                });

                // Les notes SONT enregistrees — donc jamais une erreur — mais si
                // une classe n'a pas pu etre reagregee, le message le dit et
                // l'ecran doit le dire aussi. Sans ceci, le texte d'alerte
                // s'affichait en vert, sous une notification de succes.
                const refuses = (donnees.bilan && donnees.bilan.bulletins_refuses) || [];

                this.statut = donnees.message || 'Notes enregistrees.';
                this.statutNiveau = refuses.length ? 'attention' : 'ok';
                window.dispatchEvent(new CustomEvent('rattrapage:notes-enregistrees', { detail: donnees.bilan || {} }));
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: refuses.length ? 'warning' : 'success', message: this.statut }
                }));
            } catch (erreur) {
                this.statut = erreur.message;
                this.statutNiveau = 'ko';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: erreur.message } }));
            } finally {
                this.enregistrement = false;
            }
        },
    };
}
</script>
@endpush

@endsection

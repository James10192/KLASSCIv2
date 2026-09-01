@extends('layouts.app')

@section('title', 'Répartition du reçu ' . $paiement->numero_recu . ' — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   CORRECTION DE VENTILATION — namespace pv- (paiement ventilation)
=================================================================== */
[x-cloak] { display: none !important; }
.pv-page { background: #f4f7fb; min-height: 100vh; padding-bottom: 2.5rem; }

.pv-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.pv-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.pv-hero-left { display: flex; align-items: center; gap: 1rem; }
.pv-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.pv-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.pv-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: .15rem 0 0; }

.pv-btn--glass {
    background: rgba(255,255,255,.15); color: #fff;
    border: 1px solid rgba(255,255,255,.2); border-radius: 10px;
    padding: .5rem 1rem; font-size: .82rem; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; gap: .45rem;
}
.pv-btn--glass:hover { background: rgba(255,255,255,.24); color: #fff; }

.pv-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.pv-kpi {
    flex: 1; min-width: 160px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: .9rem 1rem;
}
.pv-kpi-value { font-size: 1.2rem; font-weight: 700; color: #fff; }
.pv-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

.pv-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1.25rem;
}
.pv-card-header {
    display: flex; align-items: center; gap: .75rem;
    padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9;
}
.pv-card-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem; flex-shrink: 0;
}
.pv-card-title { font-size: 1rem; font-weight: 700; color: #1e293b; }
.pv-card-sub { font-size: .78rem; color: #64748b; margin-top: .1rem; }
.pv-card-body { padding: 1.25rem; }

.pv-ligne {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; padding: .75rem .9rem;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
}
.pv-ligne + .pv-ligne { margin-top: .5rem; }
.pv-ligne--depasse { border-color: #f59e0b; background: #fffbeb; }
.pv-ligne-nom { font-size: .9rem; font-weight: 600; color: #1e293b; }
.pv-ligne-reste { font-size: .74rem; color: #64748b; margin-top: .15rem; }
.pv-ligne-reste--alerte { color: #92400e; font-weight: 600; }
.pv-input {
    width: 170px; text-align: right;
    border: 1px solid #d9e2ef; border-radius: 8px;
    padding: .45rem .6rem; font-size: .9rem; font-weight: 700; color: #1e293b;
}
.pv-input:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }

.pv-total {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 1rem; padding: .8rem .9rem;
    border-radius: 10px; border: 1px solid #e2e8f0; background: #fff;
    font-size: .9rem; font-weight: 700; color: #334155;
}
.pv-total--ok { border-color: #10b981; background: rgba(16,185,129,.06); color: #065f46; }
.pv-total--ko { border-color: #dc2626; background: rgba(220,38,38,.05); color: #991b1b; }

.pv-note {
    padding: .8rem 1rem; border-radius: 10px; font-size: .82rem; line-height: 1.55;
    background: rgba(4,83,203,.05); border: 1px solid rgba(4,83,203,.18); color: #1e293b;
}
.pv-note--alerte { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
.pv-note--erreur { background: rgba(220,38,38,.05); border-color: #dc2626; color: #991b1b; }

.pv-motif {
    width: 100%; min-height: 96px; resize: vertical;
    border: 1px solid #d9e2ef; border-radius: 10px;
    padding: .65rem .8rem; font-size: .88rem; color: #1e293b; line-height: 1.5;
}
.pv-motif:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.pv-motif-compteur { font-size: .74rem; color: #64748b; margin-top: .35rem; }

.pv-actions { display: flex; gap: .6rem; justify-content: flex-end; flex-wrap: wrap; margin-top: 1rem; }
.pv-btn {
    border-radius: 10px; padding: .6rem 1.2rem; font-size: .86rem; font-weight: 600;
    border: 1px solid transparent; cursor: pointer;
    display: inline-flex; align-items: center; gap: .45rem; text-decoration: none;
}
.pv-btn--secondary { background: #fff; border-color: #d9e2ef; color: #475569; }
.pv-btn--secondary:hover { background: #f8fafc; color: #1e293b; }
.pv-btn--primary { background: #0453cb; color: #fff; }
.pv-btn--primary:hover:not(:disabled) { background: #033a8e; color: #fff; }
.pv-btn:disabled { opacity: .55; cursor: not-allowed; }

@media (max-width: 768px) {
    .pv-hero { padding: 1.5rem 1.25rem; }
    .pv-ligne { flex-direction: column; align-items: stretch; }
    .pv-input { width: 100%; }
}
</style>
@endpush

@section('content')
@php
    $pvLignes = $lignes;
    $pvMontant = round((float) $paiement->montant, 2);
@endphp
<div class="pv-page" x-data="ventilationPaiement">

    <div class="pv-hero">
        <div class="pv-hero-top">
            <div class="pv-hero-left">
                <div class="pv-hero-icon"><i class="fas fa-code-branch"></i></div>
                <div>
                    <h1>Corriger la répartition</h1>
                    <p>
                        Reçu {{ $paiement->numero_recu ?: '—' }} ·
                        {{ $paiement->etudiant->nom ?? '' }} {{ $paiement->etudiant->prenoms ?? '' }} ·
                        le montant encaissé ne change pas.
                    </p>
                </div>
            </div>
            <div>
                <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="pv-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour au paiement
                </a>
            </div>
        </div>

        <div class="pv-kpis">
            <div class="pv-kpi">
                <div class="pv-kpi-value">{{ number_format($pvMontant, 0, ',', ' ') }} FCFA</div>
                <div class="pv-kpi-label">Montant encaissé — figé</div>
            </div>
            <div class="pv-kpi">
                <div class="pv-kpi-value" x-text="formater(totalReparti) + ' FCFA'"></div>
                <div class="pv-kpi-label">Total réparti</div>
            </div>
            <div class="pv-kpi">
                <div class="pv-kpi-value" x-text="ecart === 0 ? 'Équilibré' : (ecart > 0 ? '−' + formater(ecart) : '+' + formater(-ecart))"></div>
                <div class="pv-kpi-label">Écart au montant</div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="pv-note pv-note--erreur" style="margin-bottom:1.25rem;">
            <i class="fas fa-triangle-exclamation me-2"></i>
            @foreach ($errors->all() as $erreur)
                <div>{{ $erreur }}</div>
            @endforeach
        </div>
    @endif

    {{-- L'envoi passe par fetch (@submit.prevent), mais le formulaire declare
         quand meme sa destination : les champs portent deja leur `name`, et si
         le JavaScript ne se charge pas, la correction reste possible au lieu de
         disparaitre en silence. Le controleur repond aux deux, JSON ou redirect. --}}
    <form method="POST" action="{{ route('esbtp.paiements.ventilation.update', $paiement->id) }}"
          @submit.prevent="enregistrer">
        @csrf
        @method('PUT')
        <div class="pv-card">
            <div class="pv-card-header">
                <div class="pv-card-icon"><i class="fas fa-scale-balanced"></i></div>
                <div>
                    <div class="pv-card-title">Sur quels frais ce versement est imputé</div>
                    <div class="pv-card-sub">Le total doit valoir exactement le montant encaissé.</div>
                </div>
            </div>
            <div class="pv-card-body">

                @if (empty($pvLignes))
                    <div class="pv-note pv-note--alerte">
                        Aucun frais n'est rattaché à cette inscription et ce versement ne désigne aucune
                        catégorie. Configurez les frais de l'étudiant avant de corriger l'imputation.
                    </div>
                @else
                    <template x-for="(ligne, i) in lignes" :key="ligne.frais_category_id">
                        <div class="pv-ligne" :class="depasse(ligne) ? 'pv-ligne--depasse' : ''">
                            <div>
                                <div class="pv-ligne-nom" x-text="ligne.name"></div>
                                <div class="pv-ligne-reste"
                                     :class="depasse(ligne) ? 'pv-ligne-reste--alerte' : ''"
                                     x-text="detail(ligne)"></div>
                            </div>
                            <input type="number" min="0" step="1" class="pv-input"
                                   :name="'repartition[' + ligne.frais_category_id + ']'"
                                   x-model.number="lignes[i].montant"
                                   @wheel.prevent>
                        </div>
                    </template>

                    <div class="pv-total"
                         :class="ecart === 0 ? 'pv-total--ok' : 'pv-total--ko'">
                        <span>Total réparti</span>
                        <span>
                            <span x-text="formater(totalReparti)"></span> /
                            {{ number_format($pvMontant, 0, ',', ' ') }} FCFA
                        </span>
                    </div>

                    <div class="pv-note pv-note--alerte" style="margin-top:.75rem;" x-show="alerteDepassement" x-cloak>
                        <i class="fas fa-circle-info me-2"></i>
                        Un frais reçoit plus qu'il ne réclame. C'est possible — une avance se dépose sur le
                        frais que le versement désigne — mais l'enregistrement le refusera pour les autres.
                    </div>
                @endif
            </div>
        </div>

        <div class="pv-card">
            <div class="pv-card-header">
                <div class="pv-card-icon"><i class="fas fa-pen-to-square"></i></div>
                <div>
                    <div class="pv-card-title">Motif de la correction</div>
                    <div class="pv-card-sub">Conservé avec l'ancienne et la nouvelle répartition.</div>
                </div>
            </div>
            <div class="pv-card-body">
                <textarea class="pv-motif" name="motif" x-model="motif" maxlength="1000"
                          placeholder="Ce qui était faux et pourquoi. Par exemple : « La ramette et la chemise cartonnée n'étaient pas encore configurées le jour de l'encaissement, tout avait été imputé à la scolarité. »"></textarea>
                <div class="pv-motif-compteur">
                    <span x-text="motif.trim().length"></span> / 30 caractères minimum
                </div>

                <div class="pv-note" style="margin-top:1rem;">
                    <i class="fas fa-receipt me-2"></i>
                    Le reçu <strong>{{ $paiement->numero_recu ?: '—' }}</strong> n'est pas réécrit : son numéro,
                    son montant et sa date restent les mêmes. Un exemplaire réédité après cette correction
                    portera la mention datée de la rectification, pour qu'on distingue toujours les deux
                    versions du papier.
                </div>

                <div class="pv-note pv-note--erreur" style="margin-top:1rem;" x-show="erreur" x-cloak>
                    <i class="fas fa-triangle-exclamation me-2"></i><span x-text="erreur"></span>
                </div>

                <div class="pv-actions">
                    <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="pv-btn pv-btn--secondary">
                        Annuler
                    </a>
                    <button type="submit" class="pv-btn pv-btn--primary" :disabled="!envoyable">
                        <i class="fas fa-check"></i>
                        <span x-show="!envoi">Enregistrer la correction</span>
                        <span x-show="envoi" x-cloak>Enregistrement…</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@php
    $pvEtat = [
        'lignes' => $pvLignes,
        'montant' => $pvMontant,
        'url' => route('esbtp.paiements.ventilation.update', $paiement->id),
    ];
@endphp
<script>
// L'etat initial passe par un attribut de donnees plutot que par une
// interpolation dans x-data : un array literal multiligne dans une directive
// Blade casse le compilateur (cf. .claude/rules/blade-pitfalls.md, piege 4).
window.__pvEtat = @json($pvEtat);

document.addEventListener('alpine:init', function () {
    Alpine.data('ventilationPaiement', function () {
        return {
            lignes: window.__pvEtat.lignes.map(function (l) { return Object.assign({}, l); }),
            montant: window.__pvEtat.montant,
            url: window.__pvEtat.url,
            motif: '',
            envoi: false,
            erreur: '',

            get totalReparti() {
                return this.lignes.reduce(function (somme, l) {
                    var v = parseFloat(l.montant);
                    return somme + (isFinite(v) ? v : 0);
                }, 0);
            },

            get ecart() {
                // Arrondi au centime : sans lui, 0.1 + 0.2 empeche l'egalite.
                return Math.round((this.montant - this.totalReparti) * 100) / 100;
            },

            get alerteDepassement() {
                var self = this;
                return this.lignes.some(function (l) { return self.depasse(l); });
            },

            // Le bouton n'est qu'un raccourci de politesse : c'est
            // l'enregistrement qui refuse pour de bon, avec les memes regles
            // pour tous les appelants. On ne bloque donc que sur ce qui est
            // certain ici — le compte et le motif.
            get envoyable() {
                return !this.envoi && this.ecart === 0 && this.motif.trim().length >= 30;
            },

            depasse: function (ligne) {
                if (ligne.reste === null || ligne.reste === undefined) {
                    return false;
                }
                var v = parseFloat(ligne.montant);
                return isFinite(v) && (v - ligne.reste) >= 0.01;
            },

            detail: function (ligne) {
                if (ligne.reste === null || ligne.reste === undefined) {
                    return "Montant de ce frais non configuré — aucun plafond.";
                }
                return this.formater(ligne.reste) + " FCFA restent dus sur ce frais (hors ce versement).";
            },

            formater: function (valeur) {
                var n = Math.round(parseFloat(valeur) || 0);
                var signe = n < 0 ? '-' : '';
                return signe + String(Math.abs(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            },

            enregistrer: async function () {
                if (!this.envoyable) {
                    return;
                }
                this.envoi = true;
                this.erreur = '';

                var parts = {};
                this.lignes.forEach(function (l) {
                    var v = parseFloat(l.montant);
                    parts[l.frais_category_id] = isFinite(v) ? v : 0;
                });

                try {
                    var reponse = await fetch(this.url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ motif: this.motif, repartition: parts })
                    });

                    var donnees = await reponse.json().catch(function () { return {}; });

                    if (!reponse.ok) {
                        // Laravel renvoie les erreurs de validation sous `errors`
                        // et les refus metier sous `message`.
                        var premiere = donnees.errors
                            ? Object.values(donnees.errors)[0][0]
                            : null;
                        throw new Error(premiere || donnees.message || 'La correction a été refusée.');
                    }

                    // EXCEPTION ajax-no-reload-premium : la correction change la
                    // ventilation, la mention du recu et la piste d'audit. Rester
                    // sur un formulaire qui ne decrit plus l'etat reel serait pire
                    // qu'une navigation.
                    window.location = donnees.redirect;
                } catch (e) {
                    this.erreur = e.message;
                    this.envoi = false;
                }
            }
        };
    });
});
</script>
@endpush

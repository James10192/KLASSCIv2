@extends('layouts.app')

@section('title', 'Sorties BTS Tronc Commun')

@push('styles')
<style>
[x-cloak] { display: none !important; }

.ot-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2.25rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.ot-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.ot-hero-left { display:flex; align-items:flex-start; gap:1rem; flex:1; }
.ot-hero-icon { width:50px; height:50px; border-radius:13px; background:rgba(255,255,255,.15); backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.20); display:flex; align-items:center; justify-content:center; font-size:1.25rem; color:#fff; flex-shrink:0; }
.ot-hero h1 { font-size:1.4rem; font-weight:700; color:#fff; margin:0 0 .3rem; }
.ot-hero p { color:rgba(255,255,255,.80); font-size:.88rem; margin:0; }

.ot-empty {
    background: #fff; border:1px dashed #cbd5e1; border-radius:14px;
    padding: 3rem 2rem; text-align:center; color:#64748b;
}
.ot-empty i { font-size:2.5rem; color:#cbd5e1; display:block; margin-bottom:.85rem; }
.ot-empty h3 { font-size:1.05rem; color:#1e293b; margin:0 0 .35rem; }
.ot-empty p { font-size:.85rem; margin:0; }

.ot-card {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    margin-bottom:1rem;
    box-shadow:0 1px 3px rgba(15,23,42,.04);
    position:relative; z-index:1;
}
.ot-card:focus-within { z-index:10; }
.ot-card-header {
    padding: 1rem 1.25rem .85rem;
    border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; gap:.65rem;
}
.ot-card-icon {
    width:38px; height:38px; border-radius:10px;
    background:linear-gradient(135deg,#0453cb,#3b7ddb);
    color:#fff; display:flex; align-items:center; justify-content:center; font-size:.95rem;
    box-shadow:0 2px 6px rgba(4,83,203,.22); flex-shrink:0;
}
.ot-card-title { font-size:.95rem; font-weight:700; color:#0f172a; }
.ot-card-subtitle { font-size:.72rem; color:#64748b; font-weight:500; }
.ot-card-meta {
    margin-left:auto; font-size:.7rem; color:#64748b; font-weight:600;
    padding:.15rem .55rem; background:#f1f5f9; border-radius:6px;
}
.ot-card-body { padding: 1rem 1.25rem; }

.ot-bulk-panel {
    background:#fff; border:1px solid #dbeafe; border-radius:14px;
    padding:1rem 1.25rem; margin-bottom:1rem;
    box-shadow:0 1px 3px rgba(15,23,42,.04);
}
.ot-bulk-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:.9rem; }
.ot-bulk-title { display:flex; align-items:center; gap:.6rem; font-size:.95rem; font-weight:700; color:#0f172a; }
.ot-bulk-title i { width:32px; height:32px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; color:#0453cb; background:#eff6ff; }
.ot-bulk-subtitle { color:#64748b; font-size:.78rem; margin-top:.2rem; }
.ot-bulk-grid { display:grid; grid-template-columns:minmax(240px, 1.1fr) minmax(280px, 1.5fr) minmax(170px, .7fr); gap:.8rem; align-items:start; }
.ot-bulk-field { display:flex; flex-direction:column; gap:.4rem; min-width:0; }
.ot-bulk-label { font-size:.76rem; font-weight:700; color:#334155; display:flex; align-items:center; gap:.35rem; }
.ot-bulk-destinations {
    border:1px solid #e2e8f0; border-radius:10px; background:#f8fafc;
    max-height:190px; overflow:auto; padding:.45rem;
}
.ot-bulk-destination {
    display:flex; align-items:center; gap:.55rem; padding:.5rem .55rem;
    border-radius:8px; cursor:pointer; font-size:.82rem; color:#1e293b;
}
.ot-bulk-destination:hover { background:#eff6ff; }
.ot-bulk-destination input { width:16px; height:16px; accent-color:#0453cb; }
.ot-bulk-destination--disabled { opacity:.45; cursor:not-allowed; }
.ot-bulk-actions { display:flex; gap:.45rem; flex-wrap:wrap; align-items:center; }
.ot-segment {
    display:inline-flex; border:1px solid #dbeafe; border-radius:10px; overflow:hidden; background:#fff;
}
.ot-segment button {
    border:0; background:#fff; color:#475569; padding:.5rem .7rem;
    font-size:.76rem; font-weight:700; cursor:pointer;
}
.ot-segment button.active { background:#0453cb; color:#fff; }
@media (max-width: 992px) { .ot-bulk-grid { grid-template-columns:1fr; } }

.ot-target-row {
    display:flex; align-items:center; gap:.7rem;
    padding:.65rem .85rem;
    background:#f8fafc; border:1px solid #e2e8f0;
    border-radius:10px; margin-bottom:.45rem;
    transition: background .15s, border-color .15s;
}
.ot-target-row:hover { background:#eff6ff; border-color:rgba(4,83,203,.30); }
.ot-target-icon {
    width:32px; height:32px; border-radius:9px;
    background:linear-gradient(135deg,#0453cb,#3b7ddb);
    color:#fff; display:inline-flex; align-items:center; justify-content:center;
    font-size:.8rem; flex-shrink:0;
}
.ot-target-name { font-weight:600; color:#0f172a; font-size:.88rem; }
.ot-target-meta { font-size:.72rem; color:#64748b; }
.ot-target-actions { display:flex; gap:.3rem; flex-shrink:0; }
.ot-target-semestre {
    background:rgba(4,83,203,.10); color:#0453cb; border:1px solid rgba(4,83,203,.25);
    padding:.18rem .5rem; border-radius:5px; font-size:.7rem; font-weight:700;
    font-family:'SFMono-Regular',Consolas,monospace;
}
.ot-target-toggle {
    appearance:none; cursor:pointer;
    width:34px; height:18px; border-radius:9px;
    background:#cbd5e1; position:relative; transition: background .15s;
}
.ot-target-toggle::after {
    content:''; position:absolute; top:2px; left:2px;
    width:14px; height:14px; border-radius:50%; background:#fff;
    transition: left .15s;
}
.ot-target-toggle:checked { background:#10b981; }
.ot-target-toggle:checked::after { left:18px; }

.ot-add-form {
    margin-top:.5rem; padding:.85rem 1rem;
    background:linear-gradient(180deg, transparent, rgba(4,83,203,.04));
    border:1px dashed #cbd5e1; border-radius:10px;
    display:flex; gap:.5rem; align-items:end; flex-wrap:wrap;
}
.ot-add-form select, .ot-add-form input {
    border:1px solid #e2e8f0; border-radius:8px;
    padding:.45rem .65rem; font-size:.85rem; background:#fff;
}
.ot-add-form select { flex:1; min-width:200px; }
.ot-add-form input[type="number"] { width:90px; }
.ot-btn {
    padding:.45rem .85rem; border-radius:8px;
    font-size:.78rem; font-weight:600; cursor:pointer;
    display:inline-flex; align-items:center; gap:.35rem;
    border:1px solid; text-decoration:none; transition: all .15s ease;
}
.ot-btn--primary { background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; border-color:transparent; }
.ot-btn--primary:hover { background:linear-gradient(135deg,#033a8e,#0453cb); color:#fff; transform:translateY(-1px); }
.ot-btn--ghost { background:transparent; color:#64748b; border-color:#e2e8f0; }
.ot-btn--ghost:hover { background:#f1f5f9; color:#0453cb; }
.ot-btn--danger { background:transparent; color:#dc2626; border-color:rgba(220,38,38,.3); }
.ot-btn--danger:hover { background:rgba(220,38,38,.08); }
.ot-btn:disabled { opacity:.55; cursor:wait; }

.ot-toasts { position:fixed; bottom:1.5rem; right:1.5rem; z-index:1100;
    display:flex; flex-direction:column; gap:.5rem; max-width:400px; }
.ot-toast { display:flex; gap:.6rem; padding:.7rem 1rem; border-radius:10px;
    background:#fff; border:1px solid #e2e8f0; box-shadow:0 8px 24px rgba(15,23,42,.12); font-size:.85rem; }
.ot-toast--success { border-left:4px solid #10b981; color:#065f46; }
.ot-toast--error { border-left:4px solid #dc2626; color:#991b1b; }
</style>
@endpush

@section('content')
<div x-data="orientationTargets()" x-init="init()">
    @php
        $bulkClassOptions = $sourceClasses->mapWithKeys(fn ($classe) => [
            $classe->id => $classe->name . ' · ' . ($classe->niveauEtude?->name ?? 'Niveau non défini'),
        ])->toArray();
    @endphp

    {{-- HERO --}}
    <div class="ot-hero">
        <div class="ot-hero-top">
            <div class="ot-hero-left">
                <div class="ot-hero-icon"><i class="fas fa-route"></i></div>
                <div>
                    <h1>Sorties BTS Tronc Commun</h1>
                    <p>Configurez les spécialités possibles pour chaque classe de tronc commun. Sans configuration, le bouton « Orienter » sur fiche étudiant affiche un message d'erreur.</p>
                </div>
            </div>
            <div style="display:flex; gap:.45rem;">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="ot-btn ot-btn--ghost" style="color:#fff; border-color:rgba(255,255,255,.25); background:rgba(255,255,255,.15);">
                    <i class="fas fa-arrow-left"></i> Inscriptions
                </a>
            </div>
        </div>
    </div>

    @if($sourceClasses->isEmpty())
        <div class="ot-empty">
            <i class="fas fa-route"></i>
            <h3>Aucune classe Tronc Commun configurée</h3>
            <p>Pour activer cette page, créez d'abord une filière marquée « tronc commun », puis une classe rattachée à cette filière.</p>
            <div style="display:flex; gap:.55rem; justify-content:center; flex-wrap:wrap; margin-top:1.25rem;">
                <a href="{{ route('esbtp.filieres.create') }}" class="ot-btn ot-btn--primary" style="font-size:.82rem;">
                    <i class="fas fa-plus"></i> Créer une filière TC
                </a>
                <a href="{{ route('esbtp.filieres.index') }}" class="ot-btn ot-btn--ghost" style="font-size:.82rem;">
                    <i class="fas fa-list"></i> Voir mes filières
                </a>
            </div>
        </div>
    @else
        <div class="ot-bulk-panel">
            <div class="ot-bulk-head">
                <div>
                    <div class="ot-bulk-title">
                        <i class="fas fa-copy"></i>
                        Copier les sorties d'une classe
                    </div>
                    <div class="ot-bulk-subtitle">
                        Utilisez une classe déjà configurée comme modèle, puis appliquez ses spécialités à une ou plusieurs classes sans recharger la page.
                    </div>
                </div>
                <div class="ot-bulk-actions">
                    <button type="button" class="ot-btn ot-btn--ghost" @click="selectAllDestinations()">
                        <i class="fas fa-check-double"></i> Tout sélectionner
                    </button>
                    <button type="button" class="ot-btn ot-btn--ghost" @click="clearDestinations()">
                        <i class="fas fa-eraser"></i> Vider
                    </button>
                </div>
            </div>

            <div class="ot-bulk-grid">
                <div class="ot-bulk-field">
                    <label class="ot-bulk-label"><i class="fas fa-chalkboard"></i> Classe modèle</label>
                    <x-au-select
                        name="bulk_source_classe_id"
                        placeholder="Choisir une classe à copier"
                        icon="fa-copy"
                        :searchable="count($bulkClassOptions) > 6"
                        :options="$bulkClassOptions"
                        @change="setBulkSource($event.target.value)" />
                </div>

                <div class="ot-bulk-field">
                    <label class="ot-bulk-label"><i class="fas fa-layer-group"></i> Classes à mettre à jour</label>
                    <div class="ot-bulk-destinations">
                        @foreach($sourceClasses as $destinationClasse)
                            <label class="ot-bulk-destination"
                                   :class="{ 'ot-bulk-destination--disabled': String(bulk.sourceClasseId) === '{{ $destinationClasse->id }}' }">
                                <input type="checkbox"
                                       value="{{ $destinationClasse->id }}"
                                       :disabled="String(bulk.sourceClasseId) === '{{ $destinationClasse->id }}'"
                                       @change="toggleDestination('{{ $destinationClasse->id }}', $event.target.checked)">
                                <span>{{ $destinationClasse->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="ot-bulk-field">
                    <label class="ot-bulk-label"><i class="fas fa-sliders-h"></i> Mode</label>
                    <div class="ot-segment" role="group" aria-label="Mode de copie">
                        <button type="button" :class="{ active: bulk.mode === 'merge' }" @click="bulk.mode = 'merge'">Fusionner</button>
                        <button type="button" :class="{ active: bulk.mode === 'replace' }" @click="bulk.mode = 'replace'">Remplacer</button>
                    </div>
                    <button type="button" class="ot-btn ot-btn--primary" style="justify-content:center; margin-top:.35rem;"
                            :disabled="bulk.loading" @click="bulkCopyTargets()">
                        <i class="fas" :class="bulk.loading ? 'fa-spinner fa-spin' : 'fa-bolt'"></i>
                        <span x-text="bulk.loading ? 'Copie...' : 'Appliquer'"></span>
                    </button>
                    <div class="ot-bulk-subtitle">
                        Fusionner garde les sorties existantes. Remplacer supprime d'abord les sorties des classes sélectionnées.
                    </div>
                </div>
            </div>
        </div>

        @foreach($sourceClasses as $sourceClasse)
            @php
                $candidates = $candidatesByClasse[$sourceClasse->id] ?? collect();
                $hasFilles = $hasFillesByClasse[$sourceClasse->id] ?? false;
                $candidateOptions = $candidates->mapWithKeys(fn ($c) => [
                    $c->id => $c->name . ($c->filiere ? ' · ' . $c->filiere->name : ''),
                ])->toArray();
            @endphp
            <div class="ot-card" data-class-card="{{ $sourceClasse->id }}">
                <div class="ot-card-header">
                    <div class="ot-card-icon"><i class="fas fa-chalkboard"></i></div>
                    <div style="flex:1; min-width:0;">
                        <div class="ot-card-title">{{ $sourceClasse->name }}</div>
                        <div class="ot-card-subtitle">
                            {{ $sourceClasse->filiere?->name ?? '—' }}
                            @if($sourceClasse->niveauEtude) · {{ $sourceClasse->niveauEtude->name }} @endif
                            @if($sourceClasse->anneeUniversitaire) · {{ $sourceClasse->anneeUniversitaire->name }} @endif
                        </div>
                    </div>
                    <span class="ot-card-meta" data-target-counter>
                        {{ $sourceClasse->orientationTargets->count() }} spécialité{{ $sourceClasse->orientationTargets->count() > 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="ot-card-body">
                    <div data-targets-for="{{ $sourceClasse->id }}">
                    @forelse($sourceClasse->orientationTargets->sortBy('sort_order') as $target)
                        <div class="ot-target-row" data-target-id="{{ $target->id }}">
                            <span class="ot-target-icon"><i class="fas fa-graduation-cap"></i></span>
                            <div style="flex:1; min-width:0;">
                                <div class="ot-target-name">{{ $target->targetClasse?->name ?? '— classe supprimée —' }}</div>
                                <div class="ot-target-meta">
                                    {{ $target->targetClasse?->filiere?->name ?? '—' }}
                                    @if($target->notes) · <em>{{ $target->notes }}</em> @endif
                                </div>
                            </div>
                            <span class="ot-target-semestre" title="Semestre d'activation de la spécialisation">
                                S{{ $target->semestre_activation }}
                            </span>
                            <input type="checkbox" class="ot-target-toggle"
                                {{ $target->is_active ? 'checked' : '' }}
                                @change="toggleActive({{ $target->id }}, $event.target.checked)"
                                title="Activer/désactiver cette sortie">
                            <button class="ot-btn ot-btn--danger" @click="deleteTarget({{ $target->id }}, $event.target.closest('button'))" title="Supprimer cette sortie">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    @empty
                        <div data-empty-marker style="padding:1rem; text-align:center; color:#94a3b8; font-size:.85rem;">
                            <i class="fas fa-arrow-down"></i> Aucune sortie configurée — ajoutez-en une ci-dessous.
                        </div>
                    @endforelse
                    </div>

                    @if($candidates->isNotEmpty())
                        <form @submit.prevent="addTarget($el, {{ $sourceClasse->id }})" class="ot-add-form" data-add-form="{{ $sourceClasse->id }}">
                            @csrf
                            <input type="hidden" name="source_classe_id" value="{{ $sourceClasse->id }}">
                            <x-au-select
                                name="target_classe_id"
                                placeholder="+ Ajouter une spécialité possible"
                                icon="fa-graduation-cap"
                                :searchable="count($candidateOptions) > 6"
                                :options="$candidateOptions" />
                            <input type="number" name="semestre_activation" min="1" max="8" value="2" title="Semestre d'activation" placeholder="S2">
                            <input type="text" name="notes" maxlength="500" placeholder="Note (optionnel)" style="flex:1; min-width:120px;">
                            <button type="submit" class="ot-btn ot-btn--primary">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </form>
                    @else
                        {{-- État vide actionnable : pas juste un message, mais des boutons CTA précis selon l'état data. --}}
                        <div style="margin-top:.5rem; padding:.95rem 1rem; background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.25); border-radius:10px; color:#92400e;">
                            <div style="display:flex; gap:.55rem; align-items:flex-start;">
                                <i class="fas fa-circle-exclamation" style="margin-top:.18rem;"></i>
                                <div style="flex:1; min-width:0;">
                                    @if($hasFilles)
                                        <strong>Aucune classe de spécialité disponible pour ce niveau.</strong>
                                        <div style="font-size:.78rem; margin-top:.18rem;">
                                            Les filières-filles de ce tronc commun existent, mais aucune classe n'est encore créée au niveau <em>{{ $sourceClasse->niveauEtude?->name ?? '—' }}</em>. Créez-en une pour pouvoir l'ajouter comme sortie.
                                        </div>
                                    @else
                                        <strong>Aucune filière-fille déclarée pour ce tronc commun.</strong>
                                        <div style="font-size:.78rem; margin-top:.18rem;">
                                            Marquez d'abord une filière de spécialité comme rattachée à ce tronc commun (<em>{{ $sourceClasse->filiere?->name ?? '—' }}</em> #{{ $sourceClasse->filiere_id }}), puis créez-y une classe au niveau <em>{{ $sourceClasse->niveauEtude?->name ?? '—' }}</em>.
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.75rem;">
                                @if($hasFilles)
                                    <a href="{{ route('esbtp.classes.create', ['niveau_etude_id' => $sourceClasse->niveau_etude_id]) }}"
                                       class="ot-btn ot-btn--primary" style="font-size:.78rem;">
                                        <i class="fas fa-plus"></i> Créer une classe spécialité
                                    </a>
                                @else
                                    <a href="{{ route('esbtp.filieres.create', ['parent_id' => $sourceClasse->filiere_id]) }}"
                                       class="ot-btn ot-btn--primary" style="font-size:.78rem;">
                                        <i class="fas fa-plus"></i> Créer une filière-fille
                                    </a>
                                    <a href="{{ route('esbtp.filieres.index') }}"
                                       class="ot-btn ot-btn--ghost" style="font-size:.78rem;">
                                        <i class="fas fa-list"></i> Voir mes filières
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <div class="ot-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="ot-toast" :class="'ot-toast--' + t.type">
                <i class="fas" :class="t.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function orientationTargets() {
    return {
        toasts: [], toastId: 0,
        bulk: {
            sourceClasseId: '',
            destinationClasseIds: [],
            mode: 'merge',
            loading: false,
        },
        init() {},

        setBulkSource(id) {
            this.bulk.sourceClasseId = String(id || '');
            this.bulk.destinationClasseIds = this.bulk.destinationClasseIds
                .filter(item => item !== this.bulk.sourceClasseId);

            document.querySelectorAll('.ot-bulk-destination input[type="checkbox"]').forEach(input => {
                if (String(input.value) === this.bulk.sourceClasseId) {
                    input.checked = false;
                }
            });
        },

        toggleDestination(id, checked) {
            const value = String(id);
            if (checked && !this.bulk.destinationClasseIds.includes(value)) {
                this.bulk.destinationClasseIds.push(value);
            }
            if (!checked) {
                this.bulk.destinationClasseIds = this.bulk.destinationClasseIds.filter(item => item !== value);
            }
        },

        selectAllDestinations() {
            if (!this.bulk.sourceClasseId) {
                this.toast('error', 'Choisissez d’abord une classe modèle.');
                return;
            }

            this.bulk.destinationClasseIds = Array.from(document.querySelectorAll('.ot-bulk-destination input[type="checkbox"]'))
                .filter(input => !input.disabled)
                .map(input => String(input.value));

            document.querySelectorAll('.ot-bulk-destination input[type="checkbox"]').forEach(input => {
                input.checked = this.bulk.destinationClasseIds.includes(String(input.value));
            });
        },

        clearDestinations() {
            this.bulk.destinationClasseIds = [];
            document.querySelectorAll('.ot-bulk-destination input[type="checkbox"]').forEach(input => {
                input.checked = false;
            });
        },

        async bulkCopyTargets() {
            if (!this.bulk.sourceClasseId) {
                this.toast('error', 'Choisissez une classe modèle.');
                return;
            }

            const destinationIds = this.bulk.destinationClasseIds
                .filter(id => String(id) !== String(this.bulk.sourceClasseId));

            if (destinationIds.length === 0) {
                this.toast('error', 'Sélectionnez au moins une classe à mettre à jour.');
                return;
            }

            this.bulk.loading = true;
            try {
                const res = await fetch('{{ route("esbtp.admin.orientation-targets.bulk-copy") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        source_classe_id: this.bulk.sourceClasseId,
                        destination_classe_ids: destinationIds,
                        mode: this.bulk.mode,
                    }),
                });
                const body = await res.json();
                if (res.status === 422) {
                    this.toast('error', body.message || Object.values(body.errors || {}).flat().join(' · '));
                    return;
                }
                if (!res.ok) throw new Error('Erreur ' + res.status);

                Object.entries(body.classes || {}).forEach(([classeId, targets]) => {
                    this.replaceTargetRows(classeId, targets || []);
                    this.syncSelectOptionsAfterBulk(classeId, targets || []);
                });

                this.toast('success', `${body.created} sortie(s) créée(s), ${body.updated} mise(s) à jour`);
            } catch (e) {
                this.toast('error', e.message);
            } finally {
                this.bulk.loading = false;
            }
        },

        async addTarget(formEl, sourceClasseId) {
            const fd = new FormData(formEl);
            const payload = {};
            fd.forEach((v, k) => { if (k !== '_token' && v !== '') payload[k] = v; });

            if (!payload.target_classe_id) {
                this.toast('error', 'Sélectionnez une classe de spécialité.');
                return;
            }

            try {
                const res = await fetch('{{ route("esbtp.admin.orientation-targets.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                if (res.status === 422) {
                    const body = await res.json();
                    const errors = Object.values(body.errors || {}).flat();
                    this.toast('error', errors.join(' · '));
                    return;
                }
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const body = await res.json();
                this.appendTargetRow(sourceClasseId, body.target);
                this.removeOptionFromSelect(formEl, payload.target_classe_id);
                this.resetForm(formEl);
                this.incrementCardCounter(sourceClasseId);
                this.toast('success', 'Spécialité ajoutée');
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        async toggleActive(id, isActive) {
            try {
                const res = await fetch(`/esbtp/admin/orientation-targets/${id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ is_active: isActive ? 1 : 0 }),
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                this.toast('success', isActive ? 'Sortie activée' : 'Sortie désactivée');
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        async deleteTarget(id, btnEl) {
            if (btnEl && btnEl.dataset.confirm !== '1') {
                btnEl.dataset.confirm = '1';
                this.toast('error', 'Cliquez encore une fois pour confirmer la suppression.');
                setTimeout(() => {
                    btnEl.dataset.confirm = '0';
                }, 3500);
                return;
            }
            try {
                const res = await fetch(`/esbtp/admin/orientation-targets/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                this.removeTargetRow(btnEl);
                this.toast('success', 'Sortie supprimée');
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        appendTargetRow(sourceClasseId, target) {
            const container = document.querySelector(`[data-targets-for="${sourceClasseId}"]`);
            if (!container) { return; }
            const emptyMarker = container.querySelector('[data-empty-marker]');
            if (emptyMarker) emptyMarker.remove();
            const targetName = target.target_classe?.name ?? '— classe inconnue —';
            const filiereName = target.target_classe?.filiere?.name ?? '—';
            const html = `
                <div class="ot-target-row" data-target-id="${target.id}">
                    <span class="ot-target-icon"><i class="fas fa-graduation-cap"></i></span>
                    <div style="flex:1; min-width:0;">
                        <div class="ot-target-name">${this.escape(targetName)}</div>
                        <div class="ot-target-meta">${this.escape(filiereName)}${target.notes ? ' · <em>' + this.escape(target.notes) + '</em>' : ''}</div>
                    </div>
                    <span class="ot-target-semestre" title="Semestre d'activation">S${target.semestre_activation}</span>
                    <input type="checkbox" class="ot-target-toggle" checked
                        @change="toggleActive(${target.id}, $event.target.checked)" title="Activer/désactiver">
                    <button class="ot-btn ot-btn--danger" @click="deleteTarget(${target.id}, $event.target.closest('button'))" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>`;
            container.insertAdjacentHTML('beforeend', html);
            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(container.lastElementChild);
            }
        },

        replaceTargetRows(sourceClasseId, targets) {
            const container = document.querySelector(`[data-targets-for="${sourceClasseId}"]`);
            if (!container) { return; }
            container.innerHTML = '';

            if (targets.length === 0) {
                container.innerHTML = `
                    <div data-empty-marker style="padding:1rem; text-align:center; color:#94a3b8; font-size:.85rem;">
                        <i class="fas fa-arrow-down"></i> Aucune sortie configurée, ajoutez-en une ci-dessous.
                    </div>`;
            } else {
                targets.forEach(target => this.appendTargetRow(sourceClasseId, target));
            }

            this.setCardCounter(sourceClasseId, targets.length);
        },

        removeTargetRow(btnEl) {
            const row = btnEl?.closest?.('.ot-target-row');
            if (!row) return;
            const card = row.closest('.ot-card');
            row.remove();
            const counter = card?.querySelector('.ot-card-meta');
            if (counter) {
                const n = Math.max(0, parseInt(counter.textContent) - 1);
                counter.textContent = n + ' spécialité' + (n > 1 ? 's' : '');
            }
        },

        incrementCardCounter(sourceClasseId) {
            const container = document.querySelector(`[data-targets-for="${sourceClasseId}"]`);
            const counter = container?.closest('.ot-card')?.querySelector('.ot-card-meta');
            if (counter) {
                const n = parseInt(counter.textContent) + 1;
                counter.textContent = n + ' spécialité' + (n > 1 ? 's' : '');
            }
        },

        setCardCounter(sourceClasseId, count) {
            const card = document.querySelector(`[data-class-card="${sourceClasseId}"]`);
            const counter = card?.querySelector('[data-target-counter]');
            if (counter) {
                counter.textContent = count + ' spécialité' + (count > 1 ? 's' : '');
            }
        },

        removeOptionFromSelect(formEl, value) {
            // au-select expose le native select via la classe au-select-native
            const native = formEl.querySelector('select.au-select-native[name="target_classe_id"]');
            if (!native) return;
            const opt = native.querySelector(`option[value="${value}"]`);
            if (opt) opt.remove();
        },

        resetForm(formEl) {
            const native = formEl.querySelector('select.au-select-native[name="target_classe_id"]');
            if (native) {
                native.value = '';
                native.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const semestre = formEl.querySelector('input[name="semestre_activation"]');
            const notes = formEl.querySelector('input[name="notes"]');
            if (semestre) semestre.value = 2;
            if (notes) notes.value = '';
        },

        syncSelectOptionsAfterBulk(sourceClasseId, targets) {
            const form = document.querySelector(`[data-add-form="${sourceClasseId}"]`);
            if (!form) return;
            const native = form.querySelector('select.au-select-native[name="target_classe_id"]');
            if (!native) return;

            targets.map(target => String(target.target_classe_id)).forEach(id => {
                const opt = native.querySelector(`option[value="${id}"]`);
                if (opt) opt.remove();
            });
            native.value = '';
            native.dispatchEvent(new Event('change', { bubbles: true }));
        },

        escape(s) {
            return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        },

        toast(type, message) {
            const id = ++this.toastId;
            this.toasts.push({ id, type, message });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
        },
    };
}
</script>
@endpush
@endsection

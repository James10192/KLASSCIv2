@extends('layouts.app')
@section('title', 'Modifier examen — '.$examen->titre)

@php
    use App\Enums\ExamenStatus;
    use App\Enums\TypeExamen;
    $statusOptions = ExamenStatus::editableOptions();
    $systeme = $examen->hasConsistentSysteme() ? $examen->systeme : 'MIXTE';
    $typeColors = [
        'EXAMEN' => '#0453cb', 'PARTIEL' => '#3b7ddb',
        'RATTRAPAGE' => '#b45309', 'SOUTENANCE' => '#033a8e',
    ];
    $typeIcons = [
        'EXAMEN' => 'fa-pen-ruler', 'PARTIEL' => 'fa-pen-to-square',
        'RATTRAPAGE' => 'fa-rotate-right', 'SOUTENANCE' => 'fa-microphone',
    ];
    $typeColor = $typeColors[$examen->type_examen] ?? '#0453cb';
    $typeIcon = $typeIcons[$examen->type_examen] ?? 'fa-calendar-check';
@endphp

@push('styles')
<style>
[x-cloak] { display:none !important; }

/* ════════════════════ HERO ════════════════════ */
.exe-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2.25rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.exe-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.exe-hero-left { display:flex; align-items:flex-start; gap:1rem; flex:1; min-width:0; }
.exe-hero-icon {
    width: 50px; height: 50px; border-radius: 13px;
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.20);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; flex-shrink: 0; color: #fff;
}
.exe-hero h1 { font-size: 1.35rem; font-weight: 700; color: #fff; margin: 0 0 .3rem; }
.exe-hero p { color: rgba(255,255,255,.78); font-size: .85rem; margin: 0;
    display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
.exe-hero p code {
    background: rgba(255,255,255,.10); padding: .12rem .5rem; border-radius: 5px;
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: .76rem; color: #fff; font-weight: 600;
    border: 1px solid rgba(255,255,255,.15);
}

.exe-hero-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .55rem; border-radius: 6px;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.22);
    color: #fff; font-size: .72rem; font-weight: 600;
}
.exe-hero-chip i { font-size: .68rem; opacity: .9; }

/* ════════════════════ FORM CARDS ════════════════════ */
.exe-grid {
    display: grid; gap: 1rem; grid-template-columns: 1fr 1fr;
    margin-bottom: 1rem;
}
@@media (max-width: 992px) { .exe-grid { grid-template-columns: 1fr; } }

.exe-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    /* PAS d'overflow:hidden — voir rule css-stacking-pitfalls (au-select dropdown) */
    position: relative; z-index: 1;
}
.exe-card:focus-within { z-index: 10; }

.exe-card-header {
    display: flex; align-items: center; gap: .55rem;
    padding: 1rem 1.25rem .85rem;
    border-bottom: 1px solid #f1f5f9;
}
.exe-card-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-size: .9rem; flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(4,83,203,.22);
}
.exe-card-title { font-size: .95rem; font-weight: 700; color: #0f172a; flex: 1; min-width: 0; }
.exe-card-subtitle { font-size: .72rem; color: #64748b; font-weight: 500; }
.exe-card-body { padding: 1.05rem 1.25rem 1.15rem; }
.exe-card-body--full { grid-column: 1 / -1; }

/* Fields */
.exe-field { display: flex; flex-direction: column; gap: .4rem; }
.exe-field label {
    font-size: .68rem; color: #475569; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
    display: flex; align-items: center; gap: .35rem;
}
.exe-field label i { color: #0453cb; font-size: .68rem; }
.exe-field label .exe-required { color: #dc2626; font-weight: 800; }
.exe-field-help { font-size: .68rem; color: #94a3b8; font-style: italic; }
.exe-field input, .exe-field textarea {
    width: 100%; border: 1px solid #e2e8f0; border-radius: 9px;
    padding: .55rem .75rem; font-size: .88rem;
    background: #fff; color: #1e293b;
    transition: border-color .15s, box-shadow .15s, background .15s;
    box-sizing: border-box;
}
.exe-field input:focus, .exe-field textarea:focus {
    outline: none; border-color: #0453cb; background: #fff;
    box-shadow: 0 0 0 3px rgba(4,83,203,.10);
}
.exe-field input:hover:not(:focus), .exe-field textarea:hover:not(:focus) {
    border-color: #cbd5e1; background: #fafbff;
}
.exe-field textarea { resize: vertical; min-height: 80px; }
.exe-field .au-select, .exe-field .au-select-trigger { width: 100%; box-sizing: border-box; }

.exe-grid-2cols {
    display: grid; gap: .85rem; grid-template-columns: 1fr 1fr;
}
@@media (max-width: 768px) { .exe-grid-2cols { grid-template-columns: 1fr; } }

.exe-checkbox {
    display: flex; align-items: center; gap: .6rem;
    padding: .7rem .85rem;
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.04));
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 10px;
    font-size: .85rem; color: #1e293b; cursor: pointer;
    transition: all .15s;
}
.exe-checkbox:hover { background: linear-gradient(135deg, rgba(4,83,203,.08), rgba(59,125,219,.08)); border-color: rgba(4,83,203,.30); }
.exe-checkbox input { width: 16px; height: 16px; accent-color: #0453cb; cursor: pointer; }
.exe-checkbox-text { flex: 1; }
.exe-checkbox-text strong { display: block; font-weight: 700; color: #0f172a; font-size: .85rem; }
.exe-checkbox-text small { font-size: .72rem; color: #64748b; }

/* ════════════════════ READONLY INFO ════════════════════ */
.exe-readonly {
    background: linear-gradient(135deg, #f8fafc, #eff6ff);
    border: 1px dashed rgba(4,83,203,.25);
    border-radius: 10px;
    padding: .75rem 1rem;
    font-size: .78rem; color: #475569;
    display: flex; gap: .55rem; align-items: flex-start;
    margin-bottom: 1rem;
}
.exe-readonly i { color: #0453cb; padding-top: .15rem; }

/* ════════════════════ FOOTER STICKY ════════════════════ */
.exe-footer {
    background: linear-gradient(135deg, #fff, #f8faff);
    border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 1rem 1.25rem;
    display: flex; align-items: center; gap: .55rem; flex-wrap: wrap;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    position: sticky; bottom: 1rem; z-index: 20;
}
.exe-footer-meta { font-size: .72rem; color: #64748b; flex: 1; min-width: 0; }
.exe-footer-meta strong { color: #0f172a; font-weight: 600; }

.exe-btn {
    padding: .55rem 1.05rem; border-radius: 10px;
    font-size: .82rem; font-weight: 600; border: 1px solid;
    cursor: pointer; display: inline-flex; align-items: center; gap: .4rem;
    text-decoration: none; transition: all .15s ease;
}
.exe-btn--primary { background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; border-color: transparent;
    box-shadow: 0 2px 8px rgba(4,83,203,.22); }
.exe-btn--primary:hover { background: linear-gradient(135deg, #033a8e, #0453cb); color: #fff; transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(4,83,203,.30); }
.exe-btn--secondary { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.exe-btn--secondary:hover { background: #e2e8f0; color: #1e293b; }
.exe-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.25); }
.exe-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; transform: translateY(-1px); }
.exe-btn:disabled { opacity: .55; cursor: wait; transform: none !important; }

/* Error banner */
.exe-error-banner {
    margin: 0 0 1rem; padding: .85rem 1rem;
    background: rgba(220,38,38,.06);
    border: 1px solid rgba(220,38,38,.25);
    border-left: 4px solid #dc2626;
    border-radius: 10px;
    color: #991b1b; font-size: .85rem;
}
.exe-error-banner ul { margin: .35rem 0 0; padding-left: 1.4rem; }
.exe-error-banner strong { font-weight: 700; color: #7f1d1d; }

/* Toast */
.exe-toasts { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 1100;
    display: flex; flex-direction: column; gap: .5rem; max-width: 400px; }
.exe-toast { display: flex; align-items: flex-start; gap: .65rem;
    padding: .75rem 1rem; border-radius: 10px;
    background: #fff; border: 1px solid #e2e8f0;
    box-shadow: 0 8px 24px rgba(15,23,42,.12); font-size: .85rem; }
.exe-toast--success { border-left: 4px solid #10b981; color: #065f46; }
.exe-toast--error { border-left: 4px solid #dc2626; color: #991b1b; }
</style>
@endpush

@section('content')
<div x-data="examenEdit()">

    {{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
    <div class="exe-hero">
        <div class="exe-hero-top">
            <div class="exe-hero-left">
                <div class="exe-hero-icon"><i class="fas fa-pen-to-square"></i></div>
                <div>
                    <h1>Modifier l'examen</h1>
                    <p>
                        @if($examen->numero_convocation)
                            <code>{{ $examen->numero_convocation }}</code>
                        @endif
                        <span class="exe-hero-chip">
                            <i class="fas {{ $typeIcon }}"></i>
                            {{ TypeExamen::labelFor($examen->type_examen) }}
                        </span>
                        <span class="exe-hero-chip">
                            <i class="fas {{ $systeme === 'LMD' ? 'fa-graduation-cap' : ($systeme === 'MIXTE' ? 'fa-circle-exclamation' : 'fa-screwdriver-wrench') }}"></i>
                            {{ $systeme }}
                        </span>
                        @if($examen->classe)
                            <span class="exe-hero-chip">
                                <i class="fas fa-chalkboard"></i>
                                {{ $examen->classe->name }}{{ $examen->classes->count() > 1 ? ' +'.($examen->classes->count() - 1) : '' }}
                            </span>
                        @endif
                        @if($examen->matiere)
                            <span class="exe-hero-chip">
                                <i class="fas fa-book"></i>
                                {{ $examen->matiere->name }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>
            <div style="display:flex; gap:.45rem;">
                <a href="{{ route('esbtp.examens.show', $examen) }}" class="exe-btn exe-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour au détail
                </a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ FORM ═══════════════════════════════ --}}
    <form x-ref="editForm" @submit.prevent="submitEdit()">
        @csrf
        @method('PUT')

        <div x-show="errors.length > 0" x-cloak class="exe-error-banner">
            <strong><i class="fas fa-circle-exclamation"></i> Validation échouée</strong>
            <ul>
                <template x-for="err in errors" :key="err">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>

        {{-- Info : ce qui n'est pas modifiable --}}
        <div class="exe-readonly">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Champs verrouillés en édition :</strong>
                la classe, la matière (ECUE), le parcours, le type d'épreuve et le numéro de convocation
                ne peuvent plus être modifiés après création (préserve l'intégrité UEMOA).
                Pour les changer, supprimez cet examen et créez-en un nouveau.
            </div>
        </div>

        <div class="exe-grid">
            {{-- ═══════════════ Identité ═══════════════ --}}
            <div class="exe-card exe-card-body--full">
                <div class="exe-card-header">
                    <div class="exe-card-icon"><i class="fas fa-file-signature"></i></div>
                    <div>
                        <div class="exe-card-title">Identité de l'épreuve</div>
                        <div class="exe-card-subtitle">Titre + consignes étudiants</div>
                    </div>
                </div>
                <div class="exe-card-body">
                    <div style="display: flex; flex-direction: column; gap: .85rem;">
                        <div class="exe-field">
                            <label><i class="fas fa-heading"></i> Titre <span class="exe-required">*</span></label>
                            <input type="text" name="titre" x-model="form.titre" required maxlength="255"
                                placeholder="Ex : Examen final — Droit Privé S1">
                        </div>
                        <div class="exe-field">
                            <label><i class="fas fa-align-left"></i> Description / Consignes</label>
                            <textarea name="description" x-model="form.description" rows="4" maxlength="1000"
                                placeholder="Consignes étudiants (matériel autorisé, calculatrice, sujets multiples...)"></textarea>
                            <span class="exe-field-help">Sera affichée sur la convocation PDF et en haut de la copie d'examen.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Logistique ═══════════════ --}}
            <div class="exe-card">
                <div class="exe-card-header">
                    <div class="exe-card-icon"><i class="fas fa-calendar-day"></i></div>
                    <div>
                        <div class="exe-card-title">Logistique</div>
                        <div class="exe-card-subtitle">Date, horaires, salle</div>
                    </div>
                </div>
                <div class="exe-card-body">
                    <div class="exe-grid-2cols">
                        <div class="exe-field">
                            <label><i class="far fa-calendar"></i> Date début <span class="exe-required">*</span></label>
                            <input type="datetime-local" name="date_debut" x-model="form.date_debut" required>
                        </div>
                        <div class="exe-field">
                            <label><i class="far fa-calendar"></i> Date fin <span class="exe-required">*</span></label>
                            <input type="datetime-local" name="date_fin" x-model="form.date_fin" required>
                        </div>
                        <div class="exe-field">
                            <label><i class="fas fa-hourglass-half"></i> Durée (minutes)</label>
                            <input type="number" name="duree_minutes" x-model.number="form.duree_minutes"
                                min="15" max="360" placeholder="120">
                        </div>
                        <div class="exe-field">
                            <label><i class="fas fa-door-open"></i> Salle</label>
                            <input type="text" name="salle" x-model="form.salle" maxlength="100"
                                placeholder="Ex : Amphi A, Salle B12">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Notation & Statut ═══════════════ --}}
            <div class="exe-card">
                <div class="exe-card-header">
                    <div class="exe-card-icon" style="background:linear-gradient(135deg,{{ $typeColor }},#3b7ddb);">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <div class="exe-card-title">Notation & Statut</div>
                        <div class="exe-card-subtitle">Coefficient, barème, workflow</div>
                    </div>
                </div>
                <div class="exe-card-body">
                    <div class="exe-grid-2cols">
                        <div class="exe-field">
                            <label><i class="fas fa-times"></i> Coefficient</label>
                            <input type="number" name="coefficient" x-model.number="form.coefficient"
                                step="0.5" min="0" max="99" placeholder="1">
                        </div>
                        <div class="exe-field">
                            <label><i class="fas fa-divide"></i> Barème</label>
                            <input type="number" name="bareme" x-model.number="form.bareme"
                                step="1" min="1" max="100" placeholder="20">
                        </div>
                        <div class="exe-field" style="grid-column: 1 / -1;">
                            <label><i class="fas fa-circle-info"></i> Statut</label>
                            <x-au-select name="status"
                                :value="$examen->status"
                                :options="$statusOptions"
                                icon="fa-circle-info"
                                :placeholderIsFirstOption="false" />
                            <span class="exe-field-help">Le statut <em>Notes verrouillées</em> est défini automatiquement via l'action dédiée (anti-tampering UEMOA).</span>
                        </div>
                        <div class="exe-field" style="grid-column: 1 / -1;">
                            <label class="exe-checkbox">
                                <input type="checkbox" name="is_anonymous" value="1" x-model="form.is_anonymous">
                                <span class="exe-checkbox-text">
                                    <strong><i class="fas fa-mask" style="color:#0453cb;"></i> Anonymiser les copies</strong>
                                    <small>Génère un numéro d'anonymat par étudiant pour la correction (UEMOA recommandé).</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════ FOOTER ═══════════════════════════════ --}}
        <div class="exe-footer">
            <div class="exe-footer-meta">
                @if($examen->updated_at)
                    Dernière modification <strong>{{ $examen->updated_at->diffForHumans() }}</strong>
                @endif
            </div>
            <a href="{{ route('esbtp.examens.show', $examen) }}" class="exe-btn exe-btn--secondary">
                <i class="fas fa-xmark"></i> Annuler
            </a>
            <button type="submit" class="exe-btn exe-btn--primary" :disabled="saving">
                <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                <span x-text="saving ? 'Enregistrement…' : 'Enregistrer les modifications'"></span>
            </button>
        </div>
    </form>

    {{-- Toasts --}}
    <div class="exe-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="exe-toast" :class="'exe-toast--' + t.type" x-transition.opacity>
                <i class="fas" :class="t.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function examenEdit() {
    return {
        saving: false,
        errors: [],
        toasts: [],
        toastId: 0,
        form: {
            titre: @json($examen->titre),
            description: @json($examen->description),
            date_debut: @json(optional($examen->date_debut)->format('Y-m-d\TH:i')),
            date_fin: @json(optional($examen->date_fin)->format('Y-m-d\TH:i')),
            duree_minutes: {{ $examen->duree_minutes ?? 'null' }},
            salle: @json($examen->salle),
            coefficient: {{ $examen->coefficient ?? 1 }},
            bareme: {{ (int) ($examen->bareme ?? 20) }},
            is_anonymous: {{ $examen->is_anonymous ? 'true' : 'false' }},
        },

        init() {
            window.addEventListener('toast', (ev) => this.pushToast(ev.detail));
        },

        async submitEdit() {
            this.errors = [];
            this.saving = true;
            try {
                const fd = new FormData(this.$refs.editForm);
                const payload = {};
                fd.forEach((v, k) => {
                    if (k === '_token' || k === '_method') return;
                    if (v === '' || v === null) return;
                    payload[k] = v;
                });
                payload.is_anonymous = this.form.is_anonymous ? 1 : 0;

                const res = await fetch('{{ route("esbtp.examens.update", $examen) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                if (res.status === 422) {
                    const body = await res.json();
                    this.errors = Object.values(body.errors || { error: ['Validation échouée'] }).flat();
                    this.saving = false;
                    return;
                }
                if (!res.ok) throw new Error('Erreur HTTP ' + res.status);
                this.pushToast({ type: 'success', message: 'Examen mis à jour.' });
                setTimeout(() => window.location = '{{ route("esbtp.examens.show", $examen) }}', 700);
            } catch (e) {
                this.errors = [e.message];
                this.pushToast({ type: 'error', message: e.message });
            } finally {
                this.saving = false;
            }
        },

        pushToast(detail) {
            const id = ++this.toastId;
            this.toasts.push({ id, type: detail.type || 'info', message: detail.message || '' });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
        },
    };
}
</script>
@endpush
@endsection

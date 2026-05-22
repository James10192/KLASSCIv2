@extends('layouts.app')
@section('title', 'Modifier examen — '.$examen->titre)

@php
    use App\Enums\ExamenStatus;
    $statusOptions = ExamenStatus::editableOptions();
@endphp

@push('styles')
<style>
[x-cloak] { display:none !important; }
.exp-form-hero { background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;
    padding:1.5rem 2rem;color:#fff;margin-bottom:1.25rem;}
.exp-form-card { background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.5rem;
    box-shadow:0 1px 3px rgba(15,23,42,.04);}
.exp-form-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:1rem; }
.exp-form-grid .full { grid-column:1/-1; }
.exp-form-field label { display:block;font-size:.72rem;color:#475569;font-weight:600;
    text-transform:uppercase;letter-spacing:.5px;margin-bottom:.35rem;}
.exp-form-field input, .exp-form-field textarea {
    width:100%;border:1px solid #e2e8f0;border-radius:9px;padding:.55rem .7rem;
    font-size:.88rem;background:#fff;color:#1e293b;
    transition: border-color .15s, box-shadow .15s;
}
.exp-form-field input:focus, .exp-form-field textarea:focus {
    outline:none;border-color:#0453cb;box-shadow:0 0 0 3px rgba(4,83,203,.10);
}
.exp-checkbox {
    display:flex;align-items:center;gap:.55rem;padding:.7rem .8rem;
    background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;
    font-size:.85rem;color:#1e293b;cursor:pointer;text-transform:none;font-weight:500;
}
.exp-checkbox input { width:auto;accent-color:#0453cb; }
.exp-form-actions { margin-top:1.5rem;display:flex;gap:.5rem;justify-content:flex-end;}
.btn { padding:.6rem 1.2rem;border-radius:10px;font-weight:600;font-size:.85rem;
    border:1px solid;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;}
.btn-primary{background:#0453cb;color:#fff;border-color:#0453cb;}
.btn-primary:hover{background:#033a8e;color:#fff;}
.btn-primary:disabled{opacity:.6;cursor:wait;}
.btn-secondary{background:#f1f5f9;color:#475569;border-color:#e2e8f0;}
.btn-secondary:hover{background:#e2e8f0;color:#1e293b;}

.exp-error-banner { margin-bottom:1rem;padding:.75rem 1rem;
    background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);
    border-radius:10px;color:#b91c1c;font-size:.85rem; }
.exp-error-banner ul { margin:0;padding-left:1.2rem; }

.exp-toasts { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 1100;
    display: flex; flex-direction: column; gap: .5rem; max-width: 400px; }
.exp-toast { display: flex; align-items: flex-start; gap: .65rem;
    padding: .75rem 1rem; border-radius: 10px;
    background: #fff; border: 1px solid #e2e8f0;
    box-shadow: 0 8px 24px rgba(15,23,42,.12); font-size: .85rem; }
.exp-toast--success { border-left: 4px solid #10b981; color: #065f46; }
.exp-toast--error { border-left: 4px solid #dc2626; color: #991b1b; }

@@media(max-width:768px){.exp-form-grid{grid-template-columns:1fr;}}
</style>
@endpush

@section('content')
<div x-data="examenEdit()">

<div class="exp-form-hero">
    <h1 style="margin:0;font-size:1.3rem;"><i class="fas fa-pen-to-square me-2"></i> Modifier examen</h1>
    <p style="margin:.25rem 0 0;color:rgba(255,255,255,.7);font-size:.85rem;">
        {{ $examen->numero_convocation ?? '—' }} · {{ $examen->classe->name ?? '' }} · {{ $examen->matiere->name ?? '' }}
    </p>
</div>

<form x-ref="editForm" @submit.prevent="submitEdit()" class="exp-form-card">
    @csrf
    @method('PUT')

    <div x-show="errors.length > 0" x-cloak class="exp-error-banner">
        <ul>
            <template x-for="err in errors" :key="err">
                <li x-text="err"></li>
            </template>
        </ul>
    </div>

    <div class="exp-form-grid">
        <div class="exp-form-field full">
            <label>Titre *</label>
            <input type="text" name="titre" x-model="form.titre" required maxlength="255">
        </div>
        <div class="exp-form-field">
            <label>Date début *</label>
            <input type="datetime-local" name="date_debut" x-model="form.date_debut" required>
        </div>
        <div class="exp-form-field">
            <label>Date fin *</label>
            <input type="datetime-local" name="date_fin" x-model="form.date_fin" required>
        </div>
        <div class="exp-form-field">
            <label>Durée (minutes)</label>
            <input type="number" name="duree_minutes" x-model.number="form.duree_minutes" min="15" max="360">
        </div>
        <div class="exp-form-field">
            <label>Salle</label>
            <input type="text" name="salle" x-model="form.salle" maxlength="100">
        </div>
        <div class="exp-form-field">
            <label>Coefficient</label>
            <input type="number" name="coefficient" x-model.number="form.coefficient" step="0.5" min="0" max="99">
        </div>
        <div class="exp-form-field">
            <label>Barème</label>
            <input type="number" name="bareme" x-model.number="form.bareme" step="1" min="1" max="100">
        </div>
        <div class="exp-form-field">
            <label>Statut</label>
            <x-au-select name="status"
                :value="$examen->status"
                :options="$statusOptions"
                icon="fa-circle-info"
                :placeholderIsFirstOption="false" />
        </div>
        <div class="exp-form-field">
            <label class="exp-checkbox">
                <input type="checkbox" name="is_anonymous" value="1" x-model="form.is_anonymous">
                <span><i class="fas fa-mask" style="color:#0453cb;margin-right:.35rem;"></i>
                Anonymiser les copies</span>
            </label>
        </div>
        <div class="exp-form-field full">
            <label>Description</label>
            <textarea name="description" x-model="form.description" rows="3" maxlength="1000"></textarea>
        </div>
    </div>

    <div class="exp-form-actions">
        <a href="{{ route('esbtp.examens.show', $examen) }}" class="btn btn-secondary">
            <i class="fas fa-xmark"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary" :disabled="saving">
            <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
            <span x-text="saving ? 'Enregistrement…' : 'Enregistrer'"></span>
        </button>
    </div>
</form>

<div class="exp-toasts">
    <template x-for="t in toasts" :key="t.id">
        <div class="exp-toast" :class="'exp-toast--' + t.type" x-transition.opacity>
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
                setTimeout(() => window.location = '{{ route("esbtp.examens.show", $examen) }}', 600);
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
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 4000);
        },
    };
}
</script>
@endpush
@endsection

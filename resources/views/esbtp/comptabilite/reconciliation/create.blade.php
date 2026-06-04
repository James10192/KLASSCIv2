@extends('layouts.app')

@section('title', 'Nouvelle session de réconciliation - KLASSCI')

@push('styles')
<style>
    .rec-form-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.5rem;
        max-width: 720px;
        margin: 0 auto;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .rec-form-title { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.25rem; }
    .rec-form-title h1 { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0; }
    .rec-form-icon {
        width: 44px; height: 44px; border-radius: 12px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center; color: #fff;
    }
    .rec-form-row { margin-bottom: 1rem; }
    .rec-form-row label { display: block; font-size: .82rem; font-weight: 600; color: #1e293b; margin-bottom: .3rem; }
    .rec-form-row .hint { font-size: .75rem; color: #64748b; margin-top: .25rem; }
    .rec-form-actions { display: flex; gap: .75rem; justify-content: flex-end; margin-top: 1.5rem; }
    .rec-btn-primary {
        background: #0453cb; color: #fff; border: none;
        padding: .6rem 1.4rem; border-radius: 10px; font-weight: 600; cursor: pointer;
        transition: background .15s;
    }
    .rec-btn-primary:hover:not(:disabled) { background: #033a8e; }
    .rec-btn-primary:disabled { opacity: .6; cursor: wait; }
    .rec-btn-ghost {
        background: transparent; color: #64748b; border: 1px solid #e2e8f0;
        padding: .6rem 1.4rem; border-radius: 10px; font-weight: 600; text-decoration: none;
    }
    .rec-btn-ghost:hover { background: #f8fafc; color: #1e293b; }
</style>
@endpush

@section('content')
<div class="container-fluid" x-data="recCreate()">
    <div class="rec-form-card">
        <div class="rec-form-title">
            <div class="rec-form-icon"><i class="fas fa-plus"></i></div>
            <h1>Nouvelle session de réconciliation</h1>
        </div>

        <p style="font-size:.88rem;color:#64748b;margin-bottom:1.25rem;">
            Ouvrez une nouvelle session de bouclage caisse. La période et la fréquence sont configurables.
            Une fois ouverte, vous saisissez les comptages physiques par mode (espèces, mobile money, etc.).
        </p>

        <form @submit.prevent="submit()">
            @csrf
            <div class="rec-form-row">
                <label for="frequency">Fréquence</label>
                <x-au-select
                    name="frequency"
                    :value="$defaultFrequency"
                    icon="fa-clock"
                    x-model="form.frequency"
                    :options="['daily' => 'Quotidien (1 session par jour)', 'weekly' => 'Hebdomadaire (1 session par semaine)', 'monthly' => 'Mensuel (1 session par mois)']" />
                <div class="hint">Par défaut : valeur du setting tenant comptabilite.reconciliation.frequency</div>
            </div>

            <div class="rec-form-row">
                <label for="start_date">Date de référence</label>
                <input type="date" name="start_date" x-model="form.start_date"
                       class="form-control" style="padding:.55rem .75rem;border-radius:8px;border:1px solid #cbd5e1;">
                <div class="hint">Date qui définit la période (par défaut aujourd'hui).</div>
            </div>

            <div class="rec-form-actions">
                <a href="{{ route('esbtp.comptabilite.reconciliation.index') }}" class="rec-btn-ghost">Annuler</a>
                <button type="submit" class="rec-btn-primary" :disabled="submitting">
                    <span x-show="!submitting"><i class="fas fa-check"></i> Ouvrir la session</span>
                    <span x-show="submitting" x-cloak><i class="fas fa-spinner fa-spin"></i> Ouverture…</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.recCreate = function () {
    return {
        form: {
            frequency: @json($defaultFrequency),
            start_date: new Date().toISOString().slice(0, 10),
        },
        submitting: false,

        async submit() {
            this.submitting = true;
            try {
                const res = await fetch('/esbtp/comptabilite/reconciliation/sessions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);

                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: 'Session ' + data.session.code + ' ouverte.' }
                }));
                window.location.href = '/esbtp/comptabilite/reconciliation/sessions/' + data.session.id;
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.submitting = false;
            }
        },
    };
};
</script>
@endsection

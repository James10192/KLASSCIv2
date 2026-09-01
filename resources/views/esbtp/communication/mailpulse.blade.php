@extends('layouts.app')

@section('title', 'MailPulse - KLASSCI')

@section('content')
<div class="main-content">
    <div class="main-card">
        <div class="main-card-header">
            <div class="main-card-title"><i class="fas fa-envelope"></i> MailPulse</div>
        </div>
        <div class="main-card-body">
            <p class="mb-3" style="color:#64748b;">Envois email et WhatsApp. La clé API se configure dans les paramètres système.</p>
            <div class="alert {{ $enabled ? 'alert-success' : 'alert-warning' }}">
                Canal {{ $enabled ? 'activé' : 'désactivé' }}
                — API {{ $apiConfigured ? 'configurée' : 'à configurer' }}.
            </div>
            @can('mailpulse.send')
            <form id="mp-test" class="row g-3">
                @csrf
                <div class="col-md-5">
                    <label class="form-label-moderne">Événement test</label>
                    <select name="event" class="form-input-moderne">
                        <option value="fee_reminder">Rappel de frais</option>
                        <option value="bulletin_published">Bulletin publié</option>
                        <option value="registration_confirmed">Inscription confirmée</option>
                        <option value="absence_reported">Absence signalée</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label-moderne">Canal</label>
                    <select name="channel" class="form-input-moderne">
                        <option value="both">Email + WhatsApp</option>
                        <option value="email">Email</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-flask me-1"></i>Envoyer un test (dry-run)
                    </button>
                </div>
            </form>
            <pre id="mp-result" class="mt-3" style="display:none;background:#f8fafc;padding:12px;border-radius:8px;"></pre>
            @endcan
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('mp-test')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const box = document.getElementById('mp-result');
    const body = {
        event: form.event.value,
        channel: form.channel.value,
        dryRun: true,
    };
    const res = await fetch(@json(route('esbtp.communication.mailpulse.test')), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
        },
        body: JSON.stringify(body),
    });
    const json = await res.json();
    box.style.display = 'block';
    box.textContent = JSON.stringify(json, null, 2);
});
</script>
@endpush

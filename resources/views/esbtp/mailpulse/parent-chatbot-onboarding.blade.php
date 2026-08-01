@extends('layouts.app')

@section('title', 'Activation des parents | MailPulse')

@push('styles')
<style>
    .mailpulse-onboarding { --mp-black: #111; --mp-orange: #f97316; --mp-line: #dedede; --mp-muted: #6b7280; background: #f6f6f5; min-height: calc(100vh - 80px); }
    .mailpulse-onboarding__shell { max-width: 1080px; }
    .mailpulse-onboarding__brand { background: var(--mp-black); border-radius: 8px; color: #fff; padding: 24px; }
    .mailpulse-onboarding__mark { align-items: center; background: #fff; border-radius: 8px; display: inline-flex; height: 48px; justify-content: center; width: 48px; }
    .mailpulse-onboarding__mark svg { height: 36px; width: 36px; }
    .mailpulse-onboarding__eyebrow { color: #fdba74; font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .mailpulse-onboarding__title { font-size: clamp(1.55rem, 3vw, 2.15rem); font-weight: 800; margin: 0; }
    .mailpulse-onboarding__copy { color: #d4d4d8; margin: 6px 0 0; max-width: 650px; }
    .mailpulse-onboarding__panel { background: #fff; border: 1px solid var(--mp-line); border-radius: 8px; }
    .mailpulse-onboarding__panel-header { border-bottom: 1px solid var(--mp-line); padding: 18px 20px; }
    .mailpulse-onboarding__panel-body { padding: 20px; }
    .mailpulse-onboarding__status { align-items: center; border-radius: 999px; display: inline-flex; font-size: .78rem; font-weight: 700; gap: 7px; padding: 6px 10px; }
    .mailpulse-onboarding__status--processing { background: #fff7ed; color: #9a3412; }
    .mailpulse-onboarding__status--completed { background: #ecfdf5; color: #047857; }
    .mailpulse-onboarding__status--cancelled { background: #f3f4f6; color: #4b5563; }
    .mailpulse-onboarding__progress { background: #e5e7eb; height: 10px; }.mailpulse-onboarding__progress .progress-bar { background: var(--mp-orange); }
    .mailpulse-onboarding__metric { border: 1px solid var(--mp-line); border-radius: 8px; min-height: 116px; padding: 16px; }
    .mailpulse-onboarding__metric-label { color: var(--mp-muted); display: block; font-size: .78rem; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; }
    .mailpulse-onboarding__metric-value { color: var(--mp-black); font-size: 1.65rem; font-weight: 800; line-height: 1; }
    .mailpulse-onboarding__metric--success .mailpulse-onboarding__metric-value { color: #047857; }.mailpulse-onboarding__metric--danger .mailpulse-onboarding__metric-value { color: #b91c1c; }
    .mailpulse-onboarding__refresh { color: var(--mp-muted); font-size: .82rem; }
    .mailpulse-onboarding .btn-mailpulse { background: var(--mp-orange); border-color: var(--mp-orange); color: #fff; font-weight: 700; min-height: 44px; }
    .mailpulse-onboarding .btn-mailpulse:hover, .mailpulse-onboarding .btn-mailpulse:focus { background: #c2410c; border-color: #c2410c; color: #fff; }
    .mailpulse-onboarding .btn-outline-danger { min-height: 44px; }
    @media (max-width: 575.98px) { .mailpulse-onboarding__brand { padding: 20px; }.mailpulse-onboarding__panel-header, .mailpulse-onboarding__panel-body { padding: 16px; }.mailpulse-onboarding__action, .mailpulse-onboarding__action .btn { width: 100%; } }
</style>
@endpush

@section('content')
<main class="mailpulse-onboarding py-3 py-md-4"><div class="container-fluid mailpulse-onboarding__shell">
    <section class="mailpulse-onboarding__brand mb-4" aria-labelledby="mailpulse-onboarding-title"><div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
        <div class="mailpulse-onboarding__mark" aria-hidden="true"><svg viewBox="0 0 36 36" fill="none"><path d="M5.5 9.5A3.5 3.5 0 0 1 9 6h18a3.5 3.5 0 0 1 3.5 3.5v17A3.5 3.5 0 0 1 27 30H9a3.5 3.5 0 0 1-3.5-3.5v-17Z" stroke="#111" stroke-width="2.4" stroke-linejoin="round"/><path d="m7 10 9.08 7.56a3 3 0 0 0 3.84 0L29 10" stroke="#111" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 23h4l2.1-4.2 3.4 7.2 2.45-4.5H27" stroke="#f97316" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div><div class="mailpulse-onboarding__eyebrow">MailPulse</div><h1 class="mailpulse-onboarding__title" id="mailpulse-onboarding-title">Activation des parents</h1><p class="mailpulse-onboarding__copy">Envoyez les codes de liaison aux tuteurs éligibles et suivez le traitement en temps réel.</p></div>
    </div></section>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2" aria-hidden="true"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button></div>@endif

    @if(! $batch)
        <section class="mailpulse-onboarding__panel" aria-labelledby="mailpulse-empty-title"><div class="mailpulse-onboarding__panel-body py-4 py-md-5 text-center">
            <i class="fas fa-envelope-open-text fa-2x text-muted mb-3" aria-hidden="true"></i><h2 class="h4 mb-2" id="mailpulse-empty-title">Aucun lot d’activation</h2><p class="text-muted mx-auto mb-4" style="max-width: 520px;">Lancez un lot pour préparer et transmettre les codes de liaison MailPulse aux parents éligibles.</p>
            <form method="POST" action="{{ route('esbtp.parent-chatbot-onboarding.start') }}">@csrf<button class="btn btn-mailpulse px-4" type="submit"><i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Lancer l’activation</button></form>
        </div></section>
    @else
        @php
            $processedCount = max(0, $batch->total_count - $batch->pending_count);
            $progress = $batch->total_count > 0 ? min(100, (int) round(($processedCount / $batch->total_count) * 100)) : 100;
            $status = $batch->isProcessing() ? 'processing' : ($batch->isCancelled() ? 'cancelled' : 'completed');
            $statusLabels = ['processing' => 'Traitement en cours', 'completed' => 'Activation terminée', 'cancelled' => 'Lot arrêté'];
        @endphp
        <section class="mailpulse-onboarding__panel" aria-labelledby="mailpulse-batch-title">
            <header class="mailpulse-onboarding__panel-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"><div><div class="d-flex flex-wrap align-items-center gap-2 mb-2"><h2 class="h5 mb-0" id="mailpulse-batch-title">Lot #{{ $batch->id }}</h2><span class="mailpulse-onboarding__status mailpulse-onboarding__status--{{ $status }}"><i class="fas {{ $batch->isProcessing() ? 'fa-spinner fa-spin' : ($batch->isCancelled() ? 'fa-stop-circle' : 'fa-check-circle') }}" aria-hidden="true"></i>{{ $statusLabels[$status] }}</span></div><p class="text-muted small mb-0">Créé le {{ optional($batch->started_at)->format('d/m/Y à H:i') }}</p></div>
                @if($batch->isProcessing())<form method="POST" action="{{ route('esbtp.parent-chatbot-onboarding.cancel', $batch) }}" class="mailpulse-onboarding__action">@csrf<button class="btn btn-outline-danger" type="submit"><i class="fas fa-stop me-2" aria-hidden="true"></i>Arrêter le lot</button></form>
                @else<form method="POST" action="{{ route('esbtp.parent-chatbot-onboarding.start') }}" class="mailpulse-onboarding__action">@csrf<button class="btn btn-mailpulse px-3" type="submit"><i class="fas fa-plus me-2" aria-hidden="true"></i>Nouveau lot</button></form>@endif
            </header>
            <div class="mailpulse-onboarding__panel-body"><div class="d-flex justify-content-between align-items-end gap-3 mb-2"><div><strong class="d-block">Progression</strong><span class="text-muted small">{{ $processedCount }} parent{{ $processedCount > 1 ? 's' : '' }} traité{{ $processedCount > 1 ? 's' : '' }} sur {{ $batch->total_count }}</span></div><strong class="fs-5">{{ $progress }} %</strong></div><div class="progress mailpulse-onboarding__progress mb-2" role="progressbar" aria-label="Progression du lot" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $progress }}%"></div></div>
                @if($batch->isProcessing())<p class="mailpulse-onboarding__refresh mb-4"><i class="fas fa-sync-alt me-1" aria-hidden="true"></i>Cette page s’actualise toutes les 15 secondes pendant le traitement.</p>@else<p class="text-muted small mb-4">Le traitement n’est plus actif. Les résultats ci-dessous sont définitifs pour ce lot.</p>@endif
                <div class="row g-3" aria-label="Résultats du lot"><div class="col-6 col-lg-3"><div class="mailpulse-onboarding__metric"><span class="mailpulse-onboarding__metric-label">À traiter</span><strong class="mailpulse-onboarding__metric-value">{{ $batch->pending_count }}</strong></div></div><div class="col-6 col-lg-3"><div class="mailpulse-onboarding__metric mailpulse-onboarding__metric--success"><span class="mailpulse-onboarding__metric-label">Acceptés</span><strong class="mailpulse-onboarding__metric-value">{{ $batch->accepted_count }}</strong></div></div><div class="col-6 col-lg-3"><div class="mailpulse-onboarding__metric mailpulse-onboarding__metric--danger"><span class="mailpulse-onboarding__metric-label">Échecs</span><strong class="mailpulse-onboarding__metric-value">{{ $batch->failed_count }}</strong></div></div><div class="col-6 col-lg-3"><div class="mailpulse-onboarding__metric"><span class="mailpulse-onboarding__metric-label">Non envoyés</span><strong class="mailpulse-onboarding__metric-value">{{ $batch->skipped_count }}</strong></div></div></div>
            </div>
        </section>
    @endif
</div></main>
@endsection

@if($batch && $batch->isProcessing())
    @push('scripts')<script>window.setTimeout(() => window.location.reload(), 15000);</script>@endpush
@endif

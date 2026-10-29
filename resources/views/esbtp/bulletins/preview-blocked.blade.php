@extends('layouts.app')

@section('title', 'Apercu PDF bloque - KLASSCI')

@push('styles')
<style>
.bulletin-preview-blocked {
    max-width: 760px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.bulletin-preview-blocked__card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
    padding: 1.5rem;
}
.bulletin-preview-blocked__icon {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: rgba(4, 83, 203, .1);
    color: #0453cb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}
.bulletin-preview-blocked h1 {
    color: #1e293b;
    font-size: 1.25rem;
    margin: 0 0 .5rem;
}
.bulletin-preview-blocked p {
    color: #64748b;
    line-height: 1.5;
    margin: 0 0 1rem;
}
.bulletin-preview-blocked__actions {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    margin-top: 1.25rem;
}
.bulletin-preview-blocked__button {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    min-height: 42px;
    padding: .55rem .9rem;
    border-radius: 8px;
    border: 1px solid #dbe5f2;
    text-decoration: none;
    font-weight: 700;
    font-size: .85rem;
}
.bulletin-preview-blocked__button--primary {
    color: #fff;
    background: #0453cb;
    border-color: #0453cb;
}
.bulletin-preview-blocked__button--secondary {
    color: #0453cb;
    background: #fff;
}
.bulletin-preview-blocked__context {
    margin-top: 1rem;
    padding: .85rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    font-size: .8rem;
}
</style>
@endpush

@section('content')
<div class="bulletin-preview-blocked">
    <div class="bulletin-preview-blocked__card">
        <div class="bulletin-preview-blocked__icon">
            <i class="fas fa-circle-exclamation"></i>
        </div>
        <h1>Apercu PDF bloque</h1>
        <p>{{ $message }}</p>

        @if(!empty($context['classe']['name']) || !empty($context['matiere']['name']) || !empty($context['periode']))
            <div class="bulletin-preview-blocked__context">
                @if(!empty($context['classe']['name']))
                    <div>Classe : {{ $context['classe']['name'] }}</div>
                @endif
                @if(!empty($context['matiere']['name']))
                    <div>Matiere : {{ $context['matiere']['name'] }}</div>
                @endif
                @if(!empty($context['periode']))
                    <div>Periode : {{ $context['periode'] }}</div>
                @endif
            </div>
        @endif

        <div class="bulletin-preview-blocked__actions">
            @if(!empty($configuration_url))
                <a href="{{ $configuration_url }}" class="bulletin-preview-blocked__button bulletin-preview-blocked__button--primary">
                    <i class="fas fa-sliders"></i>
                    Ouvrir la configuration requise
                </a>
            @endif
            <a href="{{ $back_url ?? route('esbtp.bulletins.select') }}" class="bulletin-preview-blocked__button bulletin-preview-blocked__button--secondary">
                <i class="fas fa-arrow-left"></i>
                Retour a la selection
            </a>
        </div>
    </div>
</div>
@endsection

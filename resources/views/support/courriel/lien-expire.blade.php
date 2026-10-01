@extends('layouts.app')

@section('title', 'Lien expiré')

@push('styles')
<style>
    .clx-carte { max-width: 520px; margin: 2rem auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); padding: 2rem 1.75rem; text-align: center; }
    .clx-icone { width: 60px; height: 60px; margin: 0 auto 1rem; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        background: rgba(4,83,203,.08); color: #0453cb; font-size: 1.45rem; }
    .clx-carte h1 { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0 0 .5rem; }
    .clx-carte p { color: #475569; font-size: .92rem; margin: 0 0 1.25rem; }
    .clx-actions { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; }
    .clx-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 44px; border-radius: 11px;
        padding: .6rem 1.1rem; font-size: .88rem; font-weight: 600; border: 1px solid transparent; text-decoration: none; }
    .clx-btn--primaire { background: #0453cb; color: #fff; }
    .clx-btn--primaire:hover { background: #033a8e; color: #fff; }
    .clx-btn--primaire:disabled { opacity: .55; cursor: wait; }
    .clx-btn--secondaire { background: #fff; color: #0453cb; border-color: #bfd3f2; }
    .clx-message { margin-top: 1rem !important; font-size: .86rem !important; }
    .clx-message--erreur { color: #b91c1c !important; }
</style>
@endpush

@section('content')
<div class="clx-carte" x-data="{ etat: 'repos', message: '' }">
    <div class="clx-icone"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i></div>

    @if($dejaConfirmee)
        <h1>Votre adresse est déjà confirmée.</h1>
        <p>Vous recevez déjà un e-mail quand le support vous répond.</p>
        <div class="clx-actions">
            <a href="{{ route('support.demandes.index') }}" class="clx-btn clx-btn--primaire">Mes demandes d'aide</a>
        </div>
    @else
        <h1>Ce lien a expiré.</h1>
        <p>Il n'est plus valable : il a dépassé sa durée, ou votre adresse a changé depuis son envoi.</p>
        <div class="clx-actions">
            @if($peutRenvoyer)
                <button type="button" class="clx-btn clx-btn--primaire" x-bind:disabled="etat === 'envoi' || etat === 'ok'"
                        x-on:click="
                            etat = 'envoi'; message = '';
                            fetch(@js(route('support.courriel.lien')), {
                                method: 'POST', credentials: 'same-origin',
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                            }).then(r => r.json().catch(() => ({})).then(c => ({ ok: r.ok, c })))
                              .then(r => { etat = r.ok && r.c.envoye ? 'ok' : 'erreur'; message = r.c.message || (etat === 'ok' ? 'Un nouveau lien vient de partir.' : 'L\'envoi a échoué. Réessayez dans un instant.'); })
                              .catch(() => { etat = 'erreur'; message = 'Connexion perdue. Réessayez dans un instant.'; })">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <span x-text="etat === 'envoi' ? 'Envoi…' : 'Recevoir un nouveau lien'">Recevoir un nouveau lien</span>
                </button>
            @endif
            <a href="{{ route('support.demandes.index') }}" class="clx-btn clx-btn--secondaire">Mes demandes d'aide</a>
        </div>
        <p class="clx-message" x-show="message" x-cloak x-text="message" x-bind:class="etat === 'erreur' ? 'clx-message--erreur' : ''" role="status"></p>
    @endif
</div>
@endsection

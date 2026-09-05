{{--
    Invitation à installer l'application sur l'écran d'accueil.
    Masquée par défaut (attribut hidden) : public/js/mobile-shell.js l'affiche
    uniquement quand le navigateur émet `beforeinstallprompt`, hors mode
    autonome, et pas si l'utilisateur l'a refusée depuis moins de 30 jours.
    Balise : x-m.install (sans props) — ou title="Installer l'application"
    Props :
      title   string|null   titre ; défaut « Installer {nom de l'établissement} »
      text    string        phrase d'accroche
      cta     string        libellé du bouton
    Le nom vient des réglages de l'instance (school_name), jamais en dur.
--}}
@props([
    'title' => null,
    'text' => 'Ouverture instantanée, plein écran, notifications',
    'cta' => 'Installer',
])
@php
    $school = \App\Helpers\SettingsHelper::get('school_name', config('app.name', 'KLASSCI'));
    $heading = $title ?? ('Installer ' . $school . ' sur l’écran d’accueil');
@endphp
<div {{ $attributes->merge(['class' => 'm-install']) }} hidden data-m-install role="region" aria-label="Installer l’application">
    <div class="ico" aria-hidden="true"></div>
    <div>
        <b>{{ $heading }}</b>
        <span>{{ $text }}</span>
    </div>
    <div class="acts">
        <button type="button" class="b" data-m-install-accept>{{ $cta }}</button>
        <button type="button" class="x" data-m-install-dismiss aria-label="Plus tard">
            <x-m.icon name="x" />
        </button>
    </div>
</div>

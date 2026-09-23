{{--
    KLASSCI Care — entrees « Aide / Signaler » et « Mes demandes » du menu du
    compte. Une seule source pour le menu de bureau et la feuille mobile.

    Le lien courriel ne sert que si le script du support ne s'est pas charge :
    signalement.js intercepte le clic sur `data-support-ouvrir`.
--}}
@props(['variante' => 'bureau'])
@php
    $_spMenu = app(\App\Domain\Support\Services\DisponibiliteSupport::class);
    $_spMail = 'mailto:'.config('app.support_email');
@endphp
@if($variante === 'mobile')
    @if($_spMenu->signalement())
        <a href="{{ $_spMail }}" data-support-ouvrir><x-m.icon name="msg" />Aide / Signaler un problème<span class="ch"><x-m.icon name="chr" /></span></a>
    @endif
    @if($_spMenu->suivi())
        <a href="{{ route('support.demandes.index') }}"><x-m.icon name="msg" />Mes demandes de support<span class="ch"><x-m.icon name="chr" /></span></a>
    @endif
@else
    @if($_spMenu->signalement())
        <li><a class="dropdown-item" href="{{ $_spMail }}" data-support-ouvrir><i class="fas fa-life-ring me-2"></i> Aide / Signaler un problème</a></li>
    @endif
    @if($_spMenu->suivi())
        <li><a class="dropdown-item" href="{{ route('support.demandes.index') }}"><i class="fas fa-inbox me-2"></i> Mes demandes de support</a></li>
    @endif
@endif

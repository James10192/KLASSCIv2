{{--
    Filtre « Contact non verifie » des corbeilles de demandes en ligne.
    Affiche quand la verification du contact est active, ou quand le filtre est
    deja pose (un reglage coupe ne doit pas laisser l'agent sans moyen d'en sortir).

    <x-filtre-contact-non-verifie route="esbtp.candidatures.index" />
--}}
@props(['route'])
@php
    $_actif = request()->query('contact') === 'non_verifie';
    $_visible = $_actif || app(\App\Services\TenantScolariteSettings::class)->verificationContactActive();
    $_params = array_filter(request()->except(['contact', 'page']), fn ($v) => $v !== null && $v !== '');
@endphp
@if($_visible)
    <div style="margin-top:.75rem;">
        <a href="{{ route($route, $_actif ? $_params : $_params + ['contact' => 'non_verifie']) }}"
           class="btn-acasi {{ $_actif ? 'primary' : 'secondary' }}" style="min-height:2.75rem;display:inline-flex;align-items:center;gap:.4rem;"
           aria-pressed="{{ $_actif ? 'true' : 'false' }}">
            <i class="fas fa-user-clock" aria-hidden="true"></i>
            {{ $_actif ? 'Toutes les demandes' : 'Contact non vérifié' }}
        </a>
    </div>
@endif

{{--
    Contact d'une demande du portail jamais prouve : l'ecole la voit, sait qu'il
    faut appeler, et peut « Confirmer le contact » une fois la famille jointe.

    <x-demande-contact-badge :demande="$c" route="esbtp.candidatures.confirmer-contact" permission="inscriptions.candidatures.process" />
    Sans `route`, le badge seul (tableaux de rendez-vous).
--}}
@props(['demande' => null, 'route' => null, 'permission' => null])
@php
    $_badge = $demande ? \App\Enums\StatutVerificationContact::badge($demande->verification_contact) : null;
    $_peutConfirmer = $_badge && $route && $permission && auth()->user()?->can($permission);
    // Reglage coupe apres le marquage : le badge reste, mais plus rien n'est retenu.
    $_retenue = $_badge && app(\App\Services\TenantScolariteSettings::class)->verificationContactActive();
@endphp
@if($_badge)
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.35rem;margin-top:.25rem;">
        <span title="{{ $_retenue
                ? 'La famille n\'a pas confirmé son adresse e-mail ou son numéro WhatsApp : pas de convocation automatique tant que le contact n\'est pas confirmé.'
                : 'La famille n\'a pas confirmé son adresse e-mail ou son numéro WhatsApp. La vérification est désactivée : les convocations partent normalement.' }}"
              style="display:inline-flex;align-items:center;gap:.3rem;padding:.15rem .5rem;border-radius:6px;font-size:.7rem;font-weight:600;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">
            <i class="fas fa-user-clock" aria-hidden="true"></i>{{ $_badge }}
        </span>
        @if($_peutConfirmer)
            <form method="POST" action="{{ route($route, $demande) }}" style="margin:0;">
                @csrf
                {{-- Ce que l'agent a sous les yeux : la confirmation est refusee si le dossier a bouge depuis. --}}
                <input type="hidden" name="empreinte" value="{{ $demande->empreinteContact() }}">
                <button type="submit" class="btn-acasi secondary" style="min-height:2.75rem;padding:.3rem .7rem;font-size:.75rem;"
                        title="Vous avez joint la famille et vérifié son contact">
                    <i class="fas fa-user-check" aria-hidden="true"></i> Confirmer le contact
                </button>
            </form>
        @endif
    </div>
@endif

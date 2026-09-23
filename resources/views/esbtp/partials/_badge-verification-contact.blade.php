{{--
    Contact d'une demande du portail jamais prouve : l'ecole la voit, sait qu'il
    faut appeler, et peut « Confirmer le contact » une fois la famille jointe.
    Parametres : $statutVerification, et facultatif $routeConfirmer (URL POST).
--}}
@php $_badgeContact = \App\Enums\StatutVerificationContact::badge($statutVerification ?? null); @endphp
@if($_badgeContact)
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.35rem;margin-top:.25rem;">
        <span title="La famille n'a pas confirmé son adresse e-mail ou son numéro WhatsApp : pas de convocation automatique tant que le contact n'est pas confirmé."
              style="display:inline-flex;align-items:center;gap:.3rem;padding:.15rem .5rem;border-radius:6px;font-size:.7rem;font-weight:600;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">
            <i class="fas fa-user-clock" aria-hidden="true"></i>{{ $_badgeContact }}
        </span>
        @if(!empty($routeConfirmer))
            <form method="POST" action="{{ $routeConfirmer }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn-acasi secondary" style="min-height:2.75rem;padding:.3rem .7rem;font-size:.75rem;"
                        title="Vous avez joint la famille et vérifié son contact">
                    <i class="fas fa-user-check" aria-hidden="true"></i> Confirmer le contact
                </button>
            </form>
        @endif
    </div>
@endif

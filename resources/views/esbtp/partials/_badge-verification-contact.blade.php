{{-- Contact d'une demande du portail jamais confirme (ou non verifiable) : l'ecole la voit, et sait qu'il faut appeler. --}}
@php $_badgeContact = \App\Enums\StatutVerificationContact::badge($statutVerification ?? null); @endphp
@if($_badgeContact)
    <span title="La famille n'a pas confirmé son adresse e-mail ou son numéro WhatsApp : vérifiez le contact avant de convoquer."
          style="display:inline-flex;align-items:center;gap:.3rem;margin-top:.25rem;padding:.15rem .5rem;border-radius:6px;font-size:.7rem;font-weight:600;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">
        <i class="fas fa-user-clock" aria-hidden="true"></i>{{ $_badgeContact }}
    </span>
@endif

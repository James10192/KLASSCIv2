{{-- Le mailer de l'instance, réglé par l'école : MailPulse ou le serveur de
     messagerie. Lu à l'envoi par App\Mail\Transport\MailerDeLEcole. --}}
@php
    $mailpulseCourrielsDemandes = filter_var(\App\Helpers\SettingsHelper::get(\App\Mail\Transport\MailerDeLEcole::REGLAGE, '0'), FILTER_VALIDATE_BOOLEAN);
    $mailpulseCourrielsImposes = app(\App\Mail\Transport\MailerDeLEcole::class)->imposeParLeServeur();
    $mailpulseSansFile = config('queue.default') === 'sync';
@endphp
<style>
    .mailpulse-courriels-note,
    .mailpulse-courriels-alerte {
        display: flex; align-items: flex-start; gap: 8px;
        padding: 10px 12px; border-radius: 10px; font-size: .82rem; line-height: 1.45;
    }
    .mailpulse-courriels-note { background: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe; }
    .mailpulse-courriels-alerte { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; }
    .mailpulse-courriels-note i,
    .mailpulse-courriels-alerte i { margin-top: 2px; flex-shrink: 0; }
</style>
<input type="hidden" name="setting_mailpulse_courriels_enabled" value="0">
<div class="mailpulse-field-card mb-3" data-mailpulse-courriels>
    <label class="form-label-modern">
        <i class="fas fa-envelope-open-text text-primary"></i>
        Envoyer tous les e-mails de l'école par MailPulse
    </label>
    <label class="form-switch-modern">
        <input type="checkbox" name="setting_mailpulse_courriels_enabled" value="1"
               {{ $mailpulseCourrielsDemandes ? 'checked' : '' }}>
        <span class="slider"></span>
    </label>
    <small class="text-muted d-block mt-2">Liens de confirmation, mots de passe oubliés, avis aux parents, relances : tout e-mail de KLASSCI part par MailPulse, avec l'adresse et le nom d'expéditeur ci-dessous. Case décochée, les e-mails partent par le serveur de messagerie de l'instance. Les exports PDF ne s'envoient plus par e-mail tant que la case est cochée : ils se téléchargent.</small>
    @if($mailpulseCourrielsImposes)
        <div class="mailpulse-courriels-note mt-2" role="status">
            <i class="fas fa-server" aria-hidden="true"></i>
            <span>La configuration du serveur impose déjà MailPulse pour cette instance : les e-mails y passent quelle que soit cette case.</span>
        </div>
    @endif
    @if($mailpulseSansFile)
        <div class="mailpulse-courriels-alerte mt-2" role="note" data-mailpulse-sans-file>
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            <span>Les e-mails ne pourront pas être différés si MailPulse est saturé : les tâches en arrière-plan ne tournent pas sur cette instance. Un e-mail refusé à ce moment-là ne repart pas plus tard : le refus est noté au journal.</span>
        </div>
    @endif
</div>

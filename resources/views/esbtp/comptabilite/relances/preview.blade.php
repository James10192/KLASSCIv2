{{--
    Aperçu d'un modèle de relance (réponse HTML du POST config/preview, injecté
    par innerHTML dans la fenêtre de bureau ou la feuille mobile de la page
    Configuration des relances). Les styles rlc-pv-* sont déclarés par cette page.
    Le nom de l'établissement vient des réglages d'instance, jamais du code.
--}}
@php
    $pvEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $pvEcoleNom = $pvEcole['name'] ?: ($pvEcole['acronym'] ?: config('app.name'));
    $pvType = $type ?? 'email';
    $pvContenu = (string) ($contenuApercu ?? '');
    $pvLongueurSms = mb_strlen($pvContenu, 'UTF-8');
@endphp
<div class="rlc-pv rlc-pv--{{ $pvType }}">
    @if($pvType === 'email')
        <div class="rlc-pv-head">
            <i class="fas fa-envelope"></i>
            <span>E-mail · relance de paiement</span>
        </div>
        <div class="rlc-pv-body">{!! nl2br(e($pvContenu)) !!}</div>
    @elseif($pvType === 'sms')
        <div class="rlc-pv-head">
            <i class="fas fa-sms"></i>
            <span>SMS · {{ $pvLongueurSms }}/160 caractères</span>
        </div>
        <div class="rlc-pv-body rlc-pv-body--sms">
            <div class="rlc-pv-bubble">{{ $pvContenu }}</div>
        </div>
    @else
        <div class="rlc-pv-head">
            <i class="fas fa-file-pdf"></i>
            <span>Courrier · format PDF</span>
        </div>
        <div class="rlc-pv-body rlc-pv-body--courrier">
            <div class="rlc-pv-letterhead">
                <b>{{ $pvEcoleNom }}</b>
                <span>Relance de paiement</span>
            </div>
            <div class="rlc-pv-letter">{!! nl2br(e($pvContenu)) !!}</div>
        </div>
    @endif

    <div class="rlc-pv-foot">
        <i class="fas fa-info-circle"></i>
        Cet aperçu utilise des données d'exemple. Le message réel est personnalisé pour chaque étudiant.
    </div>
</div>

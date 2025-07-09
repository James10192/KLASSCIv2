<div class="preview-container">
    @if($type === 'email')
        <div class="email-preview">
            <div class="email-header bg-primary text-white p-3 rounded-top">
                <h6 class="mb-0">
                    <i class="fas fa-envelope me-2"></i>
                    Aperçu Email - Relance de Paiement
                </h6>
            </div>
            <div class="email-body bg-white p-4 border border-top-0 rounded-bottom">
                {!! nl2br(e($contenuApercu)) !!}
            </div>
        </div>
    @elseif($type === 'sms')
        <div class="sms-preview">
            <div class="sms-header bg-success text-white p-3 rounded-top">
                <h6 class="mb-0">
                    <i class="fas fa-sms me-2"></i>
                    Aperçu SMS - {{ strlen($contenuApercu) }}/160 caractères
                </h6>
            </div>
            <div class="sms-body bg-light p-4 border border-top-0 rounded-bottom">
                <div class="d-flex">
                    <div class="phone-icon me-3">
                        <i class="fas fa-mobile-alt fa-2x text-success"></i>
                    </div>
                    <div class="message-bubble bg-white rounded p-3 shadow-sm flex-grow-1">
                        {{ $contenuApercu }}
                    </div>
                </div>
            </div>
        </div>
    @elseif($type === 'courrier')
        <div class="courrier-preview">
            <div class="courrier-header bg-info text-white p-3 rounded-top">
                <h6 class="mb-0">
                    <i class="fas fa-file-pdf me-2"></i>
                    Aperçu Courrier - Format PDF
                </h6>
            </div>
            <div class="courrier-body bg-white p-4 border border-top-0 rounded-bottom" style="font-family: 'Times New Roman', serif;">
                <div class="document-header text-center mb-4">
                    <h5>École Supérieure du Bâtiment et des Travaux Publics</h5>
                    <p class="text-muted">Relance de Paiement</p>
                </div>
                <div class="document-content">
                    {!! nl2br(e($contenuApercu)) !!}
                </div>
            </div>
        </div>
    @endif

    <div class="preview-footer mt-3 text-center">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Cet aperçu utilise des données d'exemple. Le contenu réel sera personnalisé pour chaque étudiant.
        </small>
    </div>
</div>

<style>
.message-bubble {
    position: relative;
    max-width: 80%;
}

.message-bubble::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 15px;
    width: 0;
    height: 0;
    border-top: 8px solid transparent;
    border-bottom: 8px solid transparent;
    border-right: 8px solid white;
}

.document-content {
    line-height: 1.6;
    text-align: justify;
}

.email-body, .sms-body, .courrier-body {
    min-height: 200px;
}
</style>

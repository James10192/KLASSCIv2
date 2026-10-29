{{-- Par où partent les e-mails de l'école, dans l'en-tête de l'onglet MailPulse.
     Suit l'enregistrement sans recharger : l'enregistrement émet « mailpulse:enregistre »
     avec la réponse du serveur, qui porte courriels_par_mailpulse. --}}
@php $mailpulseCourrielsActifs = \App\Mail\Transport\MailPulseTransport::actif(); @endphp
<span class="mailpulse-status-badge {{ $mailpulseCourrielsActifs ? 'configured' : '' }}" data-mailpulse-courriels-etat>
    <i class="fas {{ $mailpulseCourrielsActifs ? 'fa-circle-check' : 'fa-envelope' }}" aria-hidden="true"></i>
    <span data-libelle>{{ $mailpulseCourrielsActifs ? "E-mails de l'école par MailPulse" : 'E-mails par le serveur de messagerie' }}</span>
</span>
<script>
(function () {
    if (window.__mailpulseEtatCourriels) { return; }
    window.__mailpulseEtatCourriels = true;
    window.addEventListener('mailpulse:enregistre', function (ev) {
        var actif = ev.detail && ev.detail.courriels_par_mailpulse;
        var etat = document.querySelector('[data-mailpulse-courriels-etat]');
        if (!etat || typeof actif !== 'boolean') { return; }
        etat.classList.toggle('configured', actif);
        etat.querySelector('i').className = 'fas ' + (actif ? 'fa-circle-check' : 'fa-envelope');
        etat.querySelector('[data-libelle]').textContent = actif ? "E-mails de l'école par MailPulse" : 'E-mails par le serveur de messagerie';
    });
})();
</script>

<div class="bc-card" id="rendez-vous-reglages">
    <div class="bc-icon"><i class="fas fa-calendar-check"></i></div>
    <div class="bc-body">
        <div class="bc-label">Rendez-vous au guichet</div>
        <div class="bc-desc">
            Créneaux, jours et horaires se règlent sur la page Rendez-vous, pas ici.
            @can('inscriptions.rdv.configure')
                <a href="{{ route('esbtp.rendez-vous.index') }}#reglages">Ouvrir les réglages</a>
            @elsecan('inscriptions.rdv.view')
                <a href="{{ route('esbtp.rendez-vous.index') }}">Voir le planning</a>
            @endcan
        </div>
    </div>
</div>

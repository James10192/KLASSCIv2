{{-- Plage horaire des cours — lue par App\Services\Planning\PlageHoraireJournee --}}
@php
    $phjP = \App\Services\Planning\PlageHoraireJournee::class;
    $phjDebut = old(str_replace('.', '_', $phjP::CLE_DEBUT), \App\Helpers\SettingsHelper::get($phjP::CLE_DEBUT, $phjP::DEBUT_PAR_DEFAUT));
    $phjFin = old(str_replace('.', '_', $phjP::CLE_FIN), \App\Helpers\SettingsHelper::get($phjP::CLE_FIN, $phjP::FIN_PAR_DEFAUT));
@endphp
<div class="settings-section">
    <div class="section-header">
        <div class="section-icon school">
            <i class="fas fa-clock"></i>
        </div>
        <div>
            <h3 class="section-title">Plage horaire des cours</h3>
            <p class="section-description">Heures de la journée proposées dans l’emploi du temps, la saisie des séances et les disponibilités des enseignants. Valeurs livrées : {{ $phjP::DEBUT_PAR_DEFAUT }} h à {{ $phjP::FIN_PAR_DEFAUT }} h.</p>
        </div>
    </div>

    <div class="settings-grid">
        <div class="form-group">
            <label class="form-label-modern" for="phj-debut">
                <i class="fas fa-sun text-primary"></i>
                Premier créneau (heure)
            </label>
            <input type="number" min="0" max="22" step="1" id="phj-debut"
                   class="form-control form-control-modern"
                   name="{{ $phjP::CLE_DEBUT }}" value="{{ $phjDebut }}">
            <small class="text-muted d-block mt-1">Ex. 7 : la grille commence à 7 h 00.</small>
        </div>
        <div class="form-group">
            <label class="form-label-modern" for="phj-fin">
                <i class="fas fa-moon text-primary"></i>
                Fin du dernier créneau (heure)
            </label>
            <input type="number" min="1" max="23" step="1" id="phj-fin"
                   class="form-control form-control-modern"
                   name="{{ $phjP::CLE_FIN }}" value="{{ $phjFin }}">
            <small class="text-muted d-block mt-1">Ex. 22 pour des cours du soir. Doit être après le premier créneau.</small>
        </div>
    </div>
</div>

@php
    $_rdv = \App\Services\RendezVous\RendezVousReglages::class;
    $_joursLabels = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];
    $_joursValeur = (string) \App\Helpers\SettingsHelper::get($_rdv::JOURS, '1,2,3,4,5');
    $_joursChoisis = array_filter(preg_split('/[,\s]+/', $_joursValeur));
@endphp

<div class="bc-card">
    <div class="bc-icon"><i class="fas fa-calendar-check"></i></div>
    <div class="bc-body">
        <div class="bc-label">Rendez-vous au guichet</div>
        <div class="bc-desc">
            Créneaux pour finaliser un dossier sur place. Indépendant des inscriptions en ligne.
            Le premier jour au guichet (ci-dessus) reste le plancher : aucun créneau avant cette date.
            @can('inscriptions.rdv.view')
                <a href="{{ route('esbtp.rendez-vous.index') }}">Voir le planning</a>
            @endcan
        </div>

        <div class="row g-2" style="margin-top:.6rem;max-width:640px;">
            <div class="col-6">
                <label class="bc-desc" for="rdv-ouverture" style="display:block;margin-bottom:.2rem;">Ouverture des rendez-vous</label>
                <input type="date" class="form-control form-control-sm" id="rdv-ouverture"
                       name="{{ $_rdv::OUVERTURE }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::OUVERTURE, '') }}">
            </div>
            <div class="col-6">
                <label class="bc-desc" for="rdv-fermeture" style="display:block;margin-bottom:.2rem;">Fermeture des rendez-vous</label>
                <input type="date" class="form-control form-control-sm" id="rdv-fermeture"
                       name="{{ $_rdv::FERMETURE }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::FERMETURE, '') }}">
            </div>
            <div class="col-12" style="margin-top:.4rem;">
                <span class="bc-desc" style="display:block;margin-bottom:.2rem;">Jours ouverts</span>
                @foreach($_joursLabels as $_n => $_lib)
                    <label style="margin-right:.75rem;font-size:.85rem;">
                        <input type="checkbox" name="inscriptions_rdv_jours_ouverts[]" value="{{ $_n }}"
                               {{ in_array((string) $_n, $_joursChoisis, true) ? 'checked' : '' }}>
                        {{ $_lib }}
                    </label>
                @endforeach
            </div>
            <div class="col-4">
                <label class="bc-desc" for="rdv-h-debut" style="display:block;margin-bottom:.2rem;">Début</label>
                <input type="time" class="form-control form-control-sm" id="rdv-h-debut"
                       name="{{ $_rdv::HEURE_DEBUT }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::HEURE_DEBUT, '') }}">
            </div>
            <div class="col-4">
                <label class="bc-desc" for="rdv-h-fin" style="display:block;margin-bottom:.2rem;">Fin</label>
                <input type="time" class="form-control form-control-sm" id="rdv-h-fin"
                       name="{{ $_rdv::HEURE_FIN }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::HEURE_FIN, '') }}">
            </div>
            <div class="col-4">
                <label class="bc-desc" for="rdv-duree" style="display:block;margin-bottom:.2rem;">Durée (min)</label>
                <input type="number" min="5" max="240" class="form-control form-control-sm" id="rdv-duree"
                       name="{{ $_rdv::DUREE }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::DUREE, '30') }}">
            </div>
            <div class="col-4">
                <label class="bc-desc" for="rdv-pause-d" style="display:block;margin-bottom:.2rem;">Pause début</label>
                <input type="time" class="form-control form-control-sm" id="rdv-pause-d"
                       name="{{ $_rdv::PAUSE_DEBUT }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::PAUSE_DEBUT, '') }}">
            </div>
            <div class="col-4">
                <label class="bc-desc" for="rdv-pause-f" style="display:block;margin-bottom:.2rem;">Pause fin</label>
                <input type="time" class="form-control form-control-sm" id="rdv-pause-f"
                       name="{{ $_rdv::PAUSE_FIN }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::PAUSE_FIN, '') }}">
            </div>
            <div class="col-4">
                <label class="bc-desc" for="rdv-cap" style="display:block;margin-bottom:.2rem;">Places / créneau</label>
                <input type="number" min="1" max="200" class="form-control form-control-sm" id="rdv-cap"
                       name="{{ $_rdv::CAPACITE }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::CAPACITE, '10') }}">
            </div>
            <div class="col-4">
                <label class="bc-desc" for="rdv-min" style="display:block;margin-bottom:.2rem;">Délai min (h)</label>
                <input type="number" min="0" max="168" class="form-control form-control-sm" id="rdv-min"
                       name="{{ $_rdv::DELAI_MIN }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::DELAI_MIN, '12') }}">
            </div>
            <div class="col-4">
                <label class="bc-desc" for="rdv-modif" style="display:block;margin-bottom:.2rem;">Délai modification (h)</label>
                <input type="number" min="0" max="168" class="form-control form-control-sm" id="rdv-modif"
                       name="{{ $_rdv::DELAI_MODIF }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::DELAI_MODIF, '12') }}">
            </div>
            <div class="col-4">
                <label class="bc-desc" for="rdv-grace" style="display:block;margin-bottom:.2rem;">Grâce no-show (min)</label>
                <input type="number" min="0" max="240" class="form-control form-control-sm" id="rdv-grace"
                       name="{{ $_rdv::GRACE }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_rdv::GRACE, '15') }}">
            </div>
            <div class="col-12" style="margin-top:.35rem;">
                <label style="font-size:.85rem;">
                    <input type="checkbox" name="{{ $_rdv::OBLIGATOIRE }}" value="1"
                           {{ \App\Helpers\SettingsHelper::get($_rdv::OBLIGATOIRE, '0') == '1' ? 'checked' : '' }}>
                    Rendez-vous obligatoire pour être reçu
                </label>
            </div>
        </div>
    </div>
    <div class="bc-toggle">
        <label class="form-switch-modern">
            <input type="checkbox" name="{{ $_rdv::ENABLED }}" value="1"
                   {{ \App\Helpers\SettingsHelper::get($_rdv::ENABLED, '0') == '1' ? 'checked' : '' }}>
            <span class="slider"></span>
        </label>
    </div>
</div>

{{-- Reglages des creneaux. Ouvert d'office tant qu'ils sont incomplets. --}}
@php
    $_jours = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];
    $_choisis = array_map('strval', array_filter(preg_split('/[,\s]+/', $rdv->valeur($rdv::JOURS, '1,2,3,4,5')) ?: []));
    $_ouvert = $rdv->enabled();
@endphp
<details class="rdv-card rdv-reglages" id="reglages" @if(! $debit || ! $_ouvert) open @endif>
    <summary id="rdv-reglages-resume">
        @include('esbtp.rendez-vous.partials._reglages_resume')
    </summary>

    <form class="rdv-form" data-rdv-reglages action="{{ route('esbtp.rendez-vous.reglages') }}" method="POST" novalidate>
        @csrf
        <fieldset>
            <legend>Période</legend>
            <div class="rdv-grille rdv-grille--2">
                <label class="rdv-champ">
                    <span>Premier jour des rendez-vous</span>
                    <input type="date" name="{{ $rdv::OUVERTURE }}" value="{{ $rdv->valeur($rdv::OUVERTURE) }}">
                </label>
                <label class="rdv-champ">
                    <span>Dernier jour</span>
                    <input type="date" name="{{ $rdv::FERMETURE }}" value="{{ $rdv->valeur($rdv::FERMETURE) }}">
                </label>
            </div>
            <div class="rdv-champ">
                <span id="rdv-jours-lbl">Jours d'ouverture du guichet</span>
                <div class="rdv-jours-choix" role="group" aria-labelledby="rdv-jours-lbl">
                    @foreach($_jours as $_n => $_lib)
                        <label class="rdv-chip">
                            <input type="checkbox" name="inscriptions_rdv_jours_ouverts[]" value="{{ $_n }}" @checked(in_array((string) $_n, $_choisis, true))>
                            <span>{{ $_lib }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Horaires du guichet</legend>
            <div class="rdv-grille rdv-grille--4">
                <label class="rdv-champ">
                    <span>Ouverture</span>
                    <input type="time" name="{{ $rdv::HEURE_DEBUT }}" value="{{ $rdv->valeur($rdv::HEURE_DEBUT) }}">
                </label>
                <label class="rdv-champ">
                    <span>Fermeture</span>
                    <input type="time" name="{{ $rdv::HEURE_FIN }}" value="{{ $rdv->valeur($rdv::HEURE_FIN) }}">
                </label>
                <label class="rdv-champ">
                    <span>Pause — début <em>facultatif</em></span>
                    <input type="time" name="{{ $rdv::PAUSE_DEBUT }}" value="{{ $rdv->valeur($rdv::PAUSE_DEBUT) }}">
                </label>
                <label class="rdv-champ">
                    <span>Pause — fin</span>
                    <input type="time" name="{{ $rdv::PAUSE_FIN }}" value="{{ $rdv->valeur($rdv::PAUSE_FIN) }}">
                </label>
            </div>
        </fieldset>

        <fieldset>
            <legend>Créneaux</legend>
            <div class="rdv-grille rdv-grille--4">
                <label class="rdv-champ">
                    <span>Durée d'un créneau</span>
                    <span class="rdv-suffixe"><input type="number" min="5" step="5" name="{{ $rdv::DUREE }}" value="{{ $rdv->valeur($rdv::DUREE, '30') }}"><em>min</em></span>
                </label>
                <label class="rdv-champ">
                    <span>Familles par créneau</span>
                    <span class="rdv-suffixe"><input type="number" min="1" name="{{ $rdv::CAPACITE }}" value="{{ $rdv->valeur($rdv::CAPACITE, '10') }}"><em>places</em></span>
                </label>
                <label class="rdv-champ">
                    <span>Réserver au plus tard</span>
                    <span class="rdv-suffixe"><input type="number" min="0" name="{{ $rdv::DELAI_MIN }}" value="{{ $rdv->valeur($rdv::DELAI_MIN, '12') }}"><em>h avant</em></span>
                </label>
                <label class="rdv-champ">
                    <span>Modifier au plus tard</span>
                    <span class="rdv-suffixe"><input type="number" min="0" name="{{ $rdv::DELAI_MODIF }}" value="{{ $rdv->valeur($rdv::DELAI_MODIF, '12') }}"><em>h avant</em></span>
                </label>
            </div>
        </fieldset>

        <label class="rdv-bascule">
            <input type="checkbox" name="{{ $rdv::ENABLED }}" value="1" @checked($_ouvert)>
            <span class="rdv-bascule-piste" aria-hidden="true"><span></span></span>
            <span class="rdv-bascule-texte">
                <strong>Ouvrir la prise de rendez-vous aux familles</strong>
                <span>Décoché, le site klassci.com répond « pas ouverte » à toutes les familles, même si des créneaux existent.</span>
            </span>
        </label>

        <div class="rdv-form-pied">
            <button type="submit" class="rdv-btn rdv-btn--primary"><i class="fas fa-check"></i>Enregistrer les réglages</button>
            <span class="rdv-note">Après un changement d'horaires, pensez à regénérer les créneaux. Les créneaux déjà réservés sont conservés.</span>
        </div>
    </form>
</details>

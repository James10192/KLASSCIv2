{{-- Délais d'émargement des enseignants — lus par App\Domain\EmploiTemps\FenetresDEmargement --}}
@php
    $emgF = \App\Domain\EmploiTemps\FenetresDEmargement::class;
    $emgChamps = [
        $emgF::CLE_AVANCE => ['fa-hourglass-start', 'Émargement possible avant le début (min)', 'Ex. 10 : l’enseignant peut émarger dès 7 h 50 pour un cours de 8 h.'],
        $emgF::CLE_PRESENT => ['fa-user-check', 'Compté présent jusqu’à (min après le début)', 'Au-delà, l’émargement est enregistré « en retard ».'],
        $emgF::CLE_RETARD => ['fa-user-clock', 'Retard accepté jusqu’à (min après le début)', 'Au-delà, la conduite choisie ci-dessous s’applique.'],
        $emgF::CLE_AVANT_FIN => ['fa-flag-checkered', 'Émargement de fin ouvert (min avant la fin)', 'Suit l’heure de fin prolongée si une prolongation a été accordée.'],
        $emgF::CLE_APRES_FIN => ['fa-door-closed', 'Émargement de fin fermé (min après la fin)', ''],
    ];
    $emgDepassement = old('setting_'.$emgF::CLE_DEPASSEMENT, \App\Helpers\SettingsHelper::get($emgF::CLE_DEPASSEMENT, $emgF::DEPASSEMENT_ABSENT));
@endphp
<div class="settings-section">
    <div class="section-header">
        <div class="section-icon school">
            <i class="fas fa-signature"></i>
        </div>
        <div>
            <h3 class="section-title">Émargement des enseignants</h3>
            <p class="section-description">Délais d’émargement. Les valeurs livrées reproduisent l’ancien fonctionnement (20 / 45 / 20 / 30 minutes).</p>
        </div>
    </div>

    <div class="settings-grid">
        @foreach($emgChamps as $emgCle => [$emgIcone, $emgLabel, $emgAide])
            <div class="form-group">
                <label class="form-label-modern" for="emg-{{ $emgCle }}">
                    <i class="fas {{ $emgIcone }} text-primary"></i>
                    {{ $emgLabel }}
                </label>
                <input type="number" min="0" max="240" id="emg-{{ $emgCle }}"
                       class="form-control form-control-modern @error($emgCle) is-invalid @enderror"
                       name="setting_{{ $emgCle }}"
                       value="{{ old('setting_'.$emgCle, \App\Helpers\SettingsHelper::get($emgCle, $emgF::DEFAUTS[$emgCle])) }}">
                @if($emgAide)<small class="text-muted d-block mt-1">{{ $emgAide }}</small>@endif
                @error($emgCle)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach

        <div class="form-group">
            <label class="form-label-modern">
                <i class="fas fa-scale-balanced text-primary"></i>
                Émargement après le délai de retard
            </label>
            <x-au-select name="setting_{{ $emgF::CLE_DEPASSEMENT }}" :value="$emgDepassement"
                :placeholder-is-first-option="false"
                :options="[
                    $emgF::DEPASSEMENT_ABSENT => 'Absence enregistrée, séance non comptée',
                    $emgF::DEPASSEMENT_JUSTIFICATION => 'Retard accepté avec motif obligatoire',
                ]" />
            <small class="text-muted d-block mt-1">Avec le motif, la coordination voit le retard et sa raison dans l’écran des émargements.</small>
        </div>
    </div>
</div>

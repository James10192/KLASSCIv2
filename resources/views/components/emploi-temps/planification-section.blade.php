@props(['planificationData' => null, 'emploiTemps', 'open' => true])

@once
<style>
    [x-cloak] { display: none !important; }
    .epl-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        overflow: hidden;
    }
    .epl-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid transparent;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
        cursor: pointer;
        user-select: none;
        transition: background .15s ease;
    }
    .epl-header:hover { background: #f8fafc; }
    .epl-header--open { border-bottom-color: #e2e8f0; }
    .epl-header-title {
        display: flex;
        align-items: center;
        gap: .6rem;
        color: #0f172a;
        font-weight: 700;
        font-size: .92rem;
    }
    .epl-caret {
        margin-left: .5rem;
        color: #94a3b8;
        font-size: .75rem;
        transition: transform .2s ease;
        width: 14px;
        text-align: center;
    }
    .epl-caret--open {
        transform: rotate(90deg);
        color: #0453cb;
    }
    .epl-header-title i {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
    }
    .epl-header-summary {
        display: flex;
        gap: 1rem;
        font-size: .82rem;
    }
    .epl-summary-item {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }
    .epl-summary-value {
        color: #0f172a;
        font-weight: 700;
        line-height: 1;
        font-size: 1rem;
    }
    .epl-summary-label {
        color: #64748b;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .3px;
        margin-top: .15rem;
    }
    .epl-body {
        padding: 1rem 1.25rem;
    }

    .epl-matiere {
        padding: .85rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .epl-matiere:last-child { border-bottom: none; }
    .epl-matiere-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
        margin-bottom: .5rem;
    }
    .epl-matiere-name {
        display: flex;
        align-items: center;
        gap: .55rem;
        min-width: 0;
        flex: 1;
    }
    .epl-matiere-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: rgba(4,83,203,.1);
        color: #0453cb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        flex-shrink: 0;
    }
    .epl-matiere-label {
        font-weight: 600;
        color: #1e293b;
        font-size: .88rem;
        line-height: 1.2;
        word-break: break-word;
    }
    .epl-matiere-meta {
        font-size: .75rem;
        color: #64748b;
        margin-top: .1rem;
    }
    .epl-matiere-teacher i { color: #94a3b8; }
    .epl-matiere-numbers {
        display: flex;
        gap: .6rem;
        align-items: baseline;
        font-size: .78rem;
        color: #64748b;
        white-space: nowrap;
    }
    .epl-matiere-numbers strong {
        color: #0f172a;
        font-weight: 700;
        font-size: .95rem;
    }
    .epl-matiere-pct {
        color: #0453cb;
        font-weight: 700;
        font-size: .88rem;
    }

    .epl-progress {
        width: 100%;
        height: 8px;
        background: #f1f5f9;
        border-radius: 99px;
        overflow: hidden;
        position: relative;
    }
    .epl-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #0453cb, #3b7ddb);
        border-radius: 99px;
        transition: width .4s ease-out;
    }
    .epl-progress-bar--complete {
        background: linear-gradient(90deg, #10b981, #34d399);
    }

    .epl-teachers-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem;
        margin-top: .45rem;
    }
    .epl-teachers-chip {
        font-size: .7rem;
        padding: .2rem .55rem;
        background: #f1f5f9;
        color: #475569;
        border-radius: 99px;
        border: 1px solid #e2e8f0;
    }

    .epl-empty {
        padding: 2rem 1rem;
        text-align: center;
    }
    .epl-empty-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto .75rem;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(245,158,11,.1), rgba(245,158,11,.2));
        color: #f59e0b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .epl-empty h5 {
        color: #1e293b;
        font-size: 1rem;
        margin: 0 0 .3rem;
        font-weight: 600;
    }
    .epl-empty p {
        color: #64748b;
        font-size: .85rem;
        margin-bottom: 1rem;
    }
    .epl-empty .btn-primary {
        background: #0453cb;
        border-color: #0453cb;
        border-radius: 9px;
        font-weight: 600;
        padding: .5rem 1.1rem;
        font-size: .85rem;
    }
    .epl-empty .btn-primary:hover {
        background: #033a8e;
        border-color: #033a8e;
    }
</style>
@endonce

<div class="epl-card" x-data="{ open: @json((bool) $open) }">
    <div class="epl-header" :class="open ? 'epl-header--open' : ''" @click="open = !open" role="button" tabindex="0" :aria-expanded="open ? 'true' : 'false'" @keydown.enter.prevent="open = !open" @keydown.space.prevent="open = !open">
        <div class="epl-header-title">
            <i class="fas fa-calendar-check"></i>
            Suivi par matière
            <i class="fas fa-chevron-right epl-caret" :class="open ? 'epl-caret--open' : ''"></i>
        </div>

        @if(isset($planificationData['planifications_configurees']) && $planificationData['planifications_configurees'])
            <div class="epl-header-summary">
                <div class="epl-summary-item">
                    <div class="epl-summary-value">{{ $planificationData['heures_totales_formatted'] ?? ($planificationData['heures_totales'] ?? 0) . 'h' }}</div>
                    <div class="epl-summary-label">Planifiées</div>
                </div>
                <div class="epl-summary-item">
                    <div class="epl-summary-value">{{ $planificationData['heures_restantes_formatted'] ?? ($planificationData['heures_restantes'] ?? 0) . 'h' }}</div>
                    <div class="epl-summary-label">Restantes</div>
                </div>
            </div>
        @endif
    </div>

    <div class="epl-body" x-show="open" x-cloak x-transition.opacity>
        @if(empty($planificationData) || empty($planificationData['planifications_configurees']))
            <div class="epl-empty">
                <div class="epl-empty-icon">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <h5>Planification non configurée</h5>
                <p>{{ $planificationData['message_configuration'] ?? "Aucune planification académique n'est définie pour cette classe. Configurez d'abord les volumes horaires." }}</p>
                @if($emploiTemps->classe && $emploiTemps->annee)
                    <button type="button" class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#volumeConfigModal"
                            data-filiere-id="{{ $emploiTemps->classe->filiere_id }}"
                            data-niveau-id="{{ $emploiTemps->classe->niveau_etude_id }}"
                            data-annee-id="{{ $emploiTemps->annee->id }}"
                            data-combination-name="{{ $emploiTemps->classe->filiere->name ?? 'Filière' }} · {{ $emploiTemps->classe->niveau->name ?? 'Niveau' }}"
                            onclick="openVolumeConfigModal(this)">
                        <i class="fas fa-cog me-1"></i>Configurer les volumes horaires
                    </button>
                @endif
            </div>
        @else
            @foreach($planificationData['matieres_planifiees'] as $matiere)
                @php
                    $volumeTotal = (int) ($matiere['volume_horaire_total'] ?? 0);
                    $heuresRestantes = (int) ($matiere['heures_restantes'] ?? 0);
                    $pourcentageUtilise = (int) ($matiere['pourcentage_utilise'] ?? 0);
                    $heuresUtilisees = max(0, $volumeTotal - $heuresRestantes);
                    $isComplete = $pourcentageUtilise >= 100;
                    $enseignantPrincipal = $matiere['enseignant_affiche'] ?? null;
                    $enseignantsDispo = collect($matiere['enseignants_selectables'] ?? []);
                @endphp
                <div class="epl-matiere">
                    <div class="epl-matiere-top">
                        <div class="epl-matiere-name">
                            <div class="epl-matiere-icon"><i class="fas fa-book"></i></div>
                            <div>
                                <div class="epl-matiere-label">{{ $matiere['matiere']->name }}</div>
                                <div class="epl-matiere-meta epl-matiere-teacher">
                                    @if($enseignantPrincipal)
                                        <i class="fas fa-user-tie"></i> {{ $enseignantPrincipal->name }}
                                    @else
                                        <i class="fas fa-user-slash"></i> <span class="text-muted">Aucun enseignant assigné</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="epl-matiere-numbers">
                            <span><strong>{{ $heuresUtilisees }}h</strong> / {{ $volumeTotal }}h</span>
                            <span class="epl-matiere-pct">{{ $pourcentageUtilise }}%</span>
                        </div>
                    </div>

                    <div class="epl-progress" role="progressbar" aria-valuenow="{{ $pourcentageUtilise }}" aria-valuemin="0" aria-valuemax="100" aria-label="Volume horaire utilisé pour {{ $matiere['matiere']->name }}">
                        <div class="epl-progress-bar {{ $isComplete ? 'epl-progress-bar--complete' : '' }}"
                             style="width: {{ min(100, max(0, $pourcentageUtilise)) }}%;"></div>
                    </div>

                    @if($enseignantsDispo->count() > 1)
                        <div class="epl-teachers-chips" title="Enseignants pouvant intervenir sur cette matière">
                            @foreach($enseignantsDispo as $ens)
                                @php $nom = optional($ens->user)->name ?? $ens->name ?? 'Enseignant'; @endphp
                                <span class="epl-teachers-chip">{{ $nom }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>

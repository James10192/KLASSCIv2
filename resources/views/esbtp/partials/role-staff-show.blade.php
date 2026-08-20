{{--
    Fiche premium partagée par les quatre comptes d'organigramme scolarité.

    @param string      routeBase
    @param string      icon
    @param string      roleLabel
    @param object      model
    @param mixed|null  performanceScore
--}}
@php
    $score = null;
    if (!empty($performanceScore)) {
        $score = is_array($performanceScore)
            ? ($performanceScore['total_score'] ?? null)
            : ($performanceScore->total_score ?? null);
    }

    $infos = [
        ['icon' => 'fa-envelope', 'label' => 'Adresse e-mail', 'value' => $model->email],
        ['icon' => 'fa-phone', 'label' => 'Téléphone', 'value' => $model->telephone],
        ['icon' => 'fa-briefcase', 'label' => 'Spécialité', 'value' => $model->specialite],
    ];
@endphp

<div class="main-content">

    <x-role-hero
        :icon="$icon"
        :title="$model->name"
        :subtitle="$roleLabel">
        <x-slot:actions>
            <a class="rdx-btn" href="{{ route('esbtp.personnel.unified.index') }}">
                <i class="fas fa-arrow-left"></i>Retour
            </a>
            <a class="rdx-btn rdx-btn--white" href="{{ route($routeBase.'.edit', $model) }}">
                <i class="fas fa-pen"></i>Modifier
            </a>
        </x-slot:actions>
    </x-role-hero>

    <div class="rdx-grid">

        <x-role-panel
            icon="fa-id-card"
            title="Coordonnées"
            subtitle="Informations de contact du compte">
            <div class="rv-infos">
                @foreach($infos as $info)
                    <div class="rv-info">
                        <span class="rv-info-icon"><i class="fas {{ $info['icon'] }}"></i></span>
                        <span class="rv-info-body">
                            <span class="rv-info-label">{{ $info['label'] }}</span>
                            <span class="rv-info-value {{ $info['value'] ? '' : 'rv-info-value--empty' }}">
                                {{ $info['value'] ?: 'Non renseigné' }}
                            </span>
                        </span>
                    </div>
                @endforeach
            </div>
        </x-role-panel>

        <x-role-panel
            icon="fa-shield-halved"
            title="Statut du compte"
            subtitle="Connexion et suivi de performance">
            <div class="rv-status">
                <span class="rv-badge {{ $model->is_active ? 'rv-badge--on' : 'rv-badge--off' }}">
                    <i class="fas {{ $model->is_active ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                    {{ $model->is_active ? 'Compte actif' : 'Compte désactivé' }}
                </span>
                <p class="rv-status-hint">
                    {{ $model->is_active
                        ? 'Cette personne peut se connecter à KLASSCI.'
                        : 'La connexion est bloquée. Le compte reste visible dans l\'annuaire.' }}
                </p>
            </div>

            @if($score !== null)
                <div class="rv-score">
                    <div class="rv-score-head">
                        <span class="rv-score-label">Score de performance</span>
                        <span class="rv-score-value">{{ $score }}</span>
                    </div>
                    <div class="rv-score-bar">
                        <div class="rv-score-fill" style="width: {{ min(100, max(0, (float) $score)) }}%;"></div>
                    </div>
                </div>
            @else
                <x-role-empty
                    icon="fa-chart-simple"
                    title="Pas encore de score"
                    hint="Le score de performance apparaîtra dès que l'activité du compte aura été mesurée." />
            @endif
        </x-role-panel>

    </div>
</div>

@push('styles')
<style>
    /* Namespace rv-* : fiche d'un compte d'organigramme */
    .rv-infos { display: flex; flex-direction: column; gap: .6rem; }
    .rv-info {
        display: flex; align-items: center; gap: .8rem;
        padding: .7rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
    }
    .rv-info-icon {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        background: rgba(4, 83, 203, .08); color: #0453cb;
        display: flex; align-items: center; justify-content: center; font-size: .82rem;
    }
    .rv-info-body { display: flex; flex-direction: column; min-width: 0; }
    .rv-info-label {
        font-size: .7rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: .3px;
    }
    .rv-info-value { font-size: .88rem; font-weight: 600; color: #1e293b; margin-top: .1rem; word-break: break-word; }
    .rv-info-value--empty { color: #94a3b8; font-weight: 400; font-style: italic; }

    .rv-status { margin-bottom: 1rem; }
    .rv-badge {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .4rem .8rem; border-radius: 999px;
        font-size: .82rem; font-weight: 700;
    }
    .rv-badge--on { background: rgba(16, 185, 129, .12); color: #047857; }
    .rv-badge--off { background: rgba(100, 116, 139, .12); color: #475569; }
    .rv-status-hint { font-size: .8rem; color: #64748b; margin: .5rem 0 0; }

    .rv-score { margin-top: 1rem; }
    .rv-score-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: .4rem; }
    .rv-score-label { font-size: .78rem; font-weight: 600; color: #64748b; }
    .rv-score-value { font-size: 1.35rem; font-weight: 700; color: #0453cb; }
    .rv-score-bar { height: 8px; background: #eef2f7; border-radius: 999px; overflow: hidden; }
    .rv-score-fill { height: 100%; background: linear-gradient(90deg, #0453cb, #3b7ddb); border-radius: 999px; }
</style>
@endpush

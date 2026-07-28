@php
    $canViewInscriptionRepair = auth()->user()->can('students.view') || auth()->user()->can('inscriptions.view');
    $canRunInscriptionRepair = auth()->user()->can('inscriptions.manage') || (auth()->user()->can('inscriptions.edit') && auth()->user()->can('inscriptions.delete'));
    $repairClasses = $inscriptionRepairClasses ?? collect();
@endphp

@if($canViewInscriptionRepair)
<div
    class="insc-repair-panel"
    id="inscriptionRepairPanel"
    data-diagnose-url="{{ route('esbtp.etudiants.inscriptions.repair-diagnostic', $etudiant) }}"
    data-repair-url="{{ route('esbtp.etudiants.inscriptions.repair', $etudiant) }}"
    data-year-id="{{ $anneeCourante?->id }}"
    data-can-repair="{{ $canRunInscriptionRepair ? '1' : '0' }}"
>
    <div class="insc-repair-head">
        <div>
            <div class="insc-repair-title">
                <i class="fas fa-code-branch"></i>
                Correction des inscriptions en double
            </div>
            <div class="insc-repair-sub">
                Diagnostic de l'annee courante, conservation de l'inscription la plus payee, alignement classe, filiere, niveau et statut.
            </div>
        </div>
        @unless($canRunInscriptionRepair)
            <span class="insc-repair-badge neutral"><i class="fas fa-lock"></i> Lecture seule</span>
        @endunless
    </div>
    <div class="insc-repair-toolbar">
        <div class="insc-repair-field">
            <label for="inscriptionRepairTargetClasse">Classe cible</label>
            <select class="insc-repair-select" id="inscriptionRepairTargetClasse" @disabled($repairClasses->isEmpty())>
                <option value="">{{ $repairClasses->isEmpty() ? 'Aucune classe active disponible' : 'Choisir une classe' }}</option>
                @foreach($repairClasses as $classe)
                    <option value="{{ $classe->id }}">
                        {{ $classe->name }}
                        @if($classe->filiere)
                            - {{ $classe->filiere->name }}
                        @endif
                        @if($classe->niveauEtude)
                            / {{ $classe->niveauEtude->name }}
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
        <button type="button" class="insc-repair-btn secondary" id="inscriptionRepairDryRunBtn" @disabled($repairClasses->isEmpty())>
            <i class="fas fa-search"></i>
            Simuler
        </button>
        <button type="button" class="insc-repair-btn primary" id="inscriptionRepairApplyBtn" @disabled(!$canRunInscriptionRepair || $repairClasses->isEmpty())>
            <i class="fas fa-tools"></i>
            Reparer
        </button>
    </div>
    <div class="insc-repair-status" id="inscriptionRepairStatus">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Chargement du diagnostic...</span>
    </div>
    <div class="insc-repair-result" id="inscriptionRepairResult"></div>
</div>
@endif

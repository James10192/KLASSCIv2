@extends('layouts.app')

@section('title', 'Planning LMD')

@include('esbtp.lmd.planning._styles')
@include('esbtp.lmd.planning._edit_styles')
@include('esbtp.lmd.planning._bulk_styles')

@php
    $lpeContext = [
        'filiere_id' => $parcoursSelected?->filiere_id,
        'niveau_id' => $filters['niveau_id'],
        'semestre' => $filters['semestre'],
        'annee_universitaire_id' => optional(\App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first())->id,
    ];
@endphp

@section('content')
<div class="lp-page"
     x-data="lpPlanning"
     data-lpe-context='@json($lpeContext)'
     :class="loading ? 'lp-loading' : ''"
     @lpm:saved.window="fetchPartial()">
    <div class="lp-hero">
        <div class="lp-hero-top">
            <div class="lp-hero-left">
                <div class="lp-hero-icon"><i class="fas fa-sitemap"></i></div>
                <div>
                    <h1>Planning LMD</h1>
                    <p>Maquette pédagogique UE / ECUE par parcours et semestre</p>
                </div>
            </div>
            <div class="lp-hero-actions">
                <button type="button" class="lp-btn-glass" data-page-tour-open>
                    <i class="fas fa-route"></i> Guide
                </button>
                <button type="button" class="lp-btn-glass" data-page-help-open>
                    <i class="fas fa-question-circle"></i> Aide
                </button>
            </div>
        </div>
        <div class="lp-kpis" id="lpKpis" data-tour-node="kpis">
            @include('esbtp.lmd.planning._kpis')
        </div>
    </div>

    <div class="lp-filters" data-tour-node="filters">
        <template x-if="loading"><div class="lp-spinner"></div></template>
        <div class="lp-filters-row">
            <div class="lp-filter-group" data-tour-node="filter-parcours">
                <label class="lp-filter-label">Parcours</label>
                <x-au-select
                    name="parcours_id"
                    icon="fa-route"
                    placeholder="Tous les parcours"
                    :value="$filters['parcours_id']"
                    :searchable="$parcours->count() > 8"
                    :options="$parcours->mapWithKeys(fn ($p) => [$p->id => $p->label_complet])->all()"
                    x-on:change="reload($event.target.value, 'parcours_id')" />
            </div>
            <div class="lp-filter-group" data-tour-node="filter-niveau">
                <label class="lp-filter-label">Niveau</label>
                <x-au-select
                    name="niveau_id"
                    icon="fa-layer-group"
                    placeholder="Tous niveaux"
                    :value="$filters['niveau_id']"
                    :options="$niveaux->mapWithKeys(fn ($n) => [$n->id => $n->name])->all()"
                    x-on:change="reload($event.target.value, 'niveau_id')" />
            </div>
            <div class="lp-filter-group" data-tour-node="filter-semestre" id="lpFilterSemestre">
                <label class="lp-filter-label">Semestre</label>
                @include('esbtp.lmd.planning._filter_semestre')
            </div>
        </div>
    </div>

    <div class="lp-content-area" id="lpContent" data-tour-node="listing">
        @include('esbtp.lmd.planning._listing')
    </div>
</div>

@include('esbtp.lmd.planning._help_modal')
@include('esbtp.lmd.planning._link_ue_modal')
@include('esbtp.lmd.planning._teacher_modal')
@include('esbtp.lmd.planning._bulk_action_bar')
@include('esbtp.lmd.planning._bulk_modal')
@endsection

@include('esbtp.lmd.planning._scripts')
@include('esbtp.lmd.planning._tour_help_scripts')
@include('esbtp.lmd.planning._edit_scripts')
@include('esbtp.lmd.planning._bulk_scripts')

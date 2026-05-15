@extends('layouts.embedded')

@section('title', 'Modifier une inscription')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            @include('esbtp.inscriptions.partials.edit-form', [
                'inscription' => $inscription,
                'filieres' => $filieres,
                'niveaux' => $niveaux,
                'classes' => $classes,
                'annees' => $annees,
                'mentions' => $mentions ?? collect(),
                'parcours' => $parcours ?? collect(),
                'formId' => 'inscription-edit-form-embedded-' . $inscription->id,
                'isEmbedded' => true,
            ])
        </div>
    </div>
@endsection

@push('scripts')
    @include('esbtp.inscriptions.partials.edit-form-scripts')
@endpush

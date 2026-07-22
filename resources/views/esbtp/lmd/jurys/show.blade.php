@extends('layouts.app')
@section('title', 'Jury — ' . $jury->libelle)

@push('styles')
    @include('esbtp.lmd.jurys.partials.style')
@endpush

@section('content')
<div x-data="jurySalle({{ $jury->id }})" x-init="init()">
    @include('esbtp.lmd.jurys.partials.hero', ['jury' => $jury, 'stats' => $stats])

    @include('esbtp.lmd.jurys.partials.actions-bar', ['jury' => $jury, 'officialDocument' => $officialDocument])

    @include('esbtp.lmd.jurys.partials.readiness')

    @include('esbtp.lmd.jurys.partials.tabs')

    @include('esbtp.lmd.jurys.partials.tab-composition', ['jury' => $jury, 'enseignants' => $enseignants])

    @include('esbtp.lmd.jurys.partials.tab-deliberation', ['jury' => $jury])

    @include('esbtp.lmd.jurys.partials.tab-stats', ['jury' => $jury, 'stats' => $stats])

    <div x-show="tab==='pv'" x-cloak>
        @include('esbtp.lmd.jurys.partials.official-document-panel', ['jury' => $jury, 'officialDocument' => $officialDocument])
    </div>

    @include('esbtp.lmd.jurys.partials.review-signature-modals')
    @include('esbtp.lmd.jurys.partials.modal-overrides')
</div>

@include('esbtp.lmd.jurys.partials.jury-data')

@push('scripts')
    @include('esbtp.lmd.jurys.partials.jury-scripts')
@endpush
@endsection

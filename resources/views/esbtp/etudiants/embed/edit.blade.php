@extends('layouts.embedded')

@section('title', 'Modifier un étudiant')

@section('content')
    <div class="card-moderne">
        <div class="p-lg">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('esbtp.etudiants.partials.edit-form', ['etudiant' => $etudiant, 'isEmbedded' => true])
        </div>
    </div>
@endsection

@push('scripts')
    @include('esbtp.etudiants.partials.edit-form-scripts')
@endpush

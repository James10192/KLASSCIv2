@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Gestion des codes d'assiduité</h2>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Générer un nouveau code</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" required
                           min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Générer un code</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Liste des codes générés</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Expire le</th>
                            <th>Statut</th>
                            <th>Utilisé par</th>
                            <th>Tentatives</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($codes as $code)
                        <tr>
                            <td>{{ $code->code }}</td>
                            <td>{{ $code->date->format('d/m/Y') }}</td>
                            <td>{{ $code->expires_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($code->is_used)
                                    <span class="badge bg-success">Utilisé</span>
                                @elseif($code->expires_at->isPast())
                                    <span class="badge bg-danger">Expiré</span>
                                @else
                                    <span class="badge bg-primary">Valide</span>
                                @endif
                            </td>
                            <td>
                                @if($code->usedByTeacher)
                                    {{ $code->usedByTeacher->nom }} {{ $code->usedByTeacher->prenoms }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($code->attempts >= 3)
                                    <span class="badge bg-danger">{{ $code->attempts }}</span>
                                @else
                                    {{ $code->attempts }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $codes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

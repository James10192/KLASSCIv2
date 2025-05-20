@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Catégories de frais</h2>
    <a href="{{ route('esbtp.fee-categories.create') }}" class="btn btn-primary mb-3">Nouvelle catégorie</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Code</th>
                <th>Description</th>
                <th>Prix par défaut</th>
                <th>Statut</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $cat)
                <tr>
                    <td>{{ $cat->name }}</td>
                    <td>{{ $cat->code }}</td>
                    <td>{{ $cat->description }}</td>
                    <td>{{ $cat->default_amount ? number_format($cat->default_amount, 2) . ' F CFA' : '-' }}</td>
                    <td>{!! $cat->is_active ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-danger">Inactif</span>' !!}</td>
                    <td>
                        @if($cat->is_mandatory)
                            <span class="badge bg-primary">Obligatoire</span>
                        @else
                            <span class="badge bg-secondary">Optionnel</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('esbtp.fee-categories.show', $cat) }}" class="btn btn-info btn-sm">Voir</a>
                        <a href="{{ route('esbtp.fee-categories.edit', $cat) }}" class="btn btn-primary btn-sm">Éditer</a>
                        <form action="{{ route('esbtp.fee-categories.destroy', $cat) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $categories->links() }}
</div>
@endsection

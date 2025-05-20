@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Catégories de paiements</h2>
    <a href="{{ route('esbtp.payment-categories.create') }}" class="btn btn-primary mb-3">Nouvelle catégorie</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Code</th>
                <th>Description</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $cat)
                <tr>
                    <td>{{ $cat->name }}</td>
                    <td>{{ $cat->code }}</td>
                    <td>{{ $cat->description }}</td>
                    <td>{{ $cat->is_active ? 'Actif' : 'Inactif' }}</td>
                    <td>
                        <a href="{{ route('esbtp.payment-categories.edit', $cat) }}" class="btn btn-sm btn-warning">Modifier</a>
                        <form action="{{ route('esbtp.payment-categories.destroy', $cat) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette catégorie ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $categories->links() }}
</div>
@endsection

@extends('layouts.app')

@section('title', 'Étudiants ajournés')

@section('content')
<div class="dashboard-acasi">
    <div class="main-card" style="padding: 1.5rem;">
        <h1 style="color:#1e293b; margin:0 0 0.5rem;">Étudiants ajournés</h1>
        <p style="color:#64748b; margin:0 0 1.5rem;">
            UE et IE (ECUE) non validées — {{ $annee->name ?? 'année courante' }}
        </p>
        <div class="table-modern">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Parcours / classe</th>
                        <th>UE non validées</th>
                        <th>IE (ECUE) &lt; 10</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($lignes as $ligne)
                    <tr>
                        <td>
                            <strong>{{ $ligne['etudiant']->nom ?? '' }} {{ $ligne['etudiant']->prenoms ?? '' }}</strong><br>
                            <span style="color:#64748b">{{ $ligne['etudiant']->matricule ?? '' }}</span>
                        </td>
                        <td>
                            {{ $ligne['jury']->parcours->name ?? '' }}<br>
                            <span style="color:#64748b">{{ $ligne['jury']->classe->name ?? '' }}</span>
                        </td>
                        <td>
                            @forelse ($ligne['ues_non_validees'] as $ue)
                                <div>{{ $ue['ue'] }} ({{ $ue['statut'] }} · {{ $ue['moyenne'] }})</div>
                            @empty
                                —
                            @endforelse
                        </td>
                        <td>
                            @foreach ($ligne['ues_non_validees'] as $ue)
                                {{ implode(', ', $ue['ie']) }}
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aucun ajourné pour l'année courante.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

{{-- Boucle sur toutes les classes et inclure le partial classe-card pour chacune.
     Permissions hoisted ici plutôt que dans le partial (évite N×3 appels can() pour N cartes). --}}
@php
    $u = auth()->user();
    $cardPerms = [
        'canAdmin'         => $u->can('admin.access'),
        'canEditClasse'    => $u->can('classes.edit'),
        'canDeleteClasse'  => $u->can('classes.delete'),
        'canManageSchool'  => $u->hasAnyPermission(['admin.access', 'identity.school_manager', 'identity.coordinate']),
        'canTeach'         => $u->hasAnyPermission(['admin.access', 'identity.school_manager', 'identity.teach', 'identity.coordinate']),
    ];
@endphp
@foreach($classes as $classe)
    @include('esbtp.classes.partials.classe-card', array_merge(['classe' => $classe], $cardPerms))
@endforeach

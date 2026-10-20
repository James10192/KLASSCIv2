{{--
    Ouvre l'écran des accès individuels avec cette personne déjà choisie.
    Mêmes gardes que la route (personnel.manage + permissions.temporaires.manage) :
    si l'une change, c'est ici, et nulle part ailleurs dans la liste du personnel.
--}}
@props(['userId'])
@php $_atpUserId = (int) $userId; @endphp
@if($_atpUserId > 0 && $_atpUserId !== (int) auth()->id() && auth()->user()?->can('permissions.temporaires.manage') && auth()->user()->can('personnel.manage'))
<a href="{{ route('esbtp.acces-individuels.index', ['user_id' => $_atpUserId]) }}" class="pu-action-btn" title="Gérer les accès individuels" aria-label="Gérer les accès individuels de cette personne">
    <i class="fas fa-user-shield"></i>
</a>
@endif

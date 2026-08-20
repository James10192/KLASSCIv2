{{--
    État vide premium. Chaque liste d'un écran de rôle doit en afficher un
    plutôt qu'une zone blanche (cf. rule ship-quality : loading / empty /
    error / success sont tous gérés).

    @param string      icon
    @param string      title
    @param string|null hint
--}}
@props([
    'icon' => 'fa-inbox',
    'title' => 'Rien à afficher',
    'hint' => null,
])

<div class="rdx-empty">
    <div class="rdx-empty-icon"><i class="fas {{ $icon }}"></i></div>
    <div class="rdx-empty-title">{{ $title }}</div>
    @if($hint)<div class="rdx-empty-hint">{{ $hint }}</div>@endif
    @isset($action)
        <div style="margin-top:.85rem;">{{ $action }}</div>
    @endisset
</div>

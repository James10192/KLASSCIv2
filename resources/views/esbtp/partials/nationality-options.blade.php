@php
    $selectedValue = $selected ?? null;

    // Le vocabulaire vit dans config/nationalites.php, pas ici : c'est de la
    // donnee, et le portail public a besoin de la MEME liste pour que ce qu'un
    // candidat declare corresponde a ce que ce formulaire sait afficher.
    $_groupes = \App\Support\Nationalites::groupes();
@endphp
<option value="">{{ __('Sélectionner une nationalité') }}</option>
@foreach($_groupes as $_groupe)
    @if($_groupe['titre'] !== null)
<optgroup label="────────── {{ $_groupe['titre'] }} ──────────">
    @endif
    @foreach($_groupe['entrees'] as $_valeur => $_drapeau)
<option value="{{ $_valeur }}" {{ $selectedValue === $_valeur ? 'selected' : '' }}>{{ trim($_drapeau.' '.$_valeur) }}</option>
    @endforeach
    @if($_groupe['titre'] !== null)
</optgroup>
    @endif
@endforeach

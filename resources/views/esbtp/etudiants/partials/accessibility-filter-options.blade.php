@php
    /**
     * Options groupées du filtre Accessibility (etudiants.index).
     * Réutilisable desktop + mobile drawer.
     * Variable attendue : $current (string|null) — valeur actuellement sélectionnée.
     */
    $current = $current ?? null;
    $categories = \App\Models\ESBTPStudentAccessibilityProfile::CATEGORIES;
    $accommodations = \App\Models\ESBTPStudentAccessibilityProfile::ACCOMMODATIONS;
@endphp

<option value="">— Tous les étudiants —</option>

<optgroup label="État général">
    <option value="with"    {{ $current === 'with' ? 'selected' : '' }}>Avec profil d'accessibilité</option>
    <option value="without" {{ $current === 'without' ? 'selected' : '' }}>Sans profil d'accessibilité</option>
</optgroup>

<optgroup label="Aménagements clés">
    <option value="tiers_temps" {{ $current === 'tiers_temps' ? 'selected' : '' }}>Tiers-temps actif</option>
    <option value="assistant"   {{ $current === 'assistant' ? 'selected' : '' }}>Assistant requis</option>
    <option value="recognition" {{ $current === 'recognition' ? 'selected' : '' }}>Reconnaissance officielle</option>
</optgroup>

<optgroup label="Catégories de handicap">
    @foreach($categories as $key => $label)
        <option value="cat:{{ $key }}" {{ $current === 'cat:'.$key ? 'selected' : '' }}>Catégorie : {{ $label }}</option>
    @endforeach
</optgroup>

<optgroup label="Aménagements détaillés">
    @foreach($accommodations as $key => $label)
        <option value="acc:{{ $key }}" {{ $current === 'acc:'.$key ? 'selected' : '' }}>Aménagement : {{ $label }}</option>
    @endforeach
</optgroup>

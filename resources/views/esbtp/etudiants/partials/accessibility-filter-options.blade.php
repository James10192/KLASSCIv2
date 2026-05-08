@php
    use App\Models\ESBTPStudentAccessibilityProfile as AccProfile;
    $current = $current ?? null;
    $catPrefix = AccProfile::FILTER_PREFIX_CATEGORY;
    $accPrefix = AccProfile::FILTER_PREFIX_ACCOMMODATION;
@endphp

<option value="">— Tous les étudiants —</option>

<optgroup label="État général">
    <option value="{{ AccProfile::FILTER_WITH }}"    {{ $current === AccProfile::FILTER_WITH ? 'selected' : '' }}>Avec profil d'accessibilité</option>
    <option value="{{ AccProfile::FILTER_WITHOUT }}" {{ $current === AccProfile::FILTER_WITHOUT ? 'selected' : '' }}>Sans profil d'accessibilité</option>
</optgroup>

<optgroup label="Aménagements clés">
    <option value="{{ AccProfile::FILTER_TIERS_TEMPS }}" {{ $current === AccProfile::FILTER_TIERS_TEMPS ? 'selected' : '' }}>Tiers-temps actif</option>
    <option value="{{ AccProfile::FILTER_ASSISTANT }}"   {{ $current === AccProfile::FILTER_ASSISTANT ? 'selected' : '' }}>Assistant requis</option>
    <option value="{{ AccProfile::FILTER_RECOGNITION }}" {{ $current === AccProfile::FILTER_RECOGNITION ? 'selected' : '' }}>Reconnaissance officielle</option>
</optgroup>

<optgroup label="Catégories de handicap">
    @foreach(AccProfile::CATEGORIES as $key => $label)
        <option value="{{ $catPrefix.$key }}" {{ $current === $catPrefix.$key ? 'selected' : '' }}>Catégorie : {{ $label }}</option>
    @endforeach
</optgroup>

<optgroup label="Aménagements détaillés">
    @foreach(AccProfile::ACCOMMODATIONS as $key => $label)
        <option value="{{ $accPrefix.$key }}" {{ $current === $accPrefix.$key ? 'selected' : '' }}>Aménagement : {{ $label }}</option>
    @endforeach
</optgroup>

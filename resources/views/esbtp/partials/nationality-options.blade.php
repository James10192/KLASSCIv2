@php
    $selectedValue = $selected ?? null;
@endphp
<option value="">{{ __('Sélectionner une nationalité') }}</option>
<option value="Ivoirienne" {{ $selectedValue === 'Ivoirienne' ? 'selected' : '' }}>🇨🇮 Ivoirienne</option>
<optgroup label="────────── Afrique francophone ──────────">
    <option value="Algérienne" {{ $selectedValue === 'Algérienne' ? 'selected' : '' }}>🇩🇿 Algérienne</option>
    <option value="Angolaise" {{ $selectedValue === 'Angolaise' ? 'selected' : '' }}>🇦🇴 Angolaise</option>
    <option value="Béninoise" {{ $selectedValue === 'Béninoise' ? 'selected' : '' }}>🇧🇯 Béninoise</option>
    <option value="Botswanaise" {{ $selectedValue === 'Botswanaise' ? 'selected' : '' }}>🇧🇼 Botswanaise</option>
    <option value="Burkinabè" {{ $selectedValue === 'Burkinabè' ? 'selected' : '' }}>🇧🇫 Burkinabè</option>
    <option value="Burundaise" {{ $selectedValue === 'Burundaise' ? 'selected' : '' }}>🇧🇮 Burundaise</option>
    <option value="Camerounaise" {{ $selectedValue === 'Camerounaise' ? 'selected' : '' }}>🇨🇲 Camerounaise</option>
    <option value="Cap-verdienne" {{ $selectedValue === 'Cap-verdienne' ? 'selected' : '' }}>🇨🇻 Cap-verdienne</option>
    <option value="Centrafricaine" {{ $selectedValue === 'Centrafricaine' ? 'selected' : '' }}>🇨🇫 Centrafricaine</option>
    <option value="Comorienne" {{ $selectedValue === 'Comorienne' ? 'selected' : '' }}>🇰🇲 Comorienne</option>
    <option value="Congolaise (RDC)" {{ $selectedValue === 'Congolaise (RDC)' ? 'selected' : '' }}>🇨🇩 Congolaise (RDC)</option>
    <option value="Congolaise (RC)" {{ $selectedValue === 'Congolaise (RC)' ? 'selected' : '' }}>🇨🇬 Congolaise (RC)</option>
    <option value="Djiboutienne" {{ $selectedValue === 'Djiboutienne' ? 'selected' : '' }}>🇩🇯 Djiboutienne</option>
    <option value="Égyptienne" {{ $selectedValue === 'Égyptienne' ? 'selected' : '' }}>🇪🇬 Égyptienne</option>
    <option value="Érythréenne" {{ $selectedValue === 'Érythréenne' ? 'selected' : '' }}>🇪🇷 Érythréenne</option>
    <option value="Éthiopienne" {{ $selectedValue === 'Éthiopienne' ? 'selected' : '' }}>🇪🇹 Éthiopienne</option>
    <option value="Gabonaise" {{ $selectedValue === 'Gabonaise' ? 'selected' : '' }}>🇬🇦 Gabonaise</option>
    <option value="Gambienne" {{ $selectedValue === 'Gambienne' ? 'selected' : '' }}>🇬🇲 Gambienne</option>
    <option value="Ghanéenne" {{ $selectedValue === 'Ghanéenne' ? 'selected' : '' }}>🇬🇭 Ghanéenne</option>
    <option value="Guinéenne" {{ $selectedValue === 'Guinéenne' ? 'selected' : '' }}>🇬🇳 Guinéenne</option>
    <option value="Bissau-Guinéenne" {{ $selectedValue === 'Bissau-Guinéenne' ? 'selected' : '' }}>🇬🇼 Bissau-Guinéenne</option>
    <option value="Équato-Guinéenne" {{ $selectedValue === 'Équato-Guinéenne' ? 'selected' : '' }}>🇬🇶 Équato-Guinéenne</option>
    <option value="Kényane" {{ $selectedValue === 'Kényane' ? 'selected' : '' }}>🇰🇪 Kényane</option>
    <option value="Lesothane" {{ $selectedValue === 'Lesothane' ? 'selected' : '' }}>🇱🇸 Lesothane</option>
    <option value="Libérienne" {{ $selectedValue === 'Libérienne' ? 'selected' : '' }}>🇱🇷 Libérienne</option>
    <option value="Libyenne" {{ $selectedValue === 'Libyenne' ? 'selected' : '' }}>🇱🇾 Libyenne</option>
    <option value="Malgache" {{ $selectedValue === 'Malgache' ? 'selected' : '' }}>🇲🇬 Malgache</option>
    <option value="Malawite" {{ $selectedValue === 'Malawite' ? 'selected' : '' }}>🇲🇼 Malawite</option>
    <option value="Malienne" {{ $selectedValue === 'Malienne' ? 'selected' : '' }}>🇲🇱 Malienne</option>
    <option value="Marocaine" {{ $selectedValue === 'Marocaine' ? 'selected' : '' }}>🇲🇦 Marocaine</option>
    <option value="Mauritanienne" {{ $selectedValue === 'Mauritanienne' ? 'selected' : '' }}>🇲🇷 Mauritanienne</option>
    <option value="Mozambicaine" {{ $selectedValue === 'Mozambicaine' ? 'selected' : '' }}>🇲🇿 Mozambicaine</option>
    <option value="Namibienne" {{ $selectedValue === 'Namibienne' ? 'selected' : '' }}>🇳🇦 Namibienne</option>
    <option value="Nigérienne" {{ $selectedValue === 'Nigérienne' ? 'selected' : '' }}>🇳🇪 Nigérienne</option>
    <option value="Nigériane" {{ $selectedValue === 'Nigériane' ? 'selected' : '' }}>🇳🇬 Nigériane</option>
    <option value="Rwandaise" {{ $selectedValue === 'Rwandaise' ? 'selected' : '' }}>🇷🇼 Rwandaise</option>
    <option value="Sénégalaise" {{ $selectedValue === 'Sénégalaise' ? 'selected' : '' }}>🇸🇳 Sénégalaise</option>
    <option value="Seychelloise" {{ $selectedValue === 'Seychelloise' ? 'selected' : '' }}>🇸🇨 Seychelloise</option>
    <option value="Sierra-Léonaise" {{ $selectedValue === 'Sierra-Léonaise' ? 'selected' : '' }}>🇸🇱 Sierra-Léonaise</option>
    <option value="Somalienne" {{ $selectedValue === 'Somalienne' ? 'selected' : '' }}>🇸🇴 Somalienne</option>
    <option value="Sud-Africaine" {{ $selectedValue === 'Sud-Africaine' ? 'selected' : '' }}>🇿🇦 Sud-Africaine</option>
    <option value="Soudanaise" {{ $selectedValue === 'Soudanaise' ? 'selected' : '' }}>🇸🇩 Soudanaise</option>
    <option value="Sud-Soudanaise" {{ $selectedValue === 'Sud-Soudanaise' ? 'selected' : '' }}>🇸🇸 Sud-Soudanaise</option>
    <option value="Tanzanienne" {{ $selectedValue === 'Tanzanienne' ? 'selected' : '' }}>🇹🇿 Tanzanienne</option>
    <option value="Tchadienne" {{ $selectedValue === 'Tchadienne' ? 'selected' : '' }}>🇹🇩 Tchadienne</option>
    <option value="Togolaise" {{ $selectedValue === 'Togolaise' ? 'selected' : '' }}>🇹🇬 Togolaise</option>
    <option value="Tunisienne" {{ $selectedValue === 'Tunisienne' ? 'selected' : '' }}>🇹🇳 Tunisienne</option>
    <option value="Zambienne" {{ $selectedValue === 'Zambienne' ? 'selected' : '' }}>🇿🇲 Zambienne</option>
    <option value="Zimbabwéenne" {{ $selectedValue === 'Zimbabwéenne' ? 'selected' : '' }}>🇿🇼 Zimbabwéenne</option>
</optgroup>
<optgroup label="────────── Afrique anglophone ──────────">
    <option value="Sud-Africaine" {{ $selectedValue === 'Sud-Africaine' ? 'selected' : '' }}>🇿🇦 Sud-Africaine</option>
    <option value="Ghanéenne" {{ $selectedValue === 'Ghanéenne' ? 'selected' : '' }}>🇬🇭 Ghanéenne</option>
    <option value="Sierra-Léonaise" {{ $selectedValue === 'Sierra-Léonaise' ? 'selected' : '' }}>🇸🇱 Sierra-Léonaise</option>
    <option value="Libérienne" {{ $selectedValue === 'Libérienne' ? 'selected' : '' }}>🇱🇷 Libérienne</option>
    <option value="Nigériane" {{ $selectedValue === 'Nigériane' ? 'selected' : '' }}>🇳🇬 Nigériane</option>
    <option value="Gambienne" {{ $selectedValue === 'Gambienne' ? 'selected' : '' }}>🇬🇲 Gambienne</option>
    <option value="Kényane" {{ $selectedValue === 'Kényane' ? 'selected' : '' }}>🇰🇪 Kényane</option>
    <option value="Ougandaise" {{ $selectedValue === 'Ougandaise' ? 'selected' : '' }}>🇺🇬 Ougandaise</option>
    <option value="Tanzanienne" {{ $selectedValue === 'Tanzanienne' ? 'selected' : '' }}>🇹🇿 Tanzanienne</option>
    <option value="Botswanaise" {{ $selectedValue === 'Botswanaise' ? 'selected' : '' }}>🇧🇼 Botswanaise</option>
    <option value="Namibienne" {{ $selectedValue === 'Namibienne' ? 'selected' : '' }}>🇳🇦 Namibienne</option>
</optgroup>
<optgroup label="────────── Afrique lusophone ──────────">
    <option value="Angolaise" {{ $selectedValue === 'Angolaise' ? 'selected' : '' }}>🇦🇴 Angolaise</option>
    <option value="Mozambicaine" {{ $selectedValue === 'Mozambicaine' ? 'selected' : '' }}>🇲🇿 Mozambicaine</option>
    <option value="Cap-verdienne" {{ $selectedValue === 'Cap-verdienne' ? 'selected' : '' }}>🇨🇻 Cap-verdienne</option>
    <option value="Bissau-Guinéenne" {{ $selectedValue === 'Bissau-Guinéenne' ? 'selected' : '' }}>🇬🇼 Bissau-Guinéenne</option>
    <option value="Santoméenne" {{ $selectedValue === 'Santoméenne' ? 'selected' : '' }}>🇸🇹 Santoméenne</option>
</optgroup>
<optgroup label="────────── Afrique arabophone ──────────">
    <option value="Marocaine" {{ $selectedValue === 'Marocaine' ? 'selected' : '' }}>🇲🇦 Marocaine</option>
    <option value="Algérienne" {{ $selectedValue === 'Algérienne' ? 'selected' : '' }}>🇩🇿 Algérienne</option>
    <option value="Tunisienne" {{ $selectedValue === 'Tunisienne' ? 'selected' : '' }}>🇹🇳 Tunisienne</option>
    <option value="Libyenne" {{ $selectedValue === 'Libyenne' ? 'selected' : '' }}>🇱🇾 Libyenne</option>
    <option value="Égyptienne" {{ $selectedValue === 'Égyptienne' ? 'selected' : '' }}>🇪🇬 Égyptienne</option>
    <option value="Mauritanienne" {{ $selectedValue === 'Mauritanienne' ? 'selected' : '' }}>🇲🇷 Mauritanienne</option>
    <option value="Soudanaise" {{ $selectedValue === 'Soudanaise' ? 'selected' : '' }}>🇸🇩 Soudanaise</option>
</optgroup>
<optgroup label="────────── Afrique centrale ──────────">
    <option value="Gabonaise" {{ $selectedValue === 'Gabonaise' ? 'selected' : '' }}>🇬🇦 Gabonaise</option>
    <option value="Congolaise (RC)" {{ $selectedValue === 'Congolaise (RC)' ? 'selected' : '' }}>🇨🇬 Congolaise (RC)</option>
    <option value="Congolaise (RDC)" {{ $selectedValue === 'Congolaise (RDC)' ? 'selected' : '' }}>🇨🇩 Congolaise (RDC)</option>
    <option value="Centrafricaine" {{ $selectedValue === 'Centrafricaine' ? 'selected' : '' }}>🇨🇫 Centrafricaine</option>
    <option value="Équato-Guinéenne" {{ $selectedValue === 'Équato-Guinéenne' ? 'selected' : '' }}>🇬🇶 Équato-Guinéenne</option>
</optgroup>
<optgroup label="────────── Afrique australe ──────────">
    <option value="Sud-Africaine" {{ $selectedValue === 'Sud-Africaine' ? 'selected' : '' }}>🇿🇦 Sud-Africaine</option>
    <option value="Namibienne" {{ $selectedValue === 'Namibienne' ? 'selected' : '' }}>🇳🇦 Namibienne</option>
    <option value="Botswanaise" {{ $selectedValue === 'Botswanaise' ? 'selected' : '' }}>🇧🇼 Botswanaise</option>
    <option value="Zimbabwéenne" {{ $selectedValue === 'Zimbabwéenne' ? 'selected' : '' }}>🇿🇼 Zimbabwéenne</option>
    <option value="Zambienne" {{ $selectedValue === 'Zambienne' ? 'selected' : '' }}>🇿🇲 Zambienne</option>
    <option value="Malawite" {{ $selectedValue === 'Malawite' ? 'selected' : '' }}>🇲🇼 Malawite</option>
</optgroup>
<optgroup label="────────── Afrique de l'Est ──────────">
    <option value="Tanzanienne" {{ $selectedValue === 'Tanzanienne' ? 'selected' : '' }}>🇹🇿 Tanzanienne</option>
    <option value="Kényane" {{ $selectedValue === 'Kényane' ? 'selected' : '' }}>🇰🇪 Kényane</option>
    <option value="Ougandaise" {{ $selectedValue === 'Ougandaise' ? 'selected' : '' }}>🇺🇬 Ougandaise</option>
    <option value="Rwandaise" {{ $selectedValue === 'Rwandaise' ? 'selected' : '' }}>🇷🇼 Rwandaise</option>
    <option value="Burundaise" {{ $selectedValue === 'Burundaise' ? 'selected' : '' }}>🇧🇮 Burundaise</option>
    <option value="Éthiopienne" {{ $selectedValue === 'Éthiopienne' ? 'selected' : '' }}>🇪🇹 Éthiopienne</option>
    <option value="Somalienne" {{ $selectedValue === 'Somalienne' ? 'selected' : '' }}>🇸🇴 Somalienne</option>
</optgroup>
<optgroup label="────────── Afrique de l'Ouest ──────────">
    <option value="Ivoirienne" {{ $selectedValue === 'Ivoirienne' ? 'selected' : '' }}>🇨🇮 Ivoirienne</option>
    <option value="Burkinabè" {{ $selectedValue === 'Burkinabè' ? 'selected' : '' }}>🇧🇫 Burkinabè</option>
    <option value="Ghanéenne" {{ $selectedValue === 'Ghanéenne' ? 'selected' : '' }}>🇬🇭 Ghanéenne</option>
    <option value="Malienne" {{ $selectedValue === 'Malienne' ? 'selected' : '' }}>🇲🇱 Malienne</option>
    <option value="Nigérienne" {{ $selectedValue === 'Nigérienne' ? 'selected' : '' }}>🇳🇪 Nigérienne</option>
    <option value="Nigériane" {{ $selectedValue === 'Nigériane' ? 'selected' : '' }}>🇳🇬 Nigériane</option>
    <option value="Sénégalaise" {{ $selectedValue === 'Sénégalaise' ? 'selected' : '' }}>🇸🇳 Sénégalaise</option>
    <option value="Togolaise" {{ $selectedValue === 'Togolaise' ? 'selected' : '' }}>🇹🇬 Togolaise</option>
    <option value="Béninoise" {{ $selectedValue === 'Béninoise' ? 'selected' : '' }}>🇧🇯 Béninoise</option>
    <option value="Guinéenne" {{ $selectedValue === 'Guinéenne' ? 'selected' : '' }}>🇬🇳 Guinéenne</option>
    <option value="Cap-verdienne" {{ $selectedValue === 'Cap-verdienne' ? 'selected' : '' }}>🇨🇻 Cap-verdienne</option>
    <option value="Sierra-Léonaise" {{ $selectedValue === 'Sierra-Léonaise' ? 'selected' : '' }}>🇸🇱 Sierra-Léonaise</option>
    <option value="Libérienne" {{ $selectedValue === 'Libérienne' ? 'selected' : '' }}>🇱🇷 Libérienne</option>
</optgroup>
<optgroup label="────────── Europe ──────────">
    <option value="Française" {{ $selectedValue === 'Française' ? 'selected' : '' }}>🇫🇷 Française</option>
    <option value="Belge" {{ $selectedValue === 'Belge' ? 'selected' : '' }}>🇧🇪 Belge</option>
    <option value="Suisse" {{ $selectedValue === 'Suisse' ? 'selected' : '' }}>🇨🇭 Suisse</option>
    <option value="Allemande" {{ $selectedValue === 'Allemande' ? 'selected' : '' }}>🇩🇪 Allemande</option>
    <option value="Italienne" {{ $selectedValue === 'Italienne' ? 'selected' : '' }}>🇮🇹 Italienne</option>
    <option value="Espagnole" {{ $selectedValue === 'Espagnole' ? 'selected' : '' }}>🇪🇸 Espagnole</option>
    <option value="Portugaise" {{ $selectedValue === 'Portugaise' ? 'selected' : '' }}>🇵🇹 Portugaise</option>
    <option value="Grecque" {{ $selectedValue === 'Grecque' ? 'selected' : '' }}>🇬🇷 Grecque</option>
    <option value="Turque" {{ $selectedValue === 'Turque' ? 'selected' : '' }}>🇹🇷 Turque</option>
    <option value="Polonaise" {{ $selectedValue === 'Polonaise' ? 'selected' : '' }}>🇵🇱 Polonaise</option>
    <option value="Tchèque" {{ $selectedValue === 'Tchèque' ? 'selected' : '' }}>🇨🇿 Tchèque</option>
    <option value="Hongroise" {{ $selectedValue === 'Hongroise' ? 'selected' : '' }}>🇭🇺 Hongroise</option>
    <option value="Roumaine" {{ $selectedValue === 'Roumaine' ? 'selected' : '' }}>🇷🇴 Roumaine</option>
    <option value="Bulgare" {{ $selectedValue === 'Bulgare' ? 'selected' : '' }}>🇧🇬 Bulgare</option>
    <option value="Croate" {{ $selectedValue === 'Croate' ? 'selected' : '' }}>🇭🇷 Croate</option>
    <option value="Serbe" {{ $selectedValue === 'Serbe' ? 'selected' : '' }}>🇷🇸 Serbe</option>
    <option value="Slovène" {{ $selectedValue === 'Slovène' ? 'selected' : '' }}>🇸🇮 Slovène</option>
    <option value="Slovaque" {{ $selectedValue === 'Slovaque' ? 'selected' : '' }}>🇸🇰 Slovaque</option>
    <option value="Ukrainienne" {{ $selectedValue === 'Ukrainienne' ? 'selected' : '' }}>🇺🇦 Ukrainienne</option>
</optgroup>
<optgroup label="────────── Amériques ──────────">
    <option value="Canadienne" {{ $selectedValue === 'Canadienne' ? 'selected' : '' }}>🇨🇦 Canadienne</option>
    <option value="Américaine" {{ $selectedValue === 'Américaine' ? 'selected' : '' }}>🇺🇸 Américaine</option>
    <option value="Brésilienne" {{ $selectedValue === 'Brésilienne' ? 'selected' : '' }}>🇧🇷 Brésilienne</option>
    <option value="Haïtienne" {{ $selectedValue === 'Haïtienne' ? 'selected' : '' }}>🇭🇹 Haïtienne</option>
    <option value="Dominicaine" {{ $selectedValue === 'Dominicaine' ? 'selected' : '' }}>🇩🇴 Dominicaine</option>
    <option value="Mexicaine" {{ $selectedValue === 'Mexicaine' ? 'selected' : '' }}>🇲🇽 Mexicaine</option>
    <option value="Colombienne" {{ $selectedValue === 'Colombienne' ? 'selected' : '' }}>🇨🇴 Colombienne</option>
</optgroup>
<optgroup label="────────── Asie ──────────">
    <option value="Libanaise" {{ $selectedValue === 'Libanaise' ? 'selected' : '' }}>🇱🇧 Libanaise</option>
    <option value="Saoudienne" {{ $selectedValue === 'Saoudienne' ? 'selected' : '' }}>🇸🇦 Saoudienne</option>
    <option value="Iranienne" {{ $selectedValue === 'Iranienne' ? 'selected' : '' }}>🇮🇷 Iranienne</option>
    <option value="Israélienne" {{ $selectedValue === 'Israélienne' ? 'selected' : '' }}>🇮🇱 Israélienne</option>
    <option value="Indienne" {{ $selectedValue === 'Indienne' ? 'selected' : '' }}>🇮🇳 Indienne</option>
    <option value="Chinoise" {{ $selectedValue === 'Chinoise' ? 'selected' : '' }}>🇨🇳 Chinoise</option>
    <option value="Japonaise" {{ $selectedValue === 'Japonaise' ? 'selected' : '' }}>🇯🇵 Japonaise</option>
    <option value="Coréenne" {{ $selectedValue === 'Coréenne' ? 'selected' : '' }}>🇰🇷 Coréenne</option>
    <option value="Thaïlandaise" {{ $selectedValue === 'Thaïlandaise' ? 'selected' : '' }}>🇹🇭 Thaïlandaise</option>
    <option value="Vietnamienne" {{ $selectedValue === 'Vietnamienne' ? 'selected' : '' }}>🇻🇳 Vietnamienne</option>
    <option value="Malaisienne" {{ $selectedValue === 'Malaisienne' ? 'selected' : '' }}>🇲🇾 Malaisienne</option>
    <option value="Singapourienne" {{ $selectedValue === 'Singapourienne' ? 'selected' : '' }}>🇸🇬 Singapourienne</option>
    <option value="Indonésienne" {{ $selectedValue === 'Indonésienne' ? 'selected' : '' }}>🇮🇩 Indonésienne</option>
    <option value="Philippine" {{ $selectedValue === 'Philippine' ? 'selected' : '' }}>🇵🇭 Philippine</option>
</optgroup>
<optgroup label="────────── Océanie ──────────">
    <option value="Australienne" {{ $selectedValue === 'Australienne' ? 'selected' : '' }}>🇦🇺 Australienne</option>
    <option value="Néo-Zélandaise" {{ $selectedValue === 'Néo-Zélandaise' ? 'selected' : '' }}>🇳🇿 Néo-Zélandaise</option>
</optgroup>
<optgroup label="─────────── Autre ───────────">
    <option value="Autre" {{ $selectedValue === 'Autre' ? 'selected' : '' }}>🌍 Autre</option>
</optgroup>

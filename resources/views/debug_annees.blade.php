<!DOCTYPE html>
<html>
<head>
    <title>Debug Années</title>
</head>
<body>
    <h1>Debug Test</h1>
    
    <h2>Variable anneesUniversitaires:</h2>
    <pre>{{ var_dump($anneesUniversitaires) }}</pre>
    
    <h2>Boucle sécurisée:</h2>
    @if(isset($anneesUniversitaires) && $anneesUniversitaires)
        <ul>
        @foreach($anneesUniversitaires as $index => $annee)
            <li>
                Index: {{ $index }}<br>
                Type: {{ gettype($annee) }}<br>
                @if(is_null($annee))
                    <strong>NULL DETECTÉ!</strong>
                @elseif(is_object($annee))
                    ID: {{ $annee->id ?? 'N/A' }}<br>
                    Name: {{ $annee->name ?? 'N/A' }}<br>
                    is_current type: {{ gettype($annee->is_current ?? 'undefined') }}<br>
                    is_current value: {{ $annee->is_current ?? 'NULL' ? 'TRUE' : 'FALSE' }}
                @else
                    <strong>PAS UN OBJET: {{ $annee }}</strong>
                @endif
            </li>
        @endforeach
        </ul>
    @else
        <p>Variable anneesUniversitaires n'existe pas ou est vide</p>
    @endif
</body>
</html>
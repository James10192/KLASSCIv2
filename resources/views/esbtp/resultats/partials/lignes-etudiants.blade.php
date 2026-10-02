{{-- Pages suivantes du défilement infini : seulement les lignes TR, ajoutées au tbody. --}}
@foreach($etudiants as $etudiant)
    @include('esbtp.resultats.partials._ligne-etudiant')
@endforeach

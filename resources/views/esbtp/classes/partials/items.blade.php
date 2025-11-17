{{-- Boucle sur toutes les classes et inclure le partial classe-card pour chacune --}}
@foreach($classes as $classe)
    @include('esbtp.classes.partials.classe-card', ['classe' => $classe])
@endforeach

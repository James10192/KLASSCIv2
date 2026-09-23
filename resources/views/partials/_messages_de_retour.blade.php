{{-- Messages de retour (success, error, warning, info) de la mise en page.
     $contenuDeLaPage est la section « content » déjà rendue : un message que
     la page affiche elle-même n'est pas répété ici. --}}
@foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $cleFlash => $classeFlash)
    @if(session($cleFlash) && ! str_contains($contenuDeLaPage ?? '', e(session($cleFlash))))
        <div class="alert alert-{{ $classeFlash }} alert-dismissible fade show" role="alert">
            {{ session($cleFlash) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- Retours des corbeilles de demandes en ligne : succes, erreur, information. --}}
@foreach(['success' => 'alert-success', 'error' => 'alert-danger', 'info' => 'alert-info'] as $_cle => $_classe)
    @if(session($_cle))
        <div class="alert {{ $_classe }}" role="{{ $_cle === 'error' ? 'alert' : 'status' }}">{{ session($_cle) }}</div>
    @endif
@endforeach

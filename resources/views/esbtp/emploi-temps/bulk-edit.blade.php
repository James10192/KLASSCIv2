@extends('layouts.app')

@section('title', 'Modification rapide des emplois du temps - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .bulk-edit-header {
        background: linear-gradient(135deg, #0f3f87 0%, #0453cb 100%);
        border-radius: 18px;
        padding: 24px;
        color: #ffffff;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }

    .bulk-edit-header::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 55%);
        pointer-events: none;
    }

    .bulk-edit-header h1 {
        font-size: 1.6rem;
        margin-bottom: 0.4rem;
    }

    .bulk-edit-header p {
        opacity: 0.85;
        margin-bottom: 0;
    }

    .bulk-edit-grid {
        display: grid;
        gap: 24px;
    }

    .bulk-modal-frame {
        width: 100%;
        border: none;
        min-height: 100%;
    }

    .bulk-modal-body {
        padding: 0;
    }

    .bulk-modal-loading {
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #475569;
    }

    .modal-xxl {
        max-width: 90vw;
    }

    .bulk-modal-content {
        height: 90vh;
        display: flex;
        flex-direction: column;
    }

    .bulk-modal-body {
        flex: 1;
    }

    @media (max-width: 992px) {
        .modal-xxl {
            max-width: 96vw;
        }
        .bulk-modal-content {
            height: 92vh;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="bulk-edit-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1><i class="fas fa-layer-group me-2"></i>Modification rapide des séances</h1>
                    <p>Ajoutez des séances directement dans plusieurs emplois du temps actifs.</p>
                </div>
                <div>
                    <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger border-start border-danger border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3"><i class="fas fa-exclamation-circle fs-4"></i></div>
                    <div>{{ session('error') }}</div>
                </div>
            </div>
        @endif

        <div class="bulk-edit-grid">
            @forelse($emploiTempsData as $data)
                <div id="emploi-temps-block-{{ $data['emploiTemps']->id }}">
                    @include('esbtp.emploi-temps.partials.bulk-block', $data)
                </div>
            @empty
                <div class="alert alert-warning">
                    Aucun emploi du temps sélectionné.
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="modal fade" id="seanceModal" tabindex="-1" aria-labelledby="seanceModalLabel" aria-hidden="true">
<div class="modal-dialog modal-xxl modal-dialog-centered">
        <div class="modal-content bulk-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="seanceModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Ajouter une séance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bulk-modal-body">
                <div class="bulk-modal-loading" id="seanceModalLoading">Chargement du formulaire...</div>
                <iframe id="seanceModalFrame" class="bulk-modal-frame" title="Ajout séance" style="display: none;"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('seanceModal');
    const iframe = document.getElementById('seanceModalFrame');
    const loading = document.getElementById('seanceModalLoading');
    if (!modalElement || !iframe || !loading) {
        return;
    }

    const modalInstance = new bootstrap.Modal(modalElement);

    const openModalWithUrl = (url) => {
        loading.style.display = 'flex';
        iframe.style.display = 'none';
        iframe.src = url;
        modalInstance.show();
    };

    document.addEventListener('click', function (event) {
        const addLink = event.target.closest('.timeline-slot-add');
        if (!addLink) {
            return;
        }

        event.preventDefault();

        const block = addLink.closest('.bulk-emploi-temps-block');
        const emploiTempsId = block ? block.dataset.emploiTempsId : null;
        if (!emploiTempsId) {
            return;
        }

        const targetUrl = new URL(addLink.href, window.location.origin);
        targetUrl.searchParams.set('embed', '1');
        targetUrl.searchParams.set('emploi_temps_id', emploiTempsId);
        openModalWithUrl(targetUrl.toString());
    });

    iframe.addEventListener('load', function () {
        loading.style.display = 'none';
        iframe.style.display = 'block';
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        iframe.src = '';
    });

    window.addEventListener('message', async function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }
        const data = event.data || {};
        if (data.type !== 'seance-created' || !data.emploiTempsId) {
            return;
        }

        try {
            const response = await fetch(`{{ url('/esbtp/emploi-temps') }}/${data.emploiTempsId}/sections`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) {
                throw new Error('Erreur lors du rafraichissement');
            }
            const payload = await response.json();
            const container = document.getElementById(`emploi-temps-block-${data.emploiTempsId}`);
            if (container && payload.html) {
                container.innerHTML = payload.html;
            }
        } catch (error) {
            console.error(error);
        } finally {
            modalInstance.hide();
        }
    });
});
</script>
@endpush

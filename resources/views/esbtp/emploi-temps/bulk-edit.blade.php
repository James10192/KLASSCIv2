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

    .bulk-modal-dialog {
        width: 90vw !important;
        max-width: 90vw !important;
        min-width: 90vw;
        height: 90vh;
    }

    #seanceModal .bulk-modal-dialog {
        width: 90vw !important;
        max-width: 90vw !important;
    }

    .bulk-modal-content {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .bulk-modal-body {
        flex: 1;
    }

    @media (max-width: 992px) {
        .bulk-modal-dialog {
            width: 96vw !important;
            max-width: 96vw !important;
            min-width: 96vw;
            height: 92vh;
        }

        #seanceModal .bulk-modal-dialog {
            width: 96vw !important;
            max-width: 96vw !important;
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

        <div class="accordion" id="bulkEditAccordion">
            @forelse($emploiTempsData as $data)
                @php
                    $emploiTempsItem = $data['emploiTemps'];
                    $collapseId = 'collapse-emploi-temps-' . $emploiTempsItem->id;
                    $headingId = 'heading-emploi-temps-' . $emploiTempsItem->id;
                @endphp
                <div class="accordion-item mb-3" id="emploi-temps-block-{{ $emploiTempsItem->id }}">
                    <h2 class="accordion-header" id="{{ $headingId }}">
                        <div class="main-card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <div class="main-card-title">
                                    <i class="fas fa-calendar-alt"></i>
                                    {{ $emploiTempsItem->titre ?? 'Emploi du temps' }}
                                </div>
                                <div class="text-muted small">
                                    {{ $emploiTempsItem->classe->name ?? 'Classe non définie' }}
                                    @if($emploiTempsItem->date_debut && $emploiTempsItem->date_fin)
                                        · {{ \Carbon\Carbon::parse($emploiTempsItem->date_debut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($emploiTempsItem->date_fin)->format('d/m/Y') }}
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if($emploiTempsItem->is_current)
                                    <span class="badge bg-success">Actuel</span>
                                @elseif($emploiTempsItem->is_active)
                                    <span class="badge bg-info">Actif</span>
                                @else
                                    <span class="badge bg-secondary">Inactif</span>
                                @endif
                                <a href="{{ route('esbtp.emploi-temps.show', ['emploi_temp' => $emploiTempsItem->id]) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>Ouvrir
                                </a>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="true" aria-controls="{{ $collapseId }}">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </h2>
                    <div id="{{ $collapseId }}" class="accordion-collapse collapse show" aria-labelledby="{{ $headingId }}">
                        <div class="accordion-body p-0">
                            @include('esbtp.emploi-temps.partials.bulk-block', array_merge($data, ['showHeader' => false]))
                        </div>
                    </div>
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
<div class="modal-dialog modal-dialog-centered bulk-modal-dialog">
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

    const refreshBlock = async (emploiTempsId) => {
        const response = await fetch(`{{ url('/esbtp/emploi-temps') }}/${emploiTempsId}/sections`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) {
            throw new Error('Erreur lors du rafraichissement');
        }
        const payload = await response.json();
        const container = document.getElementById(`emploi-temps-block-${emploiTempsId}`);
        if (container && payload.html) {
            container.innerHTML = payload.html;
        }
    };

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
            await refreshBlock(data.emploiTempsId);
        } catch (error) {
            console.error(error);
        } finally {
            modalInstance.hide();
        }
    });

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('form');
        if (!form) {
            return;
        }

        if (!form.action.includes('/seances-cours/') || !form.querySelector('input[name="_method"][value="DELETE"]')) {
            return;
        }

        const block = form.closest('.bulk-emploi-temps-block');
        const emploiTempsId = block ? block.dataset.emploiTempsId : null;
        if (!emploiTempsId) {
            return;
        }

        event.preventDefault();

        if (!confirm('Êtes-vous sûr de vouloir supprimer cette séance ?')) {
            return;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Suppression impossible.');
            }

            await refreshBlock(emploiTempsId);
        } catch (error) {
            alert(error.message || 'Erreur lors de la suppression.');
        }
    });
});
</script>
@endpush

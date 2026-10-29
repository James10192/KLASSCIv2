@php
    $tailles = [
        'small' => 'col-md-6',
        'medium' => 'col-md-8',
        'large' => 'col-md-12',
        'half' => 'col-md-6'
    ];
    $tailleClass = $tailles[$taille ?? 'medium'] ?? 'col-md-8';
@endphp

<div class="{{ $tailleClass }} mb-4">
    <div class="card border-0 shadow-lg premium-glass h-100">
        <!-- Header du graphique -->
        <div class="card-header bg-transparent border-0 p-4 pb-0">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title fw-bold mb-1">{{ $titre ?? 'Graphique' }}</h5>
                    @if(isset($sousTitre))
                        <p class="text-muted small mb-0">{{ $sousTitre }}</p>
                    @endif
                </div>

                <!-- Actions du graphique -->
                <div class="d-flex gap-2">
                    @if(isset($actualiser) && $actualiser)
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                onclick="actualiserGraphique('{{ $id ?? 'chart' }}')"
                                title="Actualiser">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    @endif

                    @if(isset($pleinEcran) && $pleinEcran)
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                onclick="togglePleinEcran('{{ $id ?? 'chart' }}')"
                                title="Plein écran">
                            <i class="fas fa-expand"></i>
                        </button>
                    @endif

                    @if(isset($exporter) && $exporter)
            <div class="dropdown">
                            <button class="btn btn-sm btn-outline-info dropdown-toggle"
                                    type="button"
                                    data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i>
                </button>
                <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exporterGraphique('{{ $id ?? 'chart' }}', 'png')">
                                    <i class="fas fa-image me-2"></i>PNG
                    </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exporterGraphique('{{ $id ?? 'chart' }}', 'pdf')">
                                    <i class="fas fa-file-pdf me-2"></i>PDF
                    </a></li>
                </ul>
            </div>
            @endif
                </div>
            </div>
        </div>

        <!-- Corps du graphique -->
        <div class="card-body p-4">
            @if(isset($filtres) && is_array($filtres))
                <!-- Filtres du graphique -->
                <div class="row mb-3">
                    @foreach($filtres as $filtre)
                        <div class="col-auto">
                            <select class="form-select form-select-sm"
                                    onchange="filtrerGraphique('{{ $id ?? 'chart' }}', this.value)"
                                    data-filtre="{{ $filtre['nom'] ?? '' }}">
                                @foreach($filtre['options'] ?? [] as $key => $option)
                                    <option value="{{ $key }}"
                                            {{ ($filtre['defaut'] ?? '') == $key ? 'selected' : '' }}>
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Loading indicator -->
            <div id="loading-{{ $id ?? 'chart' }}" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="text-muted mt-2">Chargement du graphique...</p>
            </div>

            <!-- Container du canvas -->
            <div class="chart-wrapper position-relative">
                <canvas id="{{ $id ?? 'chart' }}"
                        data-type="{{ $type ?? 'line' }}"
                        data-url="{{ $dataUrl ?? '' }}"
                        style="max-height: {{ $hauteur ?? '400px' }};">
                </canvas>
            </div>

            <!-- Légende personnalisée si définie -->
            @if(isset($legende) && is_array($legende))
                <div class="row mt-3">
                    @foreach($legende as $item)
                        <div class="col-auto d-flex align-items-center me-3 mb-2">
                            <div class="legend-color me-2"
                                 style="width: 12px; height: 12px; background-color: {{ $item['couleur'] ?? '#007bff' }}; border-radius: 2px;">
                            </div>
                            <span class="small text-muted">{{ $item['label'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Footer avec métadonnées -->
        @if(isset($derniereMaj) || isset($source))
            <div class="card-footer bg-transparent border-0 p-4 pt-0">
                <div class="d-flex justify-content-between align-items-center">
                    @if(isset($source))
                        <span class="small text-muted">Source: {{ $source }}</span>
                    @endif
                    @if(isset($derniereMaj))
                        <span class="small text-muted">Dernière mise à jour: {{ $derniereMaj }}</span>
                    @endif
                </div>
        </div>
        @endif
    </div>
</div>

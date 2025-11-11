<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KLASSCI - Test Interaction Formulaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/modal-z-index-fix.css') }}" rel="stylesheet">
    <link href="{{ asset('css/form-interaction-fix.css') }}" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8fafc;
        }
        .test-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .test-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-success { background-color: #4caf50; }
        .status-error { background-color: #f44336; }
        .status-warning { background-color: #ff9800; }
        .debug-info {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.9em;
        }
        .interaction-test {
            border: 2px dashed #dee2e6;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
        }
        .interaction-test:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-card">
            <h1>🔧 KLASSCI - Diagnostic Interaction Formulaire</h1>
            <p class="text-muted">Test des corrections CSS pour résoudre les problèmes d'interaction avec les formulaires</p>
            
            <div class="alert alert-info">
                <strong>📋 Problème signalé :</strong><br>
                • Curseur se transforme anormalement sur les inputs<br>
                • Impossible de sélectionner les champs de formulaire<br>
                • Problème potentiel avec les règles CSS des modals
            </div>

            <div id="diagnostic-results" class="alert alert-warning">
                <span class="status-indicator status-warning"></span>
                Diagnostic en cours...
            </div>
        </div>

        <div class="test-card">
            <h3>🎯 Test d'Interaction - Formulaire Similaire aux Dépenses</h3>
            
            <div class="interaction-test">
                <h5>Zone de Test 1 : Inputs Basiques</h5>
                <form id="test-form-basic">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label for="test-libelle" class="form-label fw-medium">Libellé <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="test-libelle" name="libelle" placeholder="Cliquez ici pour tester" required>
                            <small class="text-muted">Testez en cliquant et en tapant</small>
                        </div>
                        <div class="col-md-6">
                            <label for="test-categorie" class="form-label fw-medium">Catégorie</label>
                            <select class="form-select" id="test-categorie" name="categorie_id" required>
                                <option value="">-- Sélectionnez une catégorie --</option>
                                <option value="1">Fournitures de bureau</option>
                                <option value="2">Transport</option>
                                <option value="3">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label for="test-montant" class="form-label fw-medium">Montant (FCFA)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="test-montant" name="montant" placeholder="0" min="0" step="0.01">
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="test-date" class="form-label fw-medium">Date de dépense</label>
                            <input type="date" class="form-control" id="test-date" name="date_depense" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </form>
            </div>

            <div class="interaction-test">
                <h5>Zone de Test 2 : Fournisseur avec Modal (Problème Principal)</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <label for="test-fournisseur" class="form-label fw-medium">Fournisseur</label>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="test-fournisseur" name="fournisseur_id" style="flex: 1;">
                                <option value="">-- Sélectionnez un fournisseur --</option>
                                <option value="1">SOGEFIBRE</option>
                                <option value="2">TOTAL ÉNERGIES</option>
                                <option value="3">ORANGE CI</option>
                                <option value="nouveau">➕ Nouveau fournisseur</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary" id="btn-test-modal" data-bs-toggle="modal" data-bs-target="#testModal">
                                <i class="fas fa-plus"></i> ➕
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="test-reference" class="form-label fw-medium">Référence</label>
                        <input type="text" class="form-control" id="test-reference" name="reference" placeholder="REF-001">
                    </div>
                </div>
            </div>

            <div class="interaction-test">
                <h5>Zone de Test 3 : Textarea et File</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <label for="test-description" class="form-label fw-medium">Description</label>
                        <textarea class="form-control" id="test-description" name="description" rows="3" placeholder="Décrivez la dépense..."></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="test-file" class="form-label fw-medium">Justificatif</label>
                        <input type="file" class="form-control" id="test-file" name="justificatif" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button type="button" class="btn btn-primary" onclick="runFullDiagnostic()">
                        🔍 Diagnostic Complet
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="testSpecificElement()">
                        🎯 Test Élément Spécifique
                    </button>
                </div>
                <div>
                    <a href="{{ url('/esbtp/comptabilite/depenses/create') }}" class="btn btn-warning">
                        🚀 Tester Page Réelle
                    </a>
                </div>
            </div>
        </div>

        <div class="test-card">
            <h3>📊 Résultats du Diagnostic</h3>
            <div id="detailed-results">
                <p class="text-muted">Cliquez sur "Diagnostic Complet" pour analyser tous les éléments.</p>
            </div>
        </div>
    </div>

    <!-- Modal Test -->
    <div class="modal fade" id="testModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🧪 Test Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="modal-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="modal-nom" class="form-label">Nom Fournisseur</label>
                                <input type="text" class="form-control" id="modal-nom" placeholder="Tapez le nom">
                            </div>
                            <div class="col-md-6">
                                <label for="modal-email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="modal-email" placeholder="email@example.com">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="testModalSuccess()">Test OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let diagnosticResults = [];

        document.addEventListener('DOMContentLoaded', function() {
            debugLog('🚀 Diagnostic KLASSCI démarré');
            
            // Test automatique initial
            setTimeout(() => {
                runBasicDiagnostic();
            }, 1000);

            // Ajouter des écouteurs pour tous les éléments
            const allElements = document.querySelectorAll('input, select, textarea, button');
            allElements.forEach(element => {
                element.addEventListener('mouseover', logElementState);
                element.addEventListener('focus', logFocusState);
                element.addEventListener('click', logClickState);
            });
        });

        function logElementState(event) {
            const el = event.target;
            const computedStyle = window.getComputedStyle(el);
            debugLog(`Hover ${el.tagName}#${el.id}: cursor=${computedStyle.cursor}, pointer-events=${computedStyle.pointerEvents}`);
        }

        function logFocusState(event) {
            debugLog(`✅ Focus réussi sur: ${event.target.tagName}#${event.target.id}`);
        }

        function logClickState(event) {
            debugLog(`🖱️ Click réussi sur: ${event.target.tagName}#${event.target.id}`);
        }

        function runBasicDiagnostic() {
            diagnosticResults = [];
            
            // Test 1: Vérifier que les CSS sont chargés
            const modalFixLoaded = Array.from(document.styleSheets).some(sheet => 
                sheet.href && sheet.href.includes('modal-z-index-fix.css')
            );
            const formFixLoaded = Array.from(document.styleSheets).some(sheet => 
                sheet.href && sheet.href.includes('form-interaction-fix.css')
            );
            
            diagnosticResults.push(`CSS Modal Fix: ${modalFixLoaded ? '✅ Chargé' : '❌ Non chargé'}`);
            diagnosticResults.push(`CSS Form Fix: ${formFixLoaded ? '✅ Chargé' : '❌ Non chargé'}`);
            
            // Test 2: Vérifier les pointer-events sur tous les inputs
            const inputs = document.querySelectorAll('input, select, textarea');
            let pointerEventsOK = 0;
            inputs.forEach(input => {
                const style = window.getComputedStyle(input);
                if (style.pointerEvents !== 'none') {
                    pointerEventsOK++;
                }
            });
            
            diagnosticResults.push(`Pointer Events: ${pointerEventsOK}/${inputs.length} éléments OK`);
            
            // Test 3: Vérifier les curseurs
            const cursorTests = [];
            inputs.forEach(input => {
                const style = window.getComputedStyle(input);
                const expectedCursor = input.type === 'text' || input.tagName === 'TEXTAREA' ? 'text' : 'pointer';
                cursorTests.push(style.cursor === expectedCursor ? '✅' : '❌');
            });
            
            diagnosticResults.push(`Curseurs corrects: ${cursorTests.filter(t => t === '✅').length}/${cursorTests.length}`);
            
            updateDiagnosticDisplay();
        }

        function runFullDiagnostic() {
            runBasicDiagnostic();
            
            // Test interactif de chaque élément
            const testResults = [];
            
            // Test input text
            const testInput = document.getElementById('test-libelle');
            try {
                testInput.focus();
                testInput.value = 'Test automatique ' + Date.now();
                testInput.blur();
                testResults.push('✅ Input texte: Interaction OK');
            } catch (e) {
                testResults.push('❌ Input texte: Erreur - ' + e.message);
            }
            
            // Test select
            const testSelect = document.getElementById('test-categorie');
            try {
                testSelect.focus();
                testSelect.value = '1';
                testSelect.dispatchEvent(new Event('change'));
                testResults.push('✅ Select: Interaction OK');
            } catch (e) {
                testResults.push('❌ Select: Erreur - ' + e.message);
            }
            
            // Test number input
            const testNumber = document.getElementById('test-montant');
            try {
                testNumber.focus();
                testNumber.value = '15000';
                testResults.push('✅ Input number: Interaction OK');
            } catch (e) {
                testResults.push('❌ Input number: Erreur - ' + e.message);
            }
            
            // Test textarea
            const testTextarea = document.getElementById('test-description');
            try {
                testTextarea.focus();
                testTextarea.value = 'Test description automatique';
                testResults.push('✅ Textarea: Interaction OK');
            } catch (e) {
                testResults.push('❌ Textarea: Erreur - ' + e.message);
            }
            
            diagnosticResults.push('--- Tests Interactifs ---');
            diagnosticResults = diagnosticResults.concat(testResults);
            
            updateDiagnosticDisplay();
        }

        function testSpecificElement() {
            const elementId = prompt('ID de l\'élément à tester:', 'test-libelle');
            if (!elementId) return;
            
            const element = document.getElementById(elementId);
            if (!element) {
                debugAlert('Élément non trouvé: ' + elementId);
                return;
            }
            
            const style = window.getComputedStyle(element);
            const info = {
                tagName: element.tagName,
                type: element.type || 'N/A',
                cursor: style.cursor,
                pointerEvents: style.pointerEvents,
                position: style.position,
                zIndex: style.zIndex,
                display: style.display
            };
            
            debugAlert('Debug ' + elementId + ':\n' + JSON.stringify(info, null, 2));
            
            // Test d'interaction
            try {
                element.focus();
                if (element.type === 'text' || element.tagName === 'TEXTAREA') {
                    element.value = 'Test spécifique ' + Date.now();
                }
                debugAlert('✅ Interaction réussie avec ' + elementId);
            } catch (e) {
                debugAlert('❌ Erreur d\'interaction: ' + e.message);
            }
        }

        function testModalSuccess() {
            const modalInput = document.getElementById('modal-nom');
            try {
                modalInput.value = 'Test Modal OK';
                debugAlert('✅ Modal interaction réussie');
                bootstrap.Modal.getInstance(document.getElementById('testModal')).hide();
            } catch (e) {
                debugAlert('❌ Modal interaction échouée: ' + e.message);
            }
        }

        function updateDiagnosticDisplay() {
            const resultsDiv = document.getElementById('diagnostic-results');
            const detailedDiv = document.getElementById('detailed-results');
            
            const hasErrors = diagnosticResults.some(r => r.includes('❌'));
            const statusClass = hasErrors ? 'alert-danger' : 'alert-success';
            const statusIcon = hasErrors ? 'status-error' : 'status-success';
            const statusText = hasErrors ? 'Problèmes détectés' : 'Tout fonctionne correctement';
            
            resultsDiv.className = `alert ${statusClass}`;
            resultsDiv.innerHTML = `<span class="status-indicator ${statusIcon}"></span>${statusText}`;
            
            detailedDiv.innerHTML = '<div class="debug-info">' + diagnosticResults.join('<br>') + '</div>';
        }

        // Test en temps réel des événements
        document.addEventListener('click', function(e) {
            if (e.target.matches('input, select, textarea, button')) {
                debugLog(`Event Click capturé sur: ${e.target.tagName}#${e.target.id}`);
            }
        });
    </script>
</body>
</html>

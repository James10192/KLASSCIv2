{{--
    Modal partagée : instructions pour changer l'année académique courante.
    Utilisée par classes.index et classes.show.
    API Bootstrap 5 (data-bs-dismiss).
--}}
<div class="modal fade" id="yearChangeModal" tabindex="-1" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ci-modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>Changer l'année académique
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol class="ci-steps">
                    <li>Aller dans <strong>Menu → Années Universitaires</strong></li>
                    <li>Trouver l'année souhaitée (ex : 2023-2024)</li>
                    <li>Cliquer sur <strong>« Activer »</strong> pour la définir comme année courante</li>
                    <li>Revenir ici, les données se mettront à jour automatiquement</li>
                </ol>
                <div class="ci-info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Note :</strong> Seule une année peut être « courante » à la fois.
                        Changer l'année courante affecte l'affichage dans toute l'application.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt me-1"></i>Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

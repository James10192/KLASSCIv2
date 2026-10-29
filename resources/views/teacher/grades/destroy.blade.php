<!-- Delete Evaluation Modal -->
<div class="modal fade" id="deleteEvaluationModal" tabindex="-1" aria-labelledby="deleteEvaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteEvaluationModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i> Supprimer l'évaluation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Êtes-vous sûr de vouloir supprimer cette évaluation ? Cette action est irréversible.</p>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading fw-bold"><i class="fas fa-info-circle me-2"></i> Information importante</h6>
                    <p class="mb-0">La suppression de cette évaluation entraînera également la suppression de toutes les notes associées à celle-ci.</p>
                </div>
                
                <h6 class="fw-bold mt-4 mb-2">Détails de l'évaluation :</h6>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Titre :</span>
                        <span class="fw-bold" id="delete-evaluation-title"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Classe :</span>
                        <span class="fw-bold" id="delete-evaluation-class"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Matière :</span>
                        <span class="fw-bold" id="delete-evaluation-subject"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Date :</span>
                        <span class="fw-bold" id="delete-evaluation-date"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <form id="deleteEvaluationForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Confirmer la suppression
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to set up delete modal with evaluation details
        window.setupDeleteModal = function(evaluationId, title, className, subject, date) {
            const deleteForm = document.getElementById('deleteEvaluationForm');
            deleteForm.action = `/teacher/grades/${evaluationId}`;
            
            // Set evaluation details in modal
            document.getElementById('delete-evaluation-title').textContent = title;
            document.getElementById('delete-evaluation-class').textContent = className;
            document.getElementById('delete-evaluation-subject').textContent = subject;
            document.getElementById('delete-evaluation-date').textContent = date;
        };
    });
</script> 
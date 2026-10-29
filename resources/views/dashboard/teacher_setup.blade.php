@extends('layouts.app')

@section('title', 'Configuration du profil enseignant')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Hero Section -->
            <div class="card border-0 shadow-lg overflow-hidden animate__animated animate__fadeIn" style="border-radius: 15px;">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-7 p-5">
                            <h1 class="display-5 fw-bold mb-3 animate__animated animate__fadeInUp text-esbtp-green">Bienvenue, {{ $user->name }} !</h1>
                            <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1s">Votre compte enseignant a été créé avec succès. Quelques étapes supplémentaires sont nécessaires pour finaliser votre profil.</p>
                            <div class="d-flex gap-3 animate__animated animate__fadeInUp animate__delay-2s">
                                <a href="{{ route('teacher.profile') }}" class="btn btn-primary px-4 py-2">
                                    <i class="fas fa-user-edit me-2"></i> Mettre à jour mon profil
                                </a>
                                <a href="#" class="btn btn-outline-secondary px-4 py-2" data-bs-toggle="modal" data-bs-target="#contactAdminModal">
                                    <i class="fas fa-envelope me-2"></i> Contacter l'administrateur
                                </a>
                            </div>
                        </div>
                        <div class="col-md-5 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green));">
                            <div class="h-100 d-flex align-items-center justify-content-center p-4">
                                <img src="https://img.freepik.com/free-vector/teacher-concept-illustration_114360-2166.jpg" alt="Teacher Setup" class="img-fluid animate__animated animate__fadeInRight" style="max-height: 250px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Section -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm animate__animated animate__fadeInUp animate__delay-3s" style="border-radius: 15px;">
                        <div class="card-header bg-white py-3 d-flex align-items-center">
                            <i class="fas fa-tasks text-primary me-2"></i>
                            <h5 class="card-title mb-0 fw-bold">État de votre configuration</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="progress-tracker">
                                <div class="progress mb-4" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-3">
                                        <div class="setup-step completed">
                                            <div class="step-icon">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                            <h6 class="mt-3 mb-1">Compte créé</h6>
                                            <p class="text-muted small mb-0">Votre compte a été créé avec succès</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="setup-step pending">
                                            <div class="step-icon">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                            <h6 class="mt-3 mb-1">Profil personnel</h6>
                                            <p class="text-muted small mb-0">Complétez vos informations personnelles</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="setup-step pending">
                                            <div class="step-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <h6 class="mt-3 mb-1">Matières assignées</h6>
                                            <p class="text-muted small mb-0">En attente d'assignation par l'admin</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="setup-step pending">
                                            <div class="step-icon">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <h6 class="mt-3 mb-1">Profil activé</h6>
                                            <p class="text-muted small mb-0">Validation finale par l'administrateur</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Section -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 animate__animated animate__fadeInUp animate__delay-4s" style="border-radius: 15px;">
                        <div class="card-header bg-white py-3 d-flex align-items-center">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            <h5 class="card-title mb-0 fw-bold">Que pouvez-vous faire maintenant ?</h5>
                        </div>
                        <div class="card-body p-4">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0 py-3 d-flex align-items-center border-0 border-bottom">
                                    <div class="icon-box bg-primary-light rounded-circle p-2 me-3">
                                        <i class="fas fa-user-edit text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Compléter votre profil</h6>
                                        <p class="text-muted mb-0 small">Ajoutez vos informations personnelles et professionnelles</p>
                                    </div>
                                </li>
                                <li class="list-group-item px-0 py-3 d-flex align-items-center border-0 border-bottom">
                                    <div class="icon-box bg-success-light rounded-circle p-2 me-3">
                                        <i class="fas fa-graduation-cap text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Explorer la plateforme</h6>
                                        <p class="text-muted mb-0 small">Familiarisez-vous avec les fonctionnalités disponibles</p>
                                    </div>
                                </li>
                                <li class="list-group-item px-0 py-3 d-flex align-items-center border-0">
                                    <div class="icon-box bg-warning-light rounded-circle p-2 me-3">
                                        <i class="fas fa-envelope text-warning"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Contacter l'administrateur</h6>
                                        <p class="text-muted mb-0 small">Pour toute question ou assistance supplémentaire</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 animate__animated animate__fadeInUp animate__delay-4s" style="border-radius: 15px;">
                        <div class="card-header bg-white py-3 d-flex align-items-center">
                            <i class="fas fa-question-circle text-warning me-2"></i>
                            <h5 class="card-title mb-0 fw-bold">Questions fréquentes</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="accordion" id="faqAccordion">
                                <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                            Quand pourrai-je accéder à toutes les fonctionnalités ?
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Vous aurez accès à toutes les fonctionnalités une fois que l'administrateur aura validé votre profil et assigné vos matières d'enseignement.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            Comment puis-je mettre à jour mes informations ?
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Vous pouvez mettre à jour vos informations personnelles en cliquant sur le bouton "Mettre à jour mon profil" ou en accédant à la section profil depuis le menu principal.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0 shadow-sm rounded">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            Comment contacter l'administrateur ?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Vous pouvez contacter l'administrateur en cliquant sur le bouton "Contacter l'administrateur" ou en envoyant un message via la section messagerie de la plateforme.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Admin Modal -->
<div class="modal fade" id="contactAdminModal" tabindex="-1" aria-labelledby="contactAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="contactAdminModalLabel">Contacter l'administrateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="{{ route('messages.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="recipient_type" value="admin">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Sujet</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="Configuration du profil enseignant" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Décrivez votre demande ou question..."></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Envoyer le message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .setup-step {
        text-align: center;
        padding: 15px;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .setup-step:hover {
        transform: translateY(-5px);
    }
    
    .step-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 24px;
        transition: all 0.3s ease;
    }
    
    .completed .step-icon {
        background-color: var(--esbtp-green-light);
        color: var(--esbtp-green);
    }
    
    .pending .step-icon {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }
    
    .icon-box {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .bg-primary-light {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-success-light {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .accordion-button:not(.collapsed) {
        background-color: var(--esbtp-light-green);
        color: var(--esbtp-green);
        box-shadow: none;
    }
    
    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(1, 99, 47, 0.1);
    }
    
    .text-esbtp-green {
        color: var(--esbtp-green);
    }
</style>
@endsection 
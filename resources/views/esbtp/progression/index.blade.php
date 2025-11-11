@extends('layouts.app')

@section('title', 'Gestion de la Progression des Étudiants')

@push('styles')
<style>
    .hero-section {
        background: linear-gradient(135deg, var(--esbtp-green), var(--esbtp-green-dark));
        border-radius: 15px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .stats-card {
        transition: all 0.3s ease;
        border-radius: 15px;
        height: 100%;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .bg-success-light {
        background-color: rgba(25, 135, 84, 0.1);
    }

    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .bg-info-light {
        background-color: rgba(13, 202, 240, 0.1);
    }

    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .filter-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .data-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .action-btn {
        border-radius: 50px;
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .table-container {
        overflow-x: auto;
    }

    .table th, .table td {
        white-space: nowrap;
    }

    .custom-select {
        border-radius: 10px;
        padding: 10px 15px;
        border: 1px solid #ced4da;
        background-color: #fff;
        transition: all 0.3s ease;
    }

    .custom-select:focus {
        border-color: var(--esbtp-green);
        box-shadow: 0 0 0 0.25rem rgba(1, 99, 47, 0.25);
    }

    .progress {
        height: 8px;
        border-radius: 4px;
        width: 80px;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: 50px 0;
    }

    .shimmer {
        animation-duration: 2.2s;
        animation-fill-mode: forwards;
        animation-iteration-count: infinite;
        animation-name: shimmer;
        animation-timing-function: linear;
        background: #f6f7f8;
        background: linear-gradient(to right, #f6f7f8 8%, #edeef1 18%, #f6f7f8 33%);
        background-size: 1200px 100%;
    }

    .card-body {
        padding: 1.25rem;
    }

    .small-stat {
        font-size: 0.85rem;
    }

    @keyframes shimmer {
        0% {
            background-position: -1200px 0;
        }
        100% {
            background-position: 1200px 0;
        }
    }
</style>
@endpush

@section('content')
<div id="app">
<div class="container-fluid px-3">
    <!-- Hero Section -->
    <div class="hero-section p-4 mb-4 animate__animated animate__fadeIn">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-6 fw-bold mb-2">Progression des Étudiants</h1>
                <p class="lead mb-0">Gérez le passage en classe supérieure ou le redoublement des étudiants</p>
            </div>
            <div class="col-md-4 text-end">
                <i class="fas fa-graduation-cap fa-3x opacity-25"></i>
            </div>
        </div>
        <!-- Circular patterns for background -->
        <div class="position-absolute bottom-0 end-0 mb-n3 me-n3 d-none d-lg-block">
            <svg width="150" height="150" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="100" cy="100" r="100" fill="rgba(255,255,255,0.1)"/>
            </svg>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card filter-card mb-4 animate__animated animate__fadeInUp">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="fas fa-filter me-2"></i>Filtrer les étudiants</h5>
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label for="classe" class="form-label">Classe</label>
                    <select class="form-select custom-select" id="classe" v-model="selectedClass" @change="loadRecommendations">
                        <option value="">Sélectionner une classe</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}">{{ $classe->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text text-muted mt-1" v-if="!selectedClass">
                        <small><i class="fas fa-info-circle me-1"></i>Veuillez choisir une classe</small>
                    </div>
                </div>
                <div class="col-md-5 mb-3">
                    <label for="annee" class="form-label">Année Universitaire</label>
                    <select class="form-select custom-select" id="annee" v-model="selectedYear" @change="loadRecommendations">
                        <option value="">Sélectionner une année</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}">{{ $annee->annee_debut }}-{{ $annee->annee_fin }}</option>
                        @endforeach
                    </select>
                    <div class="form-text text-muted mt-1" v-if="!selectedYear">
                        <small><i class="fas fa-info-circle me-1"></i>Veuillez choisir une année</small>
                    </div>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" @click="loadRecommendations" :disabled="!selectedClass || !selectedYear">
                        <i class="fas fa-search me-2"></i>Rechercher
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" v-if="students.length > 0">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card animate__animated animate__fadeInUp">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1 small-stat">Étudiants</h6>
                            <h3 class="mb-0 fw-bold" v-text="students.length"></h3>
                        </div>
                        <div class="stat-icon bg-info-light">
                            <i class="fas fa-user-graduate text-info"></i>
                        </div>
                    </div>
                    <p class="text-muted mb-0 small-stat"><i class="fas fa-info-circle me-2"></i>Nombre total d'étudiants</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1 small-stat">Passage</h6>
                            <h3 class="mb-0 fw-bold" v-text="promotionCount"></h3>
                        </div>
                        <div class="stat-icon bg-success-light">
                            <i class="fas fa-arrow-up text-success"></i>
                        </div>
                    </div>
                    <p class="text-muted mb-0 small-stat"><i class="fas fa-check-circle me-2"></i>Recommandés pour passage</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1 small-stat">Redoublement</h6>
                            <h3 class="mb-0 fw-bold" v-text="repeatCount"></h3>
                        </div>
                        <div class="stat-icon bg-warning-light">
                            <i class="fas fa-redo text-warning"></i>
                        </div>
                    </div>
                    <p class="text-muted mb-0 small-stat"><i class="fas fa-exclamation-triangle me-2"></i>Recommandés pour redoubler</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stats-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1 small-stat">Moyenne</h6>
                            <h3 class="mb-0 fw-bold" v-text="formatGrade(averageClassGrade) + '/20'"></h3>
                        </div>
                        <div class="stat-icon bg-danger-light">
                            <i class="fas fa-chart-line text-danger"></i>
                        </div>
                    </div>
                    <div class="progress mt-2">
                        <div class="progress-bar" role="progressbar" :style="'width: ' + Math.min(averageClassGrade * 5, 100) + '%'" :class="getGradeColorClass(averageClassGrade)"></div>
                    </div>
                    <p class="text-muted mt-2 mb-0 small-stat"><i class="fas fa-calculator me-2"></i>Moyenne générale de la classe</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="card data-card animate__animated animate__fadeInUp" v-cloak>
        <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-graduation-cap me-2"></i>Liste des Étudiants</h5>
            <button class="btn btn-success action-btn" @click="submitProgressions" :disabled="!canSubmit" v-if="students.length > 0">
                <i class="fas fa-check-circle me-2"></i>Valider
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" v-if="students.length > 0">
                    <thead class="table-light">
                        <tr>
                            <th class="p-3 border-bottom">Étudiant</th>
                            <th class="p-3 border-bottom">Classe</th>
                            <th class="p-3 border-bottom">Moyenne</th>
                            <th class="p-3 border-bottom">Présence</th>
                            <th class="p-3 border-bottom">Recommandation</th>
                            <th class="p-3 border-bottom">Action</th>
                            <th class="p-3 border-bottom">Nouvelle Classe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="student in students" :key="student.student_id" class="align-middle">
                            <td class="p-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2" style="background-color: #f8f9fa; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user text-secondary"></i>
                                    </div>
                                    <div>
                                        <span class="fw-medium" v-text="student.student_name"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                <span class="badge bg-light text-dark" v-text="student.current_class"></span>
                            </td>
                            <td class="p-3">
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2" :class="student.average_grade >= 10 ? 'text-success' : 'text-danger'" v-text="formatGrade(student.average_grade) + '/20'"></span>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" :style="'width: ' + Math.min(student.average_grade * 5, 100) + '%'" :class="getGradeColorClass(student.average_grade)"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2" :class="getAttendanceTextClass(student.attendance_rate)" v-text="student.attendance_rate + '%'"></span>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" :style="'width: ' + student.attendance_rate + '%'" :class="getAttendanceColorClass(student.attendance_rate)"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                <span class="status-badge" :class="{'bg-success-light text-success': student.recommendation === 'promote', 'bg-warning-light text-warning': student.recommendation === 'repeat'}">
                                    <i :class="student.recommendation === 'promote' ? 'fas fa-arrow-up me-1' : 'fas fa-redo me-1'"></i>
                                    <span v-text="student.recommendation === 'promote' ? 'Passage' : 'Redoublement'"></span>
                                </span>
                            </td>
                            <td class="p-3">
                                <select class="form-select form-select-sm" v-model="student.selected_action" style="min-width: 115px;">
                                    <option value="promote">Passage</option>
                                    <option value="repeat">Redoublement</option>
                                </select>
                            </td>
                            <td class="p-3">
                                <select class="form-select form-select-sm" v-model="student.selected_next_class" v-if="student.selected_action === 'promote'" style="min-width: 140px;">
                                    <option value="">Sélectionner</option>
                                    <option v-for="nextClass in student.possible_next_classes" :key="nextClass.id" :value="nextClass.id" v-text="nextClass.name"></option>
                                </select>
                                <span v-else class="text-muted fst-italic">Même classe</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Loading State -->
                <div v-if="loading" class="p-4 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3 text-muted">Chargement des données...</p>
                </div>

                <!-- Empty State -->
                <div v-if="!loading && students.length === 0" class="empty-state p-4">
                    <div class="mb-3">
                        <i class="fas fa-search fa-2x text-muted"></i>
                    </div>
                    <h6 class="text-muted">Aucun étudiant trouvé</h6>
                    <p class="text-muted mb-0 small">Veuillez sélectionner une classe et une année universitaire</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<script>
new Vue({
    el: '#app',
    data: {
        selectedClass: '',
        selectedYear: '',
        students: [],
        loading: false
    },
    computed: {
        canSubmit() {
            return this.students.length > 0 && this.students.every(student => {
                if (student.selected_action === 'promote') {
                    return student.selected_next_class;
                }
                return true;
            });
        },
        promotionCount() {
            return this.students.filter(student => student.recommendation === 'promote').length;
        },
        repeatCount() {
            return this.students.filter(student => student.recommendation === 'repeat').length;
        },
        averageClassGrade() {
            if (this.students.length === 0) return 0;

            const sum = this.students.reduce((total, student) => {
                return total + student.average_grade;
            }, 0);

            return sum / this.students.length;
        }
    },
    methods: {
        formatGrade(grade) {
            return parseFloat(grade).toFixed(2);
        },
        getGradeColorClass(grade) {
            if (grade >= 14) return 'bg-success';
            if (grade >= 10) return 'bg-info';
            if (grade >= 8) return 'bg-warning';
            return 'bg-danger';
        },
        getAttendanceColorClass(rate) {
            if (rate >= 90) return 'bg-success';
            if (rate >= 80) return 'bg-info';
            if (rate >= 70) return 'bg-warning';
            return 'bg-danger';
        },
        getAttendanceTextClass(rate) {
            if (rate >= 90) return 'text-success';
            if (rate >= 80) return 'text-info';
            if (rate >= 70) return 'text-warning';
            return 'text-danger';
        },
        async loadRecommendations() {
            if (!this.selectedClass || !this.selectedYear) return;

            this.students = [];
            this.loading = true;

            try {
                const response = await axios.get(`/esbtp/api/progression/recommendations/${this.selectedClass}/${this.selectedYear}`);
                this.students = response.data.map(student => ({
                    ...student,
                    selected_action: student.recommendation,
                    selected_next_class: student.recommendation === 'promote' && student.possible_next_classes.length > 0
                        ? student.possible_next_classes[0].id
                        : ''
                }));
            } catch (error) {
                debugError('Error loading recommendations:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Impossible de charger les recommandations'
                });
            } finally {
                this.loading = false;
            }
        },
        async submitProgressions() {
            try {
                const decisions = this.students.map(student => ({
                    student_id: student.student_id,
                    action: student.selected_action,
                    next_class_id: student.selected_action === 'promote' ? student.selected_next_class : null
                }));

                // Confirmation dialog
                const result = await Swal.fire({
                    title: 'Confirmer les progressions',
                    html: `
                        <p>Vous êtes sur le point de valider les progressions pour <b>${this.students.length} étudiants</b> :</p>
                        <ul>
                            <li><b>${this.students.filter(s => s.selected_action === 'promote').length}</b> étudiants en passage</li>
                            <li><b>${this.students.filter(s => s.selected_action === 'repeat').length}</b> étudiants en redoublement</li>
                        </ul>
                        <p>Cette action est irréversible. Confirmez-vous ?</p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Confirmer',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#01632f',
                    cancelButtonColor: '#6c757d',
                });

                if (!result.isConfirmed) {
                    return;
                }

                await axios.post('/esbtp/api/progression/process', {
                    decisions,
                    annee_universitaire_id: this.selectedYear
                });

                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: 'Les progressions ont été enregistrées avec succès',
                    confirmButtonColor: '#01632f',
                });

                // Reload recommendations
                await this.loadRecommendations();
            } catch (error) {
                debugError('Error submitting progressions:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: error.response?.data?.error || 'Impossible de traiter les progressions',
                    confirmButtonColor: '#01632f',
                });
            }
        }
    }
});
</script>
@endpush

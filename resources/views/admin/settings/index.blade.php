@extends('layouts.app')

@section('page_title', 'Paramètres')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Paramètres de l'application</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">Général</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="false">Email</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab" aria-controls="appearance" aria-selected="false">Apparence</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">Sécurité</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3" id="settingsTabsContent">
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <form>
                                <div class="mb-3">
                                    <label for="siteName" class="form-label">Nom de l'établissement</label>
                                    <input type="text" class="form-control" id="siteName" value="KLASSCI">
                                </div>
                                <div class="mb-3">
                                    <label for="siteDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="siteDescription" rows="3">École Supérieure du Bâtiment et des Travaux Publics de Yakro</textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="contactEmail" class="form-label">Email de contact</label>
                                    <input type="email" class="form-control" id="contactEmail" value="{{ \App\Helpers\SettingsHelper::get('school_email', '') }}" placeholder="contact@votre-ecole.com">
                                </div>
                                <div class="mb-3">
                                    <label for="contactPhone" class="form-label">Téléphone de contact</label>
                                    <input type="text" class="form-control" id="contactPhone" value="+225 XX XX XX XX">
                                </div>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                            <p class="text-center">Paramètres email - Fonctionnalité en cours de développement</p>
                        </div>
                        <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                            <p class="text-center">Paramètres d'apparence - Fonctionnalité en cours de développement</p>
                        </div>
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <p class="text-center">Paramètres de sécurité - Fonctionnalité en cours de développement</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
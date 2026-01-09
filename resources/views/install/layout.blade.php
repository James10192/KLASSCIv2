<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title') - Installation KLASSCI</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" type="image/x-icon">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
        }
        
        .install-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .step-item {
            position: relative;
            z-index: 1;
        }
        
        /* Improved step connection line */
        .steps-container {
            position: relative;
        }
        
        .steps-container:before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e5e7eb;
            z-index: 0;
        }
        
        .steps-container.progress-2:before {
            background: linear-gradient(to right, 
                #10b981 0%, 
                #10b981 25%, 
                #3b82f6 25%, 
                #3b82f6 37.5%, 
                #e5e7eb 37.5%, 
                #e5e7eb 100%);
        }
        
        .steps-container.progress-3:before {
            background: linear-gradient(to right, 
                #10b981 0%, 
                #10b981 50%, 
                #3b82f6 50%, 
                #3b82f6 62.5%, 
                #e5e7eb 62.5%, 
                #e5e7eb 100%);
        }
        
        .steps-container.progress-4:before {
            background: linear-gradient(to right, 
                #10b981 0%, 
                #10b981 75%, 
                #3b82f6 75%, 
                #3b82f6 87.5%, 
                #e5e7eb 87.5%, 
                #e5e7eb 100%);
        }
        
        .steps-container.progress-5:before {
            background: linear-gradient(to right, 
                #10b981 0%, 
                #10b981 100%);
        }
        
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .step-circle.active {
            background-color: #3b82f6;
            color: white;
        }
        
        .step-circle.completed {
            background-color: #10b981;
            color: white;
        }
        
        .console {
            background-color: #1e293b;
            color: #e2e8f0;
            font-family: 'Courier New', monospace;
            padding: 1rem;
            border-radius: 0.5rem;
            height: 300px;
            overflow-y: auto;
        }
        
        .console .success {
            color: #10b981;
        }
        
        .console .error {
            color: #ef4444;
        }
        
        .console .warning {
            color: #f59e0b;
        }
        
        .console .info {
            color: #3b82f6;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
        }
        
        .btn-primary:disabled {
            background-color: #93c5fd;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background-color: #e5e7eb;
            color: #4b5563;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        
        .btn-success {
            background-color: #10b981;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4b5563;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }
        
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #b91c1c;
        }
        
        .alert-warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
        }
        
        .alert-info {
            background-color: #dbeafe;
            border: 1px solid #3b82f6;
            color: #1e40af;
        }
        
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.5s;
        }
        
        .fade-enter, .fade-leave-to {
            opacity: 0;
        }
        
        /* Styles pour les switches */
        .toggle-checkbox {
            right: 0;
            z-index: 10;
            opacity: 0;
        }
        
        .toggle-checkbox:checked + .toggle-label {
            background-color: #3b82f6;
        }
        
        .toggle-checkbox:checked + .toggle-label:before {
            transform: translateX(100%);
            background-color: white;
        }
        
        .toggle-label {
            position: relative;
            width: 40px;
            height: 24px;
            background-color: #e5e7eb;
            border-radius: 12px;
        }
        
        .toggle-label:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            border-radius: 50%;
            background-color: white;
            transition: transform 0.3s ease;
        }
    </style>
    
    @yield('styles')
</head>
<body class="min-h-screen flex flex-col">
    <header class="bg-white shadow-sm py-4">
        <div class="install-container px-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="KLASSCI" class="h-12 mr-4">
                    <h1 class="text-2xl font-bold text-gray-800">KLASSCI</h1>
                </div>
                <div class="text-gray-600">
                    <span class="font-medium">Installation</span>
                </div>
            </div>
        </div>
    </header>
    
    <main class="flex-grow py-8">
        <div class="install-container px-4">
            <!-- Installation Steps -->
            <div class="mb-8">
                @php
                    $currentStep = 1;
                    if (request()->routeIs('install.database')) $currentStep = 2;
                    elseif (request()->routeIs('install.migration')) $currentStep = 3;
                    elseif (request()->routeIs('install.admin')) $currentStep = 4;
                    elseif (request()->routeIs('install.complete')) $currentStep = 5;
                @endphp
                
                <div class="steps-container progress-{{ $currentStep }}">
                    <div class="flex justify-between">
                        <div class="step-item {{ request()->routeIs('install.index') || request()->routeIs('install.database') || request()->routeIs('install.migration') || request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'active' : '' }} {{ request()->routeIs('install.database') || request()->routeIs('install.migration') || request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'completed' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="step-circle {{ request()->routeIs('install.index') ? 'active' : '' }} {{ request()->routeIs('install.database') || request()->routeIs('install.migration') || request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'completed' : '' }}">
                                    @if(request()->routeIs('install.database') || request()->routeIs('install.migration') || request()->routeIs('install.admin') || request()->routeIs('install.complete'))
                                        <i class="fas fa-check text-sm"></i>
                                    @else
                                        1
                                    @endif
                                </div>
                                <span class="text-sm mt-2">Bienvenue</span>
                            </div>
                        </div>
                        
                        <div class="step-item {{ request()->routeIs('install.database') || request()->routeIs('install.migration') || request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'active' : '' }} {{ request()->routeIs('install.migration') || request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'completed' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="step-circle {{ request()->routeIs('install.database') ? 'active' : '' }} {{ request()->routeIs('install.migration') || request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'completed' : '' }}">
                                    @if(request()->routeIs('install.migration') || request()->routeIs('install.admin') || request()->routeIs('install.complete'))
                                        <i class="fas fa-check text-sm"></i>
                                    @else
                                        2
                                    @endif
                                </div>
                                <span class="text-sm mt-2">Base de données</span>
                            </div>
                        </div>
                        
                        <div class="step-item {{ request()->routeIs('install.migration') || request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'active' : '' }} {{ request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'completed' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="step-circle {{ request()->routeIs('install.migration') ? 'active' : '' }} {{ request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'completed' : '' }}">
                                    @if(request()->routeIs('install.admin') || request()->routeIs('install.complete'))
                                        <i class="fas fa-check text-sm"></i>
                                    @else
                                        3
                                    @endif
                                </div>
                                <span class="text-sm mt-2">Migration</span>
                            </div>
                        </div>
                        
                        <div class="step-item {{ request()->routeIs('install.admin') || request()->routeIs('install.complete') ? 'active' : '' }} {{ request()->routeIs('install.complete') ? 'completed' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="step-circle {{ request()->routeIs('install.admin') ? 'active' : '' }} {{ request()->routeIs('install.complete') ? 'completed' : '' }}">
                                    @if(request()->routeIs('install.complete'))
                                        <i class="fas fa-check text-sm"></i>
                                    @else
                                        4
                                    @endif
                                </div>
                                <span class="text-sm mt-2">Administrateur</span>
                            </div>
                        </div>
                        
                        <div class="step-item {{ request()->routeIs('install.complete') ? 'active' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="step-circle {{ request()->routeIs('install.complete') ? 'active' : '' }}">
                                    5
                                </div>
                                <span class="text-sm mt-2">Terminé</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="bg-white rounded-lg shadow-md p-6">
                @if(session('error'))
                    <div class="alert alert-error mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif
                
                @if(session('success'))
                    <div class="alert alert-success mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif
                
                @yield('content')
            </div>
        </div>
    </main>
    
    <footer class="bg-white py-4 mt-8">
        <div class="install-container px-4">
            <div class="text-center text-gray-600 text-sm">
                &copy; {{ date('Y') }} KLASSCI. Tous droits réservés.
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    
    <script>
        // Global error handler for Axios
        axios.interceptors.response.use(
            response => response,
            error => {
                if (error.response && error.response.data && error.response.data.message) {
                    debugError('API Error:', error.response.data.message);
                } else {
                    debugError('API Error:', error.message);
                }
                return Promise.reject(error);
            }
        );
    </script>
    
    @yield('scripts')
</body>
</html> 

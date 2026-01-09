<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lien expiré - KLASSCI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-5">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="error-card text-center p-5">
                    <i class="fas fa-clock text-warning fa-4x mb-4"></i>
                    <h1 class="h3 text-danger mb-3">Lien expiré</h1>
                    <p class="text-muted mb-4">
                        Ce lien de saisie de notes a expiré ou n'est plus valide.
                    </p>
                    <div class="alert alert-info border-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Veuillez contacter l'administration pour obtenir un nouveau lien de saisie.
                    </div>
                    <div class="mt-4">
                        <i class="fas fa-university text-primary fa-2x"></i>
                        <p class="text-muted mt-2 mb-0">KLASSCI</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande de démonstration KLASSCI</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #0453cb;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #0453cb;
            margin-bottom: 10px;
        }
        .title {
            color: #1a1a1a;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }
        .subtitle {
            color: #6b7280;
            font-size: 16px;
            margin: 5px 0 0 0;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #0453cb;
        }
        .info-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .info-value {
            color: #1a1a1a;
            font-size: 15px;
            font-weight: 500;
        }
        .message-section {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .message-content {
            color: #1a1a1a;
            font-style: italic;
            line-height: 1.7;
        }
        .priority-badge {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .action-buttons {
            text-align: center;
            margin: 25px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0453cb, #1b64d4);
            color: white;
        }
        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .meta-info {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #92400e;
        }
        
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .btn {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">KLASSCI</div>
            <h1 class="title">📋 Nouvelle demande de démonstration</h1>
            <p class="subtitle">Reçue le {{ $date_demande }}</p>
        </div>

        <div class="priority-badge">🚀 Prospect qualifié - Réponse sous 24h</div>

        <div class="section">
            <h2 class="section-title">👤 Informations de contact</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nom complet</div>
                    <div class="info-value">{{ $nom }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email professionnel</div>
                    <div class="info-value">{{ $email }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value">{{ $telephone }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date de demande</div>
                    <div class="info-value">{{ $date_demande }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">🏫 Informations établissement</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nom de l'établissement</div>
                    <div class="info-value">{{ $etablissement }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Type d'établissement</div>
                    <div class="info-value">{{ $type_etablissement }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nombre d'étudiants</div>
                    <div class="info-value">{{ $nombre_etudiants }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Potentiel commercial</div>
                    <div class="info-value">
                        @if($nombre_etudiants === 'Plus de 5 000')
                            <span style="color: #059669; font-weight: bold;">🔥 ÉLEVÉ</span>
                        @elseif($nombre_etudiants === '1 000 - 5 000')
                            <span style="color: #d97706; font-weight: bold;">📈 MOYEN</span>
                        @else
                            <span style="color: #6b7280;">📊 Standard</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($message !== 'Aucun message spécifique')
        <div class="section">
            <h2 class="section-title">💬 Message du prospect</h2>
            <div class="message-section">
                <div class="message-content">"{{ $message }}"</div>
            </div>
        </div>
        @endif

        <div class="action-buttons">
            <a href="mailto:{{ $email }}?subject=Re: Demande de démonstration KLASSCI - {{ $etablissement }}&body=Bonjour {{ $nom }},%0D%0A%0D%0ANous avons bien reçu votre demande de démonstration pour {{ $etablissement }}.%0D%0A%0D%0AJe vous propose de planifier un appel de 30 minutes pour découvrir KLASSCI et voir comment notre solution peut répondre à vos besoins spécifiques.%0D%0A%0D%0AÊtes-vous disponible cette semaine pour un échange ?%0D%0A%0D%0ACordialement,%0D%0AÉquipe KLASSCI" 
               class="btn btn-primary">
                ✉️ Répondre par email
            </a>
            <a href="tel:{{ str_replace(' ', '', $telephone) }}" class="btn btn-secondary">
                📞 Appeler maintenant
            </a>
        </div>

        <div class="meta-info">
            <strong>🔍 Informations techniques :</strong><br>
            • Adresse IP : {{ $ip_address }}<br>
            • Navigateur : {{ substr($user_agent, 0, 100) }}{{ strlen($user_agent) > 100 ? '...' : '' }}<br>
            • Formulaire soumis depuis : Page d'accueil KLASSCI
        </div>

        <div class="footer">
            <p><strong>KLASSCI</strong> - Système de gestion scolaire moderne</p>
            <p>Email automatique généré le {{ $date_demande }}</p>
            <p style="font-size: 12px; color: #9ca3af;">
                Ne pas répondre à cet email. Utilisez directement l'adresse du prospect : {{ $email }}
            </p>
        </div>
    </div>
</body>
</html>
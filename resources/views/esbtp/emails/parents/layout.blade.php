<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Notification KLASSCI' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
            background: #f8f9fa;
        }

        .email-container {
            max-width: 650px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        /* Header bleu moderne inspiré du PDF */
        .email-header {
            background: #007bff;
            color: #ffffff;
            padding: 30px 25px;
            text-align: center;
        }

        .email-header .logo {
            max-height: 70px;
            max-width: 180px;
            margin-bottom: 12px;
            filter: brightness(0) invert(1);
        }

        .school-name {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 8px 0;
            letter-spacing: 0.3px;
        }

        .school-info {
            font-size: 13px;
            opacity: 0.95;
            margin: 0 0 15px 0;
        }

        .document-title-section {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .email-title {
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Contenu */
        .email-content {
            padding: 35px 30px;
            background: #f2f2f2;
        }

        .greeting {
            font-size: 16px;
            color: #333;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .message-intro {
            background: #ffffff;
            border-left: 4px solid #007bff;
            padding: 15px 18px;
            margin: 25px 0;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .message-intro p {
            margin: 0;
            color: #0056b3;
            font-weight: 500;
        }

        /* KPI Cards inspirés du PDF */
        .kpi-section {
            display: table;
            width: 100%;
            margin: 25px 0;
            border-collapse: separate;
            border-spacing: 8px 0;
        }

        .kpi-row {
            display: table-row;
        }

        .kpi-card {
            display: table-cell;
            width: 50%;
            padding: 18px;
            background: #ffffff;
            border: 2px solid #e9ecef;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .kpi-card:first-child {
            border-radius: 8px 0 0 8px;
        }

        .kpi-card:last-child {
            border-radius: 0 8px 8px 0;
        }

        .kpi-title {
            font-size: 11px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 26px;
            font-weight: 700;
            color: #007bff;
            margin-bottom: 4px;
        }

        .kpi-desc {
            font-size: 12px;
            color: #9ca3af;
        }

        /* Tableaux d'informations */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e9ecef;
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .info-table tr:nth-child(even) {
            background: #fafbfc;
        }

        .info-table th,
        .info-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .info-table th {
            background: #007bff;
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .info-table td {
            color: #495057;
        }

        .info-table tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Boutons */
        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .button {
            display: inline-block;
            padding: 14px 35px;
            background: #ffffff;
            color: #007bff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            border: 2px solid #007bff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .button:hover {
            background: #007bff;
            color: #ffffff;
            box-shadow: 0 6px 8px rgba(0, 123, 255, 0.3);
        }

        /* Alert boxes */
        .alert {
            padding: 16px 18px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .alert-danger {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .alert-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }

        /* Section d'instructions */
        .instruction-box {
            background: #ffffff;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .instruction-box h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .instruction-box ol,
        .instruction-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .instruction-box li {
            margin-bottom: 8px;
            color: #495057;
        }

        /* Footer moderne */
        .email-footer {
            background: #f8f9fa;
            padding: 25px 30px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            border-top: 3px solid #007bff;
        }

        .footer-brand {
            font-size: 16px;
            font-weight: 700;
            color: #007bff;
            margin-bottom: 10px;
        }

        .footer-contact {
            margin: 8px 0;
            color: #495057;
        }

        .footer-contact a {
            color: #007bff;
            text-decoration: none;
        }

        .footer-contact a:hover {
            text-decoration: underline;
        }

        .divider {
            height: 2px;
            background: linear-gradient(to right, transparent, #dee2e6, transparent);
            margin: 15px 0;
        }

        .footer-disclaimer {
            font-size: 11px;
            color: #999;
            font-style: italic;
            margin-top: 12px;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }

            .email-header,
            .email-content,
            .email-footer {
                padding: 20px 18px;
            }

            .kpi-card {
                display: block;
                width: 100%;
                margin-bottom: 10px;
                border-radius: 8px !important;
            }

            .info-table th,
            .info-table td {
                padding: 10px;
                font-size: 13px;
            }

            .email-title {
                font-size: 18px;
            }

            .school-name {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header bleu moderne -->
        <div class="email-header">
            <!-- DEBUG: schoolLogoPath = {{ $schoolLogoPath ?? 'NOT SET' }} -->
            <!-- DEBUG: message exists = {{ isset($message) ? 'YES' : 'NO' }} -->
            @if(isset($schoolLogoPath) && $schoolLogoPath)
                <!-- DEBUG: Attempting to embed logo -->
                <img src="{{ $message->embed($schoolLogoPath) }}" alt="Logo {{ $schoolName ?? 'KLASSCI' }}" class="logo">
            @else
                <!-- DEBUG: Logo not embedded - schoolLogoPath not set -->
            @endif
            <h1 class="school-name">{{ $schoolName ?? 'KLASSCI' }}</h1>
            @if($schoolAddress ?? false)
                <p class="school-info">{{ $schoolAddress }}</p>
            @endif

            @if(isset($emailTitle))
                <div class="document-title-section">
                    <h2 class="email-title">{{ $emailTitle }}</h2>
                </div>
            @endif
        </div>

        <!-- Contenu -->
        <div class="email-content">
            @if(isset($parentName))
                <p class="greeting">Bonjour {{ $parentName }},</p>
            @endif

            @yield('content')
        </div>

        <!-- Footer moderne -->
        <div class="email-footer">
            <div class="footer-brand">{{ $schoolName ?? 'KLASSCI' }}</div>

            @if(($schoolAddress ?? false) || ($schoolPhone ?? false) || ($schoolEmail ?? false))
                <div class="footer-contact">
                    @if($schoolAddress ?? false)
                        <div>{{ $schoolAddress }}</div>
                    @endif
                    @if(($schoolPhone ?? false) || ($schoolEmail ?? false))
                        <div style="margin-top: 5px;">
                            @if($schoolPhone ?? false)
                                <span>Tél: {{ $schoolPhone }}</span>
                            @endif
                            @if(($schoolPhone ?? false) && ($schoolEmail ?? false))
                                <span> | </span>
                            @endif
                            @if($schoolEmail ?? false)
                                <span>Email: <a href="mailto:{{ $schoolEmail }}">{{ $schoolEmail }}</a></span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <div class="divider"></div>

            <div class="footer-disclaimer">
                Ceci est un email automatique, merci de ne pas y répondre directement.<br>
                Pour toute question, veuillez contacter l'administration.
            </div>
        </div>
    </div>
</body>
</html>

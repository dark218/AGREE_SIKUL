<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 210mm;
            height: 297mm;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #3a5aa8;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #3a5aa8;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #3a5aa8;
            color: white;
            padding: 8px 10px;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
        .info-label {
            width: 40%;
            font-weight: bold;
            color: #3a5aa8;
        }
        .info-value {
            width: 60%;
            color: #333;
        }
        .table-wrapper {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th {
            background-color: #e8eef7;
            color: #3a5aa8;
            padding: 8px;
            text-align: left;
            border: 1px solid #bbb;
            font-size: 10px;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge.success {
            background-color: #28a745;
            color: white;
        }
        .badge.danger {
            background-color: #dc3545;
            color: white;
        }
        .badge.warning {
            background-color: #ffc107;
            color: #333;
        }
        .footer {
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 20px;
            color: #999;
            font-size: 9px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <h1>📋 BULLETIN SCOLAIRE</h1>
            <p>{{ $data['annee_scolaire'] ?? 'Année Scolaire' }}</p>
        </div>

        <!-- Section 1: Informations de l'Apprenant -->
        <div class="section">
            <div class="section-title">👤 INFORMATIONS DE L'APPRENANT</div>
            <div class="info-row">
                <div class="info-label">Nom Complet:</div>
                <div class="info-value"><strong>{{ ($data['apprenant']['prenoms'] ?? '') . ' ' . ($data['apprenant']['nom'] ?? '') }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Classe:</div>
                <div class="info-value">{{ $data['classe']['nom'] ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Année Scolaire:</div>
                <div class="info-value">{{ $data['annee_scolaire'] ?? '-' }}</div>
            </div>
            @if($data['rang'])
            <div class="info-row">
                <div class="info-label">Rang/Position:</div>
                <div class="info-value"><strong>#{{ $data['rang'] }}</strong></div>
            </div>
            @endif
        </div>

        <!-- Section 2: Résultats Académiques -->
        <div class="section">
            <div class="section-title">📊 RÉSULTATS ACADÉMIQUES</div>
            <table>
                <tr>
                    <th>Critère</th>
                    <th style="width: 40%; text-align: center;">Valeur</th>
                </tr>
                <tr>
                    <td>Moyenne Générale</td>
                    <td style="text-align: center;">
                        @if($data['moyenne_generale'])
                            <strong>{{ number_format($data['moyenne_generale'], 2, ',', ' ') }}/20</strong>
                        @else
                            <span style="color: #999;">Non disponible</span>
                        @endif
                    </td>
                </tr>
                @if($data['periode'])
                <tr>
                    <td>Période</td>
                    <td style="text-align: center;">
                        @switch($data['periode'])
                            @case('trimestre1')
                                <span class="badge success">1er Trimestre</span>
                                @break
                            @case('trimestre2')
                                <span class="badge success">2e Trimestre</span>
                                @break
                            @case('trimestre3')
                                <span class="badge success">3e Trimestre</span>
                                @break
                            @case('semestre1')
                                <span class="badge success">1er Semestre</span>
                                @break
                            @case('semestre2')
                                <span class="badge success">2e Semestre</span>
                                @break
                            @case('annuel')
                                <span class="badge success">Annuel</span>
                                @break
                            @default
                                <span style="color: #999;">{{ $bulletin->periode }}</span>
                        @endswitch
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Section 3: Décision du Conseil -->
        <div class="section">
            <div class="section-title">✅ DÉCISION DU CONSEIL</div>
            @if($data['decision_conseil'])
            <div class="highlight">
                <div style="margin-bottom: 8px;">
                    <strong>Statut:</strong>
                    @switch($data['decision_conseil'])
                        @case('admis')
                            <span class="badge success">✓ ADMIS</span>
                            @break
                        @case('ajourne')
                            <span class="badge danger">✗ AJOURNÉ</span>
                            @break
                        @case('en_attente')
                            <span class="badge warning">⏳ EN ATTENTE</span>
                            @break
                        @default
                            <span class="badge warning">{{ ucfirst($bulletin->decision_conseil) }}</span>
                    @endswitch
                </div>
            </div>
            @endif

            @if($data['appreciation_generale'] ?? false)
            <div class="section" style="background-color: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 10px;">
                <strong>Appréciation Générale:</strong>
                <p style="margin-top: 5px; color: #555; font-style: italic;">
                    "{{ $data['appreciation_generale'] }}"
                </p>
            </div>
            @endif
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>Document généré le {{ date('d/m/Y à H:i') }}</p>
            <p>Ce bulletin est confidentiel et destiné à l'apprenant et à sa famille.</p>
        </div>
    </div>
</body>
</html>

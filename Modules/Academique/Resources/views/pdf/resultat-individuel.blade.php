<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultat - {{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }

        .header { background: #0B5697; color: white; padding: 20px 30px; }
        .header h1 { font-size: 20px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.85; }

        .logo-bar { display: flex; justify-content: space-between; padding: 10px 30px; background: #f8f9fa; border-bottom: 3px solid #0FBCAF; }
        .logo-bar .title { font-size: 14px; font-weight: bold; color: #0B5697; }
        .logo-bar .date { font-size: 10px; color: #777; }

        .content { padding: 20px 30px; }

        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; padding: 6px 10px; font-weight: bold; color: #555; width: 35%; background: #f8f9fa; border: 1px solid #eee; }
        .info-value { display: table-cell; padding: 6px 10px; border: 1px solid #eee; }

        .score-box { text-align: center; margin: 25px 0; padding: 20px; border: 3px solid {{ $reussi ? '#28a745' : '#dc3545' }}; border-radius: 10px; }
        .score-number { font-size: 48px; font-weight: 800; color: {{ $reussi ? '#28a745' : '#dc3545' }}; }
        .score-max { font-size: 20px; color: #999; }
        .verdict { font-size: 22px; font-weight: 800; margin-top: 8px; color: {{ $reussi ? '#28a745' : '#dc3545' }}; text-transform: uppercase; }

        .stats-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .stats-table td { padding: 8px 12px; border: 1px solid #e0e0e0; }
        .stats-table td:first-child { background: #f8f9fa; font-weight: bold; color: #555; width: 50%; }

        .footer { margin-top: 30px; padding: 15px 30px; background: #f8f9fa; border-top: 2px solid #0FBCAF; font-size: 10px; color: #777; text-align: center; }

        .watermark { position: fixed; bottom: 40%; left: 20%; font-size: 60px; color: rgba(0,0,0,0.03); transform: rotate(-30deg); font-weight: 800; z-index: -1; }

        .surveillance { margin-top: 15px; padding: 10px; border: 1px solid {{ $tentative->suspicion_triche ? '#dc3545' : '#28a745' }}; border-radius: 5px; background: {{ $tentative->suspicion_triche ? '#fff5f5' : '#f0fff0' }}; }
        .surveillance-title { font-weight: bold; color: {{ $tentative->suspicion_triche ? '#dc3545' : '#28a745' }}; }
    </style>
</head>
<body>
    <div class="watermark">AGREE SIKUL</div>

    <!-- En-tête -->
    <div class="header">
        <h1>FICHE DE RÉSULTAT D'EXAMEN</h1>
        <p>Plateforme AGREE SIKUL — Gestion Scolaire</p>
    </div>

    <div class="logo-bar">
        <span class="title">Résultat d'examen en ligne</span>
        <span class="date">Généré le {{ $date }}</span>
    </div>

    <div class="content">
        <!-- Informations apprenant -->
        <h3 style="color: #0B5697; margin-bottom: 10px; border-bottom: 2px solid #0FBCAF; padding-bottom: 5px;">Informations de l'apprenant</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom complet</div>
                <div class="info-value">{{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Matricule</div>
                <div class="info-value">{{ $apprenant?->matricule }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Examen</div>
                <div class="info-value">{{ $examen->titre }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Matière</div>
                <div class="info-value">{{ $examen->matiere?->libelle }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Classe</div>
                <div class="info-value">{{ $examen->classe?->nom }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Enseignant</div>
                <div class="info-value">{{ $examen->enseignant ? $examen->enseignant->prenoms . ' ' . $examen->enseignant->nom : '-' }}</div>
            </div>
        </div>

        <!-- Score -->
        <div class="score-box">
            <span class="score-number">{{ $tentative->score }}</span>
            <span class="score-max">/{{ $examen->note_maximum }}</span>
            <div class="verdict">{{ $reussi ? 'ADMIS' : 'NON ADMIS' }}</div>
        </div>

        <!-- Détails -->
        <h3 style="color: #0B5697; margin-bottom: 10px; border-bottom: 2px solid #0FBCAF; padding-bottom: 5px;">Détails de la composition</h3>
        <table class="stats-table">
            <tr>
                <td>Pourcentage</td>
                <td>{{ round($tentative->pourcentage) }}%</td>
            </tr>
            <tr>
                <td>Réponses correctes</td>
                <td>{{ $tentative->reponses_correctes }} / {{ $tentative->questions_repondues }}</td>
            </tr>
            <tr>
                <td>Durée de composition</td>
                <td>{{ round($tentative->temps_passe_minutes) }} minutes</td>
            </tr>
            <tr>
                <td>Date de composition</td>
                <td>{{ $tentative->debut_composition?->format('d/m/Y à H:i') }}</td>
            </tr>
            <tr>
                <td>Note minimum de passage</td>
                <td>{{ $noteMin }}/{{ $examen->note_maximum }}</td>
            </tr>
        </table>

        <!-- Surveillance -->
        <div class="surveillance">
            <span class="surveillance-title">
                @if($tentative->suspicion_triche)
                    ⚠ SUSPICION DE TRICHE — {{ $tentative->nb_incidents_total }} incident(s) détecté(s)
                @else
                    ✓ Aucune anomalie détectée pendant la composition
                @endif
            </span>
        </div>
    </div>

    <div class="footer">
        Document généré automatiquement par la plateforme AGREE SIKUL — {{ $date }}
        <br>Ce document est confidentiel et destiné uniquement à l'usage interne de l'établissement.
    </div>
</body>
</html>

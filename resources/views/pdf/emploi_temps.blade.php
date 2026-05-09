<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Emploi du Temps - {{ $emploi_temps->week_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #3a5aa8;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #3a5aa8;
        }

        .header-info {
            font-size: 10px;
            color: #666;
        }

        .jour-section {
            page-break-inside: avoid;
            margin-bottom: 15px;
        }

        .jour-title {
            background: #3a5aa8;
            color: white;
            padding: 6px 8px;
            font-weight: bold;
            border-radius: 3px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th {
            background: #e8ecf1;
            border: 1px solid #bcc5d0;
            padding: 6px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }

        td {
            border: 1px solid #bcc5d0;
            padding: 5px 6px;
            font-size: 10px;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        tr:hover {
            background: #f0f0f0;
        }

        .empty-day {
            text-align: center;
            color: #999;
            padding: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Emploi du Temps - {{ $emploi_temps->week_name }}</h1>
        <div class="header-info">
            <p><strong>Classe:</strong> {{ $emploi_temps->classe?->nom }}</p>
            <p><strong>Période:</strong> {{ \Carbon\Carbon::parse($emploi_temps->week_start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($emploi_temps->week_end_date)->format('d/m/Y') }}</p>
            <p><strong>Année Scolaire:</strong> {{ $emploi_temps->anneeScolaire?->libelle }}</p>
        </div>
    </div>

    @foreach($jours as $jour)
        @if(isset($weekCourses[$jour]) && $weekCourses[$jour]->count() > 0)
        <div class="jour-section">
            <div class="jour-title">{{ ucfirst($jour) }}</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Matière</th>
                        <th style="width: 20%;">Enseignant</th>
                        <th style="width: 12%;">Début</th>
                        <th style="width: 12%;">Fin</th>
                        <th style="width: 10%;">Durée</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($weekCourses[$jour] as $cours)
                    <tr>
                        <td><strong>{{ $cours->matiere?->libelle ?? '-' }}</strong></td>
                        <td>{{ $cours->enseignant?->prenoms ?? '' }} {{ $cours->enseignant?->nom ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($cours->date_debut)->format('H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($cours->date_fin)->format('H:i') }}</td>
                        <td>{{ $cours->duree }}h</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="jour-section">
            <div class="jour-title">{{ ucfirst($jour) }}</div>
            <div class="empty-day">Aucun cours prévu</div>
        </div>
        @endif
    @endforeach

    <div style="margin-top: 30px; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; text-align: center;">
        Généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>

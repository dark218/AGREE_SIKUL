<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats - {{ $examen->titre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }

        .header { background: #0B5697; color: white; padding: 15px 25px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .header h1 { font-size: 18px; margin-bottom: 3px; }
        .header p { font-size: 10px; opacity: 0.85; }

        .sub-header { background: #f8f9fa; padding: 10px 25px; border-bottom: 3px solid #0FBCAF; }
        .sub-header table { width: 100%; }
        .sub-header td { padding: 3px 5px; font-size: 10px; }
        .sub-header .label { font-weight: bold; color: #0B5697; }

        .content { padding: 15px 25px; }

        /* Stats cards */
        .stats { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .stats td { text-align: center; padding: 10px; border: 1px solid #eee; }
        .stat-value { font-size: 22px; font-weight: 800; display: block; }
        .stat-label { font-size: 8px; text-transform: uppercase; color: #777; }
        .stat-blue { background: #e8f0f8; color: #0B5697; }
        .stat-green { background: #d4edda; color: #155724; }
        .stat-red { background: #f8d7da; color: #721c24; }
        .stat-cyan { background: #d1ecf1; color: #0c5460; }
        .stat-orange { background: #fff3cd; color: #856404; }

        /* Table résultats */
        .results { width: 100%; border-collapse: collapse; }
        .results th { background: #0B5697; color: white; padding: 8px 6px; font-size: 9px; text-transform: uppercase; text-align: left; }
        .results td { padding: 6px; border-bottom: 1px solid #eee; font-size: 10px; }
        .results tr:nth-child(even) { background: #f8f9fa; }
        .results .text-center { text-align: center; }

        .badge-admis { background: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .badge-refuse { background: #dc3545; color: white; padding: 2px 8px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .badge-triche { background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 7px; }
        .badge-ok { background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 7px; }

        .bar { height: 12px; border-radius: 2px; display: inline-block; }
        .bar-green { background: #28a745; }
        .bar-red { background: #dc3545; }

        .footer { margin-top: 15px; padding: 10px 25px; border-top: 2px solid #0FBCAF; font-size: 8px; color: #999; text-align: center; }
        .row-triche { background: #fff5f5 !important; }

        .watermark { position: fixed; bottom: 35%; left: 15%; font-size: 70px; color: rgba(0,0,0,0.03); transform: rotate(-25deg); font-weight: 800; z-index: -1; }
    </style>
</head>
<body>
    <div class="watermark">AGREE SIKUL</div>

    <!-- En-tête -->
    <div class="header">
        <div class="header-left">
            <h1>PROCÈS-VERBAL DE RÉSULTATS</h1>
            <p>Plateforme AGREE SIKUL — Gestion Scolaire</p>
        </div>
        <div class="header-right">
            <p style="font-size: 12px; font-weight: bold;">{{ $examen->titre }}</p>
            <p>{{ $date }}</p>
        </div>
    </div>

    <!-- Sous-header infos examen -->
    <div class="sub-header">
        <table>
            <tr>
                <td><span class="label">Matière :</span> {{ $examen->matiere?->libelle }}</td>
                <td><span class="label">Classe :</span> {{ $examen->classe?->nom }}</td>
                <td><span class="label">Enseignant :</span> {{ $examen->enseignant ? $examen->enseignant->prenoms . ' ' . $examen->enseignant->nom : '-' }}</td>
                <td><span class="label">Barème :</span> /{{ $examen->note_maximum }}</td>
            </tr>
        </table>
    </div>

    <div class="content">
        <!-- Statistiques -->
        <table class="stats">
            <tr>
                <td class="stat-blue">
                    <span class="stat-value">{{ $tentatives->count() }}</span>
                    <span class="stat-label">Compositions</span>
                </td>
                <td class="stat-green">
                    <span class="stat-value">{{ $nbReussis }}</span>
                    <span class="stat-label">Admis</span>
                </td>
                <td class="stat-red">
                    <span class="stat-value">{{ $nbEchoues }}</span>
                    <span class="stat-label">Refusés</span>
                </td>
                <td class="stat-cyan">
                    <span class="stat-value">{{ $tauxReussite }}%</span>
                    <span class="stat-label">Taux réussite</span>
                </td>
                <td class="stat-orange">
                    <span class="stat-value">{{ $moyenne }}/{{ $examen->note_maximum }}</span>
                    <span class="stat-label">Moyenne</span>
                </td>
            </tr>
        </table>

        <!-- Tableau des résultats -->
        <table class="results">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Nom & Prénoms</th>
                    <th>Matricule</th>
                    <th class="text-center">Note</th>
                    <th class="text-center">%</th>
                    <th class="text-center">Correctes</th>
                    <th class="text-center">Durée</th>
                    <th class="text-center">Verdict</th>
                    <th class="text-center">Surveillance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tentatives as $index => $t)
                <tr class="{{ $t->suspicion_triche ? 'row-triche' : '' }}">
                    <td>{{ $index + 1 }}</td>
                    <td style="font-weight: bold;">{{ $t->apprenant?->prenoms }} {{ $t->apprenant?->nom }}</td>
                    <td>{{ $t->apprenant?->matricule }}</td>
                    <td class="text-center" style="font-weight: bold; font-size: 12px; color: {{ $t->score >= $noteMin ? '#28a745' : '#dc3545' }};">
                        {{ $t->score }}/{{ $examen->note_maximum }}
                    </td>
                    <td class="text-center">
                        <span class="bar {{ $t->score >= $noteMin ? 'bar-green' : 'bar-red' }}" style="width: {{ max(5, round($t->pourcentage ?? 0)) }}px;"></span>
                        {{ round($t->pourcentage ?? 0) }}%
                    </td>
                    <td class="text-center">{{ $t->reponses_correctes }}/{{ $t->questions_repondues }}</td>
                    <td class="text-center">{{ round($t->temps_passe_minutes) }} min</td>
                    <td class="text-center">
                        @if($t->creation_username === 'SYSTEME - Absent')
                            <span class="badge-refuse">ABSENT</span>
                        @else
                            <span class="{{ $t->score >= $noteMin ? 'badge-admis' : 'badge-refuse' }}">
                                {{ $t->score >= $noteMin ? 'ADMIS' : 'REFUSÉ' }}
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($t->suspicion_triche)
                            <span class="badge-triche">TRICHE ({{ $t->nb_incidents_total }})</span>
                        @elseif($t->nb_incidents_total > 0)
                            {{ $t->nb_incidents_total }} inc.
                        @else
                            <span class="badge-ok">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Procès-verbal généré automatiquement par la plateforme AGREE SIKUL — {{ $date }}
        <br>Document confidentiel — Usage interne uniquement
    </div>
</body>
</html>

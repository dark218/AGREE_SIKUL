<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé - {{ $classe?->nom }} - {{ $matiere?->libelle }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 12px 15px; }

        .header { background: #0B5697; color: white; padding: 12px 18px; border-radius: 6px; margin-bottom: 10px; }
        .header h1 { font-size: 14px; font-weight: 800; }
        .header p { font-size: 9px; opacity: 0.85; }
        .header-info { margin-top: 6px; display: table; width: 100%; }
        .header-info span { display: table-cell; font-size: 9px; }

        .stats-bar { display: table; width: 100%; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
        .stat-cell { display: table-cell; text-align: center; padding: 6px; border-right: 1px solid #eee; }
        .stat-cell:last-child { border-right: none; }
        .stat-val { font-size: 16px; font-weight: 900; display: block; }
        .stat-lbl { font-size: 7px; color: #777; text-transform: uppercase; }
        .stat-blue .stat-val { color: #0B5697; }
        .stat-green .stat-val { color: #28a745; }
        .stat-red .stat-val { color: #dc3545; }
        .stat-cyan .stat-val { color: #0FBCAF; }

        .main-table { width: 100%; border-collapse: collapse; }
        .main-table th { background: #0B5697; color: white; padding: 4px 3px; font-size: 7.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .main-table td { padding: 3px 4px; border: 1px solid #ddd; font-size: 8.5px; text-align: center; }
        .main-table .name-cell { text-align: left; padding-left: 6px; font-weight: 600; }
        .main-table .mat-cell { text-align: left; padding-left: 6px; font-size: 8px; color: #555; }
        .main-table tr:nth-child(even) td { background: #f8fbff; }

        .note-pass { color: #28a745; font-weight: 700; }
        .note-fail { color: #dc3545; font-weight: 700; }

        .moyenne-row td { background: #eef3f9 !important; font-weight: 800; border-top: 2px solid #0B5697; }

        .footer { margin-top: 10px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 4px; }
        .watermark { position: fixed; top: 35%; left: 12%; font-size: 65px; color: rgba(11, 86, 151, 0.03); transform: rotate(-25deg); font-weight: 900; z-index: -1; }

        .signatures { margin-top: 15px; width: 100%; }
        .signatures td { width: 50%; text-align: center; font-size: 8px; vertical-align: top; }
    </style>
</head>
<body>
    <div class="watermark">AGREE SIKUL</div>
    <div class="page">
        <!-- HEADER -->
        <div class="header">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <h1>RELEVÉ DE NOTES PAR CLASSE</h1>
                        <p>{{ $ecole?->nom ?? 'Établissement' }}</p>
                    </td>
                    <td style="text-align: right;">
                        <p>{{ $date }}</p>
                    </td>
                </tr>
            </table>
            <table style="width: 100%; margin-top: 6px;">
                <tr>
                    <td style="font-size: 10px;"><strong>Classe :</strong> {{ $classe?->nom }}</td>
                    <td style="font-size: 10px;"><strong>Matière :</strong> {{ $matiere?->libelle }}</td>
                    <td style="font-size: 10px;"><strong>Note de passage :</strong> {{ $notePassage }}/20</td>
                </tr>
            </table>
        </div>

        <!-- STATS -->
        <div class="stats-bar">
            <div class="stat-cell stat-blue">
                <span class="stat-val">{{ $nbApprenants }}</span>
                <span class="stat-lbl">Inscrits</span>
            </div>
            <div class="stat-cell stat-green">
                <span class="stat-val">{{ $nbReussis }}</span>
                <span class="stat-lbl">Admis</span>
            </div>
            <div class="stat-cell stat-red">
                <span class="stat-val">{{ $nbApprenants - $nbReussis }}</span>
                <span class="stat-lbl">Refusés</span>
            </div>
            <div class="stat-cell stat-cyan">
                <span class="stat-val">{{ $nbApprenants > 0 ? round(($nbReussis / $nbApprenants) * 100) : 0 }}%</span>
                <span class="stat-lbl">Réussite</span>
            </div>
            <div class="stat-cell">
                <span class="stat-val" style="color: #E5590C;">{{ $moyenneClasse ? number_format($moyenneClasse, 2) : '-' }}</span>
                <span class="stat-lbl">Moy. Classe</span>
            </div>
        </div>

        <!-- TABLEAU -->
        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 10%;">Matricule</th>
                    <th style="width: 25%; text-align: left; padding-left: 6px;">Nom et Prénoms</th>
                    @php $maxNotes = $lignes->max(fn($l) => count($l['notes'])); @endphp
                    @for($i = 0; $i < max($maxNotes, 1); $i++)
                        <th>Note {{ $i + 1 }}</th>
                    @endfor
                    <th style="width: 10%;">Moyenne</th>
                    <th style="width: 10%;">Verdict</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $index => $ligne)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="mat-cell">{{ $ligne['matricule'] }}</td>
                    <td class="name-cell">{{ $ligne['nom'] }} {{ $ligne['prenoms'] }}</td>
                    @for($i = 0; $i < max($maxNotes, 1); $i++)
                        <td>{{ isset($ligne['notes'][$i]) ? number_format($ligne['notes'][$i], 1) : '-' }}</td>
                    @endfor
                    <td>
                        @if($ligne['moyenne'] !== null)
                            <span class="{{ $ligne['moyenne'] >= $notePassage ? 'note-pass' : 'note-fail' }}">
                                {{ number_format($ligne['moyenne'], 2) }}
                            </span>
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($ligne['moyenne'] !== null)
                            @if($ligne['moyenne'] >= $notePassage)
                                <span style="background: #d4edda; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: 700; color: #155724;">ADMIS</span>
                            @else
                                <span style="background: #f8d7da; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: 700; color: #721c24;">REFUSÉ</span>
                            @endif
                        @else
                            <span style="color: #999; font-size: 7px;">N/A</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- SIGNATURES -->
        <table class="signatures">
            <tr>
                <td>
                    <div style="font-weight: 700; color: #0B5697; margin-bottom: 30px;">L'Enseignant</div>
                    Signature
                </td>
                <td>
                    <div style="font-weight: 700; color: #0B5697; margin-bottom: 30px;">Le Directeur</div>
                    Fait le {{ $date }}
                </td>
            </tr>
        </table>

        <div class="footer">AGREE SIKUL — Plateforme de Gestion Scolaire — {{ $date }}</div>
    </div>
</body>
</html>

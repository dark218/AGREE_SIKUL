<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport d'absences</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 12px 15px; }
        .watermark { position: fixed; top: 35%; left: 12%; font-size: 60px; color: rgba(11,86,151,0.03); transform: rotate(-25deg); font-weight: 900; z-index: -1; }
        .header { background: #0B5697; color: white; padding: 10px 15px; border-radius: 6px; margin-bottom: 8px; }
        .header h1 { font-size: 13px; font-weight: 800; }
        .header p { font-size: 8px; opacity: 0.85; }
        .stats { display: table; width: 100%; margin-bottom: 6px; border: 1px solid #ddd; border-radius: 4px; }
        .stat { display: table-cell; text-align: center; padding: 5px; border-right: 1px solid #eee; }
        .stat:last-child { border-right: none; }
        .stat-val { font-size: 15px; font-weight: 900; display: block; }
        .stat-lbl { font-size: 7px; color: #777; text-transform: uppercase; }
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl th { background: #0B5697; color: white; padding: 4px 3px; font-size: 7.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .tbl td { padding: 3px 4px; border: 1px solid #ddd; font-size: 8.5px; text-align: center; }
        .tbl .left { text-align: left; padding-left: 6px; }
        .tbl tr:nth-child(even) td { background: #f8fbff; }
        .justifie { color: #28a745; font-weight: 700; }
        .non-justifie { color: #dc3545; font-weight: 700; }
        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header">
        <table style="width:100%;"><tr>
            <td><h1>RAPPORT D'ABSENCES{{ $type === 'enseignant' ? ' — ENSEIGNANTS' : ' — APPRENANTS' }}</h1><p>{{ $ecole ?? '' }}</p></td>
            <td style="text-align:right;"><p>{{ $classe ?? 'Toutes classes' }}</p><p>{{ $periode ?? '' }} — {{ $date }}</p></td>
        </tr></table>
    </div>
    <div class="stats">
        <div class="stat"><span class="stat-val" style="color:#0B5697;">{{ $totalAbsences }}</span><span class="stat-lbl">Total absences</span></div>
        <div class="stat"><span class="stat-val" style="color:#28a745;">{{ $justifiees }}</span><span class="stat-lbl">Justifiées</span></div>
        <div class="stat"><span class="stat-val" style="color:#dc3545;">{{ $nonJustifiees }}</span><span class="stat-lbl">Non justifiées</span></div>
        <div class="stat"><span class="stat-val" style="color:#E5590C;">{{ $totalHeures ?? '-' }}</span><span class="stat-lbl">Heures perdues</span></div>
    </div>
    <table class="tbl">
        <thead><tr>
            <th style="width:4%;">#</th>
            <th style="width:10%;">Matricule</th>
            <th style="width:22%; text-align:left; padding-left:6px;">Nom et Prénoms</th>
            <th style="width:10%;">Date</th>
            <th style="width:8%;">Heures</th>
            <th style="width:10%;">Justifiée</th>
            <th style="width:18%;">Motif</th>
            <th style="width:18%;">Observation</th>
        </tr></thead>
        <tbody>
        @foreach($absences as $i => $a)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td style="font-size:7.5px;">{{ $a['matricule'] }}</td>
            <td class="left"><strong>{{ $a['nom'] }}</strong></td>
            <td>{{ $a['date'] }}</td>
            <td>{{ $a['heures'] ?? '-' }}</td>
            <td>
                @if($a['justifiee'])<span class="justifie">Oui</span>
                @else<span class="non-justifie">Non</span>@endif
            </td>
            <td class="left" style="font-size:7.5px;">{{ $a['motif'] ?? '-' }}</td>
            <td class="left" style="font-size:7.5px;">{{ $a['observation'] ?? '' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div class="footer">AGREE SIKUL — Rapport d'Absences — {{ $date }}</div>
</div>
</body>
</html>

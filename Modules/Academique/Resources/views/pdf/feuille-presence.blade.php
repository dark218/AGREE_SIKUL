<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Feuille de présence</title>
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
        .stat-green .stat-val { color: #28a745; }
        .stat-red .stat-val { color: #dc3545; }
        .stat-blue .stat-val { color: #0B5697; }
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl th { background: #0B5697; color: white; padding: 4px 3px; font-size: 7.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .tbl td { padding: 3px 4px; border: 1px solid #ddd; font-size: 8.5px; text-align: center; }
        .tbl .left { text-align: left; padding-left: 6px; }
        .tbl tr:nth-child(even) td { background: #f8fbff; }
        .present { color: #28a745; font-weight: 700; }
        .absent { color: #dc3545; font-weight: 700; }
        .retard { color: #E5590C; font-weight: 700; }
        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header">
        <table style="width:100%;"><tr>
            <td><h1>FEUILLE DE PRÉSENCE</h1><p>{{ $ecole ?? '' }}</p></td>
            <td style="text-align:right;"><p>{{ $classe ?? '' }} — {{ $matiere ?? '' }}</p><p>{{ $dateSeance ?? '' }}</p></td>
        </tr></table>
    </div>
    <div class="stats">
        <div class="stat stat-blue"><span class="stat-val">{{ $total }}</span><span class="stat-lbl">Inscrits</span></div>
        <div class="stat stat-green"><span class="stat-val">{{ $presents }}</span><span class="stat-lbl">Présents</span></div>
        <div class="stat stat-red"><span class="stat-val">{{ $absents }}</span><span class="stat-lbl">Absents</span></div>
        <div class="stat"><span class="stat-val" style="color:#E5590C;">{{ $retards }}</span><span class="stat-lbl">Retards</span></div>
    </div>
    <table class="tbl">
        <thead><tr>
            <th style="width:4%;">#</th>
            <th style="width:10%;">Matricule</th>
            <th style="width:30%; text-align:left; padding-left:6px;">Nom et Prénoms</th>
            <th style="width:10%;">Statut</th>
            <th style="width:10%;">Heure arrivée</th>
            <th style="width:20%;">Observation</th>
            <th style="width:16%;">Signature</th>
        </tr></thead>
        <tbody>
        @foreach($lignes as $i => $l)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td style="font-size:7.5px;">{{ $l['matricule'] }}</td>
            <td class="left"><strong>{{ $l['nom'] }}</strong></td>
            <td>
                @if($l['statut'] === 'present')<span class="present">P</span>
                @elseif($l['statut'] === 'absent')<span class="absent">A</span>
                @elseif($l['statut'] === 'retard')<span class="retard">R</span>
                @else <span>-</span> @endif
            </td>
            <td>{{ $l['heure'] ?? '' }}</td>
            <td class="left" style="font-size:7.5px;">{{ $l['observation'] ?? '' }}</td>
            <td></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <table style="width:100%; margin-top:12px;"><tr>
        <td style="width:50%; text-align:center; font-size:8px;"><div style="font-weight:700; color:#0B5697; margin-bottom:25px;">L'Enseignant</div>Signature</td>
        <td style="width:50%; text-align:center; font-size:8px;"><div style="font-weight:700; color:#0B5697; margin-bottom:25px;">Le Surveillant</div>Signature</td>
    </tr></table>
    <div class="footer">AGREE SIKUL — Feuille de Présence — {{ $date }}</div>
</div>
</body>
</html>

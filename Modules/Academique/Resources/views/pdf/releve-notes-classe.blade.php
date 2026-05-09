<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé de notes - {{ $classe ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1a1a1a; }
        .page { padding: 10px 12px; }
        .watermark { position: fixed; top: 35%; left: 12%; font-size: 60px; color: rgba(11,86,151,0.03); transform: rotate(-25deg); font-weight: 900; z-index: -1; }
        .header { background: #0B5697; color: white; padding: 8px 12px; border-radius: 5px; margin-bottom: 6px; }
        .header h1 { font-size: 12px; font-weight: 800; }
        .header p { font-size: 7.5px; opacity: 0.85; }
        .stats { display: table; width: 100%; margin-bottom: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .stat { display: table-cell; text-align: center; padding: 4px; border-right: 1px solid #eee; }
        .stat:last-child { border-right: none; }
        .stat-val { font-size: 14px; font-weight: 900; display: block; }
        .stat-lbl { font-size: 6.5px; color: #777; text-transform: uppercase; }
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl th { background: #0B5697; color: white; padding: 3px 2px; font-size: 6.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .tbl td { padding: 2px 3px; border: 1px solid #ddd; font-size: 7.5px; text-align: center; }
        .tbl .left { text-align: left; padding-left: 4px; }
        .tbl tr:nth-child(even) td { background: #f8fbff; }
        .total-row td { background: #eef3f9 !important; font-weight: 800; border-top: 2px solid #0B5697; }
        .note-ok { color: #28a745; font-weight: 700; }
        .note-ko { color: #dc3545; font-weight: 700; }
        .footer { margin-top: 6px; text-align: center; font-size: 6.5px; color: #aaa; border-top: 1px solid #eee; padding-top: 3px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header">
        <table style="width:100%;"><tr>
            <td><h1>RELEVÉ DE NOTES PAR CLASSE</h1><p>{{ $ecole ?? '' }}</p></td>
            <td style="text-align:right;"><p>{{ $classe ?? '' }} — {{ $annee ?? '' }}</p><p>{{ $date }}</p></td>
        </tr></table>
    </div>
    <div class="stats">
        <div class="stat"><span class="stat-val" style="color:#0B5697;">{{ $nbApprenants }}</span><span class="stat-lbl">Élèves</span></div>
        <div class="stat"><span class="stat-val" style="color:#28a745;">{{ $nbAdmis }}</span><span class="stat-lbl">Admis</span></div>
        <div class="stat"><span class="stat-val" style="color:#dc3545;">{{ $nbRefuses }}</span><span class="stat-lbl">Refusés</span></div>
        <div class="stat"><span class="stat-val" style="color:#0FBCAF;">{{ $tauxReussite }}%</span><span class="stat-lbl">Réussite</span></div>
        <div class="stat"><span class="stat-val" style="color:#E5590C;">{{ number_format($moyenneClasse, 2) }}</span><span class="stat-lbl">Moy. Classe</span></div>
    </div>
    <table class="tbl">
        <thead><tr>
            <th style="width:3%;">#</th>
            <th style="width:8%;">Mat.</th>
            <th style="width:18%; text-align:left; padding-left:4px;">Nom et Prénoms</th>
            @foreach($matieres as $mat)
            <th>{{ \Illuminate\Support\Str::limit($mat, 6) }}</th>
            @endforeach
            <th style="width:7%;">Moy.</th>
            <th style="width:5%;">Rang</th>
            <th style="width:7%;">Verdict</th>
        </tr></thead>
        <tbody>
        @foreach($lignes as $i => $l)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td style="font-size:6.5px;">{{ $l['matricule'] }}</td>
            <td class="left" style="font-weight:600;">{{ $l['nom'] }}</td>
            @foreach($matieres as $matIdx => $mat)
            <td>
                @php $n = $l['notes'][$matIdx] ?? null; @endphp
                <span class="{{ ($n ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ $n !== null ? number_format($n, 1) : '-' }}</span>
            </td>
            @endforeach
            <td><strong class="{{ ($l['moyenne'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ $l['moyenne'] !== null ? number_format($l['moyenne'], 2) : '-' }}</strong></td>
            <td>{{ $l['rang'] }}</td>
            <td>
                @if(($l['moyenne'] ?? 0) >= 10)
                <span style="background:#d4edda; padding:1px 4px; border-radius:2px; font-size:6px; font-weight:700; color:#155724;">ADMIS</span>
                @else
                <span style="background:#f8d7da; padding:1px 4px; border-radius:2px; font-size:6px; font-weight:700; color:#721c24;">REFUSÉ</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div class="footer">AGREE SIKUL — Relevé de Notes par Classe — {{ $date }}</div>
</div>
</body>
</html>

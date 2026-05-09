<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des apprenants - {{ $classe ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 12px 15px; }
        .watermark { position: fixed; top: 35%; left: 12%; font-size: 65px; color: rgba(11,86,151,0.03); transform: rotate(-25deg); font-weight: 900; z-index: -1; }
        .header { background: #0B5697; color: white; padding: 10px 15px; border-radius: 6px; margin-bottom: 8px; }
        .header h1 { font-size: 13px; font-weight: 800; }
        .header p { font-size: 8px; opacity: 0.85; }
        .stats { display: table; width: 100%; margin-bottom: 6px; border: 1px solid #ddd; border-radius: 4px; }
        .stat { display: table-cell; text-align: center; padding: 5px; border-right: 1px solid #eee; }
        .stat:last-child { border-right: none; }
        .stat-val { font-size: 16px; font-weight: 900; color: #0B5697; display: block; }
        .stat-lbl { font-size: 7px; color: #777; text-transform: uppercase; }
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl th { background: #0B5697; color: white; padding: 4px 3px; font-size: 7.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .tbl td { padding: 3px 4px; border: 1px solid #ddd; font-size: 8.5px; }
        .tbl .left { text-align: left; padding-left: 6px; }
        .tbl .center { text-align: center; }
        .tbl tr:nth-child(even) td { background: #f8fbff; }
        .badge-m { background: #e3f2fd; color: #0B5697; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: 700; }
        .badge-f { background: #fce4ec; color: #c62828; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: 700; }
        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header">
        <table style="width:100%;"><tr>
            <td><h1>LISTE DES APPRENANTS</h1><p>{{ $ecole ?? '' }}</p></td>
            <td style="text-align:right;"><p>{{ $classe ?? 'Toutes classes' }}</p><p>{{ $annee ?? '' }} — {{ $date }}</p></td>
        </tr></table>
    </div>
    <div class="stats">
        <div class="stat"><span class="stat-val">{{ $total }}</span><span class="stat-lbl">Total</span></div>
        <div class="stat"><span class="stat-val">{{ $garcons }}</span><span class="stat-lbl">Garçons</span></div>
        <div class="stat"><span class="stat-val">{{ $filles }}</span><span class="stat-lbl">Filles</span></div>
    </div>
    <table class="tbl">
        <thead><tr>
            <th style="width:4%;">#</th>
            <th style="width:10%;">Matricule</th>
            <th style="width:25%; text-align:left; padding-left:6px;">Nom et Prénoms</th>
            <th style="width:6%;">Sexe</th>
            <th style="width:10%;">Né(e) le</th>
            <th style="width:10%;">Téléphone</th>
            <th style="width:15%;">Parent/Tuteur</th>
            <th style="width:10%;">Statut</th>
        </tr></thead>
        <tbody>
        @foreach($apprenants as $i => $a)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td class="center" style="font-size:7.5px;">{{ $a['matricule'] }}</td>
            <td class="left"><strong>{{ $a['nom'] }} {{ $a['prenoms'] }}</strong></td>
            <td class="center"><span class="{{ $a['sexe'] === 'M' ? 'badge-m' : 'badge-f' }}">{{ $a['sexe'] }}</span></td>
            <td class="center">{{ $a['date_naissance'] }}</td>
            <td class="center">{{ $a['telephone'] }}</td>
            <td class="left" style="font-size:7.5px;">{{ $a['tuteur'] }}</td>
            <td class="center"><span style="font-size:7px;">{{ $a['statut'] }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div class="footer">AGREE SIKUL — Liste des Apprenants — {{ $date }}</div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé Master - {{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 15px 22px; }
        .watermark { position: fixed; top: 38%; left: 12%; font-size: 60px; color: rgba(11,86,151,0.03); transform: rotate(-30deg); font-weight: 900; z-index: -1; }

        .program { text-align: center; font-size: 13px; font-weight: 800; color: #0B5697; margin-bottom: 8px; border-bottom: 3px solid #0FBCAF; padding-bottom: 6px; }
        .title-bar { text-align: center; margin: 6px 0; padding: 5px; background: linear-gradient(90deg, #0B5697, #0FBCAF); color: white; font-size: 12px; font-weight: 800; letter-spacing: 2px; }
        .student-info { text-align: center; font-size: 10px; margin-bottom: 8px; color: #444; }
        .student-info strong { color: #1a1a1a; }

        .sem-title { font-size: 10px; font-weight: 700; color: #0B5697; margin: 8px 0 4px; border-bottom: 2px solid #0FBCAF; padding-bottom: 3px; }

        .tbl { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .tbl th { background: #0B5697; color: white; padding: 5px 4px; font-size: 8.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .tbl td { padding: 4px 5px; border: 1px solid #ddd; font-size: 9.5px; text-align: center; }
        .tbl .left { text-align: left; padding-left: 8px; font-weight: 600; }
        .tbl tr:nth-child(even) td { background: #f8fbff; }
        .total-row td { background: #eef3f9 !important; font-weight: 800; border-top: 2px solid #0B5697; font-size: 10px; }

        .note-ok { color: #28a745; font-weight: 700; }
        .note-ko { color: #dc3545; font-weight: 700; }

        .result-section { margin-top: 10px; }
        .result-section table { width: 100%; border-collapse: collapse; }
        .result-section td { padding: 5px 8px; border: 1px solid #ddd; font-size: 10px; }
        .result-section .lbl { font-weight: 700; color: #0B5697; background: #f8fbff; width: 40%; }

        .mention-box { text-align: center; margin-top: 10px; padding: 10px; border: 3px solid #0B5697; border-radius: 8px; }
        .mention-box .big { font-size: 28px; font-weight: 900; color: #0B5697; }
        .mention-box .sm { font-size: 9px; color: #666; text-transform: uppercase; }

        .sigs { margin-top: 15px; width: 100%; }
        .sigs td { width: 50%; text-align: center; font-size: 9px; vertical-align: top; }
        .sig-t { font-weight: 700; color: #0B5697; margin-bottom: 30px; }
        .footer { margin-top: 10px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="program">{{ $programme ?? 'Master' }}</div>
    <div class="title-bar">RELEVÉ DES NOTES</div>
    <div class="student-info">
        <strong>{{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</strong>, né(e) le {{ $apprenant?->date_naissance?->format('d/m/Y') ?? '-' }} à {{ $apprenant?->lieu_naissance ?? '-' }}
    </div>

    @foreach($semestres as $sem)
    <div class="sem-title">{{ $sem['label'] }}</div>
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:35%; text-align:left; padding-left:8px;">UE / Matière</th>
                <th style="width:10%;">Type</th>
                <th style="width:12%;">Note/20</th>
                <th style="width:12%;">Coefficient</th>
                <th style="width:15%;">Total Points</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sem['matieres'] as $mat)
            <tr>
                <td class="left">{{ $mat['libelle'] }}</td>
                <td>{{ $mat['type'] ?? 'CM' }}</td>
                <td><span class="{{ ($mat['note'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ number_format($mat['note'] ?? 0, 1) }}</span></td>
                <td>{{ $mat['coefficient'] }}</td>
                <td><strong>{{ number_format(($mat['note'] ?? 0) * ($mat['coefficient'] ?? 1), 1) }}</strong></td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td class="left">TOTAL</td>
                <td></td>
                <td></td>
                <td><strong>{{ $sem['total_coef'] }}</strong></td>
                <td><strong>{{ number_format($sem['total_points'], 1) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="result-section">
        <table>
            <tr>
                <td class="lbl">Moyenne Générale</td>
                <td><strong style="font-size:14px; color:{{ ($sem['moyenne'] ?? 0) >= 10 ? '#28a745' : '#dc3545' }};">{{ number_format($sem['moyenne'] ?? 0, 2) }}/20</strong></td>
            </tr>
            <tr>
                <td class="lbl">Mention</td>
                <td><strong>{{ $sem['mention'] ?? '-' }}</strong></td>
            </tr>
        </table>
    </div>
    @endforeach

    @if(isset($mentionFinale))
    <div class="mention-box">
        <div class="sm">Mention Finale</div>
        <div class="big">{{ $mentionFinale }}</div>
    </div>
    @endif

    <table class="sigs"><tr>
        <td><div class="sig-t">Le Directeur du Programme</div><div style="height:30px;"></div>Signature & Cachet</td>
        <td><div class="sig-t">Le Recteur / Président</div><div style="height:30px;"></div>Fait le {{ $date }}</td>
    </tr></table>
    <div class="footer">AGREE SIKUL — Relevé de Notes Master — {{ $date }}</div>
</div>
</body>
</html>

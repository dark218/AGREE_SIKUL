<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé Annuel - {{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 12px 18px; }
        .watermark { position: fixed; top: 38%; left: 12%; font-size: 60px; color: rgba(11,86,151,0.03); transform: rotate(-30deg); font-weight: 900; z-index: -1; }

        .header { width: 100%; border-bottom: 3px solid #0B5697; padding-bottom: 8px; margin-bottom: 6px; }
        .header table { width: 100%; }
        .logo-cell { width: 70px; vertical-align: middle; }
        .logo-box { width: 60px; height: 60px; border: 2px solid #0B5697; border-radius: 8px; text-align: center; line-height: 60px; font-size: 9px; color: #0B5697; font-weight: 700; }
        .center-cell { text-align: center; vertical-align: middle; }
        .ecole-nom { font-size: 14px; font-weight: 800; color: #0B5697; }
        .ecole-sub { font-size: 8px; color: #555; }

        .title-bar { text-align: center; margin: 6px 0; padding: 5px; background: linear-gradient(90deg, #0B5697, #0FBCAF); color: white; font-size: 12px; font-weight: 800; letter-spacing: 2px; }
        .sub-title { text-align: center; font-size: 9px; color: #555; margin-bottom: 6px; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .info-table td { padding: 3px 6px; font-size: 9px; border: 1px solid #eee; }
        .info-table .lbl { font-weight: 700; color: #0B5697; background: #f8fbff; width: 22%; }

        .notes-tbl { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .notes-tbl th { background: #0B5697; color: white; padding: 4px 3px; font-size: 7.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .notes-tbl td { padding: 3px; border: 1px solid #ddd; font-size: 8.5px; text-align: center; }
        .notes-tbl .left { text-align: left; padding-left: 6px; }
        .notes-tbl .bold { font-weight: 700; }
        .notes-tbl tr:nth-child(even) td { background: #f8fbff; }
        .total-row td { background: #eef3f9 !important; font-weight: 800; border-top: 2px solid #0B5697; }
        .sem-header td { background: #e8f0f8 !important; font-weight: 800; color: #0B5697; font-size: 9px; text-align: left; padding-left: 8px; }

        .note-ok { color: #28a745; font-weight: 700; }
        .note-ko { color: #dc3545; font-weight: 700; }

        .resume { width: 100%; margin-bottom: 6px; }
        .resume td { padding: 4px; }
        .box { border: 2px solid #0B5697; border-radius: 6px; padding: 6px; text-align: center; }
        .box .big { font-size: 20px; font-weight: 900; display: block; }
        .box .sm { font-size: 7px; color: #666; text-transform: uppercase; }
        .box-pass { border-color: #28a745; }
        .box-pass .big { color: #28a745; }
        .box-fail { border-color: #dc3545; }
        .box-fail .big { color: #dc3545; }

        .sigs { margin-top: 12px; width: 100%; }
        .sigs td { width: 33%; text-align: center; font-size: 8px; vertical-align: top; }
        .sig-t { font-weight: 700; color: #0B5697; margin-bottom: 25px; }
        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header">
        <table><tr>
            <td class="logo-cell"><div class="logo-box">LOGO</div></td>
            <td class="center-cell">
                <div class="ecole-nom">{{ $ecole ?? 'ÉTABLISSEMENT UNIVERSITAIRE' }}</div>
                <div class="ecole-sub">{{ $autorisation ?? '' }}</div>
            </td>
        </tr></table>
    </div>

    <div class="title-bar">RELEVÉ DE NOTES ANNUEL / ANNUAL MARKS TRANSCRIPT</div>
    <div class="sub-title">{{ $idReleve ?? '' }}</div>

    <table class="info-table">
        <tr>
            <td class="lbl">Nom(s) et prénom(s)</td>
            <td colspan="3"><strong>{{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</strong></td>
        </tr>
        <tr>
            <td class="lbl">Matricule</td>
            <td>{{ $apprenant?->matricule }}</td>
            <td class="lbl">Date de Naissance</td>
            <td>{{ $apprenant?->date_naissance?->format('d/m/Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Parcours</td>
            <td>{{ $parcours ?? '-' }}</td>
            <td class="lbl">Année Académique</td>
            <td><strong>{{ $annee ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="lbl">Classe / Niveau</td>
            <td>{{ $classe ?? '-' }}</td>
            <td class="lbl">Lieu de Naissance</td>
            <td>{{ $apprenant?->lieu_naissance ?? '-' }}</td>
        </tr>
    </table>

    <table class="notes-tbl">
        <thead>
            <tr>
                <th style="width:5%;">N°</th>
                <th style="width:8%;">Code</th>
                <th style="width:28%; text-align:left; padding-left:6px;">Intitulé de la Matière</th>
                <th style="width:7%;">Crédits</th>
                <th style="width:7%;">Coef.</th>
                <th style="width:9%;">Note/20</th>
                <th style="width:9%;">Note×Coef</th>
                <th style="width:7%;">Crédits Obt.</th>
                <th style="width:20%;">Observation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($semestres as $semIdx => $semestre)
            <tr class="sem-header"><td colspan="9">{{ $semestre['label'] }}</td></tr>
            @foreach($semestre['matieres'] as $i => $mat)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="left" style="font-size:7.5px;">{{ $mat['code'] }}</td>
                <td class="left bold">{{ $mat['libelle'] }}</td>
                <td>{{ $mat['credits'] }}</td>
                <td>{{ $mat['coefficient'] }}</td>
                <td><span class="{{ ($mat['note'] ?? 0) >= 10 ? 'note-ok' : 'note-ko' }}">{{ $mat['note'] !== null ? number_format($mat['note'], 2) : '-' }}</span></td>
                <td>{{ $mat['note_coef'] !== null ? number_format($mat['note_coef'], 2) : '-' }}</td>
                <td>{{ $mat['credits_obtenus'] ?? '-' }}</td>
                <td class="left" style="font-size:7px;">{{ $mat['observation'] }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="left" style="padding-left:8px;">Total {{ $semestre['label'] }}</td>
                <td>{{ $semestre['total_credits'] }}</td>
                <td>{{ $semestre['total_coef'] }}</td>
                <td></td>
                <td><strong>{{ number_format($semestre['total_note_coef'], 2) }}</strong></td>
                <td>{{ $semestre['credits_obtenus'] }}</td>
                <td class="left" style="font-size:7px;">Moy: <strong>{{ number_format($semestre['moyenne'], 2) }}/20</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="resume">
        <tr>
            <td style="width:25%;">
                @php $pass = ($moyenneAnnuelle ?? 0) >= 10; @endphp
                <div class="box {{ $pass ? 'box-pass' : 'box-fail' }}">
                    <span class="sm">Moyenne Annuelle</span>
                    <span class="big">{{ number_format($moyenneAnnuelle ?? 0, 2) }}/20</span>
                </div>
            </td>
            <td style="width:20%;">
                <div class="box">
                    <span class="sm">Crédits Capitalisés</span>
                    <span class="big" style="color:#0B5697;">{{ $totalCreditsObtenus ?? 0 }}/{{ $totalCredits ?? 0 }}</span>
                </div>
            </td>
            <td style="width:20%;">
                <div class="box">
                    <span class="sm">Mention</span>
                    <span class="big" style="color:#0B5697; font-size:14px;">{{ $mention ?? '-' }}</span>
                </div>
            </td>
            <td style="width:35%;">
                <div class="box">
                    <span class="sm">Décision du Jury</span>
                    <span class="big" style="color:#0B5697; font-size:12px;">{{ $decision ?? 'Admis(e) au niveau supérieur' }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="sigs"><tr>
        <td><div class="sig-t">Le Chef de Département</div><div style="height:25px;"></div>Signature & Cachet</td>
        <td><div class="sig-t">Le Doyen / Directeur</div><div style="height:25px;"></div>Signature & Cachet</td>
        <td><div class="sig-t">Le Recteur</div><div style="height:25px;"></div>Fait le {{ $date }}</td>
    </tr></table>
    <div class="footer">AGREE SIKUL — Relevé de Notes Annuel — {{ $date }}</div>
</div>
</body>
</html>

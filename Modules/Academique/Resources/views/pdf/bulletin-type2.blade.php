<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin Séquences - {{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 15px 20px; }

        .header-ecole { width: 100%; border-bottom: 3px solid #0B5697; padding-bottom: 8px; margin-bottom: 8px; }
        .header-ecole table { width: 100%; }
        .ecole-nom { font-size: 15px; font-weight: 800; color: #0B5697; text-align: center; }
        .ecole-detail { font-size: 9px; color: #555; text-align: center; }

        .bulletin-title { text-align: center; margin: 6px 0; padding: 5px; background: linear-gradient(90deg, #0B5697, #0FBCAF); color: white; font-size: 12px; font-weight: 800; letter-spacing: 2px; }

        .eleve-info { width: 100%; border-collapse: collapse; margin-bottom: 6px; border: 1px solid #ddd; }
        .eleve-info td { padding: 3px 6px; font-size: 9px; border: 1px solid #eee; }
        .eleve-info .label { font-weight: 700; color: #0B5697; background: #f8fbff; }

        .notes-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .notes-table th { background: #0B5697; color: white; padding: 4px 3px; font-size: 7.5px; font-weight: 700; text-align: center; border: 1px solid #094578; }
        .notes-table td { padding: 3px; border: 1px solid #ddd; font-size: 8.5px; text-align: center; }
        .notes-table .matiere-cell { text-align: left; padding-left: 6px; font-weight: 600; }
        .notes-table tr:nth-child(even) td { background: #f8fbff; }

        .total-row td { background: #eef3f9 !important; font-weight: 800; border-top: 2px solid #0B5697; }

        .note-good { color: #28a745; font-weight: 700; }
        .note-bad { color: #dc3545; font-weight: 700; }

        .resume-box { border: 2px solid #0B5697; border-radius: 6px; padding: 6px; text-align: center; display: inline-block; }
        .big-number { font-size: 22px; font-weight: 900; display: block; }
        .label-small { font-size: 7px; color: #666; text-transform: uppercase; }

        .decision-section { margin-top: 8px; padding: 6px; border: 2px solid #0FBCAF; border-radius: 5px; background: #f0faf9; }

        .signatures { margin-top: 12px; width: 100%; }
        .signatures td { width: 33%; text-align: center; padding-top: 5px; font-size: 8px; vertical-align: top; }
        .sig-title { font-weight: 700; color: #0B5697; margin-bottom: 25px; }

        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 4px; }
        .watermark { position: fixed; top: 40%; left: 15%; font-size: 55px; color: rgba(11, 86, 151, 0.03); transform: rotate(-30deg); font-weight: 900; z-index: -1; }
    </style>
</head>
<body>
    <div class="watermark">AGREE SIKUL</div>
    <div class="page">
        <div class="header-ecole">
            <div class="ecole-nom">{{ $ecole?->nom ?? 'ÉTABLISSEMENT SCOLAIRE' }}</div>
            <div class="ecole-detail">{{ $ecole?->adresse ?? '' }} | Tél : {{ $ecole?->telephone ?? '' }}</div>
        </div>

        <div class="bulletin-title">BULLETIN DE NOTES — {{ strtoupper($periode) }}</div>

        <table class="eleve-info">
            <tr>
                <td class="label">Nom et Prénoms</td>
                <td colspan="3"><strong>{{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</strong></td>
            </tr>
            <tr>
                <td class="label">Matricule</td>
                <td>{{ $apprenant?->matricule }}</td>
                <td class="label">Classe</td>
                <td><strong>{{ $classe?->nom }}</strong></td>
            </tr>
            <tr>
                <td class="label">Année Scolaire</td>
                <td>{{ $annee }}</td>
                <td class="label">Né(e) le</td>
                <td>{{ $apprenant?->date_naissance?->format('d/m/Y') ?? '-' }}</td>
            </tr>
        </table>

        <!-- TABLEAU AVEC SÉQUENCES -->
        <table class="notes-table">
            <thead>
                <tr>
                    <th style="width: 25%; text-align: left; padding-left: 6px;">Disciplines</th>
                    <th style="width: 8%;">Séq. 1</th>
                    <th style="width: 8%;">Séq. 2</th>
                    <th style="width: 8%;">Séq. 3</th>
                    <th style="width: 8%;">Examen</th>
                    <th style="width: 9%;">Moy./20</th>
                    <th style="width: 7%;">Coef.</th>
                    <th style="width: 10%;">M. x Coef.</th>
                    <th style="width: 17%;">Appréciation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matieres as $matiere)
                <tr>
                    <td class="matiere-cell">{{ $matiere['libelle'] }}</td>
                    <td>{{ $matiere['seq1'] !== null ? number_format($matiere['seq1'], 1) : '-' }}</td>
                    <td>{{ $matiere['seq2'] !== null ? number_format($matiere['seq2'], 1) : '-' }}</td>
                    <td>{{ $matiere['seq3'] !== null ? number_format($matiere['seq3'], 1) : '-' }}</td>
                    <td>{{ $matiere['exam'] !== null ? number_format($matiere['exam'], 1) : '-' }}</td>
                    <td>
                        <span class="{{ ($matiere['moyenne'] ?? 0) >= 10 ? 'note-good' : 'note-bad' }}">
                            {{ $matiere['moyenne'] !== null ? number_format($matiere['moyenne'], 2) : '-' }}
                        </span>
                    </td>
                    <td>{{ $matiere['coefficient'] }}</td>
                    <td><strong>{{ number_format($matiere['moyenne_coef'], 2) }}</strong></td>
                    <td style="text-align: left; padding-left: 4px; font-size: 7.5px;">{{ $matiere['appreciation'] }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td class="matiere-cell">TOTAL</td>
                    <td colspan="4"></td>
                    <td></td>
                    <td><strong>{{ $totalCoef }}</strong></td>
                    <td><strong>{{ number_format($totalMoyCoef, 2) }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- MOYENNE GÉNÉRALE -->
        <table style="width: 100%; margin-bottom: 6px;">
            <tr>
                <td style="width: 30%;">
                    @php $isPass = $moyenneGenerale >= 10; @endphp
                    <div class="resume-box" style="border-color: {{ $isPass ? '#28a745' : '#dc3545' }}; width: 100%;">
                        <span class="label-small">Moyenne Générale</span>
                        <span class="big-number" style="color: {{ $isPass ? '#28a745' : '#dc3545' }};">{{ number_format($moyenneGenerale, 2) }}/20</span>
                    </div>
                </td>
                <td style="width: 15%; padding-left: 8px;">
                    <div class="resume-box" style="width: 100%;">
                        <span class="label-small">Rang</span>
                        <span class="big-number" style="color: #0B5697;">{{ $rang ?? '-' }}</span>
                    </div>
                </td>
                <td style="width: 55%; padding-left: 8px;">
                    <div class="decision-section" style="margin-top: 0;">
                        <div style="font-weight: 800; color: #0B5697; font-size: 9px;">Décision du Conseil :</div>
                        <div style="font-size: 10px; font-weight: 700;">{{ $decision ?? 'En attente' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- SIGNATURES -->
        <table class="signatures">
            <tr>
                <td><div class="sig-title">Le Professeur Principal</div><div style="height: 30px;"></div>Signature</td>
                <td><div class="sig-title">Le Parent / Tuteur</div><div style="height: 30px;"></div>Signature</td>
                <td><div class="sig-title">Le Directeur</div><div style="height: 30px;"></div>Fait le {{ $date }}</td>
            </tr>
        </table>

        <div class="footer">AGREE SIKUL — Plateforme de Gestion Scolaire — {{ $date }}</div>
    </div>
</body>
</html>

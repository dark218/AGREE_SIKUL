<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin - {{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 15px 20px; }

        /* === EN-TÊTE ÉCOLE === */
        .header-ecole {
            width: 100%;
            border-bottom: 3px solid #0B5697;
            padding-bottom: 10px;
            margin-bottom: 8px;
        }
        .header-ecole table { width: 100%; }
        .header-ecole .logo-cell { width: 80px; vertical-align: middle; }
        .header-ecole .logo-placeholder {
            width: 65px; height: 65px;
            border: 2px solid #0B5697;
            border-radius: 8px;
            text-align: center;
            line-height: 65px;
            font-size: 10px;
            color: #0B5697;
            font-weight: bold;
        }
        .header-ecole .info-cell { text-align: center; vertical-align: middle; }
        .header-ecole .ecole-nom { font-size: 16px; font-weight: 800; color: #0B5697; }
        .header-ecole .ecole-detail { font-size: 9px; color: #555; margin-top: 2px; }
        .header-ecole .photo-cell { width: 70px; vertical-align: middle; text-align: right; }
        .header-ecole .photo-placeholder {
            width: 60px; height: 75px;
            border: 1px solid #ccc;
            text-align: center;
            line-height: 75px;
            font-size: 8px;
            color: #aaa;
            display: inline-block;
        }

        /* === TITRE BULLETIN === */
        .bulletin-title {
            text-align: center;
            margin: 8px 0;
            padding: 6px 0;
            background: linear-gradient(90deg, #0B5697, #0FBCAF);
            color: white;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* === INFOS ÉLÈVE === */
        .eleve-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border: 1px solid #ddd;
        }
        .eleve-info td {
            padding: 4px 8px;
            font-size: 9px;
            border: 1px solid #eee;
        }
        .eleve-info .label { font-weight: 700; color: #0B5697; width: 20%; background: #f8fbff; }
        .eleve-info .value { width: 30%; }

        /* === TABLEAU DES NOTES === */
        .notes-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .notes-table th {
            background: #0B5697;
            color: white;
            padding: 5px 4px;
            font-size: 8px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            border: 1px solid #094578;
        }
        .notes-table td {
            padding: 4px;
            border: 1px solid #ddd;
            font-size: 9px;
            text-align: center;
        }
        .notes-table .matiere-cell {
            text-align: left;
            padding-left: 8px;
            font-weight: 600;
        }
        .notes-table tr:nth-child(even) td { background: #f8fbff; }
        .notes-table tr:hover td { background: #eef5fc; }

        /* Notes coloration */
        .note-excellent { color: #0B5697; font-weight: 800; }
        .note-bien { color: #28a745; font-weight: 700; }
        .note-passable { color: #E5590C; font-weight: 600; }
        .note-insuffisant { color: #dc3545; font-weight: 700; }

        /* Total row */
        .total-row td {
            background: #f0f5fa !important;
            font-weight: 800;
            font-size: 10px;
            border-top: 2px solid #0B5697;
        }

        /* === RÉSUMÉ === */
        .resume-section {
            width: 100%;
            margin-bottom: 8px;
        }
        .resume-section table { width: 100%; border-collapse: collapse; }
        .resume-box {
            border: 2px solid #0B5697;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .resume-box .big-number {
            font-size: 24px;
            font-weight: 900;
            display: block;
        }
        .resume-box .label-small {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
        }
        .pass { border-color: #28a745; }
        .pass .big-number { color: #28a745; }
        .fail { border-color: #dc3545; }
        .fail .big-number { color: #dc3545; }

        /* === STATS CLASSE === */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .stats-table td {
            padding: 4px 8px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        .stats-table .stat-label { font-weight: 700; color: #0B5697; background: #f8fbff; width: 40%; }

        /* === DÉCISION & SIGNATURES === */
        .decision-section {
            margin-top: 8px;
            padding: 8px;
            border: 2px solid #0FBCAF;
            border-radius: 6px;
            background: #f0faf9;
        }
        .decision-title { font-weight: 800; color: #0B5697; font-size: 10px; margin-bottom: 4px; }
        .decision-text { font-size: 11px; font-weight: 700; }

        .signatures {
            margin-top: 15px;
            width: 100%;
        }
        .signatures td {
            width: 33%;
            text-align: center;
            padding-top: 5px;
            font-size: 9px;
            vertical-align: top;
        }
        .sig-title { font-weight: 700; color: #0B5697; margin-bottom: 30px; }

        /* === FOOTER === */
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 7px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }

        /* === WATERMARK === */
        .watermark {
            position: fixed;
            top: 40%;
            left: 15%;
            font-size: 60px;
            color: rgba(11, 86, 151, 0.03);
            transform: rotate(-30deg);
            font-weight: 900;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="watermark">AGREE SIKUL</div>
    <div class="page">
        <!-- EN-TÊTE ÉCOLE -->
        <div class="header-ecole">
            <table>
                <tr>
                    <td class="logo-cell">
                        <div class="logo-placeholder">LOGO</div>
                    </td>
                    <td class="info-cell">
                        <div class="ecole-nom">{{ $ecole?->nom ?? 'ÉTABLISSEMENT SCOLAIRE' }}</div>
                        <div class="ecole-detail">{{ $ecole?->adresse ?? '' }}</div>
                        <div class="ecole-detail">Tél : {{ $ecole?->telephone ?? '' }} | Email : {{ $ecole?->email ?? '' }}</div>
                    </td>
                    <td class="photo-cell">
                        <div class="photo-placeholder">Photo</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- TITRE -->
        <div class="bulletin-title">
            BULLETIN DE NOTES — {{ strtoupper($periode) }}
        </div>

        <!-- INFOS ÉLÈVE -->
        <table class="eleve-info">
            <tr>
                <td class="label">Nom et Prénoms</td>
                <td class="value" colspan="3"><strong>{{ $apprenant?->prenoms }} {{ $apprenant?->nom }}</strong></td>
            </tr>
            <tr>
                <td class="label">Matricule</td>
                <td class="value">{{ $apprenant?->matricule }}</td>
                <td class="label">Classe</td>
                <td class="value"><strong>{{ $classe?->nom }}</strong></td>
            </tr>
            <tr>
                <td class="label">Date de Naissance</td>
                <td class="value">{{ $apprenant?->date_naissance?->format('d/m/Y') ?? '-' }}</td>
                <td class="label">Année Scolaire</td>
                <td class="value"><strong>{{ $annee }}</strong></td>
            </tr>
            <tr>
                <td class="label">Sexe</td>
                <td class="value">{{ $apprenant?->sexe ?? '-' }}</td>
                <td class="label">Effectif Classe</td>
                <td class="value">{{ $effectif }} élèves</td>
            </tr>
        </table>

        <!-- TABLEAU DES NOTES -->
        <table class="notes-table">
            <thead>
                <tr>
                    <th style="width: 30%; text-align: left; padding-left: 8px;">Disciplines</th>
                    <th style="width: 10%;">Moy./20</th>
                    <th style="width: 8%;">Coef.</th>
                    <th style="width: 12%;">Moy. x Coef.</th>
                    <th style="width: 8%;">Rang</th>
                    <th style="width: 32%;">Appréciation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matieres as $matiere)
                <tr>
                    <td class="matiere-cell">{{ $matiere['libelle'] }}</td>
                    <td>
                        @php
                            $note = $matiere['moyenne'];
                            $noteClass = $note >= 16 ? 'note-excellent' : ($note >= 12 ? 'note-bien' : ($note >= 10 ? 'note-passable' : 'note-insuffisant'));
                        @endphp
                        <span class="{{ $noteClass }}">{{ $note !== null ? number_format($note, 2) : '-' }}</span>
                    </td>
                    <td>{{ $matiere['coefficient'] }}</td>
                    <td><strong>{{ number_format($matiere['moyenne_coef'], 2) }}</strong></td>
                    <td>{{ $matiere['rang'] ?? '-' }}</td>
                    <td style="text-align: left; padding-left: 6px; font-size: 8px;">{{ $matiere['appreciation'] }}</td>
                </tr>
                @endforeach
                <!-- TOTAL -->
                <tr class="total-row">
                    <td class="matiere-cell">TOTAL</td>
                    <td></td>
                    <td><strong>{{ $totalCoef }}</strong></td>
                    <td><strong>{{ number_format($totalMoyCoef, 2) }}</strong></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- RÉSUMÉ -->
        <table style="width: 100%; margin-bottom: 8px;">
            <tr>
                <td style="width: 25%; padding-right: 8px;">
                    @php $isPass = $moyenneGenerale >= 10; @endphp
                    <div class="resume-box {{ $isPass ? 'pass' : 'fail' }}">
                        <span class="label-small">Moyenne Générale</span>
                        <span class="big-number">{{ number_format($moyenneGenerale, 2) }}/20</span>
                    </div>
                </td>
                <td style="width: 15%; padding-right: 8px;">
                    <div class="resume-box">
                        <span class="label-small">Rang</span>
                        <span class="big-number" style="color: #0B5697;">{{ $rang ?? '-' }}</span>
                        <span class="label-small">/ {{ $effectif }}</span>
                    </div>
                </td>
                <td style="width: 60%;">
                    <table class="stats-table">
                        <tr>
                            <td class="stat-label">Moyenne de la classe</td>
                            <td>{{ $moyenneClasse }}/20</td>
                        </tr>
                        <tr>
                            <td class="stat-label">Premier de la classe</td>
                            <td>{{ $premierClasse }}/20</td>
                        </tr>
                        <tr>
                            <td class="stat-label">Dernier de la classe</td>
                            <td>{{ $dernierClasse }}/20</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- DÉCISION DU CONSEIL -->
        <div class="decision-section">
            <div class="decision-title">Décision du Conseil de Classe :</div>
            <div class="decision-text">
                {{ $decision ?? 'En attente de délibération' }}
            </div>
            <div style="font-size: 8px; color: #555; margin-top: 3px;">
                Appréciation générale : <strong>{{ $appreciation }}</strong>
            </div>
        </div>

        <!-- SIGNATURES -->
        <table class="signatures">
            <tr>
                <td>
                    <div class="sig-title">Le Professeur Principal</div>
                    <div style="height: 35px;"></div>
                    <div>Signature & Cachet</div>
                </td>
                <td>
                    <div class="sig-title">Le Parent / Tuteur</div>
                    <div style="height: 35px;"></div>
                    <div>Signature</div>
                </td>
                <td>
                    <div class="sig-title">Le Directeur</div>
                    <div style="height: 35px;"></div>
                    <div>Signature & Cachet</div>
                    <div style="font-size: 8px; margin-top: 5px;">Fait à ................, le {{ $date }}</div>
                </td>
            </tr>
        </table>

        <!-- FOOTER -->
        <div class="footer">
            Document généré automatiquement par AGREE SIKUL — Plateforme de Gestion Scolaire — {{ $date }}
        </div>
    </div>
</body>
</html>

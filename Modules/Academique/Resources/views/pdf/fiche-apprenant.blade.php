<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche - {{ $apprenant->prenoms }} {{ $apprenant->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 15px 22px; }
        .watermark { position: fixed; top: 38%; left: 15%; font-size: 60px; color: rgba(11,86,151,0.03); transform: rotate(-30deg); font-weight: 900; z-index: -1; }
        .header { width: 100%; border-bottom: 3px solid #0B5697; padding-bottom: 8px; margin-bottom: 8px; }
        .header table { width: 100%; }
        .logo-box { width: 60px; height: 60px; border: 2px solid #0B5697; border-radius: 8px; text-align: center; line-height: 60px; font-size: 9px; color: #0B5697; font-weight: 700; }
        .ecole-nom { font-size: 14px; font-weight: 800; color: #0B5697; text-align: center; }
        .ecole-sub { font-size: 8px; color: #555; text-align: center; }
        .title-bar { text-align: center; margin: 6px 0; padding: 5px; background: linear-gradient(90deg, #0B5697, #0FBCAF); color: white; font-size: 12px; font-weight: 800; letter-spacing: 2px; }
        .photo-box { width: 80px; height: 100px; border: 2px solid #0B5697; border-radius: 6px; text-align: center; line-height: 100px; font-size: 8px; color: #aaa; float: right; margin-left: 10px; }
        .info-tbl { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .info-tbl td { padding: 4px 8px; font-size: 9.5px; border: 1px solid #eee; }
        .info-tbl .lbl { font-weight: 700; color: #0B5697; background: #f8fbff; width: 25%; }
        .section-title { font-size: 11px; font-weight: 800; color: #0B5697; border-bottom: 2px solid #0FBCAF; padding-bottom: 3px; margin: 10px 0 6px; }
        .footer { margin-top: 15px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header">
        <table><tr>
            <td style="width:70px; vertical-align:middle;"><div class="logo-box">LOGO</div></td>
            <td style="text-align:center; vertical-align:middle;">
                <div class="ecole-nom">{{ $ecole ?? 'ÉTABLISSEMENT SCOLAIRE' }}</div>
                <div class="ecole-sub">{{ $adresse ?? '' }}</div>
            </td>
        </tr></table>
    </div>
    <div class="title-bar">FICHE INDIVIDUELLE DE L'APPRENANT</div>
    <div class="photo-box">Photo</div>
    <div class="section-title">Identité</div>
    <table class="info-tbl">
        <tr><td class="lbl">Nom</td><td><strong>{{ $apprenant->nom }}</strong></td><td class="lbl">Prénom(s)</td><td><strong>{{ $apprenant->prenoms }}</strong></td></tr>
        <tr><td class="lbl">Matricule</td><td>{{ $apprenant->matricule }}</td><td class="lbl">Sexe</td><td>{{ $apprenant->sexe ?? '-' }}</td></tr>
        <tr><td class="lbl">Date de naissance</td><td>{{ $apprenant->date_naissance?->format('d/m/Y') ?? '-' }}</td><td class="lbl">Lieu de naissance</td><td>{{ $apprenant->lieu_naissance ?? '-' }}</td></tr>
        <tr><td class="lbl">Nationalité</td><td>{{ $apprenant->nationalite ?? '-' }}</td><td class="lbl">Groupe sanguin</td><td>{{ $apprenant->groupe_sanguin ?? '-' }}</td></tr>
    </table>
    <div class="section-title">Scolarité</div>
    <table class="info-tbl">
        <tr><td class="lbl">Classe</td><td><strong>{{ $classe ?? '-' }}</strong></td><td class="lbl">Année scolaire</td><td><strong>{{ $annee ?? '-' }}</strong></td></tr>
        <tr><td class="lbl">École</td><td>{{ $ecole ?? '-' }}</td><td class="lbl">N° inscription</td><td>{{ $apprenant->numero_inscription ?? '-' }}</td></tr>
        <tr><td class="lbl">Date d'entrée</td><td>{{ $apprenant->date_entree_ecole?->format('d/m/Y') ?? '-' }}</td><td class="lbl">Interne</td><td>{{ $apprenant->est_interne ? 'Oui' : 'Non' }}</td></tr>
    </table>
    <div class="section-title">Contact / Famille</div>
    <table class="info-tbl">
        <tr><td class="lbl">Téléphone</td><td>{{ $apprenant->telephone ?? '-' }}</td><td class="lbl">Email</td><td>{{ $apprenant->email ?? '-' }}</td></tr>
        <tr><td class="lbl">Adresse</td><td colspan="3">{{ $apprenant->adresse ?? '-' }}</td></tr>
        <tr><td class="lbl">Nom du père</td><td>{{ $pere ?? '-' }}</td><td class="lbl">Nom de la mère</td><td>{{ $mere ?? '-' }}</td></tr>
        <tr><td class="lbl">Tuteur</td><td>{{ $tuteur ?? '-' }}</td><td class="lbl">Responsable légal</td><td>{{ $responsable_legal ?? '-' }}</td></tr>
    </table>
    <div class="footer">AGREE SIKUL — Fiche Apprenant — Généré le {{ $date }}</div>
</div>
</body>
</html>

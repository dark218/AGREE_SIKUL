<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation - {{ $apprenant->prenoms }} {{ $apprenant->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        .page { padding: 30px 40px; }
        .watermark { position: fixed; top: 38%; left: 15%; font-size: 60px; color: rgba(11,86,151,0.04); transform: rotate(-30deg); font-weight: 900; z-index: -1; }
        .header { text-align: center; border-bottom: 3px solid #0B5697; padding-bottom: 12px; margin-bottom: 15px; }
        .ecole-nom { font-size: 16px; font-weight: 800; color: #0B5697; }
        .ecole-sub { font-size: 9px; color: #555; }
        .title { text-align: center; margin: 20px 0; }
        .title h1 { font-size: 18px; font-weight: 800; color: #0B5697; text-transform: uppercase; letter-spacing: 3px; border-bottom: 3px double #0FBCAF; display: inline-block; padding-bottom: 5px; }
        .ref { text-align: center; font-size: 10px; color: #777; margin-bottom: 25px; }
        .body-text { line-height: 2; font-size: 12px; text-align: justify; margin-bottom: 20px; }
        .body-text strong { color: #0B5697; }
        .info-box { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin-bottom: 20px; background: #f8fbff; }
        .info-box table { width: 100%; }
        .info-box td { padding: 3px 6px; font-size: 10px; }
        .info-box .lbl { font-weight: 700; color: #0B5697; width: 30%; }
        .validity { text-align: center; margin: 20px 0; font-size: 10px; color: #777; font-style: italic; }
        .signature { margin-top: 30px; text-align: right; padding-right: 40px; }
        .sig-title { font-weight: 700; color: #0B5697; font-size: 11px; }
        .sig-date { font-size: 10px; color: #555; margin-bottom: 40px; }
        .footer { margin-top: 30px; text-align: center; font-size: 7px; color: #aaa; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
<div class="watermark">AGREE SIKUL</div>
<div class="page">
    <div class="header">
        <div class="ecole-nom">{{ $ecole ?? 'ÉTABLISSEMENT SCOLAIRE' }}</div>
        <div class="ecole-sub">{{ $adresse ?? '' }} | Tél : {{ $telephone ?? '' }}</div>
    </div>
    <div class="title"><h1>Attestation d'Inscription</h1></div>
    <div class="ref">Réf. N° {{ $reference ?? 'ATT-' . date('Y') . '-' . str_pad($apprenant->id, 4, '0', STR_PAD_LEFT) }}</div>
    <div class="body-text">
        Le Directeur de <strong>{{ $ecole ?? 'l\'établissement' }}</strong> atteste par la présente que :
        <br><br>
        <strong>{{ $apprenant->sexe === 'F' ? 'Mademoiselle' : 'Monsieur' }} {{ $apprenant->prenoms }} {{ $apprenant->nom }}</strong>,
        né{{ $apprenant->sexe === 'F' ? 'e' : '' }} le <strong>{{ $apprenant->date_naissance?->format('d/m/Y') ?? '...' }}</strong>
        à <strong>{{ $apprenant->lieu_naissance ?? '...' }}</strong>,
        matricule <strong>{{ $apprenant->matricule }}</strong>,
        <br><br>
        est régulièrement inscrit{{ $apprenant->sexe === 'F' ? 'e' : '' }} dans notre établissement
        en classe de <strong>{{ $classe ?? '...' }}</strong>
        pour l'année scolaire <strong>{{ $annee ?? '...' }}</strong>.
    </div>
    <div class="info-box">
        <table>
            <tr><td class="lbl">Numéro d'inscription</td><td>{{ $apprenant->numero_inscription ?? $apprenant->matricule }}</td></tr>
            <tr><td class="lbl">Classe</td><td><strong>{{ $classe ?? '-' }}</strong></td></tr>
            <tr><td class="lbl">Année scolaire</td><td><strong>{{ $annee ?? '-' }}</strong></td></tr>
            <tr><td class="lbl">Statut</td><td>{{ $statut ?? 'Inscrit(e)' }}</td></tr>
        </table>
    </div>
    <div class="validity">Cette attestation est délivrée à l'intéressé{{ $apprenant->sexe === 'F' ? 'e' : '' }} pour servir et valoir ce que de droit.</div>
    <div class="signature">
        <div class="sig-date">Fait à ................, le {{ $date }}</div>
        <div class="sig-title">Le Directeur</div>
        <div style="height: 50px;"></div>
        <div style="font-size: 9px;">Signature et Cachet</div>
    </div>
    <div class="footer">AGREE SIKUL — Attestation d'Inscription — {{ $date }}</div>
</div>
</body>
</html>

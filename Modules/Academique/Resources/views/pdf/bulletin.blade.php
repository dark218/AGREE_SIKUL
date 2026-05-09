<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulletin - {{ $data['apprenant']['prenoms'] }} {{ $data['apprenant']['nom'] }}</title>
    <style>
        body { font-family: Arial; margin: 40px; color: #333; }
        .header { text-align: center; border-bottom: 3px solid #0B5697; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #0B5697; }
        .student-info { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-left: 4px solid #E5590C; }
        .info-line { margin: 5px 0; }
        .info-label { font-weight: bold; color: #0B5697; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background-color: #0FBCAF; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .summary { display: flex; gap: 15px; margin-top: 20px; }
        .box { flex: 1; padding: 15px; text-align: center; color: white; }
        .avg-box { background-color: #E5590C; }
        .rank-box { background-color: #0FBCAF; }
        .decision-box { background-color: #0B5697; }
        .box-value { font-size: 24px; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="color: #0FBCAF; font-size: 14px;">SIKUL/AGREE</div>
        <h1>📋 BULLETIN SCOLAIRE</h1>
        <div style="color: #666;">{{ $data['annee_scolaire'] }}</div>
    </div>

    <div class="student-info">
        <div class="info-line"><span class="info-label">Élève:</span> {{ $data['apprenant']['prenoms'] }} {{ $data['apprenant']['nom'] }}</div>
        <div class="info-line"><span class="info-label">Matricule:</span> {{ $data['apprenant']['matricule'] }}</div>
        <div class="info-line"><span class="info-label">Classe:</span> {{ $data['classe']['nom'] }}</div>
        <div class="info-line"><span class="info-label">Période:</span> {{ $data['periode'] }}</div>
    </div>

    <h3 style="color: #0B5697; border-bottom: 2px solid #0B5697; padding-bottom: 10px;">📚 Résultats par Matière</h3>
    <table>
        <thead>
            <tr>
                <th>Matière</th>
                <th>Coefficient</th>
                <th>Note</th>
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['moyennes_matieres'] as $m)
                <tr>
                    <td>{{ $m['matiere'] }}</td>
                    <td>{{ $m['coefficient'] }}</td>
                    <td style="font-weight: bold;">{{ number_format($m['note'], 2) }}/20</td>
                    <td>{{ $m['appreciation'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align: center; color: #999;">Aucune note enregistrée</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 style="color: #0B5697; border-bottom: 2px solid #0B5697; padding-bottom: 10px;">📊 Récapitulatif</h3>
    <div class="summary">
        <div class="box avg-box">
            <div>Moyenne Générale</div>
            <div class="box-value">{{ number_format($data['moyenne_generale'], 2) }}/20</div>
        </div>
        <div class="box rank-box">
            <div>Rang</div>
            <div class="box-value">#{{ $data['rang'] }}</div>
        </div>
        <div class="box decision-box">
            <div>Décision</div>
            <div class="box-value">{{ ucfirst($data['decision_conseil']) }}</div>
        </div>
    </div>

    <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #999;">
        <p>Bulletin généré par SIKUL/AGREE - {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>

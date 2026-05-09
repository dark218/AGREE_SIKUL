<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bienvenue sur {{ env('APP_NAME') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; background-color: #f4f4f4; padding: 30px;">
<div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <h2 style="color: #2c3e50;">{{ __('Bonjour') }} {{ $nom }}, 👋</h2>

    <p style="font-size: 16px; color: #333;">
        🎉 {{ __('Bienvenue dans') }} <strong style="color: #007bff;">{{ env('APP_NAME') }}</strong> !<br><br>

        Nous sommes ravis de vous accueillir parmi nous.<br>
        Préparez-vous à vivre une expérience exceptionnelle au sein de notre communauté.
    </p>

    <p style="font-size: 16px; color: #333;">
        Si vous avez des questions ou besoin d’assistance, notre équipe est là pour vous accompagner à chaque étape.
    </p>

    <p style="font-size: 16px; color: #333;">
        Encore une fois, bienvenue sur <strong>{{ env('APP_NAME') }}</strong> ! 🚀
    </p>

    <p style="margin-top: 40px; color: #999; font-size: 14px;">
        — L’équipe {{ env('APP_NAME') }}
    </p>
</div>
</body>
</html>

@php use Illuminate\Support\Str; @endphp
    <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation de votre mot de passe - {{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; background-color: #f4f4f4; padding: 30px;">
<div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <h2 style="color: #2c3e50;">Bonjour,</h2>

    <p style="font-size: 16px; color: #333;">
        Vous avez récemment demandé la réinitialisation de votre mot de passe pour votre compte sur <strong style="color: #007bff;">{{ config('app.name') }}</strong>.
    </p>

    <p style="font-size: 16px; color: #333;">
        Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe :
    </p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $url }}" style="display: inline-block; background-color: #007bff; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 5px; font-weight: bold;">
            Réinitialiser mon mot de passe
        </a>
    </div>

    <p style="font-size: 14px; color: #666;">
        Ce lien est valable pendant <strong>60 minutes</strong> et ne peut être utilisé qu’une seule fois.
    </p>

    <p style="font-size: 14px; color: #666;">
        Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet email.
    </p>

    <p style="margin-top: 40px; color: #999; font-size: 14px;">
        — L’équipe {{ config('app.name') }}
    </p>
</div>
</body>
</html>

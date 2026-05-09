@component('mail::message')
    <p style="font-weight: bold">Bienvenue {{$nom}} sur SmilPay. </p>
    <p>Votre code marchand/Login est : <span style="font-weight: bold;color: red">{{$login}}</span></p>
    <p>Pour définir votre mot de passe, cliquer sur le lien ci-dessous</p>
        @component('mail::button', ['url' => env('URL_EMAIL').$token])
             Reinitialiser mot de pass
        @endcomponent
@endcomponent

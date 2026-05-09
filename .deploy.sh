#!/bin/bash

# Supprimer les fichiers existants
#rm -Rf vendor composer.lock .idea;
#build
npm run build

# Se déplacer d'un niveau vers le répertoire parent
cd ..;

# Créer une archive zip du projet
#zip -r smilpaybio.zip SmilPay-V2;
zip -r smilpaybio.zip SmilPay-V2 --exclude "SmilPay-V2/public/files/*";

# Transférer l'archive vers le serveur distant
scp smilpaybio.zip amyv4492@roux.o2switch.net:/home/amyv4492/;

# Supprimer l'archive locale
rm smilpaybio.zip;

# Connexion SSH pour exécuter des commandes sur le serveur distant
ssh amyv4492@roux.o2switch.net'
    cd /home/amyv4492/smil_pay_bio;
    mv public/images/ ../files_save_smil_pay_bio;
    rm -Rf *;
    rm -Rf .*;
    mv ../smilpaybio.zip .;
    unzip smilpaybio.zip;
    rm smilpaybio.zip;
    cd SmilPay-V2;
    mv * .* ..;
    cd ..;
    rm -rf SmilPay-V2;
    composer install;
#    npm install;
    mv .env.example .env;
    php artisan key:generate;
#    php artisan migrate:fresh --seed;
    php artisan migrate --seed;
    php artisan route:cache;
    mv ../files_save_smil_pay_bio/* public/images/;
   php artisan l5-swagger:generate;
    rm ./.deploy.sh;
    cd ..;
    exit;';

# Revenir au répertoire d'origine
cd SmilPay-V2;

# Installer les dépendances à l'aide de Composer
composer install;
npm install;

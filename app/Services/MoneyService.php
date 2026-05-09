<?php

namespace App\Services;

class MoneyService
{
    /**
     * Liste des devises qui n'ont PAS de décimales
     * (minor unit = 0 selon ISO 4217)
     *
     * @return array
     */
    public static function currenciesWithoutDecimals(): array
    {
        return [
            // Franc CFA
            'XOF', // Franc CFA BCEAO
            'XAF', // Franc CFA BEAC

            // Autres devises sans décimales
            'JPY', // Yen japonais
            'KRW', // Won sud-coréen
            'VND', // Dong vietnamien
            'IDR', // Roupie indonésienne
            'CLP', // Peso chilien
            'PYG', // Guarani paraguayen
            'UGX', // Shilling ougandais
            'RWF', // Franc rwandais
            'BIF', // Franc burundais
            'DJF', // Franc djiboutien
            'GNF', // Franc guinéen
            'KMF', // Franc comorien
            'XPF', // Franc Pacifique
            'LAK', // Kip laotien
            'MGA', // Ariary malgache
            'MMK', // Kyat birman
            'IQD', // Dinar irakien (théoriquement 3 décimales mais utilisé sans)
            'IRR', // Rial iranien
            'SYP', // Livre syrienne
            'YER', // Rial yéménite
        ];
    }
    /**
     * Indique si une devise n'a pas de décimales
     *
     * @param string $currency
     * @return bool
     */
    public static function hasNoDecimals(string $currency): bool
    {
        return in_array(
            strtoupper($currency),
            self::currenciesWithoutDecimals(),
            true
        );
    }
    /**
     * Transforme un montant saisi par l'utilisateur
     * en valeur entière stockable en base de données.
     *
     * @param string|int|float $amount  Montant saisi (ex: "12,50", 2500)
     * @param string $currency          Code devise (XOF, EUR, USD, ...)
     *
     * @return int
     */
    public static function toDatabase($amount, string $currency): int
    {

        if ($amount === null || $amount === '') {
            return 0;
        }

        // Normalisation (remplacer , par .)
        $normalized = str_replace(',', '.', (string) $amount);

        // Devises sans décimales (FCFA, etc.)
        if (self::hasNoDecimals($currency)) {
            return (int) round((float) $normalized);
        }

        // Devises avec décimales (EUR, USD, etc.)
        return (int) round(((float) $normalized) * 100); //1050
    }

    /**
     * Transforme un montant stocké en base
     * en valeur lisible pour l'utilisateur.
     *
     * @param int $amountInDb  Valeur en base (prix_cents)
     * @param string $currency Code devise
     *
     * @return string
     */
    public static function toDisplay(int $amountInDb, string $currency): string
    {
        // Devises sans décimales
        if (self::hasNoDecimals($currency)) {
            return number_format($amountInDb, 0, '.', ' ');
        }

        // Devises avec décimales
        return number_format($amountInDb / 100, 2, '.', ' ');
    }
}

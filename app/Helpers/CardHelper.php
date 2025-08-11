<?php

namespace App\Helpers;

class CardHelper
{
    /**
     * Masque un numéro de carte bancaire en gardant les 4 premiers et 6 derniers chiffres
     */
    public static function maskCardNumber(string $cardNumber): string
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $cardNumber);

        if (strlen($cleanNumber) < 10) {
            return $cleanNumber;
        }

        $firstFour = substr($cleanNumber, 0, 4);
        $lastSix = substr($cleanNumber, -6);
        $middleLength = strlen($cleanNumber) - 10;

        return $firstFour.str_repeat('*', max(4, $middleLength)).$lastSix;
    }

    /**
     * Valide un numéro de carte bancaire en utilisant l'algorithme de Luhn
     */
    public static function validateCardNumber(string $cardNumber): bool
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $cardNumber);

        if (strlen($cleanNumber) < 13 || strlen($cleanNumber) > 19) {
            return false;
        }

        return self::luhnCheck($cleanNumber);
    }

    /**
     * Algorithme de Luhn pour valider les numéros de carte
     */
    private static function luhnCheck(string $number): bool
    {
        $sum = 0;
        $numDigits = strlen($number);
        $oddEven = $numDigits & 1;

        for ($i = 0; $i < $numDigits; $i++) {
            $digit = intval($number[$i]);

            if (! (($i & 1) ^ $oddEven)) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return ($sum % 10) === 0;
    }

    /**
     * Détermine le type de carte basé sur le numéro
     */
    public static function getCardType(string $cardNumber): string
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $cardNumber);

        if (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $cleanNumber)) {
            return 'visa';
        }

        if (preg_match('/^5[1-5][0-9]{14}$/', $cleanNumber)) {
            return 'mastercard';
        }

        if (preg_match('/^3[47][0-9]{13}$/', $cleanNumber)) {
            return 'amex';
        }

        return 'unknown';
    }

    /**
     * Formate un numéro de carte avec espaces tous les 4 chiffres et limite à 16 chiffres
     */
    public static function formatCardNumber(string $cardNumber): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $cardNumber);

        if (strlen($cleaned) > 16) {
            $cleaned = substr($cleaned, 0, 16);
        }

        $formatted = '';
        for ($i = 0; $i < strlen($cleaned); $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $formatted .= ' ';
            }
            $formatted .= $cleaned[$i];
        }

        return $formatted;
    }
}

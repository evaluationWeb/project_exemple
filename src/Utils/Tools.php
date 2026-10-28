<?php

namespace App\Utils;

class Tools
{
    /**
     * Méthode qui nettoie les entrées utilisateur
     * @param string $str chaine à nettoyer
     * @return string $str chaine nettoyée (sanitize) 
     */
    public static function sanitize(string $str): string
    {
        $str = trim($str);
        $str = strip_tags($str);
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        return $str;
    }

    /**
     * Méthode qui sanitize (nettoie) un tableau
     * @param array &$data Tableau à nettoyer
     * @return void 
     */
    public static function sanitizeArray(array &$data): void
    {
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $data[$key] = self::sanitize($value);
            }
        }
    }
}

<?php

namespace App\Helpers;

class TextHelper
{
    /**
     * Convierte texto a mayúsculas sin acentos
     */
    public static function toUpperWithoutAccents(?string $text): string
    {

        $unwanted_array = [
            'á'=>'A', 'à'=>'A', 'ä'=>'A', 'â'=>'A', 'ª'=>'A',
            'é'=>'E', 'è'=>'E', 'ë'=>'E', 'ê'=>'E',
            'í'=>'I', 'ì'=>'I', 'ï'=>'I', 'î'=>'I',
            'ó'=>'O', 'ò'=>'O', 'ö'=>'O', 'ô'=>'O',
            'ú'=>'U', 'ù'=>'U', 'ü'=>'U', 'û'=>'U',
            'ñ'=>'N',
            'Á'=>'A', 'À'=>'A', 'Ä'=>'A', 'Â'=>'A',
            'É'=>'E', 'È'=>'E', 'Ë'=>'E', 'Ê'=>'E',
            'Í'=>'I', 'Ì'=>'I', 'Ï'=>'I', 'Î'=>'I',
            'Ó'=>'O', 'Ò'=>'O', 'Ö'=>'O', 'Ô'=>'O',
            'Ú'=>'U', 'Ù'=>'U', 'Ü'=>'U', 'Û'=>'U',
            'Ñ'=>'N'
        ];

        $text = strtr(mb_strtoupper($text, 'UTF-8'), $unwanted_array);
        return $text;
    }

    public static function numberToWords($number): string
    {
        $number = round($number, 1);
        $integerPart = floor($number);
        $decimalPart = round(($number - $integerPart) * 10);

        $units = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $teens = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];

        $result = '';

        if ($integerPart == 10) {
            $result = 'diez';
        } elseif ($integerPart < 10) {
            $result = $units[$integerPart];
        }

        if ($decimalPart > 0) {
            $result .= ' punto ' . $units[$decimalPart];
        }

        return ucfirst(trim($result));
    }
}

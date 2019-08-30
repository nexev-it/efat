<?php

namespace Nexev\EFat\Entities\Abstracts;

class AbstractBaseClass {

    protected function format($numero): string
    {
        return number_format($numero, 2, '.', '');
    }

    /**
     * Pulisce i caratteri speciali dalle stringhe. Per XML
     * 
     * @param	Stringa da ripulire.
     * @return	string
     */
    protected function clearString(string $string): string
    {

        $string = htmlspecialchars(preg_replace('/[ ]{2,}/', ' ', strip_tags(str_replace('<', ' <', $string))), ENT_XML1);
        $string = str_replace('|', '-', $string);
        $string = str_replace('€', 'E.', $string);
        return preg_replace('/[^A-Za-z0-9, \.\-]/', '-', $string);
    }
    
    protected function getValorePercentuale(float $number): string
    {
        $percentuale = $number * 100;
        return number_format($percentuale, 2, '.', '');
    }

    protected function startsWith(string $string, string $start): bool
    {
        return (substr($string, 0, strlen($start)) === $start);
    }

    protected function endsWith(string $string, string $end): bool
    {
        $len = strlen($end);
        if ($len == 0) return true;
        return (substr($string, -$len) === $end); 
    }


}
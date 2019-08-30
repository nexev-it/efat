<?php

namespace Nexev\EFat;

use Nexev\EFat\Entities\Abstracts\AbstractFattura;
use Nexev\EFat\Entities\Interfaces\CessionarioInterface;

class FatturaPA extends AbstractFattura {

    public function __construct(string $numero, string $progressivoInvio, ?DateTime $data = null)
    {
        $this->formatoTrasmissione = 'FPA12';
        parent::__construct($numero, $progressivoInvio, $data);
    }


    /**
     * Override della funzione per permettere l'inserimento di cessionari di solo tipo PubblicaAmministrazione
     *
     * @param Interfaces\CessionarioInterface $cessionario
     * @return void
     */
    public function setCessionario(CessionarioInterface $cessionario): void
    {
        if(!is_a($cessionario, 'Nexev\EFat\Entities\PubblicaAmministrazione')) throw new \Exception("Il cessionario di una FatturaPA deve necessariamente essere una pubblica amministrazione");
        parent::setCessionario($cessionario);
    }
}
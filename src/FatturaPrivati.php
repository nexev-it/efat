<?php

namespace Nexev\EFat;

use DateTime;
use Nexev\EFat\Entities\Abstracts\AbstractFattura;
use Nexev\EFat\Entities\Interfaces\CessionarioInterface;

class FatturaPrivati extends AbstractFattura {

    public function __construct(string $numero, string $progressivoInvio, ?DateTime $data = null)
    {
        $this->formatoTrasmissione = 'FPR12';
        parent::__construct($numero, $progressivoInvio, $data);
    }

    /**
     * Override della funzione per NON permettere l'inserimento di cessionari di tipo PubblicaAmministrazione
     *
     * @param Interfaces\CessionarioInterface $cessionario
     * @return void
     */
    public function setCessionario(CessionarioInterface $cessionario): void
    {
        if (is_a($cessionario, 'Nexev\EFat\Entities\PubblicaAmministrazione')) throw new \Exception("Il cessionario di una FatturaPrivati non può essere una Pubblica Amministrazione");
        parent::setCessionario($cessionario);
    }
}

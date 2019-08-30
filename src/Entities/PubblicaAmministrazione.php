<?php

namespace Nexev\EFat\Entities;

use Nexev\EFat\Entities\Abstracts\AbstractPersonaGiuridica;
use Nexev\EFat\Entities\Interfaces\CessionarioInterface;

class PubblicaAmministrazione extends AbstractPersonaGiuridica implements CessionarioInterface {
    
    protected function init(): void
    {
        if (!$this->sdi) throw new \Exception("Non è stato impostato il codice SDI");
    }

    public function checkForCessionario(): bool
    {
        $this->errori = [];

        $return = true;
        if (!$this->sdi) {
            $return = false;
            $this->errori[] = "Non è stato impostato il codice SDI per la PA";
        }
        if (!$this->indirizzo) {
            $return = false;
            $this->errori[] = "Non è stato impostato un indirizzo per la PA";
        }

        return $return;
    }
}
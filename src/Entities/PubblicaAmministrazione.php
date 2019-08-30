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

        $error = false;
        if (!$this->sdi) {
            $error = true;
            $this->errori[] = "Non è stato impostato il codice SDI per la PA";
        }
        if (!$this->address) {
            $error = true;
            $this->errori[] = "Non è stato impostato un indirizzo per la PA";
        }

        return $error;
    }
}
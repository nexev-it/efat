<?php

namespace Nexev\EFat\Entities;

use Nexev\EFat\Entities\Abstracts\AbstractSoggetto;
use Nexev\EFat\Entities\Interfaces\CessionarioInterface;

class PersonaFisica extends AbstractSoggetto implements CessionarioInterface {

    protected function init(): void {
        if(!$this->pec && !$this->sdi) throw new \Exception("Non è stato impostato nè codice SDI nè PEC");
        if(!$this->indirizzo) throw new \Exception("Non è stato impostato un indirizzo per il Cessionario");
    }

    public function checkForCessionario(): bool
    {
        $this->errori = [];
        
        $return = true;
        if(!$this->pec && !$this->sdi) {
            $return = false;
            $this->errori[] = "Non è stato impostato nè codice SDI nè PEC per il Cessionario";
        }
        if(!$this->indirizzo) {
            $return = false;
            $this->errori[] = "Non è stato impostato un indirizzo per il Cessionario";
        }

        return $return;
    }
}

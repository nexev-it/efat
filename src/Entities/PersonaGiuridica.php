<?php

namespace Nexev\EFat\Entities;

use Nexev\EFat\Entities\REA;
use Nexev\EFat\Entities\Abstracts\AbstractPersonaGiuridica;
use Nexev\EFat\Entities\Interfaces\CedenteInterface;
use Nexev\EFat\Entities\Interfaces\CessionarioInterface;
use Nexev\EFat\Entities\Interfaces\TrasmittenteInterface;

class PersonaGiuridica extends AbstractPersonaGiuridica implements CessionarioInterface, CedenteInterface, TrasmittenteInterface {

    /**
     * Dati REA.
     */
    protected REA $rea;

    public function setREA(REA $rea): void
    {
        $this->rea = $rea;
    }

    public function getREA(): ?REA
    {
        return $this->rea;
    }

    public function hasREA(): bool
    {
        return (isset($this->rea) and $this->rea) ?? FALSE;
    }

    public function checkForCessionario(): bool
    {
        $this->errori = [];

        $return = true;
        if (!$this->pec && !$this->sdi) {
            $return = false;
            $this->errori[] = "Non è stato impostato nè codice SDI nè PEC per il Cessionario";
        }
        if (!$this->indirizzo) {
            $return = false;
            $this->errori[] = "Non è stato impostato un indirizzo per il Cessionario";
        }

        return $return;
    }

    public function checkForCedente(): bool
    {
        $this->errori = [];

        if (!$this->indirizzo) {
            $this->errori[] = "Non è stato impostato un indirizzo per il Cedente/Prestatore";
            return false;
        }

        return true;
    }

    public function checkForTrasmittente(): bool
    {
        return true;
    }

    public function compilaIdTrasmittente(\SimpleXMLElement $el): void
    {
        $el->addChild('IdPaese', 'IT');
        $el->addChild('IdCodice', $this->getPartitaIVA());
    }

    public function compilaCedentePrestatore(\SimpleXMLElement $el): void
    {
        $CPdatiAnagrafici = $el->addChild('DatiAnagrafici');
        $CPDAidFiscaleIva = $CPdatiAnagrafici->addChild('IdFiscaleIVA');
        $CPDAidFiscaleIva->addChild('IdPaese', $this->getPaese());
        $CPDAidFiscaleIva->addChild('IdCodice', $this->getPartitaIVA());
        $CPdatiAnagrafici->addChild('Anagrafica')->addChild('Denominazione', htmlspecialchars($this->getDenominazione(), ENT_XML1));
        $CPdatiAnagrafici->addChild('RegimeFiscale', $this->getRegimeFiscale());

        $CPsede = $el->addChild('Sede');
        $CPsede = $this->getIndirizzo()->compilaSede($CPsede);

        // controlla se il mittente è iscritto al REA
        if ($this->hasREA()) {

            $CPiscrizioneRea = $el->addChild('IscrizioneREA');
            $CPiscrizioneRea = $this->getREA()->compilaREA($CPiscrizioneRea);
        }
    }

    public function compilaTerzoIntermediario(\SimpleXMLElement $el): void
    {

        $TIdatiAnagrafici = $el->addChild('DatiAnagrafici');
        $TIDAidFiscaleIva = $TIdatiAnagrafici->addChild('IdFiscaleIVA');
        $TIDAidFiscaleIva->addChild('IdPaese', $this->getPaese());
        $TIDAidFiscaleIva->addChild('IdCodice', $this->getPartitaIVA()); // PARTITA IVA
        $TIdatiAnagrafici->addChild('Anagrafica')->addChild('Denominazione', htmlspecialchars($this->getDenominazione(), ENT_XML1));
    }
}
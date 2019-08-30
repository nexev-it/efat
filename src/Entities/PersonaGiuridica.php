<?php

namespace Nexev\EFat\Entities;

use Nexev\EFat\Entities\Abstracts\AbstractPersonaGiuridica;
use Nexev\EFat\Entities\Interfaces\CedenteInterface;
use Nexev\EFat\Entities\Interfaces\CessionarioInterface;
use Nexev\EFat\Entities\Interfaces\TrasmittenteInterface;

class PersonaGiuridica extends AbstractPersonaGiuridica implements CessionarioInterface, CedenteInterface, TrasmittenteInterface {

    /**
     * Dati REA
     *
     * @var \Nexev\EFat\Entities\REA
     */
    protected $rea;

    public function setREA(REA $rea): void
    {
        $this->rea = $rea;
    }

    public function getREA(): ? REA
    {
        return $this->rea;
    }

    public function hasREA(): bool
    {
        return !is_null($this->rea);
    }

    public function checkForCessionario(): bool
    {
        $this->errori = [];

        $error = false;
        if (!$this->pec && !$this->sdi) {
            $error = true;
            $this->errori[] = "Non è stato impostato nè codice SDI nè PEC per il Cessionario";
        }
        if (!$this->address) {
            $error = true;
            $this->errori[] = "Non è stato impostato un indirizzo per il Cessionario";
        }

        return $error;
    }

    public function checkForCedente(): bool
    {
        $this->errori = [];

        if (!$this->address) {
            $this->errori[] = "Non è stato impostato un indirizzo per il Cedente/Prestatore";
            return false;
        }

        return true;
    }

    public function checkForTrasmittente(): bool
    {
        return true;
    }

    public function compilaIdTrasmittente(\SimpleXMLElement $el): \SimpleXMLElement
    {
        $el->addChild('IdPaese', 'IT');
        $el->addChild('IdCodice', $this->getPartitaIVA());

        return $el;
    }

    public function compilaCedentePrestatore(\SimpleXMLElement $el): \SimpleXMLElement
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

        return $el;
    }

    public function compilaTerzoIntermediario(\SimpleXMLElement $el): \SimpleXMLElement
    {

        $TIdatiAnagrafici = $el->addChild('DatiAnagrafici');
        $TIDAidFiscaleIva = $TIdatiAnagrafici->addChild('IdFiscaleIVA');
        $TIDAidFiscaleIva->addChild('IdPaese', $this->getPaese());
        $TIDAidFiscaleIva->addChild('IdCodice', $this->getPartitaIVA()); // PARTITA IVA
        $TIdatiAnagrafici->addChild('Anagrafica')->addChild('Denominazione', htmlspecialchars($this->getDenominazione(), ENT_XML1));

        return $el;
    }
}
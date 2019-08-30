<?php

namespace Nexev\EFat\Entities;

use Nexev\EFat\Entities\Abstracts\AbstractBaseClass;

class Indirizzo extends AbstractBaseClass {
    
    protected $indirizzo;
    protected $citta;
    protected $provincia;
    protected $cap;
    protected $paese = 'IT';
    
    public function __construct(string $indirizzo, string $citta, string $provincia, string $cap) { 
        $this->setIndirizzo($indirizzo);
        $this->setCitta($citta);
        $this->setProvincia($provincia);
        $this->setCAP($cap);
    }

    public function setIndirizzo(string $indirizzo): void
    {
        if(strlen($indirizzo) < 5) throw new \Exception("Nome dell'indirizzo/via non valido");
        $this->indirizzo = strtoupper($indirizzo);
    }

    public function setCitta(string $citta): void
    {
        if(strlen($citta) < 3) throw new \Exception("Nome della città non valido");
        $this->citta = strtoupper($citta);
    }

    public function setProvincia(string $provincia): void
    {
        if(strlen($provincia) != 2) throw new \Exception("Provincia non valida: numero dei caratteri da utilizzare: 2");
        $this->provincia = strtoupper($provincia);
    }

    public function setCAP(string $cap): void
    {
        if(strlen($cap) != 5) throw new \Exception("CAP non valido: numero dei caratteri da utilizzare: 5");
        if(!is_numeric($cap)) throw new \Exception("CAP non valido: la stringa non contiene soltanto numeri");
        $this->cap = $cap;
    }

    public function getIndirizzo(): string
    {
        return $this->indirizzo;
    }

    public function getCitta(): string
    {
        return $this->citta;
    }

    public function getProvincia(): string
    {
        return $this->provincia;
    }

    public function getCAP(): string
    {
        return $this->cap;
    }

    public function getPaese(): string
    {
        return $this->paese;
    }

    public function compilaSede(\SimpleXMLElement $el): \SimpleXMLElement
    {
        $el->addChild('Indirizzo', $this->getIndirizzo());
        $el->addChild('CAP', $this->getCAP());
        $el->addChild('Comune', $this->getCitta());
        $el->addChild('Provincia', $this->getProvincia());
        $el->addChild('Nazione', $this->getPaese());

        return $el;
    }
}
<?php

namespace Nexev\EFat\Entities;

use Nexev\EFat\Entities\Abstracts\AbstractBaseClass;

class REA extends AbstractBaseClass {
    
    /**
     * Provincia dell'ufficio REA a cui si è iscritti
     *
     * @var string
     */
    private $ufficio;
    
    /**
     * Numero REA dell'azienda
     *
     * @var string
     */
    private $numero;
    
    /**
     * Capitale sociale dell'azienda (facoltativo)
     *
     * @var float
     */
    private $capitaleSociale;

    /**
     * Socio unico (boolean): SU: socio unico. SM: pluripersonale
     *
     * @var boolean
     */
    private $socioUnico;

    /**
     * Azienda in liquidazione (boolean): LS: in liquidazione. LN: non in liquidazione
     * Impostato di default a false
     * 
     * @var boolean
     */
    private $inLiquidazione;

    public function __construct(string $numero, string $ufficio, bool $socioUnico, ?float $capitaleSociale = null, bool $inLiquidazione = false) {
        $this->setNumero($numero);
        $this->setUfficio($ufficio);
        $this->setSocioUnico($socioUnico);
        $this->setCapitaleSociale($capitaleSociale);
        $this->setInLiquidazione($inLiquidazione);
    }


    /** SETTERS */

    public function setNumero(string $numero): void {
        // TODO: Controllare numero REA e come formattarlo
        if(strlen($numero) < 5) throw new \Exception("Numero REA troppo corto"); // TODO

        $this->numero = $numero;
    }

    public function setUfficio(string $ufficio): void
    {
        if(strlen($ufficio) != 2) throw new \Exception("Ufficio REA è la sigla della provincia composta da 2 cifre"); // TODO

        $this->ufficio = $ufficio;
    }

    public function setSocioUnico(bool $socioUnico): void
    {
        $this->socioUnico = $socioUnico;
    }

    public function setCapitaleSociale(?float $capitaleSociale): void
    {
        if(is_float($capitaleSociale) && $capitaleSociale < 0) throw new \Exception("Impossibile settare il capitale sociale con un numero negativo"); // TODO
        $this->capitaleSociale = $capitaleSociale;
    }

    public function setInLiquidazione(bool $inLiquidazione): void
    {
        $this->inLiquidazione = $inLiquidazione;
    }
    

    /** GETTERS */

    public function getUfficio(): string
    {
        return $this->ufficio;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getSocioUnico(): bool
    {
        return $this->socioUnico;
    }

    public function getCapitaleSociale(): ?float
    {
        return $this->capitaleSociale;
    }

    public function getInLiquidazione(): bool
    {
        return $this->inLiquidazione;
    }

    public function compilaREA(\SimpleXMLElement $el): void
    {
        $el->addChild('Ufficio', $this->getUfficio());
        $el->addChild('NumeroREA', $this->getNumero());

        if ($this->capitaleSociale) $el->addChild('CapitaleSociale', $this->format($this->getCapitaleSociale()));
        $el->addChild('SocioUnico', $this->getSocioUnico() ? 'SU' : 'SM');
        $el->addChild('StatoLiquidazione', $this->getInLiquidazione() ? 'LS' : 'LN');
    }
}
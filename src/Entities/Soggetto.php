<?php

namespace Nexev\EFat\Entities;

abstract class Soggetto {

    /**
     * Persona fisica o giuridica
     *
     * @var bool
     */
    private $personaFisica;
    
    /**
     * Se iscritto o no al REA
     *
     * @var bool
     */
    private $iscrittoREA;

    /**
     * Codice univoco del Sistema di Interscambio
     *
     * @var string
     */
    private $sdi;

    /**
     * Indirizzo PEC
     *
     * @var string
     */
    private $pec;

    /**
     * Denominazione del soggetto
     *
     * @var string
     */
    private $denominazione;
    
    /**
     * Regime fiscale del soggetto
     *
     * @var string
     */
    private $regimeFiscale;

    /**
     * Oggetto indirizzo
     *
     * @var Nexev\EFat\Entities\Indirizzo
     */
    private $indirizzo;

    public abstract function setIscrittoRea(bool $value): bool;
    public abstract function aggiungiDatiRea(array $rea): void;
    public abstract function aggiungiIndirizzo(array $indirizzo): void;
    public abstract function setPersonaFisica(): void;
    public abstract function setPersonaGiuridica(): void;
    public abstract function checkForTrasmittente(): bool;
    public abstract function checkForCedente(): bool;
    public abstract function checkForCessionario(): bool;
}
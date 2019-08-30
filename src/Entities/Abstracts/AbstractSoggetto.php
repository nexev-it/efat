<?php

namespace Nexev\EFat\Entities\Abstracts;

use Nexev\EFat\Entities\Indirizzo;
use Nexev\EFat\Entities\Traits\ErrorTrait;

abstract class AbstractSoggetto extends AbstractBaseClass {

    use ErrorTrait;
    

    /**
     * Denominazione del soggetto
     *
     * @var string
     */
    protected $denominazione;

    /**
     * Paese da impostare su fattura elettronica
     */
    protected $paese = 'IT';
    
    /**
     * Partita IVA o Codice Fiscale del soggetto
     *
     * @var string
     */
    protected $partitaIVA;

    /**
     * Persona fisica o giuridica
     *
     * @var bool
     */
    protected $personaFisica;

    /**
     * Parametro boolean che specifica se il soggetto
     * è una PA
     */
    protected $isPA;

    /**
     * Codice univoco del Sistema di Interscambio
     *
     * @var string
     */
    protected $sdi;

    /**
     * Indirizzo PEC
     *
     * @var string
     */
    protected $pec;
    
    /**
     * Regime fiscale del soggetto
     *
     * @var string
     */
    protected $regimeFiscale;

    /**
     * Oggetto indirizzo
     *
     * @var \Nexev\EFat\Entities\Indirizzo
     */
    protected $indirizzo;

    public function __construct(string $denominazione, string $partitaIVA, string $codiceSDI = null, string $pec = null, string $regimeFiscale = 'RF01') {
        // TODO 
        $this->isPA = is_a($this, 'Nexev\EFat\Entities\PubblicaAmministrazione') ? true : false;
        $this->personaFisica = is_a($this, 'Nexev\EFat\Entities\PersonaFisica') ? true : false;
        $this->setDenominazione($denominazione);
        $this->setPartitaIVA($partitaIVA);
        $this->setCodiceSDI($codiceSDI);
        $this->setPEC($pec);
        $this->setRegimeFiscale($regimeFiscale);
        $this->errori = [];

        $this->init();

        /*
        Trasmittente:
        - Partita IVA
        - Denominazione
        */

        /*
        Cedente (è personagiuridica):
        - Denominazione
        - Partita IVA
        - Indirizzo
        - ?REA
        - Regime fiscale
        */

        /*
        Cessionario (persona fisica o giuridica, o PA)
            - Persona fisica:
                - Nome e cognome
                - Codice fiscale O Partita IVA
                - Indirizzo
                - PEC o codice SDI
            - Persona giuridica:
                - Denominazione
                - Partita IVA
                - Indirizzo
                - PEC o codice SDI
            - Pubblica amministrazione:
                - Denominazione
                - Partita IVA
                - Indirizzo
                - PEC o codice SDI
        */
    }

    /** GETTERS */

    public function getNome(): string
    {
        return $this->denominazione;
    }

    public function getDenominazione(): string
    {
        return $this->getNome();
    }

    public function getPartitaIVA(): string
    {
        return $this->partitaIVA;
    }

    public function getPEC(): ?string
    {
        return $this->pec;
    }

    public function getCodiceSDI(): string
    {
        return $this->sdi ?: '0000000';
    }

    public function getRegimeFiscale(): ?string
    {
        return $this->regimeFiscale;
    }

    public function getIndirizzo(): ?Indirizzo
    {
        return $this->indirizzo;
    }

    public function getPaese(): string
    {
        return $this->paese;
    }

    /** SETTERS */

    public function setNome(string $nome): void
    {
        if (strlen($nome) < 2) throw new \Exception("La denominazione è troppo corta");
        $this->denominazione = $nome;
    }

    public function setDenominazione(string $denominazione): void
    {
        $this->setNome($denominazione);
    }

    public function setPartitaIVA(string $partitaIVA): void
    {
        if($this->startsWith($partitaIVA, $this->paese)) {
            $partitaIVA = substr($partitaIVA, strlen($this->paese));
        }

        if(!preg_match('/[0-9]{11}/i', $partitaIVA) && !preg_match('/[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]/i', $partitaIVA)) throw new \Exception("Partita IVA / Codice fiscale non riconosciuto");

        $this->partitaIVA = $partitaIVA;
    }

    public function setPEC(?string $pec): void
    {
        if(is_null($pec)) return;
        if(!preg_match('/[a-z0-9_]+@[a-z0-9\-]+\.[a-z0-9\-\.]+$]/i', $pec)) throw new \Exception("Indirizzo email non formattato in modo corretto"); // TODO
    }

    public function setCodiceSDI(?string $sdi): void
    {
        if(is_null($sdi)) return;
        if(!preg_match($this->getSdiRegEx(), $sdi)) throw new \Exception("Codice SDI non formattato in modo corretto");
    }

    public function setRegimeFiscale(string $regimeFiscale): void
    {
        if(!in_array(
            $regimeFiscale,
            [
                'RF01',
                'RF02',
                'RF03',
                'RF04',
                'RF05',
                'RF06',
                'RF07',
                'RF08',
                'RF09',
                'RF10',
                'RF11',
                'RF12',
                'RF13',
                'RF14',
                'RF15',
                'RF16',
                'RF17',
                'RF18',
            ]
            )
        ) throw new \Exception("Regime fiscale non valido"); // TODO

        $this->regimeFiscale = $regimeFiscale;
    }
    

    public function aggiungiIndirizzo(Indirizzo $indirizzo): void
    {
        $this->indirizzo = $indirizzo;
    }

    public function setIndirizzo(Indirizzo $indirizzo): void
    {
        $this->aggiungiIndirizzo($indirizzo);
    }

    public function hasREA(): bool
    {
        return false;
    }

    public function compilaCessionarioCommittente(\SimpleXMLElement $el): \SimpleXMLElement
    {
        $CCdatiAnagrafici = $el->addChild('DatiAnagrafici');
        $CCDAidFiscaleIva = $CCdatiAnagrafici->addChild('IdFiscaleIVA');
        $CCDAidFiscaleIva->addChild('IdPaese', $this->getPaese());
        $CCDAidFiscaleIva->addChild('IdCodice', $this->getPartitaIVA());
        $CCdatiAnagrafici->addChild('Anagrafica')->addChild('Denominazione', htmlspecialchars($this->getDenominazione(), ENT_XML1));

        $CCsede = $el->addChild('Sede');
        $CCsede = $this->getIndirizzo()->compilaSede($CCsede);

        return $el;
    }


    /** Funzioni di sistema */

    protected function getSdiRegEx(): string
    {
        return $this->isPA ? '/[a-z0-9]{7}/i' : '/[a-z0-9]{6}/i';
    }

    protected function init(): void { }
}
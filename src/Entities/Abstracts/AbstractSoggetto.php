<?php

namespace Nexev\EFat\Entities\Abstracts;

use Nexev\EFat\Entities\Indirizzo;

abstract class AbstractSoggetto extends AbstractBaseClass {
    

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
     * Codice univoco del Sistema di Interscambio.
     * Per i Cessionari/Committenti è necessario che
     * sia valorizzato almeno uno tra codice SDI e PEC
     *
     * @var string
     */
    protected $sdi;

    /**
     * Indirizzo PEC
     * Per i Cessionari/Committenti è necessario che
     * sia valorizzato almeno uno tra codice SDI e PEC
     *
     * @var string
     */
    protected $pec;
    
    /**
     * Regime fiscale del soggetto.
     * Se non definito è impostato automaticamente
     * a RF01
     *
     * @var string
     */
    protected $regimeFiscale;

    /**
     * Oggetto indirizzo
     * Obbligatorio per Cessionario/Committente
     * e per Cedente/Prestatore
     *
     * @var \Nexev\EFat\Entities\Indirizzo
     */
    protected $indirizzo;

    /**
     * Numero di telefono del Soggetto
     *
     * @var null|string
     */
    protected $telefono;

    /**
     * Indirizzo email del Soggetto
     *
     * @var null|string
     */
    protected $email;

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
    }

    /** GETTERS */

    /**
     * Restituisce il nome/denominazione del Soggetto
     *
     * @return string
     */
    public function getNome(): string
    {
        return $this->denominazione;
    }

    /**
     * Restituisce il nome/denominazione del Soggetto
     *
     * @return string
     */
    public function getDenominazione(): string
    {
        return $this->getNome();
    }

    /**
     * Restituisce la partiva IVA del Soggetto
     *
     * @return string
     */
    public function getPartitaIVA(): string
    {
        return $this->partitaIVA;
    }

    /**
     * Restituisce l'indirizzo di PEC del Soggetto,
     * se impostato
     */
    public function getPEC(): ?string
    {
        return $this->pec;
    }

    /**
     * Restituisce il codice SDI del Soggetto, se
     * impostato
     *
     * @return string
     */
    public function getCodiceSDI(): string
    {
        return $this->sdi ?: '0000000';
    }

    /**
     * Restituisce il regime fiscale del Soggetto
     *
     * @return string
     */
    public function getRegimeFiscale(): string
    {
        return $this->regimeFiscale;
    }

    /**
     * Restituisce l'indirizzo del Soggetto, se
     * impostato
     *
     * @return Indirizzo|null
     */
    public function getIndirizzo(): ?Indirizzo
    {
        return $this->indirizzo;
    }

    /**
     * Restituisce le iniziali del paese del Soggetto.
     * È attualmente settato automaticamente a IT
     *
     * @return string
     */
    public function getPaese(): string
    {
        return $this->paese;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /** SETTERS */

    /**
     * Imposta il nome/denominazione del Soggetto.
     *
     * @param string $nome
     * @return void
     */
    public function setNome(string $nome): void
    {
        if (strlen($nome) < 2) throw new \Exception("La denominazione è troppo corta");
        $this->denominazione = $nome;
    }

    /**
     * Imposta il nome/denominazione del Soggetto.
     *
     * @param string $denominazione
     * @return void
     */
    public function setDenominazione(string $denominazione): void
    {
        $this->setNome($denominazione);
    }

    /**
     * Imposta la partita IVA del Soggetto
     *
     * @param string $partitaIVA
     * @return void
     */
    public function setPartitaIVA(string $partitaIVA): void
    {
        if($this->startsWith($partitaIVA, $this->paese)) {
            $partitaIVA = substr($partitaIVA, strlen($this->paese));
        }

        if(!preg_match('/[0-9]{11}/i', $partitaIVA) && !preg_match('/[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]/i', $partitaIVA)) throw new \Exception("Partita IVA / Codice fiscale non riconosciuto");

        $this->partitaIVA = $partitaIVA;
    }

    /**
     * Imposta l'indirizzo di PEC del Soggetto
     *
     * @param string|null $pec
     * @return void
     */
    public function setPEC(?string $pec): void
    {
        if(in_array($pec, [null, ''])) return;
        if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i", $pec)) throw new \Exception("Indirizzo email non formattato in modo corretto");

        $this->pec = $pec;
    }

    /**
     * Imposta il codice SDI del Soggetto
     *
     * @param string|null $sdi
     * @return void
     */
    public function setCodiceSDI(?string $sdi): void
    {
        if(in_array($sdi, [null, ''])) return;
        if(!preg_match($this->getSdiRegEx(), $sdi)) throw new \Exception("Codice SDI non formattato in modo corretto");

        $this->sdi = $sdi;
    }

    /**
     * Imposta il regime fiscale del Soggetto
     *
     * @param string $regimeFiscale
     * @return void
     */
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
        ) throw new \Exception("Regime fiscale non valido");

        $this->regimeFiscale = $regimeFiscale;
    }

    public function setTelefono(string $telefono): void
    {
        $this->telefono = $telefono;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
    

    /**
     * Imposta l'indirizzo del Soggetto
     *
     * @param Indirizzo $indirizzo
     * @return void
     */
    public function aggiungiIndirizzo(Indirizzo $indirizzo): void
    {
        $this->indirizzo = $indirizzo;
    }

    /**
     * Imposta l'indirizzo del Soggetto
     *
     * @param Indirizzo $indirizzo
     * @return void
     */
    public function setIndirizzo(Indirizzo $indirizzo): void
    {
        $this->aggiungiIndirizzo($indirizzo);
    }

    /**
     * Restituisce true se il Soggetto presenta
     * dati REA.
     *
     * @return boolean
     */
    public function hasREA(): bool
    {
        return false;
    }

    public function compilaCessionarioCommittente(\SimpleXMLElement $el): void
    {
        $CCdatiAnagrafici = $el->addChild('DatiAnagrafici');
        $CCDAidFiscaleIva = $CCdatiAnagrafici->addChild('IdFiscaleIVA');
        $CCDAidFiscaleIva->addChild('IdPaese', $this->getPaese());
        $CCDAidFiscaleIva->addChild('IdCodice', $this->getPartitaIVA());
        $CCdatiAnagrafici->addChild('Anagrafica')->addChild('Denominazione', htmlspecialchars($this->getDenominazione(), ENT_XML1));

        $CCsede = $el->addChild('Sede');
        $CCsede = $this->getIndirizzo()->compilaSede($CCsede);
    }


    /** Funzioni di sistema */

    protected function getSdiRegEx(): string
    {
        return $this->isPA ? '/[a-z0-9]{6}/i' : '/[a-z0-9]{7}/i';
    }

    protected function init(): void { }
}
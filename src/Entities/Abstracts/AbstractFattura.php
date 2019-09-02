<?php

namespace Nexev\EFat\Entities\Abstracts;

use DateTime;
use Nexev\EFat\Builders\XMLBuilder;
use Nexev\EFat\Entities\Containers\ServiziContainer;
use Nexev\EFat\Entities\Interfaces\CedenteInterface;
use Nexev\EFat\Entities\Interfaces\CessionarioInterface;
use Nexev\EFat\Entities\Interfaces\TrasmittenteInterface;
use Nexev\EFat\Entities\Ritenuta;
use Nexev\EFat\Entities\Servizio;

/**
 * Classe astratta principale per la creazione della fattura
 */
abstract class AbstractFattura extends AbstractBaseClass {
    
    /**
     * Oggetto per la costruzione del file xml
     *
     * @var \Nexev\EFat\Builders\XMLBuilder
     */
    protected $builder;

    /**
     * Stringa rappresentante il formato trasmissione.
     * I due valori impostabili sono 'FPA12' e 'FPR12'.
     * Il campo è valorizzato automaticamente a seconda
     * del tipo di oggetto che viene creato
     * 
     * @var string
     */
    protected $formatoTrasmissione;

    /**
     * Rappresenta il numero della fattura.
     * È una stringa fino a 5 caratteri
     *
     * @var string
     */
    protected $numero;
    
    /**
     * La data di emissione della fattura.
     * In formato DateTime
     *
     * @var DateTime
     */
    protected $data;

    /**
     * Identificativo interno della fattura. 
     * Stringa fino ad 8 caratteri
     *
     * @var string
     */
    protected $progressivoInvio;

    /**
     * Oggetto rappresentante il Soggetto Trasmittente
     *
     * @var null|\Nexev\EFat\Entities\Interfaces\TrasmittenteInterface
     */
    protected $trasmittente;

    /**
     * Oggetto rappresentante il Cedente/Prestatore
     *
     * @var null|\Nexev\EFat\Entities\Interfaces\CedenteInterface
     */
    protected $cedente;

    /**
     * Oggetto rappresentante il Cessionario/Committente
     *
     * @var null|\Nexev\EFat\Entities\Interfaces\CessionarioInterface
     */
    protected $cessionario;

    /**
     * Oggetto Container dei servizi inseriti
     *
     * @var \Nexev\EFat\Entities\Containers\ServiziContainer
     */
    protected $beniServizi;

    /**
     * Oggetto rappresentante la ritenuta d'acconto
     *
     * @return null|\Nexev\EFat\Entities\Ritenuta
     */
    protected $ritenuta;


    public function __construct(string $numero, string $progressivoInvio, ?DateTime $data = null)
    {
        $this->beniServizi = new ServiziContainer();
        $this->setNumero($numero);
        $this->setProgressivoInvio($progressivoInvio);
        $this->setData($data);
        
    }

    /** SETTERS */

    /**
     * Imposta il numero della fattura.
     * Il numero è una stringa compresa tra 1 e 5
     * caratteri
     *
     * @param string $numero
     * @return void
     */
    public function setNumero(string $numero)
    {
        if(strlen($numero) < 1 OR strlen($numero) > 5) throw new \Exception("Il numero della fattura deve essere compreso tra 1 e 5 caratteri");
        $this->numero = $numero;
    }

    /**
     * Imposta il progressivo invio della fattura.
     * Il numero è una stringa compresa tra 1 ed 8
     * caratteri
     *
     * @param string $progressivoInvio
     * @return void
     */
    public function setProgressivoInvio(string $progressivoInvio)
    {
        if(strlen($progressivoInvio) < 1 OR strlen($progressivoInvio) > 8) throw new \Exception("Il numero di progressivo invio deve essere copmreso tra 1 ed 8 caratteri");
        $this->progressivoInvio = $progressivoInvio;
    }

    /**
     * Imposta la data di emissione della fattura.
     * Essa deve essere in formato DateTime
     *
     * @param \Datetime|null $data
     * @return void
     */
    public function setData(?Datetime $data) {
        if(is_null($data)) $this->data = new DateTime();
        else $this->data = $data;
    }

    /**
     * Inserisce il soggetto trasmittente.
     * Egli può essere solo una persona giuridica.
     * Il campo non è obbligatorio al fine della creazione
     * della fattura. Se non impostato, lo script utilizza
     * il cedente/prestatore come trasmittente.
     *
     * @param \Nexev\EFat\Entities\Interfaces\TrasmittenteInterface $trasmittente
     * @return void
     */
    public function setTrasmittente(TrasmittenteInterface $trasmittente): void
    {
        
        if(!$trasmittente->checkForTrasmittente()) throw new \Exception($trasmittente->getStringaErrori());
        $this->trasmittente = $trasmittente;
    }

    /**
     * Inserisce il soggetto Cedente/Prestatore
     * Egli può essere solo una persona giuridica.
     * Il campo è obbligatorio ai fini della creazione
     * della fattura.
     *
     * @param \Nexev\EFat\Entities\Interfaces\CedenteInterface $cedente
     * @return void
     */
    public function setCedente(CedenteInterface $cedente): void
    {
        if(!$cedente->checkForCedente()) throw new \Exception($cedente->getStringaErrori());
        $this->cedente = $cedente;
    }

    /**
     * Inserisce il soggetto Cessionario/Committente.
     * Egli può essere una persona fisica o una persona
     * giuridica nel caso di una fattura verso privati,
     * mentre può essere solo un ente PA nel caso di una
     * fattura PA.
     * Il campo è obbligatorio ai fini della creazione
     * della fattura.
     *
     * @param \Nexev\EFat\Entities\Interfaces\CessionarioInterface $cessionario
     * @return void
     */
    public function setCessionario(CessionarioInterface $cessionario): void
    {
        if(!$cessionario->checkForCessionario()) throw new \Exception($cessionario->getStringaErrori());
        $this->cessionario = $cessionario;
    }

    /**
     * Inserisce un array di servizi passato come parametro
     * tra i beni oggetto della fattura.
     * L'array deve essere composto da soli oggetti di tipo
     * \Nexev\EFat\Entities\Servizio .
     *
     * @param array $servizi
     * @return void
     */
    public function setServizi(array $servizi): void
    {
        $this->beniServizi->aggiungiServizi($servizi);
    }

    /**
     * Aggiunge un Servizio alla lista dei beni della
     * fattura.
     *
     * @param \Nexev\EFat\Entities\Servizio $servizio
     * @return void
     */
    public function setServizio(Servizio $servizio): void
    {
        $this->beniServizi->aggiungiServizio($servizio);
    }


    /**
     * Imposta la ritenuta passata come parametro.
     *
     * @param \Nexev\EFat\Entities\Ritenuta $ritenuta
     * @return void
     */
    public function setRitenuta(Ritenuta $ritenuta): void {
        if(!$ritenuta->getTipo()) {
            $tipo = 'RT02';
            if(is_a($this->cedente, 'Nexev\EFat\Entities\PersonaFisica')) $tipo = 'RT01';
            $ritenuta->setTipo($tipo);
        }
        $this->ritenuta = $ritenuta;
    }

    /**
     * Restituisce la stringa rappresentante il nome del file
     * seguendo i canoni dell'Agenzia delle Entrate, utilizzando
     * i dati di cui l'oggetto è in possesso.
     *
     * @return string
     */
    public function getNomeFile(): string
    {
        $paese = $this->getTrasmittente()->getPaese();
        $pIVA = $this->getTrasmittente()->getPartitaIVA();
        $codice = $this->getProgressivoInvio();

        return $paese . $pIVA . '_' . substr($codice, 0, 5) . '.xml';
    }

    
    /** GETTERS */

    /**
     * Restituisce il formato trasmissione
     * FPR12: Fattura per privati
     * FPA12: Fattura per PA
     *
     * @return string
     */
    public function getFormatoTrasmissione(): string
    {
        return $this->formatoTrasmissione;
    }

    /**
     * Restituisce il numero della fattura impostato
     *
     * @return string
     */
    public function getNumero(): string
    {
        return $this->numero;
    }

    /**
     * Restituisce la data di emissione della fattura
     * impostata
     *
     * @return DateTime
     */
    public function getData(): DateTime
    {
        return $this->data;
    }

    /**
     * Restituisce il progressivo invio della fattura
     * impostato
     *
     * @return string
     */
    public function getProgressivoInvio(): string
    {
        return $this->progressivoInvio;
    }

    /**
     * Restituisce il trasmittente della fattura.
     * Se non è stato impostato, il cedente è considerato
     * il trasmittente della fattura. Il campo viene quindi
     * sempre ritornato.
     *
     * @return TrasmittenteInterface
     */
    public function getTrasmittente(): TrasmittenteInterface
    {
        if(!$this->trasmittente) $this->trasmittente = clone $this->cedente;
        return $this->trasmittente;
    }

    /**
     * Restituisce true se il trasmittente è diverso dal cedente
     *
     * @return boolean
     */
    public function hasTrasmittente(): bool
    {
        if($this->getTrasmittente()->getPartitaIVA() == $this->getCedente()->getPartitaIVA()) return false;
        return true;
    }

    /**
     * Restituisce true se il trasmittente ed il cedente 
     * coincidono, ovvero se la fattura generata è una
     * fattura passiva: inviata dal cedente
     *
     * @return boolean
     */
    public function isPassiva(): bool
    {
        if($this->getTrasmittente()->getPartitaIVA() == $this->getCessionario()->getPartitaIVA()) return true;
        return false;
    }

    /**
     * Restituisce il Cedente/Prestatore della fattura
     * o null se non ancora impostato.
     * Il campo deve essere valorizzato prima della creazione
     * dell'XML, altrimenti lancia un'eccezione
     *
     * @return \Nexev\EFat\Entities\Interfaces\CedenteInterface|null
     */
    public function getCedente(): ?CedenteInterface
    {
        return $this->cedente;
    }

    /**
     * Restituisce il Cessionario/Committente della fattura
     * o null se non ancora impostato.
     * Il campo deve essere valorizzato prima della creazione
     * dell'XML, altrimenti lancia un'eccezione
     *
     * @return \Nexev\EFat\Entities\Interfaces\CessionarioInterface|null
     */
    public function getCessionario(): ?CessionarioInterface
    {
        return $this->cessionario;
    }

    /**
     * Restituisce l'array dei servizi inseriti
     *
     * @return array
     */
    public function getServizi(): array
    {
        return $this->beniServizi->getArray();
    }

    /**
     * Restituisce l'oggetto contenitore dei servizi
     *
     * @return \Nexev\EFat\Entities\Containers\ServiziContainer
     */
    public function getServiziContainer(): ServiziContainer
    {
        return $this->beniServizi;
    }

    /**
     * Restituisce il totale della fattura, sottraendo
     * eventualmente la ritenuta d'acconto
     *
     * @return float
     */
    public function getTotale(): float
    {
        $totale = $this->getServiziContainer()->getTotale();
        return $this->getServiziContainer()->getTotale() - $this->getRitenuta();
    }

    public function getImponibileRitenuta(): float
    {
        if($this->hasRitenuta()) {
            $imponibile = $this->getServiziContainer()->getImponibile();
            return $imponibile - ($imponibile * $this->getRitenuta()->getPercentualeSuImponibile() * $this->getRitenuta()->getAliquota());
        }
        return 0;
    }

    /**
     * Restituisce la ritenuta d'acconto impostata,
     * se presente, null altrimenti
     *
     * @return Ritenuta|null
     */
    public function getRitenuta(): ?Ritenuta
    {
        return $this->ritenuta;
    }

    /**
     * Restituisce true se la ritenuta è impostata,
     * false altrimenti
     *
     * @return boolean
     */
    public function hasRitenuta(): bool
    {
        return !is_null($this->ritenuta);
    }

    /**
     * Restituisce il costruttore tramite 
     * il quale creare il file XML
     *
     * @return XMLBuilder
     */
    public function getBuilder(): XMLBuilder
    {
        if(!$this->builder) $this->builder = new XMLBuilder($this);

        return $this->builder;
    }

    public function compilaDatiRitenuta(\SimpleXMLElement $el): void
    {
        $el->addChild('TipoRitenuta', $this->getRitenuta()->getTipo()); // RT01 per persone fisiche, RT02 per persone giuridiche
        $el->addChild('ImportoRitenuta', $this->format($this->getImponibileRitenuta()));
        $el->addChild('AliquotaRitenuta', $this->getValorePercentuale($this->getRitenuta()->getAliquota()));
        $el->addChild('CausalePagamento', $this->getRitenuta()->getCausale());
    }


    /**
     * Esegue un check generale da chiamare prima 
     * dell'esportazione per non incorrere in errori
     *
     * @return boolean
     */
    public function check(): bool
    {
        $this->errori = [];
        $return = true;

        if(is_null($this->cessionario)) {
            $this->errori[] = "Non è stato impostato alcun Cessionario/Committente. Utilizzare la funzione setCessionario()";
            $return = false;
        }
        if(is_null($this->cedente)) {
            $this->errori[] = "Non è stato impostato alcun Cedente/Prestatore. Utilizzare la funzione setCedente()";
            $return = false;
        }
        if(!$this->beniServizi->count()) {
            $this->errori[] = "Non è stato inserito neanche un Bene/Servizio. Utilizzare le funzioni setServizio(Servizio) e setServizi(array)";
            $return = false;
        }

        return $return;
    }
}
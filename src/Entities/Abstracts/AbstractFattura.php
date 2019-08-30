<?php

namespace Nexev\EFat\Entities\Abstracts;

use DateTime;
use Nexev\EFat\Entities\Containers\ServiziContainer;
use Nexev\EFat\Entities\Interfaces\CedenteInterface;
use Nexev\EFat\Entities\Interfaces\CessionarioInterface;
use Nexev\EFat\Entities\Interfaces\TrasmittenteInterface;
use Nexev\EFat\Entities\Ritenuta;
use Nexev\EFat\Entities\Servizio;
use Nexev\EFat\Entities\Traits\ErrorTrait;

/**
 * Classe principale per la creazione della fattura.
 * Tramite questa classe sarà possibile estrapolare il file
 * XML delle fatture ed il file in versione PDF
 */
abstract class AbstractFattura extends AbstractBaseClass {

    use ErrorTrait;

    /**
     * Stringa rappresentante il formato trasmissione: FPA12 o FPR12.
     * A seconda del valore impostato viene creata una fattura PA o una fattura B2B
     *
     * @var string
     */
    protected $formatoTrasmissione;

    /**
     * Rappresenta il numero della fattura
     *
     * @var string
     */
    protected $numero;
    
    /**
     * La data di emissione della fattura
     *
     * @var DateTime
     */
    protected $data;

    /**
     * Identificativo interno della fattura (stringa fino ad 8 caratteri)
     *
     * @var string
     */
    protected $progressivoInvio;

    /**
     * Oggetto rappresentante la persona trasmittente
     *
     * @var \Nexev\EFat\Entities\Interfaces\TrasmittenteInterface
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
     * Oggetto rappresentante la ritenuta d'acconto: Tipo di ritenuta e percentuale
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

    public function setNumero(string $numero)
    {
        if(strlen($numero) < 1 OR strlen($numero) > 5) throw new \Exception("Il numero della fattura deve essere compreso tra 1 e 5 caratteri");
        $this->numero = $numero;
    }

    public function setProgressivoInvio(string $progressivoInvio)
    {
        if(strlen($progressivoInvio) < 1 OR strlen($progressivoInvio) > 8) throw new \Exception("Il numero di progressivo invio deve essere copmreso tra 1 ed 8 caratteri");
        $this->progressivoInvio = $progressivoInvio;
    }

    public function setData(?Datetime $data) {
        if(is_null($data)) $this->data = new DateTime();
        else $this->data = $data;
    }

    /**
     * Inserisce il soggetto trasmittente
     *
     * @param TrasmittenteInterface $trasmittente
     * @return void
     */
    public function setTrasmittente(TrasmittenteInterface $trasmittente): void
    {
        
        if(!$trasmittente->checkForTrasmittente()) throw new \Exception($trasmittente->getStringaErrori());
        $this->trasmittente = $trasmittente;
    }

    /**
     * Inserisce il soggetto Cedente/Prestatore
     *
     * @param CedenteInterface $cedente
     * @return void
     */
    public function setCedente(CedenteInterface $cedente): void
    {
        if(!$cedente->checkForCedente()) throw new \Exception($cedente->getStringaErrori());
        $this->cedente = $cedente;
    }

    /**
     * Inserisce il soggetto Cessionario/Committente
     *
     * @param CessionarioInterface $cessionario
     * @return void
     */
    public function setCessionario(CessionarioInterface $cessionario): void
    {
        if(!$cessionario->checkForCessionario()) throw new \Exception($cessionario->getStringaErrori());
        $this->cessionario = $cessionario;
    }

    /**
     * Inserisce un array di beni passato come parametro tra i beni oggetto della fattura
     *
     * @param array $servizi
     * @return void
     */
    public function setServizi(array $servizi): void
    {
        $this->beniServizi->aggiungiServizi($servizi);
    }

    /**
     * Aggiunge un bene di tipo Servizio alla lista dei beni della fattura
     *
     * @param Servizio $servizio
     * @return void
     */
    public function setServizio(Servizio $servizio): void
    {
        $this->beniServizi->aggiungiServizio($servizio);
    }


    public function setRitenuta(Ritenuta $ritenuta): void {
        if(!$ritenuta->getTipo()) {
            $tipo = 'RT02';
            if(is_a($this->cedente, 'Nexev\EFat\Entities\PersonaFisica')) $tipo = 'RT01';
            $ritenuta->setTipo($tipo);
        }
        $this->ritenuta = $ritenuta;
    }

    public function getNomeFile(): string
    {
        $paese = $this->getTrasmittente()->getPaese();
        $pIVA = $this->getTrasmittente()->getPartitaIVA();
        $codice = $this->getProgressivoInvio();

        return $paese . $pIVA . '_' . substr($codice, 0, 5) . '.xml';
    }

    
    /** GETTERS */

    public function getFormatoTrasmissione(): string
    {
        return $this->formatoTrasmissione;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getData(): DateTime
    {
        return $this->data;
    }

    public function getProgressivoInvio(): string
    {
        return $this->progressivoInvio;
    }

    public function getTrasmittente(): TrasmittenteInterface
    {
        if(!$this->trasmittente) $this->trasmittente = clone $this->cedente;
        return $this->trasmittente;
    }

    public function hasTrasmittente(): bool
    {
        if($this->getTrasmittente()->getPartitaIVA() == $this->getCedente()->getPartitaIVA()) return false;
        return true;
    }

    public function isPassiva(): bool
    {
        if($this->getTrasmittente()->getPartitaIVA() == $this->getCessionario()->getPartitaIVA()) return true;
        return false;
    }

    public function getCedente(): ?CedenteInterface
    {
        return $this->cedente;
    }

    public function getCessionario(): ?CessionarioInterface
    {
        return $this->cessionario;
    }

    public function getServizi(): array
    {
        return $this->beniServizi->getArray();
    }

    public function getRitenuta(): ?Ritenuta
    {
        return $this->ritenuta;
    }

    public function hasRitenuta(): bool
    {
        return !is_null($this->ritenuta);
    }



    /**
     * Esegue un check generale da chiamare prima dell'esportazione per non incorrere in errori
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
        if(count($this->beniServizi->getArray()) < 1) {
            $this->errori[] = "Non è stato inserito neanche un Bene/Servizio. Utilizzare le funzioni setServizio(Servizio) e setServizi(array)";
            $return = false;
        }

        return $return;
    }

}
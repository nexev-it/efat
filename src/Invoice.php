<?php

namespace Nexev\EFat;

use Entities\{Servizio, Soggetto};

/**
 * Classe principale per la creazione della fattura.
 * Tramite questa classe sarà possibile estrapolare il file
 * XML delle fatture ed il file in versione PDF
 */
class Invoice {

    /**
     * Stringa rappresentante il formato trasmissione: FPA12 o FPR12.
     * A seconda del valore impostato viene creata una fattura PA o una fattura B2B
     *
     * @var string
     */
    private $formatoTrasmissione;


    /**
     * Rappresenta il numero della fattura
     *
     * @var string
     */
    private $numero;
    
    /**
     * La data di emissione della fattura
     *
     * @var DateTime
     */
    private $data;

    /**
     * Identificativo interno della fattura (stringa fino ad 8 caratteri)
     *
     * @var string
     */
    private $progressivoInvio;

    /**
     * Oggetto rappresentante la persona trasmittente
     *
     * @var array
     */
    private $trasmittente = [
        'denominazione' => '',
        'partita_iva' => '',
        'codice_fiscale' => '',
    ];

    /**
     * Oggetto rappresentante il Cedente/Prestatore
     *
     * @var array
     */
    private $cedente = [

    ];

    /**
     * Oggetto rappresentante il Cessionario/Committente
     *
     * @var array
     */
    private $cessionario = [

    ];

    /**
     * Array di oggetti rappresentanti i Beni/Servizi venduti
     *
     * @var array
     */
    private $beniServizi =  [

    ];

    /**
     * Oggetto rappresentante la ritenuta d'acconto: Tipo di ritenuta e percentuale
     *
     * @return array
     */
    private $ritenuta = [

    ];

    /**
     * Inserisce il soggetto trasmittente
     *
     * @param Soggetto $trasmittente
     * @return boolean
     */
    public function inserisciTrasmittente(Soggetto $trasmittente): bool {
        
        if(!$trasmittente->checkForTrasmittente()) return false;
        $this->trasmittente = $trasmittente;
        return true;
    }

    /**
     * Inserisce il soggetto Cedente/Prestatore
     *
     * @param Soggetto $cedente
     * @return boolean
     */
    public function inserisciCedente(Soggetto $cedente): bool {
        if(!$cedente->checkForCedente()) return false;
        $this->cedente = $cedente;
        return true;
    }

    /**
     * Inserisce il soggetto Cessionario/Committente
     *
     * @param Soggetto $cessionario
     * @return boolean
     */
    public function inserisciCessionario(Soggetto $cessionario): bool {
        if(!$cessionario->checkForCessionario()) return false;
        $this->cessionario = $cessionario;
        return true;
    }

    /**
     * Inserisce un array di beni passato come parametro tra i beni oggetto della fattura
     *
     * @param array $servizi
     * @return boolean
     */
    public function inserisciBeni(array $servizi): bool {
        foreach($servizi as $s) {
            if(!is_a($s, 'Nexev\EFat\Utilities\Servizio')) return false;
            if(!$s->check()) return false;
        }
        $this->beniServizi = array_merge($this->beniServizi, $servizi);
        return true;
    }

    public function aggiungiBene(Servizio $servizio): bool {
        if (!$servizio->check()) return false;
        $this->beniServizi[] = $servizio;
        return true;
    }

    public function inserisciRitenuta(array $ritenuta): bool {
        // TODO: Vedere come fare per le ritenute d'acconto
        return true;
    }

    /**
     * Esegue un check generale da chiamare prima dell'esportazione per non incorrere in errori
     *
     * @return boolean
     */
    public function controlla(): bool {
        if(is_null($this->cessionario)) return false;
        if(is_null($this->cedente)) return false;
        if(count($this->beniServizi) < 1) return false;
        return true;
    }

    /**
     * Restituisce l'array degli errori creati dopo la chiamata della funzione controlla()
     *
     * @return array
     */
    public function errori(): array {
        // TODO: creare l'array degli errori
        return [];
    }

    /**
     * Restituisce la stringa XML pronta ad essere salvata all'interno di un file XML.
     *
     * @return string
     */
    public function esportaXML(): string {
        // TODO: lanciare eventualmente eccezioni
        return '';
    }

    /**
     * Esporta il file XML salvandolo nel percorso passato come parametro, con nome file
     * impostato come parametro.
     *
     * @param string $filePath
     * @param string $fileName
     * @return string
     */
    public function esportaFile(string $filePath, string $fileName): bool {
        // TODO: lanciare eventualmente eccezioni e segnalare l'effettiva creazione del file
        return true;
    }


    private function buildXml(): ?string
    {
        /*
		 * Tipologie di fattura ($this->vatCharge):
		 * 
		 * art_74 -> no iva
		 * reverse_charge -> no iva
		 * no_charge -> no iva?
		 * flat_rate -> no iva
		 * standard -> iva
		 * withholding_tax -> iva
		 */

        // chi invia la fattura
        $trasmittente = $this->getCompany();

        $mittente = $this->getSender();
        $destinatario = $this->getRecipient();

        $items = InvoiceItem::getAllObjects(['invoiceId' => $this->id]);
        $amount    = $this->getAmount();
        $vat    = $this->getVat();
        $total    = $this->getTotal(); // $amount - $vat - $ritenuta


        if (!$destinatario->hasRecipientCode() && !$destinatario->hasCertifiedEmail()) {
            $this->addError('Non è possibile creare la fattura elettronica a causa della mancanza di alcuni dati obbligatori.');
            return '';
        }

        // nodo radice
        $simpleXmlElement = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" versione="FPR12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd" />', 0, false, 'p', false);


        // FatturaHeader, contiene DatiTrasmissione, CedentePrestatore, CessionarioCommittente, TerzoIntermediarioOSoggettoEmittente, SoggettoEmittente
        $header = $simpleXmlElement->addChild('FatturaElettronicaHeader', '', '');

        // FatturaHeader -> DatiTrasmissione
        $datiTrasmissione = $header->addChild('DatiTrasmissione');

        $idTrasmittente = $datiTrasmissione->addChild('IdTrasmittente');
        $idTrasmittente->addChild('IdPaese', 'IT');
        $idTrasmittente->addChild('IdCodice', $trasmittente->getVatNumber()); // PARTITA IVA

        // ProgressivoInvio: numero progressivo a scelta nostra (può essere alfanumerico) (id della fattura nel db?)
        $datiTrasmissione->addChild('ProgressivoInvio', $this->id);

        // FormatoTrasmissione: FPA12 per PA, FPR12 per privati (FPR12, FPA12)
        $datiTrasmissione->addChild('FormatoTrasmissione', 'FPR12');

        // CodiceDestinatario: (Soggetto non PA: 7 caratteri di codice SdI), (Soggetto tramite pec: 0000000)
        $datiTrasmissione->addChild('CodiceDestinatario', $destinatario->getRecipientCode());

        // NB: ContattiTrasmittente non è un tag obbligatorio
        $contattiTrasmittente = $datiTrasmissione->addChild('ContattiTrasmittente');
        $contattiTrasmittente->addChild('Telefono', $trasmittente->getPhone());
        $contattiTrasmittente->addChild('Email', $trasmittente->getEmail());

        if (!$destinatario->hasRecipientCode()) {
            $datiTrasmissione->addChild('PECDestinatario', $destinatario->getCertifiedEmail());
        }

        // FatturaHeader -> CedentePrestatore
        $cedentePrestatore = $header->addChild('CedentePrestatore');

        $CPdatiAnagrafici = $cedentePrestatore->addChild('DatiAnagrafici');
        $CPDAidFiscaleIva = $CPdatiAnagrafici->addChild('IdFiscaleIVA');
        $CPDAidFiscaleIva->addChild('IdPaese', 'IT');
        $CPDAidFiscaleIva->addChild('IdCodice', $mittente->getVatNumber()); // PARTITA IVA
        $CPdatiAnagrafici->addChild('Anagrafica')->addChild('Denominazione', htmlspecialchars($mittente->getName(), ENT_XML1));
        $CPdatiAnagrafici->addChild('RegimeFiscale', $mittente->taxRegime);

        /*
         *  REGIME FISCALE:
         * 
         *  RF01 Ordinario;
         *  RF02 Contribuenti minimi (art. 1, c.96-117, L. 244/2007);
         *  RF03 Nuove iniziative produttive (art.13, L. 388/2000);
         *  RF04 Agricoltura e attività connesse e pesca (artt. 34 e 34-bis, D.P.R. 633/1972);
         *  RF05 Vendita sali e tabacchi (art. 74, c.1, D.P.R. 633/1972);
         *  RF06 Commercio dei fiammiferi (art. 74, c.1, D.P.R. 633/1972);
         *  RF07 Editoria (art. 74, c.1, D.P.R. 633/1972);
         *  RF08 Gestione di servizi di telefonia pubblica (art. 74, c.1, D.P.R. 633/1972);
         *  RF09 Rivendita di documenti di trasporto pubblico e di sosta (art. 74, c.1, D.P.R. 633/1972);
         *  RF10 Intrattenimenti, giochi e altre attività di cui alla tariffa allegata al D.P.R. n. 640/72 (art. 74, c.6, D.P.R. 633/1972); 
         *  RF11 Agenzie di viaggi e turismo (art. 74-ter, D.P.R. 633/1972);
         *  RF12 Agriturismo (art. 5, c.2, L. 413/1991);
         *  RF13 Vendite a domicilio (art. 25-bis, c.6, D.P.R. 600/1973);
         *  RF14 Rivendita di beni usati, di oggetti d’arte, d’antiquariato o da collezione (art. 36, D.L. 41/1995);
         *  RF15 Agenzie di vendite all’asta di oggetti d’arte, antiquariato o da collezione (art. 40-bis, D.L. 41/1995);
         *  RF16 IVA per cassa P.A. (art. 6, c.5, D.P.R. 633/1972);
         *  RF17 IVA per cassa soggetti con volume d’affari inferiore a € 200.000 (art. 7, D.L. 185/2008);
         *  RF18 Altro
        */

        $CPcity = $mittente->getCity();
        $CPsede = $cedentePrestatore->addChild('Sede');
        $CPsede->addChild('Indirizzo', $mittente->getAddress());
        $CPsede->addChild('CAP', $CPcity->zipCode);
        $CPsede->addChild('Comune', $CPcity->name);
        $CPsede->addChild('Provincia', $CPcity->getRelated('provinceId')->abbreviation);    // PROVINCIA: 2 lettere
        $CPsede->addChild('Nazione', 'IT');        // NAZIONE: 2 lettere

        // controlla se il mittente è iscritto al REA
        if ($mittente->isSubscribedToRea()) {

            $CPiscrizioneRea = $cedentePrestatore->addChild('IscrizioneREA');
            $CPiscrizioneRea->addChild('Ufficio', $CPcity->getRelated('provinceId')->abbreviation);    // Sigla provincia in cui la società è iscritta
            $CPiscrizioneRea->addChild('NumeroREA', $mittente->reaNumber);

            if (!in_array($mittente->shareCapital, ['', null, 0])) {
                $CPiscrizioneRea->addChild('CapitaleSociale', $this->format($mittente->shareCapital)); // CAPITALESOCIALE obbligatorio solo se società di capitali
            }
            $CPiscrizioneRea->addChild('SocioUnico', $mittente->soleShareholder ? 'SU' : 'SM'); // SU: socio unico. SM: pluripersonale
            $CPiscrizioneRea->addChild('StatoLiquidazione', $mittente->isWoundUp() ? 'LS' : 'LN'); // LS: in liquidazione. LN: non in liquidazione
        }

        // FatturaHeader -> CessionarioCommittente		
        $cessionarioCommittente = $header->addChild('CessionarioCommittente');

        $CCdatiAnagrafici = $cessionarioCommittente->addChild('DatiAnagrafici');
        $CCDAidFiscaleIva = $CCdatiAnagrafici->addChild('IdFiscaleIVA');
        $CCDAidFiscaleIva->addChild('IdPaese', 'IT');
        $CCDAidFiscaleIva->addChild('IdCodice', $destinatario->getVatNumber()); // PARTITA IVA
        $CCdatiAnagrafici->addChild('Anagrafica')->addChild('Denominazione', htmlspecialchars($destinatario->getName(), ENT_XML1)); // RAGIONE SOCIALE

        $CCcity = $destinatario->getCity();
        // SEDE: Sede legale per le società e domicilio fiscale per ditte individuali e lavoratori autonomi
        $CCsede = $cessionarioCommittente->addChild('Sede');
        $CCsede->addChild('Indirizzo', $destinatario->getAddress());
        $CCsede->addChild('CAP', $CCcity->zipCode);
        $CCsede->addChild('Comune', $CCcity->name);
        $CCsede->addChild('Provincia', $CCcity->getRelated('provinceId')->abbreviation);    // PROVINCIA: 2 lettere
        $CCsede->addChild('Nazione', 'IT');        // NAZIONE: 2 lettere

        /*
		 * FatturaHeader -> TerzoIntermediarioOSoggettoEmittente
		 */

        // è obbligatorio solo se l’impegno di emettere fattura elettronica per conto del cedente/prestatore è
        // assunto da un terzo sulla base di un accordo preventivo; il cedente/prestatore
        // rimane responsabile dell’adempimento fiscale

        // SE IL TRASMITTENTE È DIVERSO DAL MITTENTE VA INSERITO

        if ($this->passive) {

            $terzoIntermediario = $header->addChild('TerzoIntermediarioOSoggettoEmittente');

            $TIdatiAnagrafici = $terzoIntermediario->addChild('DatiAnagrafici');
            $TIDAidFiscaleIva = $TIdatiAnagrafici->addChild('IdFiscaleIVA');
            $TIDAidFiscaleIva->addChild('IdPaese', 'IT');
            $TIDAidFiscaleIva->addChild('IdCodice', $trasmittente->getVatNumber()); // PARTITA IVA
            $TIdatiAnagrafici->addChild('Anagrafica')->addChild('Denominazione', htmlspecialchars($trasmittente->getName(), ENT_XML1)); // RAGIONE SOCIALE

            /*
			* FatturaHeader -> SoggettoEmittente
			*/

            // SoggettoEmittente: Nei casi di documenti emessi da un soggetto diverso dal cedente/prestatore va valorizzato questo elemento, altrimenti può
            // non essere inserito. Il campo contiene un codice a due cifre: TZ se se la fattura è stata compilata da un soggetto terzo, CC se è stata compilata
            // dal cessionario/committente.
            $header->addChild('SoggettoEmittente', 'CC');
        }

        // FatturaBody, contiene DatiGenerali, DatiBeniServizi, DatiPagamento
        $body = $simpleXmlElement->addChild('FatturaElettronicaBody', '', '');

        // FatturaBody -> DatiGenerali
        $datiGenerali = $body->addChild('DatiGenerali');
        $DGdocumento = $datiGenerali->addChild('DatiGeneraliDocumento');

        /*
		 * TipoDocumento:
		 * 
		 * TD01 Fattura
		 * TD02 Acconto/Anticipo su fattura
		 * TD03 Acconto/Anticipo su parcella
		 * TD04 Nota di Credito
		 * TD05 Nota di Debito
		 * TD06 Parcella
		 */
        $DGdocumento->addChild('TipoDocumento', 'TD01');

        $DGdocumento->addChild('Divisa', 'EUR'); // VALUTA
        $DGdocumento->addChild('Data', $this->date->format('Y-m-d')); // Formato Y-m-d
        $DGdocumento->addChild('Numero', $this->number . '/' . $this->numberSuffix);

        // DatiRitenuta: obbligatorio solo se il cedente/prestatore è soggetto a ritenuta a titolo di acconto o a titolo definitivo
        $DGdatiRitenuta = $DGdocumento->addChild('DatiRitenuta');
        $DGdatiRitenuta->addChild('TipoRitenuta', 'RT02'); // RT01 per persone fisiche, RT02 per persone giuridiche
        $DGdatiRitenuta->addChild('ImportoRitenuta', $this->format($this->getWithholdingTax()));
        $DGdatiRitenuta->addChild('AliquotaRitenuta', '23.00'); // AliquotaRitenuta: valore percentuale della ritenuta
        $DGdatiRitenuta->addChild('CausalePagamento', 'Z');

        /*
		 * CausalePagamento:
		 * 
		 * A prestazioni di lavoro autonomo rientranti nell’esercizio di arte o professione abituale;
		 * B utilizzazione economica, da parte dell’autore o dell’inventore, di opere dell’ingegno, di brevetti industriali e di processi, formule o informazioni relativi ad esperienze acquisite in campo industriale, commerciale o scientifico;
		 * C utili derivanti da contratti di associazione in partecipazione e da contratti di cointeressenza, quando l’apporto è costituito esclusivamente dalla prestazione di lavoro;
		 * D utili spettanti ai soci promotori ed ai soci fondatori delle società di capitali;
		 * E levata di protesti cambiari da parte dei segretari comunali;
		 * F prestazioni rese dagli sportivi con contratto di lavoro autonomo;
		 * G indennità corrisposte per la cessazione di attività sportiva professionale;
		 * H indennità corrisposte per la cessazione dei rapporti di agenzia delle persone fisiche e delle società di persone con esclusione delle somme maturate entro il 31 dicembre 2003, già imputate per competenza e tassate come reddito d’impresa;
		 * I indennità corrisposte per la cessazione da funzioni notarili;
		 * L utilizzazione economica, da parte di soggetto diverso dall’autore o dall’inventore, di opere dell’ingegno, di brevetti industriali e di processi, formule e informazioni relativi ad esperienze acquisite in campo industriale, commerciale o scientifico;
		 * M prestazioni di lavoro autonomo non esercitate abitualmente, obblighi di fare, di non fare o permettere;
		 * N indennità di trasferta, rimborso forfetario di spese, premi e compensi erogati: o nell’esercizio diretto di attività sportive dilettantistiche; o in relazione a rapporti di collaborazione coordinata e continuativa di carattere amministrativogestionale di natura non professionale resi a favore di società e associazioni sportive dilettantistiche e di cori, bande e filodrammatiche da parte del direttore e dei collaboratori tecnici;
		 * O prestazioni di lavoro autonomo non esercitate abitualmente, obblighi di fare, di non fare o permettere, per le quali non sussiste l’obbligo di iscrizione alla gestione separata (Cir. INPS n. 104/2001);
		 * P compensi corrisposti a soggetti non residenti privi di stabile organizzazione per l’uso o la concessione in uso di attrezzature industriali, commerciali o scientifiche che si trovano nel territorio dello Stato ovvero a società svizzere o stabili organizzazioni di società svizzere che possiedono i requisiti di cui all’art. 15, comma 2 dell’Accordo tra la Comunità europea e la Confederazione svizzera del 26 ottobre 2004 (pubblicato in G.U.C.E. del 29 dicembre 2004 n. L385/30);
		 * Q provvigioni corrisposte ad agente o rappresentante di commercio monomandatario;
		 * R provvigioni corrisposte ad agente o rappresentante di commercio plurimandatario;
		 * S provvigioni corrisposte a commissionario;
		 * T provvigioni corrisposte a mediatore;
		 * U provvigioni corrisposte a procacciatore di affari;
		 * V provvigioni corrisposte a incaricato per le vendite a domicilio; provvigioni corrisposte a incaricato per la vendita porta a porta e per la vendita ambulante di giornali quotidiani e periodici (L. 25 febbraio 1987, n. 67);
		 * W corrispettivi erogati nel 2008 per prestazioni relative a contratti d’appalto cui si sono resi applicabili le disposizioni contenute nell’art. 25-ter del D.P.R. n. 600 del 1973;
		 * X canoni corrisposti nel 2004 da società o enti residenti ovvero da stabili organizzazioni di società estere di cui all’art. 26-quater, comma 1, lett. a) e b) del D.P.R. 600/73, a società o stabili organizzazioni di società, situate in altro stato membro dell’Unione Europea in presenza dei requisiti di cui al citato art. 26-quater, del D.P.R. 600/73, per i quali è stato effettuato, nell’anno 2006, il rimborso della ritenuta ai sensi dell’art. 4 del D.Lgs. 30 maggio 2005 n. 143;
		 * Y canoni corrisposti dal 1° gennaio 2005 al 26 luglio 2005 da società o enti residenti ovvero da stabili organizzazioni di società estere di cui all’art. 26-quater, comma 1, lett. a) e b) del D.P.R. n. 600 del 1973, a società o stabili organizzazioni di società, situate in altro stato membro dell’Unione Europea in presenza dei requisiti di cui al citato art. 26-quater, del D.P.R. n. 600 del 1973, per i quali è stato effettuato, nell’anno 2006, il rimborso della ritenuta ai sensi dell’art. 4 del D.Lgs. 30 maggio 2005 n. 143;
		 * Z titolo diverso dai precedenti.
		 */

        $DGdocumento->addChild('ImportoTotaleDocumento', $this->format($total)); // Importo totale anche dei campi successivi

        // FatturaBody -> DatiBeniServizi

        $datiBeniServizi = $body->addchild('DatiBeniServizi');

        // contatore linee
        $itemNumber = 0;

        foreach ($items as $item) {

            // ripulisce il campo descrizione
            $descrizione = $this->clearString($item->description);

            $itemNumber++;
            $dl = $datiBeniServizi->addChild('DettaglioLinee');
            $dl->addChild('NumeroLinea', $itemNumber);
            $dl->addChild('Descrizione', $descrizione);
            $dl->addChild('Quantita', $this->format($item->quantity)); // Numero float con due decimali
            $dl->addChild('PrezzoUnitario', $this->format($item->amount)); // float
            $dl->addChild('PrezzoTotale', $this->format($item->amount * $item->quantity));
            $dl->addChild('AliquotaIVA', $this->format($item->vatPerc)); // Esempio 22.00
        }

        // DatiRiepilogo: in realtà anche questo campo andrebbe replicato per ogni aliquota e/o natura presenti tra le righe di dettaglio
        // del documento. Da replicare anche nel caso in cui, a fronte della stessa aliquota, i metodi di versamento siano diversi.
        $datiRiepilogo = $datiBeniServizi->addChild('DatiRiepilogo');
        $datiRiepilogo->addChild('AliquotaIVA', $this->format($items[0]->vatPerc));
        $datiRiepilogo->addChild('ImponibileImporto', $this->format($amount));
        $datiRiepilogo->addChild('Imposta', $this->format($vat)); // il 22% di 407.38
        // EsigibilitaIVA: obbligatorio solo se si è nel campo delle operazioni imponibili.
        $datiRiepilogo->addChild('EsigibilitaIVA', 'I'); // 'I' per IVA ad esigibilità immediata, 'D' per IVA ad esigibilità differita.

        /*
		 * FatturaBody -> DatiPagamento

		// Non è obbligatorio: si inserisce solo se si vuole dare evidenza delle informazioni relative al pagamento in termini di condizioni, modalità e termini
		$datiPagamento = $body->addChild('DatiPagamento');

		$datiPagamento->addChild('CondizioniPagamento', 'TP02'); // TP01: rate, TP02: unica soluzione, TP03: pagamento di un anticipo

		$datiPagamento->addChild('ModalitaPagamento', 'MP05');
		
		 * MP01 contanti
		 * MP02 assegno
		 * MP03 assegno circolare
		 * MP04 contanti presso Tesoreria
		 * MP05 bonifico
		 * MP06 vaglia cambiario
		 * MP07 bollettino bancario
		 * MP08 carta di credito
		 * MP09 RID
		 * MP10 RID utenze
		 * MP11 RID veloce
		 * MP12 Riba
		 * MP13 MAV
		 * MP14 quietanza erario stato
		 * MP15 giroconto su conti di contabilità speciale

		// TODO: Capire in base a cosa viene calcolata la data di scadenza del pagamento
		$datiPagamento->addChild('DataScadenzaPagamento', '2019-03-20'); // Y-m-d
		$datiPagamento->addChild('ImportoPagamento', $this->format($total));
		$datiPagamento->addChild('CognomeQuietanzante', 'Rossi'); // cognome di chi ritira il denaro in contanti. Da impostare solo in caso di necessità (modalità pagamento)
		$datiPagamento->addChild('NomeQuietanzante', 'Mario'); // vale lo stesso di cui sopra
		$datiPagamento->addChild('CFQuietanzante', 'RSSMRA88A05D122E'); // vale lo stesso di cui sopra
		$datiPagamento->addChild('IBAN', 'IT023423092835092342'); // se necessario per il pagamento (bonifico)
		$datiPagamento->addChild('IstitutoFinanziario', 'Banca popolare del Mezzogiorno'); // se necessario per il pagamento (bonifico)
		*/

        try {

            // nuovo oggetto XML
            $xmlDom = new DOMDocument('1.0', 'utf-8');

            // aggiunge il foglio di stile
            $xmlDom->appendChild($xmlDom->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="../../assets/fatturaordinaria_v1.2.1.xsl"'));

            // aggiunge in coda al nuovo oggetto DOMDocument l'oggetto SimpleXML
            $xmlDom->appendChild($xmlDom->importNode(dom_import_simplexml($simpleXmlElement), TRUE));

            // esporta in una nuova variabile
            $xmlData = $xmlDom->saveXML();
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            return '';
        }

        // validazione fattura XML
        $feValidator = new Validator();

        try {
            $feValidator->assertValidXml($xmlData);
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            return '';
        }

        return $xmlData;
    }

}
<?php

namespace Nexev\EFat\Builders;

use Nexev\EFat\Entities\Abstracts\AbstractFattura;
use SlamFatturaElettronica\Validator;

class XMLBuilder {

    private $fattura;

    public function __construct(AbstractFattura $fattura)
    {
        $this->fattura = $fattura;
        if($this->fattura->check()) throw new \Exception($this->fattura->getStringaErrori());

    }

    /**
     * Restituisce la stringa XML pronta ad essere salvata all'interno di un file XML.
     *
     * @return string
     */
    public function esportaXML(): string
    {
        $simpleXmlElement = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" versione="' . $this->formatoTrasmissione . '" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd" />', 0, false, 'p', false);

        $header = $simpleXmlElement->addChild('FatturaElettronicaHeader', '', '');

        $datiTrasmissione = $header->addChild('DatiTrasmissione');

        $idTrasmittente = $datiTrasmissione->addChild('IdTrasmittente');
        $idTrasmittente = $this->fattura->getTrasmittente()->compilaIdTrasmittente($idTrasmittente);

        $datiTrasmissione->addChild('ProgressivoInvio', $this->fattura->getProgressivoInvio());

        $datiTrasmissione->addChild('FormatoTrasmissione', $this->fattura->getFormatoTrasmissione());

        $datiTrasmissione->addChild('CodiceDestinatario', $this->fattura->getCessionario()->getCodiceSDI());

        if (!$this->fattura->getCessionario()->getCodiceSDI() == '0000000') {
            $datiTrasmissione->addChild('PECDestinatario', $this->fattura->getCessionario()->getPEC());
        }

        $cedentePrestatore = $header->addChild('CedentePrestatore');
        $cedentePrestatore = $this->fattura->getCedente()->compilaCedentePrestatore($cedentePrestatore);

        $cessionarioCommittente = $header->addChild('CessionarioCommittente');
        $cessionarioCommittente = $this->fattura->getCessionario()->compilaCessionarioCommittente($cessionarioCommittente);

        if($this->fattura->hasTrasmittente()) {
            $terzoIntermediario = $header->addChild('TerzoIntermediarioOSoggettoEmittente');
            $terzoIntermediario = $this->fattura->getTrasmittente()->compilaTerzoIntermediario($terzoIntermediario);
            
            $header->addChild('SoggettoEmittente', $this->fattura->isPassiva() ? 'CC' : 'TZ');
        }

        $body = $simpleXmlElement->addChild('FatturaElettronicaBody', '', '');
        $datiGenerali = $body->addChild('DatiGenerali');
        $DGdocumento = $datiGenerali->addChild('DatiGeneraliDocumento');

        $DGdocumento->addChild('TipoDocumento', 'TD01');
        // TODO: pensare all'estensione del tool: TD01 è fattura

        $DGdocumento->addChild('Divisa', 'EUR'); // VALUTA
        $DGdocumento->addChild('Data', $this->fattura->getData()->format('Y-m-d'));
        $DGdocumento->addChild('Numero', $this->fattura->getNumero());

        if($this->fattura->hasRitenuta()) {
            $DGdatiRitenuta = $DGdocumento->addChild('DatiRitenuta');
            $DGdatiRitenuta = $this->fattura->getRitenuta()->compilaDatiRitenuta($DGdatiRitenuta, $this->fattura->getServizi()->getTotale());
        }

        $DGdocumento->addChild('ImportoTotaleDocumento', $this->format($this->fattura->getServizi()->getTotale()));

        $datiBeniServizi = $body->addchild('DatiBeniServizi');

        $datiBeniServizi = $this->fattura->getServizi()->compilaBeniServizi($datiBeniServizi);

        // Creo il file
        $xmlDom = new \DOMDocument('1.0', 'utf-8');
        $xmlDom->appendChild($xmlDom->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="../../assets/fatturaordinaria_v1.2.1.xsl"'));

        // aggiunge in coda al nuovo oggetto DOMDocument l'oggetto SimpleXML
        $xmlDom->appendChild($xmlDom->importNode(dom_import_simplexml($simpleXmlElement), TRUE));

        // esporta in una nuova variabile
        $xmlData = $xmlDom->saveXML();

        $validator = new Validator();

        try {
            $validator->assertValidXml($xmlData);
        }
        catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $xmlData;
    }

    /**
     * Esporta il file XML salvandolo nel percorso passato come parametro, con nome file
     * impostato come parametro.
     *
     * @param string $filePath
     * @return string
     */
    public function esportaFile(string $filePath): bool
    {
        $file = '';
        $fileName = $this->fattura->getNomeFile();

        if($this->endsWith($filePath, '/')) $file = $filePath . $fileName;
        else $file = $filePath . '/' . $fileName;

        $content = $this->esportaXML();
        if(false === file_put_contents($file, $content, LOCK_EX)) return false;
        
        return true;
    }
}
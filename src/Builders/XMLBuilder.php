<?php

namespace Nexev\EFat\Builders;

use Nexev\EFat\Entities\Abstracts\AbstractBaseClass;
use Nexev\EFat\Entities\Abstracts\AbstractFattura;
use SlamFatturaElettronica\Validator;

class XMLBuilder extends AbstractBaseClass {

    private $fattura;

    public function __construct(AbstractFattura $fattura)
    {
        if (!$fattura->check())
            throw new \Exception($fattura->getStringaErrori());

        $this->fattura = $fattura;

    }

    /**
     * Restituisce la stringa XML pronta ad essere salvata all'interno di un file XML.
     *
     * @return string
     */
    public function esportaXML(): string
    {
        $simpleXmlElement = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" versione="' . $this->fattura->getFormatoTrasmissione() . '" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd" />', 0, false, 'p', false);

        $header = $simpleXmlElement->addChild('FatturaElettronicaHeader', '', '');

        $datiTrasmissione = $header->addChild('DatiTrasmissione');

        $idTrasmittente = $datiTrasmissione->addChild('IdTrasmittente');
        $this->fattura->getTrasmittente()->compilaIdTrasmittente($idTrasmittente);

        $datiTrasmissione->addChild('ProgressivoInvio', $this->fattura->getProgressivoInvio());

        $datiTrasmissione->addChild('FormatoTrasmissione', $this->fattura->getFormatoTrasmissione());

        $datiTrasmissione->addChild('CodiceDestinatario', $this->fattura->getCessionario()->getCodiceSDI());

        if (!$this->fattura->getCessionario()->getCodiceSDI() == '0000000') {
            $datiTrasmissione->addChild('PECDestinatario', $this->fattura->getCessionario()->getPEC());
        }

        $cedentePrestatore = $header->addChild('CedentePrestatore');
        $this->fattura->getCedente()->compilaCedentePrestatore($cedentePrestatore);

        $cessionarioCommittente = $header->addChild('CessionarioCommittente');
        $this->fattura->getCessionario()->compilaCessionarioCommittente($cessionarioCommittente);

        if($this->fattura->hasTrasmittente()) {
            $terzoIntermediario = $header->addChild('TerzoIntermediarioOSoggettoEmittente');
            $this->fattura->getTrasmittente()->compilaTerzoIntermediario($terzoIntermediario);
            
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
            $this->fattura->compilaDatiRitenuta($DGdatiRitenuta);
        }

        $DGdocumento->addChild('ImportoTotaleDocumento', $this->format($this->fattura->getTotale()));

        $datiBeniServizi = $body->addchild('DatiBeniServizi');

        $this->fattura->getServiziContainer()->compilaBeniServizi($datiBeniServizi, $this->fattura->getEsigibileIva());

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
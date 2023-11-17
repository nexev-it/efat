<?php

namespace Nexev\EFat\Builders;

use DateTime;
use Nexev\EFat\Entities\Abstracts\AbstractBaseClass;
use SlamFatturaElettronica\Validator;

class EasyFattBuilder extends AbstractBaseClass {

    protected $filePath;

    protected $daneaCode;

    protected $fatturaXML;
    
    public function __construct(string $filePath, ?string $daneaCode = null)
    {
        $this->filePath = $filePath;
        $this->daneaCode = $daneaCode;

        if(!file_exists($this->filePath))
        {
            throw new \Exception("Errore durante la creazione del file EasyFatt: il file di fattura elettronica in ingresso non esiste");
        }

        if(($xml = simplexml_load_file($this->filePath)) === false)
        {
            throw new \Exception("Errore durante la creazione del file EasyFatt: il file di fattura elettronica non è un documento XML valido");
        }

        $xmlData = $xml->saveXML();

        $validator = new Validator();

        try {
            $validator->assertValidXml($xmlData);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $this->fatturaXML = $xml;
    }

    public function esportaFile(string $filePath): bool
    {
        $content = $this->esportaXML();
        if (false === file_put_contents($filePath, $content, LOCK_EX)) return false;

        return true;
    }

    public function esportaXML(): string
    {

        $f = $this->getArrayFattura();
        
        $ef = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><EasyfattDocuments AppVersion="2" Creator="Danea Easyfatt Enterprise  2019.45b" CreatorUrl="http://www.danea.it/software/easyfatt" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://www.danea.it/public/easyfatt-xml.xsd" />', 0, false, '', false);

        $company = $ef->addChild('Company', '', '');

        $ca = $f["FatturaElettronicaHeader"]["CedentePrestatore"]["DatiAnagrafici"];
        $cs = $f["FatturaElettronicaHeader"]["CedentePrestatore"]["Sede"];
        $cea = $f["FatturaElettronicaHeader"]["CessionarioCommittente"]["DatiAnagrafici"];
        $ces = $f["FatturaElettronicaHeader"]["CessionarioCommittente"]["Sede"];
        $documento = $f["FatturaElettronicaBody"]["DatiGenerali"]["DatiGeneraliDocumento"];
        $ritenuta = $documento["DatiRitenuta"] ?? null;
        $number = $documento["Numero"];
        $items = $f["FatturaElettronicaBody"]["DatiBeniServizi"]["DettaglioLinee"];
        $codiceDestinatario = $f["FatturaElettronicaHeader"]["DatiTrasmissione"]["CodiceDestinatario"];

        $company->addChild('Name', $ca["Anagrafica"]["Denominazione"]);
        $company->addChild('Address', $cs["Indirizzo"]);
        $company->addChild('Postcode', $cs["CAP"]);
        $company->addChild('City', $cs["Comune"]);
        $company->addChild('Province', $cs["Provincia"]);
        $company->addChild('FiscalCode', $ca["IdFiscaleIVA"]["IdCodice"]);
        $company->addChild('VatCode', $ca["IdFiscaleIVA"]["IdCodice"]);
        $company->addChild('Tel', '');
        $company->addChild('Email', '');

        $documents = $ef->addChild('Documents', '', '');
        $document = $documents->addChild('Document', '', '');
        $document->addChild('CustomerCode', $this->daneaCode);
        $document->addChild('CustomerWebLogin', '');
        $document->addChild('CustomerName', $cea["Anagrafica"]["Denominazione"]);
        $document->addChild('CustomerAddress', $ces["Indirizzo"]);
        $document->addChild('CustomerPostcode', $ces["CAP"]);
        $document->addChild('CustomerCity', $ces["Comune"]);
        $document->addChild('CustomerProvince', $ces["Provincia"]);
        $document->addChild('CustomerCountry', $ces["Nazione"]);
        $document->addChild('CustomerVatCode', $cea["IdFiscaleIVA"]["IdCodice"] ?? '');
        $document->addChild('CustomerEInvoiceDestCode', $codiceDestinatario);

        $document->addChild('DocumentType', 'I'); // Tipo di documento: I: fattura
        $document->addChild('Date', $documento["Data"]);
        $document->addChild('Number', $this->getNumber($number));
        $document->addChild('Numbering', $this->getNumbering($number));
        $document->addChild('CostDescription', '');
        $document->addChild('CostVatCode', '');
        $document->addChild('CostAmount', '');
        $document->addChild('ContribDescription', '');
        $document->addChild('ContribPerc', '');
        $document->addChild('ContribSubjectToWithholdingTax', '');
        $document->addChild('ContribAmount', '');
        $document->addChild('ContribVatCode', '');


        $totalWithoutTax = 0;
        $vatAmount = 0; // Ammontare totale dell'IVA
        $withHoldingTax = $ritenuta ? $ritenuta["ImportoRitenuta"] : 0;

        foreach($items as $i) {
            $aliquotaIva = round($i["AliquotaIVA"], 0);
            $prUnit = round($i["PrezzoUnitario"], 2);
            $qt = round($i["Quantita"], 2);
            $prTot = $prUnit * $qt;
            $totalWithoutTax += $prTot;
            $vatAmount += round(($prTot * ($aliquotaIva / 100)), 2);
        }

        // Calcolare il totale con iva
        $total = round($totalWithoutTax + $vatAmount - $withHoldingTax, 2);

        $document->addChild('TotalWithoutTax', $this->format($totalWithoutTax));
        $document->addChild('VatAmount', $this->format($vatAmount));
        $document->addChild('WithholdingTaxAmount', $withHoldingTax);
        $document->addChild('WithholdingTaxAmountB', 0);
        $document->addChild('WithholdingTaxNameB', '');
        $document->addChild('Total', $this->format($total));
        $document->addChild('PriceList', '');
        $document->addChild('PricesIncludeVat', 'false');
        $document->addChild('TotalSubjectToWithholdingTax', $ritenuta ? $this->format($totalWithoutTax) : 0);
        $document->addChild('WithholdingTaxPerc', 23);
        $document->addChild('WithholdingTaxPerc2', 50);
        $document->addChild('PaymentName', "Bonifico immediato");
        $document->addChild('PaymentBank', '');

        $payments = $document->addChild('Payments');
        $payment = $payments->addChild('Payment');

        $payment->addChild('Advance', 'false');
        $payment->addChild('Date', $documento["Data"]);
        $payment->addChild('Amount', $total);
        $payment->addChild('Paid', 'false');

        $document->addChild('InternalComment', $documento["Causale"] ?? '');
        $document->addChild('CustomField1', '');
        $document->addChild('CustomField2', '');
        $document->addChild('CustomField3', '');
        $document->addChild('CustomField4', '');
        $document->addChild('FootNotes', '');
        $document->addChild('SalesAgent', '');
        $document->addChild('DelayedVat', 'false');
        
        $rows = $document->addChild('Rows');

        foreach($items as $i) {
            $aliquotaIva = round($i["AliquotaIVA"], 0);
            // Calcolare il prezzo unitario comprensivo di IVA
            $price = round($i["PrezzoUnitario"] + ($i["PrezzoUnitario"] * ($aliquotaIva / 100)), 2);

            // Calcolare il prezzo totale comprensivo di iva
            $totalPrice = round($price * $i["Quantita"], 2);

            $row = $rows->addChild('Row');
            $row->addChild('Code', '');
            $row->addChild('Description', $i["Descrizione"]);
            $row->addChild('Qty', round($i["Quantita"]));
            $row->addChild('Um', 'pz');
            $row->addChild('Price', $this->format($price));
            $row->addChild('Discounts', '');
            $vatCode = $row->addChild('VatCode', $aliquotaIva);
            $vatCode->addAttribute("Perc", $aliquotaIva);
            $vatCode->addAttribute("Class", "Imponibile");
            $vatCode->addAttribute("Description", "Imponibile " . $aliquotaIva . "%");
            $row->addChild('Total', $this->format($totalPrice));
            $row->addChild('Stock', 'false');
            $row->addChild('Notes', '');
        }

        return $ef->saveXML();
    }

    protected function getArrayFattura(): array
    {
        return json_decode(json_encode((array) $this->fatturaXML), TRUE);
    }

    protected function getNumber(string $number): int
    {
        return $this->explodeNumber($number)[0];
    }

    protected function getNumbering(string $number): string
    {
        $n = $this->explodeNumber($number);

        if(count($n) > 1) return '/' . $n[1];
        else return '';
    }

    protected function explodeNumber(string $number): array
    {
        return explode('/', $number);
    }
}
<?php

namespace Nexev\EFat\Entities\Containers;

use Nexev\EFat\Entities\Abstracts\AbstractBaseClass;
use Nexev\EFat\Entities\Servizio;

class ServiziContainer extends AbstractBaseClass
{

    /**
     * Array contenente gli oggetti di tipo Servizio
     *
     * @var array<\Nexev\EFat\Entities\Servizio>
     */
    private $servizi;

    /**
     * Totale imponibile
     *
     * @var float
     */
    private $imponibile;

    /**
     * Totale IVA
     *
     * @var float
     */
    private $iva;

    public function __construct()
    {
        $this->servizi = [];
        $this->imponibile = 0;
        $this->iva = 0;
    }

    public function aggiungiServizio(Servizio $servizio)
    {
        $this->servizi[] = $servizio;

        $this->imponibile += $servizio->getImponibile();
        $this->iva += $servizio->getIVA();
    }

    public function aggiungiServizi(array $servizi)
    {
        foreach ($servizi as $s) {
            if (!is_a($s, 'Nexev\EFat\Entities\Servizio')) throw new \Exception("L'array non è popolato da soli elementi di tipo Nexev\EFat\Entities\Servizio");
        }

        foreach ($servizi as $s) {
            $this->aggiungiServizio($s);
        }
    }

    public function rimuoviServizio(Servizio $servizio)
    {
        for ($i = 0; $i < count($this->servizi); $i++) {
            if ($this->servizi[$i] == $servizio) {
                // TODO: rimuovi servizio dall'array
            }
        }
    }

    public function getImponibile(): float
    {
        return $this->imponibile;
    }

    public function getIVA(): float
    {
        return $this->iva;
    }

    public function getTotale(): float
    {
        return $this->iva + $this->imponibile;
    }

    public function getArray(): array
    {
        return $this->servizi;
    }

    public function count(): int
    {
        return count($this->servizi);
    }

    /**
     * Ritorna un array strutturato con il valore delle aliquote iva,
     * l'imponibile e il prezzo iva riferito a quella aliquota
     * [
     *  'aliquota',
     *  'imponibile',
     *  'iva'
     * ]
     * 
     *
     * @return array
     */
    public function getAliquoteIVA(): array
    {
        $aliquote = [];
        foreach ($this->servizi as $s) {

            $found = false;

            for ($i = 0; $i < count($aliquote); $i++) {
                if ($s->getAliquotaIva() == $aliquote[$i]['aliquota']) {
                    $found = true;
                    $aliquote[$i]['imponibile'] += $s->getImponibile();
                    $aliquote[$i]['iva'] += $s->getIVA();
                    break;
                }
            }

            if (!$found) {
                $i = count($aliquote);
                $aliquote[$i] = [
                    'aliquota' => $s->getAliquotaIva(),
                    'imponibile' => $s->getImponibile(),
                    'iva' => $s->getIVA()
                ];
            }
        }

        return $aliquote;
    }

    public function compilaBeniServizi(\SimpleXMLElement $el): \SimpleXMLElement
    {
        $itemNumber = 0;

        foreach ($this->servizi as $item) {

            $itemNumber++;
            $dl = $el->addChild('DettaglioLinee');
            $dl->addChild('NumeroLinea', $itemNumber);
            $dl->addChild('Descrizione', $this->clearString($item->getDescrizione()));
            $dl->addChild('Quantita', $this->format($item->getQuantita()));
            $dl->addChild('PrezzoUnitario', $this->format($item->getPrezzoUnitario()));
            $dl->addChild('PrezzoTotale', $this->format($item->getImponibile()));
            $dl->addChild('AliquotaIVA', $this->getValorePercentuale($item->getAliquotaIva()));
        }


        foreach ($this->getAliquoteIVA() as $aliquota) {
            $datiRiepilogo = $el->addChild('DatiRiepilogo');
            $datiRiepilogo->addChild('AliquotaIVA', $this->getValorePercentuale($aliquota['aliquota']));
            $datiRiepilogo->addChild('ImponibileImporto', $this->format($aliquota['imponibile']));
            $datiRiepilogo->addChild('Imposta', $this->format($aliquota['iva']));

            // EsigibilitaIVA: obbligatorio solo se si è nel campo delle operazioni imponibili.
            $datiRiepilogo->addChild('EsigibilitaIVA', 'I'); // 'I' per IVA ad esigibilità immediata, 'D' per IVA ad esigibilità differita.
        }

        return $el;
    }
}

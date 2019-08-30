<?php

namespace Nexev\EFat\Entities;

use Nexev\EFat\Entities\Abstracts\AbstractBaseClass;

class Ritenuta extends AbstractBaseClass {

    /**
     * Tipo di ritenuta: RT01, RT02.
     * Se non impostata la imposta guardando la tipologia di cessionario/committente
     *
     * @var string
     */
    private $tipo;

    /**
     * Valore rappresentante l'aliquota della ritenuta: numero da 0 a 1
     *
     * @var float
     */
    private $aliquota;

    /**
     * Causale pagamento: un valore tra quelli definiti
     *
     * @var string
     */
    private $causale;

    public function __construct(float $aliquota, string $causale = 'Z', ?string $tipo = null)
    {
        $this->setAliquota($aliquota);
        $this->setCausale($causale);
        $this->setTipo($tipo);
    }

    public function setAliquota(float $aliquota): void
    {
        if($aliquota < 0 OR $aliquota > 1) throw new \Exception("L'aliquota della ritenuta deve essere un valore percentuale, quindi compreso tra 0 e 1");

        $this->aliquota = $aliquota;
    }

    public function setCausale(string $causale): void
    {
        if(!in_array(
            $causale,
            [
                'A',
                'B',
                'C',
                'D',
                'E',
                'F',
                'G',
                'H',
                'I',
                'L',
                'M',
                'N',
                'O',
                'P',
                'Q',
                'R',
                'S',
                'T',
                'U',
                'V',
                'W',
                'X',
                'Y',
                'Z',
            ]
        )) throw new \Exception("Causale dell'aliquota non valida");

        $this->causale = $causale;
    }

    public function setTipo(?string $tipo): void
    {
        if(is_null($tipo)) return;

        if(!in_array(
            $tipo,
            [
                'RT01',
                'RT02'
            ]
        )) throw new \Exception("Il tipo di ritenuta può essere soltanto un valore dei seguenti: [RT01, RT02]");

        $this->tipo = $tipo;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function getAliquota(): float
    {
        return $this->aliquota;
    }

    public function getCausale(): string
    {
        return $this->causale;
    }

    public function compilaDatiRitenuta(\SimpleXMLElement $el, float $totale): void
    {
        $importoRitenuta = $totale * $this->aliquota;
        $el->addChild('TipoRitenuta', $this->tipo); // RT01 per persone fisiche, RT02 per persone giuridiche
        $el->addChild('ImportoRitenuta', $this->format($importoRitenuta));
        $el->addChild('AliquotaRitenuta', $this->getValorePercentuale($this->aliquota));
        $el->addChild('CausalePagamento', $this->causale);
    }
}
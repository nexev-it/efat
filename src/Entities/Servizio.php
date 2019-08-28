<?php

namespace Nexev\EFat\Entities;

abstract class Servizio {

    /**
     * Descrizione del servizio
     *
     * @var string
     */
    private $descrizione;

    /**
     * Quantità del servizio
     *
     * @var integer
     */
    private $quantita;

    /**
     * Prezzo del singolo servizio IVA esclusa
     *
     * @var float
     */
    private $prezzoUnitario;

    /**
     * Percentuale IVA del servizio (numero tra 0 e 1)
     *
     * @var float
     */
    private $aliquotaIVA;
    
    public function __construct(string $descrizione, int $quantita, float $prezzoUnitario, float $aliquotaIVA) {
        if(strlen(($descrizione) < 1)) throw new \Exception("Descrizione troppo corta"); // TODO: inserire classe descrittiva
        if($quantita < 1) throw new \Exception("Quantità troppo bassa"); // TODO: inserire classe descrittiva
        if($prezzoUnitario < 0) throw new \Exception("Prezzo unitario inserito negativo"); // TODO: inserire classe descrittiva
        if($aliquotaIVA < 0 OR $aliquotaIVA > 1) throw new \Exception("Aliquota IVA non corretta"); // TODO: inserire classe descrittiva
        
        $this->descrizione = $descrizione;
        $this->quantita = $quantita;
        $this->prezzoUnitario = $prezzoUnitario;
        $this->aliquotaIVA = $aliquotaIVA;
    }
}
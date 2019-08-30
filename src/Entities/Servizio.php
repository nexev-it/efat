<?php

namespace Nexev\EFat\Entities;

use Nexev\EFat\Entities\Abstracts\AbstractBaseClass;

abstract class Servizio extends AbstractBaseClass {

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
    
    public function __construct(string $descrizione, float $prezzoUnitario, int $quantita = 1, float $aliquotaIVA = 0.22)
    {
        $this->setDescrizione($descrizione);
        $this->setPrezzoUnitario($prezzoUnitario);
        $this->setQuantita($quantita);
        $this->setAliquotaIVA($aliquotaIVA);
    }

    public function setDescrizione(string $descrizione): void
    {
        if (strlen(($descrizione) < 1)) throw new \Exception("Descrizione del servizio troppo corta");
        $this->descrizione = $descrizione;
    }

    public function setPrezzoUnitario(float $prezzoUnitario): void
    {
        if($prezzoUnitario < 0) throw new \Exception("Il prezzo unitario del servizio non può essere negativo");
        $this->prezzoUnitario = $prezzoUnitario;
    }

    public function setQuantita(int $quantita): void
    {
        if($quantita < 0) throw new \Exception("La quantità di un servizio non può essere negativa");
        $this->quantita = $quantita;
    }

    public function setAliquotaIVA(float $aliquotaIVA): void
    {
        if($aliquotaIVA < 0 OR $aliquotaIVA > 1) throw new \Exception("L'aliquota IVA deve essere una percentuale, ovvero un numero compreso tra 0 e 1");
        $this->aliquotaIVA = $aliquotaIVA;
    }

    public function getDescrizione(): string
    {
        return $this->descrizione;
    }

    public function getQuantita(): int
    {
        return $this->quantita;
    }

    public function getPrezzoUnitario(): float
    {
        return $this->prezzoUnitario;
    }

    public function getAliquotaIVA(): float
    {
        return $this->aliquotaIVA;
    }

    public function getImponibile(): float
    {
        return $this->prezzoUnitario * $this->quantita;
    }

    public function getIVA(): float
    {
        return $this->getImponibile() * $this->aliquotaIVA;
    }

    public function getTotale(): float
    {
        return $this->getImponibile() + $this->getIVA();
    }
}
<?php

namespace Nexev\EFat\Builders;;

use Nexev\EFat\Entities\Indirizzo;
use Nexev\EFat\Entities\PersonaFisica;
use Nexev\EFat\Entities\PersonaGiuridica;
use Nexev\EFat\Entities\PubblicaAmministrazione;
use Nexev\EFat\Entities\REA;
use Nexev\EFat\Entities\Ritenuta;
use Nexev\EFat\Entities\Servizio;
use Nexev\EFat\FatturaPA;
use Nexev\EFat\FatturaPrivati;

class FatturaBuilder {

    /**
     * Crea un oggetto Fattura destinata ad una persona giuridica
     * o ad una persona fisica
     *
     * @param string $numero
     * @param string $progressivoInvio
     * @param DateTime|null $data
     * @return FatturaPrivati
     */
    public static function creaFatturaPrivati(string $numero, string $progressivoInvio, ?DateTime $data = null, $esigibileIVA = true): FatturaPrivati {
        return new FatturaPrivati($numero, $progressivoInvio, $data, $esigibileIVA);
    }

    /**
     * Crea un oggetto Fattura destinata ad alla Pubblica
     * Amministrazione
     *
     * @param string $numero
     * @param string $progressivoInvio
     * @param DateTime|null $data
     * @return FatturaPA
     */
    public static function creaFatturaPA(string $numero, string $progressivoInvio, ?DateTime $data = null, $esigibileIVA = true): FatturaPA {
        return new FatturaPA($numero, $progressivoInvio, $data, $esigibileIVA);
    }

    /**
     * Crea un oggetto Persona Fisica
     * La persona fisica può essere impostata come
     * cedente/prestatore della fattura
     *
     * @param string $denominazione
     * @param string $partitaIVA
     * @param string $codiceSDI
     * @param string $pec
     * @param string $regimeFiscale
     * @return PersonaFisica
     */
    public function creaPersonaFisica(string $denominazione, string $partitaIVA, string $codiceSDI = null, string $pec = null, string $regimeFiscale = 'RF01'): PersonaFisica {
        return new PersonaFisica($denominazione, $partitaIVA, $codiceSDI, $pec, $regimeFiscale);
    }

    /**
     * Crea un oggetto Persona Giuridica
     *
     * @param string $denominazione
     * @param string $partitaIVA
     * @param string $codiceSDI
     * @param string $pec
     * @param string $regimeFiscale
     * @return PersonaGiuridica
     */
    public function creaPersonaGiuridica(string $denominazione, string $partitaIVA, string $codiceSDI = null, string $pec = null, string $regimeFiscale = 'RF01'): PersonaGiuridica {
        return new PersonaGiuridica($denominazione, $partitaIVA, $codiceSDI, $pec, $regimeFiscale);
    }

    /**
     * Crea un oggetto Pubblica Amministrazione
     *
     * @param string $denominazione
     * @param string $partitaIVA
     * @param string $codiceSDI
     * @param string $pec
     * @param string $regimeFiscale
     * @return PubblicaAmministrazione
     */
    public function creaPubblicaAmministrazione(string $denominazione, string $partitaIVA, string $codiceSDI = null, string $pec = null, string $regimeFiscale = 'RF01'): PubblicaAmministrazione {
        return new PubblicaAmministrazione($denominazione, $partitaIVA, $codiceSDI, $pec, $regimeFiscale);
    }

    /**
     * Crea un oggetto Indirizzo da assegnare ad un soggetto
     * (Pubblica Amministrazione, Persona Fisica, Persona 
     * Giuridica)
     *
     * @param string $indirizzo
     * @param string $citta
     * @param string $provincia
     * @param string $cap
     * @return Indirizzo
     */
    public function creaIndirizzo(string $indirizzo, string $citta, string $provincia, string $cap): Indirizzo {
        return new Indirizzo($indirizzo, $citta, $provincia, $cap);
    }

    /**
     * Crea un servizio da associare alla fattura
     *
     * @param string $descrizione
     * @param float $prezzoUnitario
     * @param integer $quantita
     * @param float $aliquotaIVA
     * @return Servizio
     */
    public function creaServizio(string $descrizione, float $prezzoUnitario, int $quantita = 1, float $aliquotaIVA = 0.22): Servizio {
        return new Servizio($descrizione, $prezzoUnitario, $quantita, $aliquotaIVA);
    }

    /**
     * Crea un oggetto di dettaglio dei dai REA da associare al
     * cedente/prestatore nel caso in cui egli sia una persona 
     * giuridica e che sia iscritto al Registro delle Imprese
     *
     * @param string $numero
     * @param string $ufficio
     * @param boolean $socioUnico
     * @param float|null $capitaleSociale
     * @param boolean $inLiquidazione
     * @return REA
     */
    public function creaREA(string $numero, string $ufficio, bool $socioUnico, ?float $capitaleSociale = null, bool $inLiquidazione = false): REA {
        return new REA($numero, $ufficio, $socioUnico, $capitaleSociale, $inLiquidazione);
    }

    /**
     * Crea una ritenuta d'acconto da associare alla fattura
     * creata
     *
     * @param float $aliquota
     * @param string $causale
     * @param string|null $tipo
     * @return Ritenuta
     */
    public function creaRitenuta(float $aliquota, float $percentualeSuImponibile = 1, string $causale = 'Z', ?string $tipo = null): Ritenuta {
        return new Ritenuta($aliquota, $percentualeSuImponibile, $causale, $tipo);
    }
}
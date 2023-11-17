<?php

namespace Nexev\EFat\Entities\Interfaces;

interface CedenteInterface {
	public function checkForCedente(): bool;

	public function compilaCedentePrestatore(\SimpleXMLElement $el): void;

	public function getPartitaIVA(): string;

	public function getStringaErrori(): string;

}

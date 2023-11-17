<?php

namespace Nexev\EFat\Entities\Interfaces;

interface TrasmittenteInterface {

	public function checkForTrasmittente(): bool;

	public function compilaIdTrasmittente(\SimpleXMLElement $el): void;

	public function compilaTerzoIntermediario(\SimpleXMLElement $el): void;

	public function getPartitaIVA(): string;

	public function getPaese(): string;

	public function getStringaErrori(): string;

}
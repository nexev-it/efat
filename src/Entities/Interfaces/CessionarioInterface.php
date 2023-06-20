<?php

namespace Nexev\EFat\Entities\Interfaces;

interface CessionarioInterface {

	public function checkForCessionario(): bool;

	public function getCodiceSDI(): string;

	public function getPEC(): ?string;

	public function compilaCessionarioCommittente(\SimpleXMLElement $el): void;

}

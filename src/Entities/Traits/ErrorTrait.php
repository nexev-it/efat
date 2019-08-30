<?php

namespace Nexev\EFat\Entities\Traits;

trait ErrorTrait {


    /**
     * Array di errori accumulati dai processi di controllo
     *
     * @var array
     */
    protected $errori;

    public function getStringaErrori(): string
    {
        $return = "";
        for ($i = 0; $i < count($this->errori); $i++) {
            $j = $i + 1;
            $return .= $j . ". " . $this->errori[$i];
            if ($j != count($this->errori)) $return .= " - ";
        }

        return $return;
    }
}
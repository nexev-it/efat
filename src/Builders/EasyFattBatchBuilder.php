<?php

namespace Nexev\EFat\Builders;

use Nexev\EFat\Entities\Abstracts\AbstractBaseClass;
use Exception;

class EasyFattBatchBuilder extends AbstractBaseClass
{
    protected $zipName;
    protected $builders;

    public function __construct(array $elements)
    {
        if(count($elements) < 1) throw new Exception("Non è stata dichiarata alcuna fattura di cui creare il file EasyFatt");

        $this->builders = [];
        
        for($i=0; $i<count($elements); $i++) {
            $e = $elements[$i];
            if(!array_key_exists('xml', $e) OR !array_key_exists('name', $e) OR !is_string($e['xml']) OR !is_string($e['name']))
                throw new Exception("L'array non è formattato in modo corretto alla posizione $i. Contattare l'amministratore di sistema");
            
            try {
                $this->builders[] = [
                    'name' => $e['name'],
                    'content' => (new EasyFattBuilder($e['xml']))->esportaXML()
                ];
            }
            catch(Exception $e) {
                throw new Exception("File di riferimento" . $e['name'] . ": " . $e->getMessage());
            }
        }

        $this->zipName = 'EasyFatt_export_' . (new \DateTime())->format('Y-m-d_H-i') . '.zip';
    }

    public function creaZip()
    {
        $options = new \ZipStream\Option\Archive();
        $options->setSendHttpHeaders(true);

        # create a new zipstream object
        $zip = new \ZipStream\ZipStream($this->zipName, $options);

        foreach($this->builders as $b) {
            $zip->addFile($b['name'], $b['content']);
        }
        $zip->finish();
    }
}

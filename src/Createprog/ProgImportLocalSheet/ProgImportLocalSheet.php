<?php
/**
 * Created by PhpStorm.
 * User: nrt
 * Date: 28/11/2017
 * Time: 15:39
 */

namespace Smoetje\Proggenerator\CreateProg\ProgImportLocalSheet;

use Smoetje\Proggenerator\CreateProg\IProgImport;

class ProgImportLocalSheet implements IProgImport
{
    private $url = null;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function readDocument()
    {
        // TODO: Implement algorithm() method.
        dd("Verify and Import Local Sheet");

    }

    public function getProgData(){
        return $this->data;
    }

    public function getProgHeaders(){

    }

    private function validateProgHeaders(){

    }

}
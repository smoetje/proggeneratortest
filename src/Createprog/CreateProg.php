<?php
/**
 * Created by PhpStorm.
 * User: nrt
 * Date: 28/11/2017
 * Time: 15:18
 */

namespace Smoetje\Proggenerator\CreateProg;

use Smoetje\Proggenerator\CreateProg\ProgrammatieParser\ProgrammatieParseAdapter;


class CreateProg
{
    private $prog = NULL;
    private $progAdapter;

    public function __construct($url, $sheet_kind = "googlesheet")
    {
        switch ($sheet_kind) {
            case "googlesheet":
                $this->prog = new \App\Custom\CreateProg\ProgImportGoogleSheet\ProgImportGoogleSheet($url);
                break;
            case "excelsheet":
                $this->prog = new \App\Custom\CreateProg\ProgImportExcel\ProgImportExcel($url);
                break;
            case "jsonfile":
                $this->prog = new \App\Custom\CreateProg\ProgImportLocalSheet\ProgImportLocalSheet($url);
                break;
            default:
                $this->prog = NULL;
        }

        $result = null;
        if (isset($this->prog)) {
            $result = $this->parseProg();
        }
    }

    public function parseProg()
    {
        $result = $this->prog->readDocument(); // inlezen googlesheet, excel, json, etc

        //dd($this->prog);

        return $result;
    }

    /**
     * Stores the sheet object data into the database
     * @return array|bool
     */
    public function storeProg()
    {
        $this->progAdapter = new ProgrammatieParseAdapter($this->prog);
        $result = $this->progAdapter->createTables();

        //dd($this->progAdapter);
        //dump($result);

        if(!$result){
            return false;
        }

        //dd($this->progAdapter);

        $this->progAdapter->storeTables();
        return true;
    }

    private function getProg()
    {
        return $this->prog->getProgData();
    }

    public function getAantalGroepenovergeslagen(){
        return $this->progAdapter->getAantalGroepenOvergeslagen();
    }

    public function getAantalGroepenToegevoegdInDb(){
        return $this->progAdapter->getAantalGroepenToegevoegdInDb();
    }

    public function getOpgenomenGroepen(){
        return $this->progAdapter->getOpgenomenGroepen();
    }

    public function getOvergeslagenGroepen(){
        return $this->progAdapter->getOvergeslagenGroepen();
    }

    public function getErrors(){
        return $this->prog->getErrors();
    }

    public function getWarnings(){
        return $this->progAdapter->getWarnings();
    }

}


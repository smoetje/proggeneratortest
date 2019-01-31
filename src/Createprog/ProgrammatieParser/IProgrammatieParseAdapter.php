<?php
/**
 * Created by PhpStorm.
 * User: nicol
 * Date: 28/11/2017
 * Time: 22:59
 */

namespace Smoetje\Proggenerator\CreateProg\ProgrammatieParser;


interface IProgrammatieParseAdapter
{
    public function getAantalGroepenToegevoegdInDb();
    public function getAantalGroepenOvergeslagen();
    public function getOpgenomenGroepen();
    public function getOvergeslagenGroepen();
    public function getWarnings();

    public function createTables();
    public function storeTables();

}
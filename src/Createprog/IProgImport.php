<?php
/**
 * Created by PhpStorm.
 * User: nrt
 * Date: 28/11/2017
 * Time: 14:52
 */

namespace Smoetje\Proggenerator\CreateProg;

interface IProgImport
{
    public function readDocument();
    public function getProgData();
    public function getProgHeaders();
}


<?php
/**
 * Doel van deze class:
 * - Een algoritme maken, die de google sheet opent, checks maakt en integraal alle gegevens uitleest in in een property zet
 * - Als alle checks ok zijn, wordt de array in dit object bewaard voor latere verwerking
 *
 * Een andere klasse kan dan verder het object verwerken, afparsen, bewaren in database en resultaat teruggeven
 *
 * Created by PhpStorm.
 * User: nrt
 * Date: 28/11/2017
 * Time: 15:39
 */

namespace Smoetje\Proggenerator\CreateProg\ProgImportGoogleSheet;

use App\GoogleModel;
use Google;
use GuzzleHttp\Exception\ConnectException;
use Sheets;
use Google_Service_Exception;

use Smoetje\Proggenerator\CreateProg\IProgImport;
use App\Custom\ProgDataValidator\ProgDataValidator;

use Exception;

class ProgImportGoogleSheet implements IProgImport
{
    private $googleId = "";
    private $url = null;
    private $data = [];
    private $dataLoaded = false;          // Data loaded into object ?
    private $columnHeaders = [];

    private $onlineGoogleDataAvailable = null;

    private $errors = [];

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Get database ID, verify database and check if google sheet is valid and found!
     * @param null $database_id
     * @throws \Exception
     */
    public function readDocument()
    {
        $sheetFound = $this->filterUrl();
        if(!$sheetFound){
            throw new \Exception('Google sheet ID could not be parsed!');

        }
        $result = $this->getGoogleSheetData();  // Haal google sheet op, bewaar in property

        try{
            $this->validateProgHeaders();
        }
        catch (\Exception $e) {
            array_push($this->errors, $e);
            return false;
        }

        return $result;
    }

    /**
     * @param $id
     * @return bool
     */
    private function filterUrl() {
        if($this->url){
            $string = $this->url;
            $pattern = "/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/"; // Filter full url

            preg_match($pattern, $string, $matches);

            if(empty($matches)){
                $pattern = "/([a-zA-Z0-9-_]+)/"; // Filter partial url
                preg_match($pattern, $string, $matches);
            }

            if(empty($matches)){
                return false;
            }
            else {
                $this->googleId = $matches[1];
                return $matches[1];
            }
        }

        return false;

    }

    /**
     * Get the data from the active google sheet
     * Stores the result in $this->data
     * @return bool
     */
    private function getGoogleSheetData(){
        try {
            Sheets::setService(Google::make('sheets'));
            Sheets::spreadsheet($this->googleId);

            $rows = Sheets::sheet('PrgPaulusfeesten')->get();    // $rows = Laravel Collection
            $this->columnHeaders = $rows->pull(1);                      // Titels uithalen
            $this->data = Sheets::collection($this->columnHeaders, $rows)->toArray();

            unset($this->data[0]);                              // Verwijder eerste nodeloze top-row array elementen (header data)

            $this->onlineGoogleDataAvailable = true;
            $this->dataLoaded = true;
            return true;
        }
        catch (Google_Service_Exception $e) {
            array_push($this->errors, $e->getMessage());
            $this->onlineGoogleDataAvailable = false;
            return false;
        }
        catch (ConnectException $e) {
            // In case of no internet-connection, different exception...
            array_push($this->errors, $e->getMessage());
            $this->onlineGoogleDataAvailable = false;
            return false;
        }
    }

    public function getProgData(){
        return $this->data;
    }

    public function getErrors(){
        return $this->errors;
    }

    public function getProgHeaders(){
        return $this->columnHeaders;
    }

    private function validateProgHeaders(){
        $validation = ProgDataValidator::validateData($this->columnHeaders);
        if(is_array($validation)){
            throw new Exception('Columns are missing in source file: ' . implode(",", $validation));
        }
    }
}
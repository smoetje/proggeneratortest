<?php

namespace Smoetje\Proggenerator;

use App\Http\Controllers\Controller;
use Smoetje\Proggenerator\CreateProg\CreateProg;
use Smoetje\Proggenerator\models\GoogleModel;
use Smoetje\Proggenerator\modules\ApiResponse;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Google;
use League\Glide\Api\Api;
use Mockery\Exception;
use Sheets;
use Google_Service_Exception;


class GoogleSheetMgtController extends Controller
{
    private $apiResponse = null;

    private $sheetColumns = ['dag','weekdag','locatie','uur','groepsnaam','genre','subgenre','omschrijvingkort','omschrijvinglang','website','foto01','foto02','foto03','videolink1','videolink2','videolink3'];

    private $errorMsg = [
        'validationError' => [
            'nr' => '0',
            'description' => 'url validator error'
        ],
        'googleUrlInvalid' => [
            'nr' => '1',
            'description' => 'Deze URL bevat geen geldige Google Sheet ID!'
        ],
        'invalidGoogleSheet' => [
            'nr' => '2',
            'description' => 'Deze URL bevat geen geldige Google Sheet!'
        ],
        'invalidGoogleSheetID' => [
            'nr' => '3',
            'description' => 'Geen geldige Google Sheet ID gevonden!  Controleer of "link sharing" is geactiveerd!  Check eigenaar van desbetreffende sheet!'
        ],
        'invalidSheetData' => [
            'nr' => '4',
            'description' => 'Google sheet bevat geen "PrgPaulusfeesten" tab, geen geldige programmatie sheet.'
        ],
        'cannotOpenSheet' => [
            'nr' => '5',
            'description' => 'Google sheet kon niet worden geopend.  De link bevatte geen geldig document.'
        ],
        'cannotFindSheetTab' => [
            'nr' => '6',
            'description' => 'Google sheet bevat geen geldige "PrgPaulusfeesten" tab.'
        ],
        'invalidColumnData' => [
            'nr' => '7',
            'description' => 'Google sheet bevat geen geldige kolom data'
        ],
        'columnDataNotFound' => [
            'nr' => '8',
            'description' => 'Geen kolom data gevonden'
        ],
        'columnNotFound' => [
            'nr' => '10',
            'description' => 'Rij 2 Kolom ... bevat geen naam ... in de Google Sheet'
        ],
        'urlUnAvailable' => [
            'nr' => '503',
            'description' => 'GoogleSheet is onbereikbaar. Probeer later opnieuw!'
        ]
    ];

    public function __construct(){
        $this->apiResponse = new ApiResponse();
    }

    // Obsolete code
    //public function import(Request $request, $year = null) {
    public function import(Request $request, GoogleModel $googleModel) {
        dd($request->all());

        /* Old code - OBSOLETE!
        *  optioneel kan je later de google ID gebruiken (die uit een database wordt gehaald per jaar)
        *  $importObj = new importGoogleSheet($request->get('id'));
        *  $importObj->parseActiveGoogleSheet();
        */

        // Retrieve URL to googlesheet, excelsheet, json...
        $record_id = $request->get('id');
        $urlData = $googleModel->where('id','=', $record_id)->first();

        if(is_null($urlData)){
            return redirect('/beheer/importeren')
                ->withErrors([
                'errormsg' => 'No valid record found in database!  Reload page & try again!'
            ]);
        }

        // Put URL to parser-classes
        $programmatie = new CreateProg($urlData->googlesheet_id);
        $result = $programmatie->parseProg(); // true = successfully parsed

        if(!$result){
            return redirect('/beheer/importeren')
                ->withErrors([
                    'errormsg' => 'This document could not be read.  Invalid file?'
                ]);
        }

        $result = $programmatie->storeProg();

        //dd($programmatie);

        if(!$result){
            return redirect('/beheer/importeren')
                ->withErrors([
                    'errormsg' => 'This document content could not be parsed.  Incompatible format or not implemented?'
                ]);
        }

        $year = $urlData->editie_jaar;

        return view('pages.status', [
            'editie' => $year,
            'aantal_opgenomen' => $programmatie->getAantalGroepenToegevoegdInDb(),
            'aantal_overgeslagen' => $programmatie->getAantalGroepenovergeslagen(),
            'toegevoegde_groepen' => $programmatie->getOpgenomenGroepen(),
            'overgeslagen_groepen' => $programmatie->getOvergeslagenGroepen()
        ]);
//        return view('pages.status', ['importStatuses' =>
//            [
//                'status' => 'Import alle data uit google sheet complete !',
//                'programmatie' => 'Programmatie editie "' . $year . '" ingesteld!',
//            ],
//            //'geimporteerd' => $result['geimporteerd'],
//            //'geweigerd' => $result['overgeslagen']
//        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
//    public function index(GoogleModel $googleSheetDb)
//    {
//
//        $errors = [];
//
//        $result = $googleSheetDb->all();
//
//        //dd($result);
//
//        //return $this->apiResponse->create($result, $errors);
//        //return view('pages.manage.jaargangen', ['googleSheetData' => $result]);
//        return view('pages.manage.googlesheets', ['googleSheetData' => $result]);
//    }

    public function index2(GoogleModel $googleSheetDb)
    {
        return view('pages.admin.sheets.progmanagement');
    }

//    public function importProgrammatieIndex(GoogleModel $googleModel){
//        $errors = [];
//        $result = $googleModel->all();
//
//        //dd($result);
//
//        return view('pages.manage.importeer', [ 'sheets' => $result ]);
//    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

    }

    /**
     * Blade response
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
//    public function store(Request $request, GoogleModel $googleSheetDb)
//    {
//        //-- Validator
//        $rules = [
//            'editie_jaar' => 'required|min:4',
//            'googlesheet_id' => 'required|url|regex:^https://docs.google.com/spreadsheets/d^'  //-- Valid URL?
//        ];
//
//        $result = [];
//        $errors = [];
//
//        $data = $request->except('_token');
//
//        //-- Validatie check gegevens
//        $validator = Validator::make($data, $rules);
//
//        if ($validator->fails()) {
//            $errors = $this->errorMsg['validationError'];
//            $errors['description'] = $validator->errors();  // override 'default' error message to specific error
//            return $this->apiResponse->create($result,  $errors);
//        }
//
//        //-- URL gevalideerd: nu uit elkaar fluizen
//        try {
//            $url = $request->get('googlesheet_id');
//            preg_match("/spreadsheets\/d\/([a-zA-Z0-9-_]+)/", $url, $googleId); //-- Filter URL
//
//            //-- Check if retrieved data matches the pattern?
//            if(count($googleId) == 0){
//                $errors = $this->errorMsg['googleUrlInvalid'];
//                return $this->apiResponse->create($result, $errors);
//            }
//
//            //-- Filter google ID (normaal lijkt het op bijvb: "1Jv0rl-Q5ob7fQr_jtJcLS_GO5fz03y_WksiIBfol60E")
//            if((str_replace($googleId[1], "", $googleId[0]) != "spreadsheets/d/") || $googleId[1] == ""){
//                $errors = $this->errorMsg['invalidGoogleSheet'];
//                return $this->apiResponse->create($result, $errors);
//            }
//
//            $googleId = $googleId[1];
//
//        } catch (Exception $e) {
//            $errors = $this->errorMsg['invalidGoogleSheetID'];
//            return $this->apiResponse->create($result, $errors);
//        }
//
//        //-- Gevonden Google ID nu on-line checken of het document wel degelijk bestaat?
//        Sheets::setService(Google::make('sheets'));
//        Sheets::spreadsheet($googleId);
//
//        try {
//            //-- Checken of de sheet 'PrgPaulusfeesten' bestaat in het geselecteerde document (op basis van template)
//            $sheetTabs = Sheets::sheetList();
//
//            //-- Google ID bestaat!!
//            if(!in_array('PrgPaulusfeesten',$sheetTabs)){
//                $errors = $this->errorMsg['invalidSheetData'];
//                return $this->apiResponse->create($result, $errors);
//            };
//
//            //-- Google ID bestaat niet of is ongeldig!! (of de boel ligt plat)
//        } catch (Google_Service_Exception $e) {
//            $errors = $this->errorMsg['cannotOpenSheet'];
//            return $this->apiResponse->create($result, $errors);
//        }
//
//        //-- Haal nu alle data op uit de sheet-tab "PrgPaulusfeesten"
//        try {
//            $document = Sheets::sheet('PrgPaulusfeesten')->get();
//        } catch (Google_Service_Exception $e) {
//            dump($e);
//            $errors = $this->errorMsg['cannotFindSheetTab'];
//            return $this->apiResponse->create($result, $errors);
//        }
//
//        //-- Check nu op bestaan v kolommen.  Als ze bestaan -> GELDIGE PAULUSFEESTEN PROGRAMMATIE FILE, Feestje! JEUJ!
//        try {
//            $rij2 = 1;
//            if(!$document->has($rij2)) throw new Exception();
//            $test = $document[$rij2];
//
//        } catch (Exception $e) {
//            $errors = $this->errorMsg['columnDataNotFound'];
//            return $this->apiResponse->create($result, $errors);
//        }
//
//        for($i = 0; $i < count($this->sheetColumns); $i++){
//            $pos = null;
//            $kolomNr = $i + 1;
//
//            //-- Check of kolom bestaat (anders krijg je exception)
//            if(array_key_exists($i, $test)){
//                //-- verwijder onnodige spaties + maak alle letters klein
//                //dump($test[$i]);
//                $test[$i] = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $test[$i]));
//                //dump($test[$i]);
//                $pos = strpos($test[$i], $this->sheetColumns[$i]);
//            }
//
//            if(in_array($this->sheetColumns[$i], $test)) {
//                //-- Element existing = ok
//                //dump($checkKolom[$i] . " exists!");
//            }
//            else {
//                //dump($checkKolom[$i] . " dus NOT exists!");
//                $errors = $this->errorMsg['columnNotFound'];
//                $errors['description'] = 'Rij 2 Kolom ' . $kolomNr . ' bevat geen naam ' . $this->sheetColumns[$i] . ' in de Google Sheet';
//            }
//        }
//
//        if(!empty($errors)) return $this->apiResponse->create($result, $errors);
//
//        //-- Good enough for me, genoeg checks doorstaan.  Bewaar veranderde gegevens maar in database...
//        $data['googlesheet_status'] = true;
//        //dd($data);
//        $result = $googleSheetDb->create($data);
//
//        // stuur json-response terug met het (hopelijk heugelijke) nieuws...
//        return $this->apiResponse->create($result, $errors);
//    }

    private function verifyGoogleSheetContent(){

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(GoogleModel $googleSheetDb, $id)
    {
        $errors = [];

        $data = $googleSheetDb->where('id',$id)->first();
        $result = $this->checkNewGoogleSheet($data, $googleSheetDb);
        return $result;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * API call
     * Verify several formatting parameters of the Google Sheet
     * Update the specified resource in storage if all parameters are met.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GoogleModel $googleSheetDb, $id)
    {
        //-- Validator
        $rules = [
            'editie_jaar' => 'required|min:4',
            'googlesheet_id' => 'required|url|regex:^https://docs.google.com/spreadsheets/d^'  //-- Valid URL?
        ];

        $result = [];
        $errors = [];

        $data = $request->except('_token');

        //-- Validatie check gegevens
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $errors = $this->errorMsg['validationError'];
            $errors['description'] = $validator->errors();  // override 'default' error message to specific error
            return $this->apiResponse->create($result,  $errors);
        }

        //-- URL gevalideerd: nu uit elkaar fluizen
        try {
            $url = $request->get('googlesheet_id');
            preg_match("/spreadsheets\/d\/([a-zA-Z0-9-_]+)/", $url, $googleId); //-- Filter URL

            //-- Check if retrieved data matches the pattern?
            if(count($googleId) == 0){
                $errors = $this->errorMsg['googleUrlInvalid'];
                return $this->apiResponse->create($result, $errors);
            }

            //-- Filter google ID (normaal lijkt het op bijvb: "1Jv0rl-Q5ob7fQr_jtJcLS_GO5fz03y_WksiIBfol60E")
            if((str_replace($googleId[1], "", $googleId[0]) != "spreadsheets/d/") || $googleId[1] == ""){
                $errors = $this->errorMsg['invalidGoogleSheet'];
                return $this->apiResponse->create($result, $errors);
            }

            $googleId = $googleId[1];

        } catch (Exception $e) {
            $errors = $this->errorMsg['invalidGoogleSheetID'];
            return $this->apiResponse->create($result, $errors);
        }

        //-- Gevonden Google ID nu on-line checken of het document wel degelijk bestaat?
        Sheets::setService(Google::make('sheets'));
        Sheets::spreadsheet($googleId);

        try {
            //-- Checken of de sheet 'PrgPaulusfeesten' bestaat in het geselecteerde document (op basis van template)
            $sheetTabs = Sheets::sheetList();

            //-- Google ID bestaat!!
            if(!in_array('PrgPaulusfeesten',$sheetTabs)){
                $errors = $this->errorMsg['invalidSheetData'];
                return $this->apiResponse->create($result, $errors);
            };

        //-- Google ID bestaat niet of is ongeldig!! (of de boel ligt plat)
        } catch (Google_Service_Exception $e) {
            $errors = $this->errorMsg['cannotOpenSheet'];
            return $this->apiResponse->create($result, $errors);
        }

        //-- Haal nu alle data op uit de sheet-tab "PrgPaulusfeesten"
        try {
            $document = Sheets::sheet('PrgPaulusfeesten')->get();
        } catch (Google_Service_Exception $e) {
            dump($e);
            $errors = $this->errorMsg['cannotFindSheetTab'];
            return $this->apiResponse->create($result, $errors);
        }

        //-- Check nu op bestaan v kolommen.  Als ze bestaan -> GELDIGE PAULUSFEESTEN PROGRAMMATIE FILE, Feestje! JEUJ!
        try {
            $rij2 = 1;
            if(!$document->has($rij2)) throw new Exception();
            $test = $document[$rij2];

        } catch (Exception $e) {
            $errors = $this->errorMsg['columnDataNotFound'];
            return $this->apiResponse->create($result, $errors);
        }

        for($i = 0; $i < count($this->sheetColumns); $i++){
            $pos = null;
            $kolomNr = $i + 1;

            //-- Check of kolom bestaat (anders krijg je exception)
            if(array_key_exists($i, $test)){
                //-- verwijder onnodige spaties + maak alle letters klein
                //dump($test[$i]);
                $test[$i] = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $test[$i]));
                //dump($test[$i]);
                $pos = strpos($test[$i], $this->sheetColumns[$i]);
            }

            if(in_array($this->sheetColumns[$i], $test)) {
                //-- Element existing = ok
                //dump($checkKolom[$i] . " exists!");
            }
            else {
                //dump($checkKolom[$i] . " dus NOT exists!");
                $errors = $this->errorMsg['columnNotFound'];
                $errors['description'] = 'Rij 2 Kolom \' . $kolomNr' . 'Bevat geen naam ' . $this->sheetColumns[$i] . ' in de Google Sheet';
            }
        }

        if(!empty($errors)) return $this->apiResponse->create($result, $errors);

        //-- Good enough for me, genoeg checks doorstaan.  Bewaar veranderde gegevens maar in database...
        $result = $googleSheetDb->find($id);
        $result->update($data);

        // stuur json-response terug met het (hopelijk heugelijke) nieuws...
        return $this->apiResponse->create($result, $errors);
    }

    /**
     * API call
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(GoogleModel $googleSheetDb, $id)
    {
        $result = $googleSheetDb->find($id);
        $result->delete();

        return redirect('/beheer/jaargangen');

    }

    public function checkNewGoogleSheet($request, $googleSheetDb){
        //-- Validator
        $rules = [
            'editie_jaar' => 'required|min:4',
            'googlesheet_id' => 'required|url|regex:^https://docs.google.com/spreadsheets/d^'  //-- Valid URL?
        ];

        $result = [];
        $errors = [];

        if(method_exists($request, 'except')){
            $data = $request->except('_token');

            //-- Validatie check gegevens
            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return $this->apiResponse->create($result,  $validator->errors());
            }
        }
        else
            $data = $request;

        //-- URL gevalideerd: nu uit elkaar fluizen
        try {

            if(method_exists($request, 'except')){
                $url = $request->get('googlesheet_id');
            }
            else {
                $url = $request->googlesheet_id;
            }

            preg_match("/spreadsheets\/d\/([a-zA-Z0-9-_]+)/", $url, $googleId); //-- Filter URL

            //-- Check if retrieved data matches the pattern?
            if(count($googleId) == 0){
                $errors = $this->errorMsg['googleUrlInvalid'];
                return $this->apiResponse->create($result, $errors);
            }

            //-- Filter google ID (normaal lijkt het op bijvb: "1Jv0rl-Q5ob7fQr_jtJcLS_GO5fz03y_WksiIBfol60E")
            if((str_replace($googleId[1], "", $googleId[0]) != "spreadsheets/d/") || $googleId[1] == ""){
                $errors = $this->errorMsg['invalidGoogleSheet'];
                return $this->apiResponse->create($result, $errors);
            }

            $googleId = $googleId[1];

        } catch (Exception $e) {
            $errors = $this->errorMsg['invalidGoogleSheetID'];
            return $this->apiResponse->create($result, $errors);
        }

        //-- Gevonden Google ID nu on-line checken of het document wel degelijk bestaat?
        Sheets::setService(Google::make('sheets'));
        Sheets::spreadsheet($googleId);

        try {
            //-- Checken of de sheet 'PrgPaulusfeesten' bestaat in het geselecteerde document (op basis van template)
            $sheetTabs = Sheets::sheetList();

            //-- Google ID bestaat!!
            if(!in_array('PrgPaulusfeesten',$sheetTabs)){
                $errors = $this->errorMsg['invalidSheetData'];
                return $this->apiResponse->create($result, $errors);
            };

            //-- Google ID bestaat niet of is ongeldig!! (of de boel ligt plat)
        } catch (Google_Service_Exception $e) {
            $errors = $this->errorMsg['cannotOpenSheet'];
            return $this->apiResponse->create($result, $errors);
        } catch (ConnectException $e) {
            $errors = $this->errorMsg['urlUnAvailable'];
            $errors['real_error'] = $e->getMessage();

            // optie: Check for fallback-version (re-use importGoogleSheet methods? create new methods? -> direct load fallback)

            return $this->apiResponse->create($result, $errors);
        }

        //-- Haal nu alle data op uit de sheet-tab "PrgPaulusfeesten"
        try {
            $document = Sheets::sheet('PrgPaulusfeesten')->get();
        } catch (Google_Service_Exception $e) {
            dump($e);
            $errors = $this->errorMsg['cannotFindSheetTab'];
            return $this->apiResponse->create($result, $errors);
        }

        //-- Check nu op bestaan v kolommen.  Als ze bestaan -> GELDIGE PAULUSFEESTEN PROGRAMMATIE FILE, Feestje! JEUJ!
        try {
            $rij2 = 1;
            if(!$document->has($rij2)) throw new Exception();
            $test = $document[$rij2];

        } catch (Exception $e) {
            $errors = $this->errorMsg['columnDataNotFound'];
            return $this->apiResponse->create($result, $errors);
        }

        $checkKolom = ['dag','weekdag','locatie','uur','groepsnaam','genre','subgenre','omschrijvingkort','omschrijvinglang','website','foto01','foto02','foto03','videolink1','videolink2','videolink3'];

        for($i = 0; $i < count($checkKolom); $i++){
            $pos = null;
            $kolomNr = $i + 1;

            //-- Check of kolom bestaat (anders krijg je exception)
            if(array_key_exists($i, $test)){
                //-- verwijder onnodige spaties + maak alle letters klein
                //dump($test[$i]);
                $test[$i] = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $test[$i]));
                //dump($test[$i]);
                $pos = strpos($test[$i], $checkKolom[$i]);
            }

            if(in_array($checkKolom[$i], $test)) {
                //-- Element existing = ok
                //dump($checkKolom[$i] . " exists!");
            }
            else {
                //dump($checkKolom[$i] . " dus NOT exists!");
                $errors = $this->errorMsg['columnNotFound'];
                $errors['description'] = 'Rij 2 Kolom \' . $kolomNr' . 'Bevat geen naam ' . $checkKolom[$i] . ' in de Google Sheet';
            }
        }

        if(!empty($errors)) return $this->apiResponse->create($result, $errors);

        //-- Good enough for me, genoeg checks doorstaan.  Bewaar veranderde gegevens maar in database...
        $result = $googleSheetDb->find($request->id);

        return $this->apiResponse->create($result, $errors);
        //return $result;
    }

    public function proggeneratorWizard(){
        return view('pages.admin.sheets.proggenerator');
    }
}

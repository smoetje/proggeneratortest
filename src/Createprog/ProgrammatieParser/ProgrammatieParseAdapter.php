<?php
/**
 * Created by PhpStorm.
 * User: nicol
 * Date: 28/11/2017
 * Time: 22:58
 */

namespace Smoetje\Proggenerator\CreateProg\ProgrammatieParser;

use Smoetje\Proggenerator\CreateProg\IProgImport;
use App\Http\Controllers\programmatieController;
use Smoetje\Proggenerator\models\ProgrammatieModel;
use Smoetje\Proggenerator\models\GroupModel;
use Smoetje\Proggenerator\models\FotoModel;
use Smoetje\Proggenerator\models\VideoModel;
use Smoetje\Proggenerator\models\LocatieModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;

class ProgrammatieParseAdapter implements IProgrammatieParseAdapter
{
    // Input data
    private $progObject;
    private $importData;

    // Output tabellen
    private $locaties = [];
    private $programmaties = [];
    private $groepData = [];
    private $videoData = [];
    private $fotoData = [];

    // Extra info
    private $aantalGroepenToegevoegdInDb = 0;
    private $aantalGroepenOvergeslagen = 0;
    private $opgenomenGroepen = [];
    private $overgeslagenGroepen = [];
    private $rejectedImports = [];
    private $aantalFotosGeladen = 0;
    private $aantalVideolinksGeladen = 0;

    private $classDebugger = true;  // True = werkt enkel met OFFLINE data indien beschikbaar
    private $errors = [];
    private $warnings = [];

    public function __construct(IProgImport $prog){
        $this->progObject = $prog;
        $this->importData = $this->progObject->getProgData();

        for($i = 1; $i <= count($this->importData); $i++){
            $this->importData[$i]['groepscode'] = $this->generateGroepCode($this->importData[$i]['GroepsNaam']);
        }

        $this->sanitizeInvalidRecords();

//        if(!$this->sanitizeInvalidRecords())
//            $this->errors[] = "Google Sheet is leeg of bevat geen geldige datums";
    }

    public function __destruct()
    {
        //dd($this);
    }

    /**
     * Verwijdert lege en invalid elementen in 'import', die de verdere werking van de applicatie verstoort
     */
    private function sanitizeInvalidRecords(){
        $importData = collect($this->importData);
        $datumInfo = [];
        $count = 0;

        // Alle geldige datums in zelfde formaat zetten...
        $importData = $importData->map(function($item) {
            $item['WeekDag'] = str_replace('/', '-', $item['WeekDag']);
            if(strtotime($item['WeekDag'])){
                $item['WeekDag'] = Carbon::parse($item['WeekDag'])->format('d-m-Y');
            }
            return $item;
        });

        // Rijen met lege datums eruit kletsen
        $importData = $importData->reject(function($value, $key){
            if(empty($value['WeekDag'])){
                $this->rejectedImports['rij'] = $key + 2;
                $this->rejectedImports['data'] = $value;
                Return $value;
            }
        })->prepend([])->values();

        // Indien sheet leeg is of geen datums bevat: default dummy record...
        if(count($importData) < 2){
            //return false;
            $importData->push([
                'WeekDag' => '01-01-2018',
                'Locatie' => '',
                'Uur' => '',
                'GroepsNaam' => '',
                'Genre' => '',
                'Subgenre' => '',
                'OmschrijvingKort' => '',
                'OmschrijvingLang' => '',
                'Website' => '',
                'Foto01' => '',
                'Foto02' => '',
                'Foto03' => '',
                'VideoLink1' => '',
                'VideoLink2' => '',
                'VideoLink3' => '',
                'groepscode' => ''
            ]);
        }

        unset($importData[0]);

        foreach($importData as $value){
            // Check if datum is valid
            if(strtotime($value['WeekDag'])){
                $datumInfo[Carbon::parse($value['WeekDag'])->format('d-m-Y')] = $count;
                $count++;
            }
        }

//        $newdatumInfo = $this::vindUniekeDatums($datumInfo, 'd-m-Y');

        // Als dag niet meer in source-data voorkomt (nu wel nog), mag je geen fout meer geven
        $this->importData = $importData->map(function($item) {
            if(isset($item['Dag']))
                unset($item['Dag']);
            return $item;
        })->toArray();

    }

    // getters
    public function getErrors(){
        return $this->errors;
    }

    public function getWarnings(){
        return $this->warnings;
    }

    public function getAantalGroepenToegevoegdInDb(){
        return $this->aantalGroepenToegevoegdInDb;
    }

    public function getAantalGroepenOvergeslagen(){
        return $this->aantalGroepenOvergeslagen;
    }

    public function getOpgenomenGroepen(){
        return $this->opgenomenGroepen;
    }

    public function getOvergeslagenGroepen(){
        return $this->overgeslagenGroepen;
    }

    public function createTables() {
        //dump("Creating tables");

        $this->parseLocations();
        $this->parseGroups();
        $this->buildProgrammatie();
        $this->buildPhotoVideoLibraries();

        return true; // If all tables were succesfully parsed and created... --> no checks?
    }

    /**
     * Removes old programming-data from the database, replaces with new imported sheet-data
     */
    public function storeTables() {
        //dump("Storing tables to Database");
        $this->truncateDataTables();

        // losse privates maken per save hieronder?
        $programmatieModel = new ProgrammatieModel;
        $groupModel = new GroupModel;
        $fotoModel = new FotoModel;
        $videoModel = new VideoModel;
        $locatieModel = new LocatieModel;

        $locatieModel->insert($this->locaties);
        //dd($this->importData);
        //dd($this->programmaties);
        $programmatieModel->insert($this->programmaties);
        $groupModel->insert($this->groepData);
        $fotoModel->insert($this->fotoData);
        $videoModel->insert($this->videoData);
    }

    /**
     * Handige 'magic method' om enkel de data in je object te 'tonen' in dumps, die zinvol zijn...
     * @return array
     */
    public function __debugInfo()
    {
        // TODO: Implement __debugInfo() method.
        return [
            "Bands" => $this->groepData,
            "Locaties" => $this->locaties,
            //"Locaties" => $this->locaties->toArray(),
            "Programmaties" => $this->programmaties,
            "Videos" => $this->videoData,
            "Fotos" => $this->fotoData,

            "Aantal bands gevonden bij parsen" => $this->aantalGroepenToegevoegdInDb,
            "Aantal bands (duplicaten) overgeslagen" => $this->aantalGroepenOvergeslagen,
            "Opgenomen bands in DB" => $this->opgenomenGroepen,
            "Meervoudig geprogrammeerde bands" => $this->overgeslagenGroepen,
            "Aantal foto's toegevoegd" => $this->aantalFotosGeladen,
            "Aantal videos toegevoegd" => $this->aantalVideolinksGeladen,
            "Ongeldige data zonder dag of datum" => $this->rejectedImports,

            "Debug" => $this->classDebugger, // off-line data parsen indien beschikbaar
            "Errors" => $this->errors
        ];
    }

    private function truncateDataTables(){
        $programmatieModel = new ProgrammatieModel;
        $groupModel = new GroupModel;
        // $fotoModel = new FotoModel; //--> We behouden de foto's, zodat previews geen impact heeft op de LIVE versie + bestaande foto's worden toch gewoon overschreven...
        $videoModel = new VideoModel;
        $locatieModel = new LocatieModel;

        // -- Delete complete database table content, zo begin je steeds met een schone 'programmatie' lei.
        // -- Groups, Videos, Fotos en Locaties blijven behouden (kunnen mogelijks worden gerecycled in de toekomst)
        $programmatieModel->truncate();    // Programmatie wordt volledig ge(her)genereerd.  Reeds geïmporteerde data wordt nu gerecycleerd...
        $groupModel->truncate();           // todo: integrale import omschrijving en info groep zal nodig zijn, dus ook nu ook truncaten tot online kan worden geëditeerd
        $locatieModel->truncate();         // Als er locaties bijkomen, de dropdown in google sheet aanpassen
        // $videoModel->truncate(); //--> We behouden de video's, zodat ze ook kunnen worden gerecycled in de toekomst...
        // $fotoModel->truncate(); //--> We behouden de foto's, zodat previews geen impact heeft op de LIVE versie + bestaande foto's worden toch gewoon overschreven...
    }

    /*************************************************************************
     * Creëer individuele records van elke gevonden programmatie locatie
     * Meervoudige kopijen worden gereduceerd naar 1 unieke record per locatie
     * Indien geen locatie of een corrupte locatie: "other" ...
     * @return bool
     */
    private function parseLocations(){
        $now = Carbon::now()->format('Y-m-d H:i:s');  // insert() vult geen timestamps in, enkel manueel...

        // Scan alle velden met sleutel 'Locatie' per rij af in array $this->importData
        $locaties = [];
        $data = null;

        for($i = 1; $i <= count($this->importData); $i++){
            // alles in kleine letters zetten (voor consistentie)
            // $data = isset($this->importData[$i]['Locatie']) ? strtolower($this->importData[$i]['Locatie']) : '';
            $data = isset($this->importData[$i]['Locatie']) ? $this->importData[$i]['Locatie'] : '';
            $data == '' ? : $locaties[$data] = $i;
        }

        // Alles afgsescand? Draai array-sleutels en content nu om
        $locaties = array_flip($locaties);

        // array sleutels herindexeren + bewaren in DB (1, 2... start vanaf 1 -> noSQL !)
        $locatieData = [];
        if($locaties != []){
            $index = 1;
            foreach($locaties as $locatie){
                $locatieData[$index]['id'] = $index;
                $locatieData[$index]['naam'] = $locatie;
                $locatieData[$index]['created_at'] = $now;
                $locatieData[$index]['updated_at'] = $now;
                $index++;
            }

            // indien geen locatie toegekend in googleSheet: 'other'
            $locatieData[$index]['id'] = $index;
            $locatieData[$index]['naam'] = 'other';
            $locatieData[$index]['created_at'] = $now;
            $locatieData[$index]['updated_at'] = $now;

        } else {
            // indien geen locatie toegekend in googleSheet: 'other'
            $locatieData[1]['id'] = 1;
            $locatieData[1]['naam'] = 'other';
            $locatieData[1]['created_at'] = $now;
            $locatieData[1]['updated_at'] = $now;
        }

        $this->locaties = $locatieData;

        return true;
    }

    /*************************************************************************
     * Creëer individuele records per groep
     * Meervoudige kopijen worden gereduceerd naar 1 unieke record per groep
     * Oude groepsdata wordt integraal, volledig verwijderd
     * Enkel de actueelste googleSheet data komt erin!
     **************************************************************************/
    private function parseGroups(){
        $now = Carbon::now()->format('Y-m-d H:i:s');  // insert() vult geen timestamps in, enkel manueel...

        // Scan alle velden met sleutel 'Locatie' per rij af in array $googleData
        $groups = [];
        for($i = 1; $i <= count($this->importData); $i++){
            $data = [
                'id' => $i,
                'groepsnaam' => $this->importData[$i]['GroepsNaam'] != '' ? $this->importData[$i]['GroepsNaam'] : '',
                'genre' => $this->importData[$i]['Genre'],
                'subgenre' => $this->importData[$i]['Subgenre'],
                'omschrijvingkort' => $this->importData[$i]['OmschrijvingKort'],
                'omschrijvinglang' => $this->importData[$i]['OmschrijvingLang'],
                'url' => $this->importData[$i]['Website'],
                'groepscode' => '',
                'created_at' => $now,
                'updated_at' => $now
            ];

            // Genereer unieke groepscode (kleine letters en cijfers zonder spatie, rest vd rommel (speciale karakters) WEG !!) + push to $groepsData array
            // $data['groepscode'] = strtolower(preg_replace("/\W/", "", $data['groepsnaam']));
            $data['groepscode'] = $this->generateGroepCode($data['groepsnaam']);

            // Check of groepscode reeds bestaat in $groups.  Zoja -> duplicaat = overslaan.  Zoniet, toevoegen in array $groups!
            $groupExisting = false;

            foreach($groups as $group){
                if(isset($group['groepscode'])){
                    if($data['groepscode'] == $group['groepscode']){
                        $groupExisting = true;
                    }
                }
            } // end foreach

            if($groupExisting == false || $groups == []){
                // Array leeg, data not (yet) existing
                $groups[$i] = $data;
                array_push($this->opgenomenGroepen, $data);
                $this->aantalGroepenToegevoegdInDb++;

            } else {
                // Reset flag
                $groupExisting = false;
                // Data existing, drop this row
                array_push($this->overgeslagenGroepen, $data);
                $this->aantalGroepenOvergeslagen++;
            }
        } // end for loop building $groups[] array

        // array overplaatsen van lokale array naar global property $this->groepdata (toegankelijk andere methods)
        $this->groepData = $groups;
        return true;
    }

    // enkel de foto's uit de eerste programmat-entry wordt geparsed (de opvolgende kopijen niet dus...)
    private function parseFotos($groep){
        $importCollection = collect($this->importData);
        $groepscode = $groep['groepscode'];
        $progItems = $importCollection->where('groepscode', '=', $groepscode)->all(); // nieuwe code

        $filterCriteria = array("Foto01", "Foto02", "Foto03");
        $filtered = $this->filterProgElements($progItems, $filterCriteria);

        $fotoModel = new FotoModel();

        $filtered->map(function($value, $key) use ($fotoModel, $groepscode){
            $file = $this->sanitizeFileName($value);
            $fotoExistsInDb = $fotoModel->where('name', $file['filename'])->first();

            if($fotoExistsInDb){
                // Als de foto al bestaat in de Database, doe in principe NIETS, géén record toevoegen
                return false;
            } else {
                // Als de foto nog NIET bestaat in de database, voeg je hem doodleuk toe...

                if(!$this->verifyFotoExtensionExists($value))
                    return false; // Verify, log & skip invalid foto filenames

                //$groepscode = $this->generateGroepCode($progItem['GroepsNaam']);
                $now = Carbon::now()->format('Y-m-d H:i:s');

                $foto = [
                    'name' => $file['filename'],
                    'extension' => $file['extension'],
                    'groepscode_fk' => $groepscode,  // checken of $this->groepData goed werd ge-enterd
                    'group_id' => $this->lookupGroupId($groepscode),
                    'sheet_index' => $key,
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                array_push($this->fotoData, $foto);
            } // end if
        });

        // T.B.C. ...

        // Eigenlijk heb je verschillende scenario's:
        // 1. Nieuw programma-item zonder foto -> NIET toevoegen in DB
        // 2. Nieuw programma-item mét foto ($filtered bevat alle mogelijke foto's)
        //    Elke foto checken of ze 1) bestaat (opzoeken in $fotoModel- zoniet, data toevoegen in object, zoja, doe NIETS)
        // 3. Unieke identificatie van foto's blijft nog steeds op basis van een gegenereerde groepscode...
        // Ook even nadenken voor foto's met NULL path wat mee doen... (eventueel NULL path in JSON generator uitsluiten lijkt me logisch!)
    }

    /**
     * Removes all weird and exotic stuff and characters in filenames to letters and numbers
     * @param $file
     * @return array
     */
    static public function sanitizeFileName($file){
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $FirstName = str_replace($extension, "", $file);
        $cleanName = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $FirstName));
        $sanitizedFileName = $cleanName . "." . $extension;
        return [
            'extension' => $extension,
            'firstname' => $FirstName,
            'filename' => $sanitizedFileName
        ];
    }

    /**
     * Controleer of bestandsnamen van de foto's, wel een geldige extensie bevat...
     * @param $filename
     */
    public function verifyFotoExtensionExists($filename){
        if(pathinfo($filename, PATHINFO_EXTENSION) == ""){
            $message = 'Deze foto in de sheet werd niet opgenomen want ze bevat geen extensie: ' . '"' . $filename . '"';
            $this->warnings[] = $message;
            return false;
        }
        return true;
    }

    // enkel de videos uit de eerste programma-entry wordt geparsed (de opvolgende kopijen niet dus...)
    private function parseVideos($groep){
        $importCollection = collect($this->importData);
        $groepscode = $groep['groepscode'];
        $progItems = $importCollection->where('groepscode', '=', $groepscode)->all();

        $filterCriteria = array("VideoLink1", "VideoLink2", "VideoLink3");
        $filtered = $this->filterProgElements($progItems, $filterCriteria);

        $videoModel = new VideoModel();

        $filtered->map(function($value, $key) use ($videoModel, $groepscode){
            $videoExistsInDb = $videoModel->where('url', $value)->first();

            if($videoExistsInDb){
                // Als de video al bestaat in de Database, doe in principe NIETS, géén record toevoegen
                return false;
            } else {
                  // Als de video nog NIET bestaat in de database, voeg je hem doodleuk toe...
                  $now = Carbon::now()->format('Y-m-d H:i:s');
                  $video = [
                      'url' => $value,
                      'groepscode_fk' => $groepscode,  // checken of $this->groepData goed werd ge-enterd
                      'group_id' => $this->lookupGroupId($groepscode),
                      'sheet_index' => $key,
                      'created_at' => $now,
                      'updated_at' => $now
                  ];

                  array_push($this->videoData, $video);
            } // end if
        });
    }

    private function filterProgElements($progItems, $criteria = array()){
        $progItems = collect($progItems);
        $filtered = $progItems->map(function($value, $key) use ($criteria) {
            $keys = array_filter(
                $value,
                function ($key) use ($criteria) {
                    if(in_array($key, $criteria)) {
                        return $key;
                    }
                },
                ARRAY_FILTER_USE_KEY
            );
            return $keys;
        })->flatten()
            ->reject(function ($name) {
                return empty($name); // Removes empty array elements
            })->unique();

        return $filtered;
    }

    private function buildProgrammatie() {
        $now = Carbon::now()->format('Y-m-d H:i:s');

        $locaties = $this->locaties; // local copy previous collection, now array (because no longer retrieved from DB

        // Remove all (spaces &) capitals in local collection before starting comparison collection data
        for($i = 1; $i <= count($locaties); $i++){
            $locaties[$i]['naam'] = str_replace(' ', '', $locaties[$i]['naam']);    // already done strtolower in parseLocations
            //$locaties[$i]->naam = strtolower(str_replace(' ', '', $locaties[$i]->naam));
        }

        $locaties = collect($locaties);

        $count = 0;
        for($i = 1; $i <= count($this->importData); $i++){
            $datumInfo[$this->importData[$i]['WeekDag']] = $count;
            $count++;
        }

        $datums = $this::vindUniekeDatums($datumInfo, 'd-m-Y');
        $datums = collect($datums);

        // We lopen gans de google sheet af, en bouwen de programmatie op...
        for($i = 1; $i <= count($this->importData); $i++){

            // Lookup locatie_id (via collection, performater dan DB...):
            // $locatieData = isset($this->importData[$i]['Locatie']) ? strtolower(str_replace(' ', '', $this->importData[$i]['Locatie'])) : '';
            $locatieData = isset($this->importData[$i]['Locatie']) ? str_replace(' ', '', $this->importData[$i]['Locatie']) : '';
            $locatie = $locaties->where('naam', $locatieData);
            if($locatie->isEmpty()){
                // Geen id gevonden, zet locatie op 'other'
                $locatie = $locaties->where('naam', 'other');
            };

            $locatie = $locatie->flatMap(function ($values){
                return $values;
            });

            $locatie_id = $locatie->get('id');

            //dump(Carbon::parse($this->importData[$i]['WeekDag'])->format('d-m-Y'));

            try {
                $uur = $this->importData[$i]['Uur'] ? Carbon::createFromTimeString($this->importData[$i]['Uur'])->format('H:i') : "";
            } catch (\Exception $e) { // Invalid time input
                //dump($i+3);
                //dump($this->errors);

                $message = "Ongeldig uur gedetecteerd in source file: " . $this->importData[$i]['Uur'];
                $this->warnings[] =  $message;
                //dd($e->getMessage());

                $uur = null;
            }

            $data = [
                //'dagnr' => (int)$this->importData[$i]['Dag'],
                'dagnr' => $datums->where('datum', Carbon::parse($this->importData[$i]['WeekDag'])->format('d-m-Y'))->first()['dag'], // Dag nummer wordt nu DYNAMISCH toegekend!
                'datum' => new Carbon($this->importData[$i]['WeekDag']),
                // 'uur' => $this->importData[$i]['Uur'] ? $this->importData[$i]['Uur'] : null,
                'uur' => $uur,
                'chronologie' => $i,
                'locatie_id' => $locatie_id,
                'groepscode_fk' => $this->generateGroepCode($this->importData[$i]['GroepsNaam']),
                'group_id' => $this->lookupGroupId($this->generateGroepCode($this->importData[$i]['GroepsNaam'])),
                'created_at' => $now,
                'updated_at' => $now
            ];

            // i.p.v. elke record saven & result checken, elke record toevoegen in een nieuwe array
            $this->programmaties[] = $data;
        }
    }

    // Bouwt alle links
    private function buildPhotoVideoLibraries(){
        // Foto's en video's linken aan lijst van unieke groepen
        foreach($this->groepData as $groep){
            $this->parseVideos($groep);
            $this->parseFotos($groep);
        }

        // Assoc naar numeric index, duplicaten zijn weg, data ready!
        $this->videoData = array_values($this->videoData);
        $this->fotoData = array_values($this->fotoData);
        $this->aantalFotosGeladen = count($this->fotoData);
        $this->aantalVideolinksGeladen = count($this->videoData);
    }

    /**
     * If the group exists, return group id
     * If the group doesn't exist, return null
     * @param $groepscode_fk
     * @return mixed
     */
    private function lookupGroupId($groepscode_fk){
        $result = array_first($this->groepData, function($array, $key) use ($groepscode_fk) {
            if($array['groepscode'] == $groepscode_fk){
                return $array['id'];
            } else {
                return null;
            }
        });

        return $result['id'];
    }

    private function generateGroepCode($groepNaam = ''){
        return strtolower(preg_replace("/\W/", "", $groepNaam));
    }

    static public function vindUniekeDatums($datumInfo, $format = 'Y-m-d'){
        ksort($datumInfo); // Datums-dagen chronologisch sorteren
        $datumInfo = array_flip($datumInfo); // We houden géén rekening met dagnr uit GoogleSheet (mismatch fout is snel gemaakt)
        $datumInfo = array_values($datumInfo);

        // Laagste datum (element 0) wordt dag 1
        $newdatumInfo[0]['dag'] = 1;
        $newdatumInfo[0]['datum'] = Carbon::parse($datumInfo[0])->format($format);

        for($i = 1; $i < count($datumInfo); $i++){
            $oldDate = Carbon::parse($datumInfo[0]);
            $newDate = Carbon::parse($datumInfo[$i]);
            $newdatumInfo[$i]['dag'] = $oldDate->diffInDays($newDate) + 1;
            $newdatumInfo[$i]['datum'] = Carbon::parse($datumInfo[$i])->format($format);
        }

        return $newdatumInfo;
    }
}
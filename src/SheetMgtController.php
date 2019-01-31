<?php

namespace Smoetje\Proggenerator;

use App\Http\Controllers\Controller;
use Smoetje\Proggenerator\models\GoogleModel;
use Illuminate\Http\Request;
use Smoetje\Proggenerator\CreateProg\CreateProg;
use Smoetje\Proggenerator\modules\ApiResponse;
use Smoetje\Proggenerator\GoogleSheetMgtController;

class SheetMgtController extends Controller
{
    private $sheetModel = null;
    private $apiResponse = null;

    public function __construct(GoogleModel $sheetModel){
        $this->sheetModel = $sheetModel;
        $this->apiResponse = new ApiResponse();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->sheetModel->all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $sheet = $this->sheetModel->create($request->all());
        return $sheet;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\GoogleModel  $googleModel
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->sheetModel->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\GoogleModel  $googleModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $sheet = $this->sheetModel->findOrFail($id);
        $sheet->update($request->all());

        return $sheet;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\GoogleModel  $googleModel
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $sheet = $this->sheetModel::findOrFail($id);
        $sheet->delete();
        return '';
    }

    /**
     * Imports a new sheet into the Databases with the 'ProgGenerator' class-collections
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        $record_id = $request->get('id');
        $urlData = $this->sheetModel->where('id','=', $record_id)->first();

        //$programmatie = new CreateProg("https://docs.google.com/spreadsheets/d/1lpHTG0V5WJpadyh5dK1USY3BOSeVd8HfL6vzNaqKjxE/edit#gid=130347533");
        $programmatie = new CreateProg($urlData->googlesheet_id);

        $result = $programmatie->parseProg(); // true = successfully parsed
        $result = $programmatie->storeProg(); // true = successfully stored

        $response = [
            'editie' => $urlData->editie_jaar,
            'aantal_opgenomen' => $programmatie->getAantalGroepenToegevoegdInDb(),
            'aantal_overgeslagen' => $programmatie->getAantalGroepenovergeslagen(),
            'toegevoegde_groepen' => $programmatie->getOpgenomenGroepen(),
            'overgeslagen_groepen' => $programmatie->getOvergeslagenGroepen(),
            'warnings' => $programmatie->getWarnings(),
        ];

        // dd($response);

        return $this->apiResponse->create($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function verify($id)
    {
        $errors = [];

        $data = $this->sheetModel->where('id',$id)->first();
        $googleSheetCtl = new GoogleSheetMgtController(); // Voorlopige oplossing, totdat deze eens is opgekuist (moet in custom class komen!)
        $result = $googleSheetCtl->checkNewGoogleSheet($data, $this->sheetModel);
        return $result;
    }
}

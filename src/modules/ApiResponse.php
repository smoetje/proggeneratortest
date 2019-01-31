<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 18/04/17
 * Time: 21:21
 */

namespace Smoetje\Proggenerator\modules;
use Response;

/**
 * Class ApiResponse
 * @package App\Modules
 */
class ApiResponse
{
    /**
     * Creates a 'standard, uniform response output' for all API calls
     * @param $result
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function create($result, array $errors = [], $errorstatus = 422) {

        $response = [
            'result' => $result,
            'status' => count($errors) ? 'error' : 'success',
            'errors' => $errors
        ];

        if (!empty($errors))
            return Response::json($response, $errorstatus);

        return Response::json($response, 200);
    }
}
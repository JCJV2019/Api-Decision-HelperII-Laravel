<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function sendResponse($response)
    {
        return response()->json($response, 200);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = null, $code = 400)
    {
        $response = [
            'message' => $error,
        ];

        if (! is_null($errorMessages)) {
            $arrayErrors = $errorMessages->all();
            if (!empty($arrayErrors)) {
                for ($i=0; $i < count($arrayErrors); $i++) {
                    $response['message'] .= '<br>' . $arrayErrors[$i];
                }
            }
        }

        return response()->json($response, $code);
    }
}

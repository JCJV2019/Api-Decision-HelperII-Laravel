<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Question;
use App\Models\Negative;
use App\Rules\TheSame;
use App\Rules\ValuePoints;

class NegativeController extends BaseController
{
    /**
     * List negatives of question
     *
     * @return \Illuminate\Http\Response
     */
    public function negativeQuestion($idQuestion)
    {
        $converInterf = function($array_input) {
            $aKeys = array_keys($array_input);
            $aValues = array_values($array_input);
            $arrayNew = array();
            //var_dump($aKeys, $aValues, count($array_input));
            for ($i=0; $i < count($array_input) ; $i++) {
                if ($aKeys[$i] == "_id" || $aKeys[$i] == "user" || $aKeys[$i] == "question") {
                    $arrayNew[$aKeys[$i]] = strval($aValues[$i]);
                } else {
                    $arrayNew[$aKeys[$i]] = $aValues[$i];
                }
            }
            return $arrayNew;
        };

        // Ojo que al estar con MySQL hay borrado en cascada en las relaciones
        // Y puede que cuando llegemos aquí no exista la Pregunta ni los items
        $question = Question::find($idQuestion);

        if (is_null($question)) {
            return $this->sendResponse([]);
        }

        $userAuth = Auth::user();

        if ($userAuth->id <> $question->user_id) {
            return $this->sendError('Id inconsistent with parameter.', []);
        }

        $itemsNegatives = Negative::where('question_id', $question->id)
                                ->get(['id as _id', 'desc', 'point', 'question_id as question', 'user_id as user'])
                                ->toArray();
        // Conversión de interface
        $itemsNegativesNew = array_map($converInterf, $itemsNegatives);
        return $this->sendResponse($itemsNegativesNew);
    }

    public function removeNegativeQuestion($idQuestion)
    {
        // Ojo que al estar con MySQL hay borrado en cascada en las relaciones
        // Y puede que cuando llegemos aquí no exista la Pregunta ni los items
        $question = Question::find($idQuestion);

        if (is_null($question)) {
            return $this->sendResponse([]);
        }

        $userAuth = Auth::user();

        if ($userAuth->id <> $question->user_id) {
            return $this->sendError('Id inconsistent with parameter.');
        }

        $itemsNegatives = Negative::where('question_id', intval($idQuestion))->get();

        if (is_null($itemsNegatives)) {
            return $this->sendError("There are no records of negative items of question number $idQuestion.");
        }

        $response = Negative::where('question_id', intval($idQuestion))->delete();

        return $this->sendResponse($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) //create
    {
        $input = $request->all();
        $userAuth = Auth::user();

        $validator = Validator::make($input, [
            'desc' => 'required',
            'point' => ['required', new ValuePoints(1, 4)],
            'question' => 'required',
            'user' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Descripcion o puntos o pregunta o usuario no encontrados en body');
        }

        if ($userAuth->id <> $input['user']) {
            return $this->sendError('User inconsistent with parameter.');
        }

        $idQuestion = intval($input['question']);
        $question = Question::find($idQuestion);
        if (is_null($question)) {
            return $this->sendError("The record question $idQuestion does not exist.");
        }

        if ($userAuth->id <> $question->user_id) {
            return $this->sendError('Id inconsistent with parameter.', []);
        }

        // Conversión de interface
        $input_new['desc'] = $input['desc'];
        $input_new['point'] = $input['point'];
        $input_new['question_id'] = $input['question'];
        $input_new['user_id'] = intval($input['user']);

        $negative = Negative::create($input_new);

        $response['_id'] = strval($negative->id);
        $response['desc'] = $negative->desc;
        $response['point'] = $negative->point;
        $response['question'] = strval($negative->question_id);
        $response['user'] = strval($negative->user_id);

        return $this->sendResponse($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Positive  $positive
     * @return \Illuminate\Http\Response
     */
    public function show($id) //listOne
    {
        $negative = Negative::find($id);

        if (is_null($negative)) {
            return $this->sendResponse([]);
        }

        $idQuestion = intval($negative->question->id);
        $question = Question::find($idQuestion);

        if (is_null($question)) {
            //return $this->sendError("The record question $idQuestion does not exist.");
            return $this->sendResponse([]);
        }

        $userAuth = Auth::user();

        if ($userAuth->id <> $question->user_id) {
            return $this->sendError('Id inconsistent with parameter.');
        }

        // Conversión de interface
        $response['_id'] = strval($negative->id);
        $response['desc'] = $negative->desc;
        $response['point'] = $negative->point;
        $response['question'] = strval($negative->question_id);
        $response['user'] = strval($negative->user_id);

        return $this->sendResponse($response);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Positive  $positive
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // $request->request->get('_id') Esto da null
        // Problemas con _id en Laravel cuando viene de Angular, no lo puedo obtener
        $input = $request->all();
        $userAuth = Auth::user();
        $idItemBody = "";
        if (array_key_exists('_id', $input)) {
            // Viene de PostMan?
            $validator = Validator::make($input, [
                '_id' => 'required',
                'desc' => 'required',
                'point' => ['required', new ValuePoints(1, 4)],
                'question' => 'required',
                'user' => ['required', new TheSame(strval($userAuth->id))],
            ]);
            $idItemBody = $input['_id'];
        } else {
            // Viene de Angular
            $validator = Validator::make($input, [
                'id' => 'required',
                'desc' => 'required',
                'point' => ['required', new ValuePoints(1, 4)],
                'question' => 'required',
                'user' => ['required', new TheSame(strval($userAuth->id))],
            ]);
            $idItemBody = $input['id'];
        }

        if ($validator->fails()) {
            return $this->sendError('_id o descripcion o puntos o pregunta o usuario no encontrados en body');
        }

        if ($userAuth->id <> $input['user']) {
            return $this->sendError('User inconsistent with parameter.');
        }

        $question = Question::find(intval($input['question']));

        if (is_null($question)) {
            return $this->sendError("The record question " . $input['question'] . " does not exist.");
        }

        if ($userAuth->id <> $question->user_id) {
            return $this->sendError('User inconsistent with parameter.');
        }

        if ($idItemBody <> $id) {
            return $this->sendError('Item Negative inconsistent with parameter.');
        }

        $negativeNew = Negative::find($id);

        if (is_null($negativeNew)) {
            return $this->sendError("The record item negative $id does not exist.");
        }

        // Conversión de interface
        $negativeNew['id'] = intval($id);
        $negativeNew['desc'] = $input['desc'];
        $negativeNew['point'] = $input['point'];
        $negativeNew['question_id'] = intval($input['question']);
        $negativeNew['user_id'] = intval($input['user']);

        $negativeNew->update();

        // Conversión de interface
        $response['_id'] = strval($negativeNew->id);
        $response['desc'] = $negativeNew->desc;
        $response['point'] = $negativeNew->point;
        $response['question'] = strval($negativeNew->question_id);
        $response['user'] = strval($negativeNew->user_id);

        return $this->sendResponse($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Positive  $positive
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $negative = Negative::find($id);

        if (is_null($negative)) {
            return $this->sendError("The record item negative $id does not exist.");
        }

        $userAuth = Auth::user();

        if ($userAuth->id <> $negative->user_id) {
            return $this->sendError('Id inconsistent with parameter.');
        }

        $negative->delete();

        // Conversión de interface
        $response['_id'] = strval($negative->id);
        $response['desc'] = $negative->desc;
        $response['point'] = $negative->point;
        $response['question'] = strval($negative->question_id);
        $response['user'] = strval($negative->user_id);

        return $this->sendResponse($response);
    }
}

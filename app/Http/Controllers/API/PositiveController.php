<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Question;
use App\Models\Positive;
use App\Rules\TheSame;
use App\Rules\ValuePoints;

class PositiveController extends BaseController
{
    /**
     * List positives of question
     *
     * @return \Illuminate\Http\Response
     */
    public function positiveQuestion($idQuestion)
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
            return $this->sendError('Id inconsistent with parameter.');
        }

        $itemsPositives = Positive::where('question_id', $question->id)
                                ->get(['id as _id', 'desc', 'point', 'question_id as question', 'user_id as user'])
                                ->toArray();
        // Conversión de interface
        $itemsPositivesNew = array_map($converInterf, $itemsPositives);
        return $this->sendResponse($itemsPositivesNew);
    }

    public function removePositiveQuestion($idQuestion)
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

        $itemsPositives = Positive::where('question_id', intval($idQuestion))->get();

        if (is_null($itemsPositives)) {
            return $this->sendError("There are no records of positive items of question number $idQuestion.");
        }

        $response = Positive::where('question_id', intval($idQuestion))->delete();

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
            return $this->sendError('User inconsistent with question.');
        }

        // Conversión de interface
        $input_new['desc'] = $input['desc'];
        $input_new['point'] = $input['point'];
        $input_new['question_id'] = $input['question'];
        $input_new['user_id'] = intval($input['user']);

        $positive = Positive::create($input_new);

        $response['_id'] = strval($positive->id);
        $response['desc'] = $positive->desc;
        $response['point'] = $positive->point;
        $response['question'] = strval($positive->question_id);
        $response['user'] = strval($positive->user_id);

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
        $positive = Positive::find($id);

        if (is_null($positive)) {
            return $this->sendResponse([]);
        }

        $idQuestion = intval($positive->question->id);
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
        $response['_id'] = strval($positive->id);
        $response['desc'] = $positive->desc;
        $response['point'] = $positive->point;
        $response['question'] = strval($positive->question_id);
        $response['user'] = strval($positive->user_id);

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
            return $this->sendError('User inconsistent with question.');
        }

        if ($idItemBody <> $id) {
            return $this->sendError('Item Positive inconsistent with parameter.');
        }

        $positiveNew = Positive::find($id);

        if (is_null($positiveNew)) {
            return $this->sendError("The record item positive $id does not exist.");
        }

        // Conversión de interface
        $positiveNew['id'] = intval($id);
        $positiveNew['desc'] = $input['desc'];
        $positiveNew['point'] = $input['point'];
        $positiveNew['question_id'] = intval($input['question']);
        $positiveNew['user_id'] = intval($input['user']);

        $positiveNew->update();

        // Conversión de interface
        $response['_id'] = strval($positiveNew->id);
        $response['desc'] = $positiveNew->desc;
        $response['point'] = $positiveNew->point;
        $response['question'] = strval($positiveNew->question_id);
        $response['user'] = strval($positiveNew->user_id);

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
        $positive = Positive::find($id);

        if (is_null($positive)) {
            return $this->sendError("The record item positive $id does not exist.");
        }

        $userAuth = Auth::user();

        if ($userAuth->id <> $positive->user_id) {
            return $this->sendError('Id inconsistent with parameter.');
        }

        $positive->delete();

        // Conversión de interface
        $response['_id'] = strval($positive->id);
        $response['desc'] = $positive->desc;
        $response['point'] = $positive->point;
        $response['question'] = strval($positive->question_id);
        $response['user'] = strval($positive->user_id);

        return $this->sendResponse($response);
    }
}

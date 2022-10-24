<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Question;
use App\Models\Positive;
use App\Models\Negative;
use App\Rules\TheSame;

// 6|HNQMqjS0KduxpuVmffeRhTuViIMCFSLcE3wJiZoh Marga
// 5|opoNoEQypu6uiq4pH7aJ70Aqt7qJiMLEYq1Q28zH Carlos

class QuestionController extends BaseController
{
    /**
     * List of user questions.
     *
     * @return \Illuminate\Http\Response
     */
    public function questionUser($idUser)
    {
        $converInterf = function($array_input) {
            $aKeys = array_keys($array_input);
            $aValues = array_values($array_input);
            $arrayNew = array();
            //var_dump($aKeys, $aValues, count($array_input));
            for ($i=0; $i < count($array_input) ; $i++) {
                if ($aKeys[$i] == "_id" || $aKeys[$i] == "user") {
                    $arrayNew[$aKeys[$i]] = strval($aValues[$i]);
                } else {
                    $arrayNew[$aKeys[$i]] = $aValues[$i];
                }
            }
            return $arrayNew;
        };

        $user = User::find($idUser);

        if (is_null($user)) {
            return $this->sendError("The record user $idUser does not exist.");
        }

        $userAuth = Auth::user();

        if ($userAuth->id <> $user->id) {
            return $this->sendError('Id inconsistent with parameter.');
        }

        $questions = Question::where('user_id', $user->id)
                            ->get(['id as _id', 'question', 'user_id as user'])
                            ->toArray();
        // Conversión de interface
        $questionsNew = array_map($converInterf, $questions);
        return $this->sendResponse($questionsNew);
    }

    public function removeUser($idUser)
    {
        $user = User::find($idUser);

        // Borrado en cascada (Positivos-Negativos-Preguntas-Usuarios)

        if (is_null($user)) {
            return $this->sendError("The record user $idUser does not exist.");
        }

        $userAuth = Auth::user();

        if ($userAuth->name !== "CJORDAN") {
            return $this->sendError('User not authorized.');
        }

        $response["Positivos"] = Positive::where('user_id', intval($idUser))->delete();
        $response["Negativos"] = Negative::where('user_id', intval($idUser))->delete();
        $response["Preguntas"] = Question::where('user_id', intval($idUser))->delete();
        $response["Usuario"] = $user->delete();

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
            'question' => 'required',
            'user' => ['required', new TheSame(strval($userAuth->id))]
        ]);

        if ($validator->fails()) {
            return $this->sendError('Pregunta o usuario no encontrados en body');
        }

        // Conversión de interface
        $input_new['question'] = $input['question'];
        $input_new['user_id'] = intval($input['user']);

        $question = Question::create($input_new);

        $response['_id'] = strval($question->id);
        $response['question'] = $question->question;
        $response['user'] = strval($question->user_id);

        return $this->sendResponse($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function show($id) //listOne
    {
        $question = Question::find($id);

        if (is_null($question)) {
            return $this->sendResponse([]);
        }

        $userAuth = Auth::user();

        if ($userAuth->id <> $question->user_id) {
            return $this->sendError('Id inconsistent with parameter.');
        }

        // Conversión de interface
        $response['_id'] = strval($question->id);
        $response['question'] = $question->question;
        $response['user'] = strval($question->user_id);

        return $this->sendResponse($response);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) //updateOne
    {
        $input = $request->all();
        $userAuth = Auth::user();
        //var_dump("HOLA", $request->request->get('_id'));
        // $request->request->get('_id') Esto da null
        // Problemas con _id en Laravel cuando viene de Angular, no lo puedo obtener
        $validator = Validator::make($input, [
            //'_id' => ['required', new TheSame($id)],
            'question' => 'required',
            'user' => ['required', new TheSame(strval($userAuth->id))]
        ]);

        if ($validator->fails()) {
            return $this->sendError('_id o pregunta o usuario no encontrados en body');
        }

        $questionNew = Question::find($id);

        if (is_null($questionNew)) {
            return $this->sendError("The record question $id does not exist.");
        }

        // Conversión de interface
        $questionNew->id = intval($id);
        $questionNew->question = $input['question'];
        $questionNew->user_id = intval($input['user']);

        $questionNew->update();

        $response['_id'] = strval($questionNew->id);
        $response['question'] = $questionNew->question;
        $response['user'] = strval($questionNew->user_id);

        return $this->sendResponse($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) //removeOne
    {
        $question = Question::find($id);

        if (is_null($question)) {
            return $this->sendError("The record question $id does not exist.");
        }

        $userAuth = Auth::user();

        if ($userAuth->id <> $question->user_id) {
            return $this->sendError('Id inconsistent with parameter.');
        }

        $question->delete();

        // Conversión de interface
        $response['_id'] = strval($question->id);
        $response['question'] = $question->question;
        $response['user'] = strval($question->user_id);

        return $this->sendResponse($response);
    }
}

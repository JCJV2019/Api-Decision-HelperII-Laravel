<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

use DateTime;

class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Email o password o name faltan o son incorrectos');
        }

        // Tenemos que verificar que no exista ya en la BD
        $input = $request->all();
        $user = User::where('email', $input['email'])->get();

        if ($user->count() > 0) {
            return $this->sendError('Ya existe el usuario');
        }

        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        //$success['token'] =  $user->createToken('MyApp')->plainTextToken;
        //$success['name'] =  $user->name;

        $response = [
            'message' => 'Sing-up Ok'
        ];
        return response()->json($response, 200);
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Email o password faltan o son incorrectos');
        }

        // Tenemos que verificar que exista en la BD
        $input = $request->all();
        $user = User::where('email', $input['email'])->get();

        if ($user->count() == 0) {
            return $this->sendError('No existe el usuario');
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            $secondsExpiration = config('auth.password_timeout');
            $days = $secondsExpiration / 86400;
            $expiration = new DateTime();
            $expiration->modify('+' . $secondsExpiration . ' second');
            $nameApp = config('app.name');
            $success = [
                'message' => 'Login OK',
                'token' =>  $user->createToken($nameApp)->plainTextToken,
                'expires_in' => strval($days) . ' days',
                'expires_at' => $expiration->format('d-m-Y H:m:s'),
                'id_user' => strval($user->id),
                'name_user' => $user->name,
            ];

            return $this->sendResponse($success);
        } else {
            return $this->sendError('Password incorrecto');
        }
    }

    public function userList(Request $request)
    {
        $converInterf = function($array_input) {
            $aKeys = array_keys($array_input);
            $aValues = array_values($array_input);
            $arrayNew = array();
            //var_dump($aKeys, $aValues, count($array_input));
            for ($i=0; $i < count($array_input) ; $i++) {
                if ($aKeys[$i] == "_id") {
                    $arrayNew[$aKeys[$i]] = strval($aValues[$i]);
                } else {
                    $arrayNew[$aKeys[$i]] = $aValues[$i];
                }
            }
            return $arrayNew;
        };

        $user = Auth::user();
        if ($user->name == "CJORDAN") {
            $users = User::select('id as _id', 'email', 'name')->get()->toArray();
            // Conversión de interface
            $usersNew = array_map($converInterf, $users);
            return response()->json($usersNew, 200);
        } else {
            //return $this->sendError("No puedes listar los usuarios");
            return response()->json([], 200);
        }

    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Routing\Controller as BaseController;

class AuthController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:100',
            'phone_number' => 'required|string',  //|regex:/^[0-9+\-\s]+$/|max:20|unique:users',
            'email' => 'nullable|string|email|max:100|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'nullable|string|in:admin,passenger,driver',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

       $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'phone_number' => $this->formatPhoneNumber($request->phone_number),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Formatter le numéro de téléphone pour la recherche
        $formattedPhone = $this->formatPhoneNumber($request->phone_number);
        
        // Tentative de connexion avec le numéro de téléphone
        $credentials = [
            'phone_number' => $formattedPhone,
            'password' => $request->password
        ];

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Numéro de téléphone ou mot de passe incorrect'
            ], 401);
        }

        return $this->createNewToken($token);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    public function refresh()
    {
        return $this->createNewToken(JWTAuth::refresh(JWTAuth::getToken()));
    }

    public function userProfile()
    {
        return response()->json([
            'success' => true,
            'user' => Auth::user()
        ]);
    }

    protected function createNewToken($token)
    {
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => Auth::user()
        ]);
    }

    /**
     * Formate le numéro de téléphone pour assurer la cohérence
     */
    private function formatPhoneNumber($phone_number)
    {

        // Supprimer tous les espaces et caractères spéciaux sauf + et chiffres
        $phone_number = preg_replace('/[^0-9+]/', '', $phone_number);
        
        // Si le numéro commence par 0, remplacer par +237 (Cameroun)
        if (substr($phone_number, 0, 1) === '0') {
            $phone_number = '+237' . substr($phone_number, 1);
        }
        
        // Si le numéro ne commence pas par +, ajouter +237
        if (substr($phone_number, 0, 1) !== '+') {
            $phone_number = '+237' . $phone_number;
        }
        
        return $phone_number;
    }
}
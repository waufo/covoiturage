<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Afficher la liste des utilisateurs
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = User::query();

        // Recherche par nom, email ou téléphone
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('created_at', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:100',
            'phone_number' => 'required|string|regex:/^[0-9+\-\s]+$/|max:20|unique:users',
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

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'data' => $user
        ], 201);
    }

    /**
     * Afficher un utilisateur spécifique
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        // Seul l'utilisateur lui-même ou un admin peut modifier
        if (Auth::id() !== $user->id && !$this->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        // dd($request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100',
            'username' => 'sometimes|required|string|max:100',
            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[0-9+\-\s]+$/',
                'max:20',
                Rule::unique('users')->ignore($user->id),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $dataToUpdate = $request->only(['name', 'username', 'email', 'longitude', 'latitude','otp', 'role', 'isOnline', 'isMobileVerified']);

        // Formater le téléphone si fourni
        if ($request->has('phone_number')) {
            $dataToUpdate['phone_number'] = $this->formatPhoneNumber($request->phone_number);
        }

        // Hasher le mot de passe si fourni
        if ($request->has('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }

        $user->update($dataToUpdate);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => $user->fresh()
        ]);
    }

    

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        // Seul un admin peut supprimer un utilisateur
        if (!$this->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé - Droits administrateur requis'
            ], 403);
        }

        // Empêcher la suppression de son propre compte
        if (Auth::id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    /**
     * Changer le mot de passe de l'utilisateur connecté
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Vérifier l'ancien mot de passe
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect'
            ], 422);
        }

        // Mettre à jour le mot de passe
        // $user->first()->update([
        //     'password' => Hash::make($request->new_password)
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe changé avec succès'
        ]);
    }

    /**
     * Obtenir les statistiques des utilisateurs (admin seulement)
     */
    public function stats()
    {
        if (!$this->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'users_today' => User::whereDate('created_at', today())->count(),
            'users_this_week' => User::whereBetween('created_at', [
                now()->startOfWeek(), 
                now()->endOfWeek()
            ])->count(),
            'users_this_month' => User::whereMonth('created_at', now()->month)
                                     ->whereYear('created_at', now()->year)
                                     ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
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

    /**
     * Vérifier si l'utilisateur connecté est admin
     */
    private function isAdmin()
    {
        $user = Auth::user();
        return $user && isset($user->role) && $user->role === 'admin';
    }
}
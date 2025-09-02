<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TripController extends Controller
{
    /**
     * Créer un nouveau trajet.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_address' => 'required|string|max:255',
            'destination_address' => 'required|string|max:255',
            'price' => 'required|numeric',
            'passenger_id' => 'required|exists:users,id',
            'driver_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $trip = Trip::create([
            'pickup_address' => $request->pickup_address,
            'destination_address' => $request->destination_address,
            'price' => $request->price,
            'passenger_id' => $request->passenger_id,
            'driver_id' => $request->driver_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trajet créé avec succès',
            'trip' => $trip,
        ], 201);
    }

    /**
     * Récupérer tous les trajets.
     */
    public function index()
    {
        $trips = Trip::all();
        return response()->json([
            'success' => true,
            'trips' => $trips,
        ]);
    }

    /**
     * Récupérer un trajet spécifique.
     */
    public function show($id)
    {
        $trip = Trip::find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'trip' => $trip,
        ]);
    }

    /**
     * Mettre à jour un trajet existant.
     */
    public function update(Request $request, $id)
    {
        $trip = Trip::find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'pickup_address' => 'nullable|string|max:255',
            'destination_address' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'driver_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:PENDING,ONGOING,COMPLETED,CANCELLED',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $trip->update($request->only('pickup_address', 'destination_address', 'price', 'driver_id', 'status'));

        return response()->json([
            'success' => true,
            'message' => 'Trajet mis à jour avec succès',
            'trip' => $trip,
        ]);
    }

    /**
     * Supprimer un trajet.
     */
    public function destroy($id)
    {
        $trip = Trip::find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé',
            ], 404);
        }

        $trip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trajet supprimé avec succès',
        ]);
    }
}
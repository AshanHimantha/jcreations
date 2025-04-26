<?php

namespace App\Http\Controllers;

use App\Models\DeliveryLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeliveryLocationController extends Controller
{
    /**
     * Display a listing of the locations.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $locations = DeliveryLocation::all();
        return response()->json(['locations' => $locations]);
    }

    /**
     * Store a newly created location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'city' => 'required|string|max:255|unique:delivery_locations',
                'shipping_charge' => 'required|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            $location = DeliveryLocation::create($validated);

            return response()->json([
                'message' => 'Location created successfully',
                'location' => $location
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified location.
     *
     * @param  \App\Models\DeliveryLocation  $location
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(DeliveryLocation $location)
    {
        return response()->json(['location' => $location]);
    }

    /**
     * Update the specified location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DeliveryLocation  $location
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, DeliveryLocation $location)
    {
        try {
            $validated = $request->validate([
                'city' => 'required|string|max:255|unique:delivery_locations,city,'.$location->id,
                'shipping_charge' => 'required|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            $location->update($validated);

            return response()->json([
                'message' => 'Location updated successfully',
                'location' => $location
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified location from storage.
     *
     * @param  \App\Models\DeliveryLocation  $location
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DeliveryLocation $location)
    {
        $location->delete();

        return response()->json([
            'message' => 'Location deleted successfully'
        ]);
    }
}
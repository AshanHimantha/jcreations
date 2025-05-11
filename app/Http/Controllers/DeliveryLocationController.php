<?php

namespace App\Http\Controllers;

use App\Models\DeliveryLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Delivery Locations",
 *     description="API Endpoints for managing delivery locations"
 * )
 */
class DeliveryLocationController extends Controller
{
    /**
     * Display a listing of the locations.
     *
     * @OA\Get(
     *     path="/api/locations",
     *     tags={"Delivery Locations"},
     *     summary="Get list of all delivery locations",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="locations",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/DeliveryLocation")
     *             )
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/admin/locations",
     *     tags={"Delivery Locations"},
     *     summary="Create a new delivery location",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"city", "shipping_charge"},
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="shipping_charge", type="number", example=10.50),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Location created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Location created successfully"),
     *             @OA\Property(property="location", ref="#/components/schemas/DeliveryLocation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/admin/locations/{location}",
     *     tags={"Delivery Locations"},
     *     summary="Get a specific delivery location",
     *     @OA\Parameter(
     *         name="location",
     *         in="path",
     *         description="Delivery location ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="location", ref="#/components/schemas/DeliveryLocation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Location not found"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/admin/locations/{location}",
     *     tags={"Delivery Locations"},
     *     summary="Update a delivery location",
     *     @OA\Parameter(
     *         name="location",
     *         in="path",
     *         description="Delivery location ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"city", "shipping_charge"},
     *             @OA\Property(property="city", type="string", example="Chicago"),
     *             @OA\Property(property="shipping_charge", type="number", example=15.75),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Location updated successfully"),
     *             @OA\Property(property="location", ref="#/components/schemas/DeliveryLocation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Location not found"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/admin/locations/{location}",
     *     tags={"Delivery Locations"},
     *     summary="Delete a delivery location",
     *     @OA\Parameter(
     *         name="location",
     *         in="path",
     *         description="Delivery location ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Location deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Location not found"
     *     )
     * )
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
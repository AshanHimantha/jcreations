<?php

namespace App\Http\Controllers;

use App\Models\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MobileNumberController extends Controller
{
    /**
     * Display a listing of mobile numbers.
     * 
     * @OA\Get(
     *     path="/api/mobile-numbers",
     *     tags={"Mobile Numbers"},
     *     summary="Get list of mobile numbers",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="number", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $mobileNumbers = MobileNumber::orderBy('created_at', 'desc')->get();
        return response()->json($mobileNumbers);
    }

    /**
     * Store a newly created mobile number.
     * 
     * @OA\Post(
     *     path="/api/admin/mobile-numbers",
     *     tags={"Mobile Numbers"},
     *     summary="Store a new mobile number",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"number"},
     *             @OA\Property(property="number", type="string", maxLength=20, example="1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mobile number created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mobile number created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'required|string|max:20|unique:mobile_numbers',
        ]);

        $mobileNumber = MobileNumber::create($validated);

        return response()->json([
            'message' => 'Mobile number created successfully',
            'data' => $mobileNumber
        ], 201);
    }

    /**
     * Display the specified mobile number.
     * 
     * @OA\Get(
     *     path="/api/mobile-numbers/{mobileNumber}",
     *     tags={"Mobile Numbers"},
     *     summary="Get a specific mobile number",
     *     @OA\Parameter(
     *         name="mobileNumber",
     *         in="path",
     *         required=true,
     *         description="ID of mobile number to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mobile number retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="number", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mobile number not found"
     *     )
     * )
     */
    public function show(MobileNumber $mobileNumber): JsonResponse
    {
        return response()->json($mobileNumber);
    }

    /**
     * Update the specified mobile number.
     * 
     * @OA\Put(
     *     path="/api/admin/mobile-numbers/{mobileNumber}",
     *     tags={"Mobile Numbers"},
     *     summary="Update an existing mobile number",
     *     @OA\Parameter(
     *         name="mobileNumber",
     *         in="path",
     *         required=true,
     *         description="ID of mobile number to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"number"},
     *             @OA\Property(property="number", type="string", maxLength=20, example="9876543210")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mobile number updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mobile number updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mobile number not found"
     *     )
     * )
     */
    public function update(Request $request, MobileNumber $mobileNumber): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'required|string|max:20|unique:mobile_numbers,number,' . $mobileNumber->id,
        ]);

        $mobileNumber->update($validated);

        return response()->json([
            'message' => 'Mobile number updated successfully',
            'data' => $mobileNumber
        ]);
    }

    /**
     * Remove the specified mobile number.
     * 
     * @OA\Delete(
     *     path="/api/admin/mobile-numbers/{mobileNumber}",
     *     tags={"Mobile Numbers"},
     *     summary="Delete a mobile number",
     *     @OA\Parameter(
     *         name="mobileNumber",
     *         in="path",
     *         required=true,
     *         description="ID of mobile number to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mobile number deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mobile number deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mobile number not found"
     *     )
     * )
     */
    public function destroy(MobileNumber $mobileNumber): JsonResponse
    {
        $mobileNumber->delete();

        return response()->json([
            'message' => 'Mobile number deleted successfully'
        ]);
    }
}
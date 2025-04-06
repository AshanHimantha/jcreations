<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="JCreations API",
 *     version="1.0.0",
 *     description="API Documentation for JCreations Application",
 *     @OA\Contact(
 *         email="support@jcreations.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="/",
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="API endpoints for managing users"
 * )
 */
abstract class Controller
{
    //
}

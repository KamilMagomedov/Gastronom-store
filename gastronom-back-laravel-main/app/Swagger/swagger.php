<?php

/**
 * @OA\Info(
 *     title="Home Delivery API",
 *     version="1.0.0",
 *     description="API for home delivery mobile application"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Home Delivery API Server"
 * )
 *
 * @OA\PathItem(
 *     path="/api/auth/register",
 *
 *     @OA\Post(
 *         summary="Register new user",
 *         tags={"Authentication"},
 *
 *         @OA\RequestBody(
 *             required=true,
 *
 *             @OA\JsonContent(
 *                 required={"name","email","password","password_confirmation"},
 *
 *                 @OA\Property(property="name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *                 @OA\Property(property="password", type="string", format="password", example="password123"),
 *                 @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
 *             )
 *         ),
 *
 *         @OA\Response(
 *             response=201,
 *             description="User registered successfully",
 *
 *             @OA\JsonContent(
 *
 *                 @OA\Property(property="message", type="string", example="User registered successfully. Please check your email for verification code.")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/auth/verify",
 *
 *     @OA\Post(
 *         summary="Verify email with OTP and get token",
 *         tags={"Authentication"},
 *
 *         @OA\RequestBody(
 *             required=true,
 *
 *             @OA\JsonContent(
 *                 required={"email","code"},
 *
 *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *                 @OA\Property(property="code", type="string", example="123456")
 *             )
 *         ),
 *
 *         @OA\Response(
 *             response=200,
 *             description="Email verified successfully",
 *
 *             @OA\JsonContent(
 *
 *                 @OA\Property(property="message", type="string", example="Email verified successfully"),
 *                 @OA\Property(property="token", type="string", example="1|abc123..."),
 *                 @OA\Property(property="user", type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="email", type="string", format="email", example="john@example.com")
 *                 )
 *             )
 *         )
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/user",
 *
 *     @OA\Get(
 *         summary="Get authenticated user",
 *         tags={"User"},
 *         security={{"sanctum":{}}},
 *
 *         @OA\Response(
 *             response=200,
 *             description="User data retrieved successfully",
 *
 *             @OA\JsonContent(
 *
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Components(
 *
 *     @OA\SecurityScheme(
 *         securityScheme="sanctum",
 *         type="http",
 *         scheme="bearer",
 *         bearerFormat="JWT"
 *     )
 * )
 */

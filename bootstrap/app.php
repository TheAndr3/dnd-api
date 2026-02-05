<?php

use App\Exceptions\CharacterAlreadyInCampaignException;
use App\Exceptions\CharacterNotInCampaignException;
use App\Exceptions\CharacterOwnershipException;
use App\Exceptions\InvalidInvitationCodeException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register SanitizeInput middleware globally for API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\SanitizeInput::class,
        ]);

        // For API routes, return JSON 401 instead of redirecting to login
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // Will throw AuthenticationException with JSON response
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ModelNotFoundException - 404
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'errors' => [],
                ], 404);
            }
        });

        // ValidationException - 422
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // AuthorizationException - 403
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Unauthorized',
                    'errors' => [],
                ], 403);
            }
        });

        // InvalidInvitationCodeException - 404
        $exceptions->render(function (InvalidInvitationCodeException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [],
                ], 404);
            }
        });

        // CharacterOwnershipException - 403
        $exceptions->render(function (CharacterOwnershipException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [],
                ], 403);
            }
        });

        // CharacterAlreadyInCampaignException - 409 (Conflict)
        $exceptions->render(function (CharacterAlreadyInCampaignException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [],
                ], 409);
            }
        });

        // CharacterNotInCampaignException - 404
        $exceptions->render(function (CharacterNotInCampaignException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [],
                ], 404);
            }
        });
    })->create();

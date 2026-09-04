<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers\Auth;

use App\Http\Api\Requests\Auth\LoginRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use App\Http\Api\Responses\TokenResponse;

final readonly class LoginController
{
    public function __construct() {}

    /**
     * @throws ValidationException|Throwable
     */
    public function __invoke(LoginRequest $request): TokenResponse
    {
        $request->authenticate();

        /** @var NewAccessToken $token */
        $token = $request->user()?->createToken(
            name: $request->header('X-Integration-Name', 'default-integration'),
            abilities: [],
        );

        return new TokenResponse(token: $token->plainTextToken);
    }
}

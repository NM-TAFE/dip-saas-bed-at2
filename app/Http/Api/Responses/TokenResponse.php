<?php

declare(strict_types=1);

namespace App\Http\Api\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class TokenResponse implements Responsable
{
    public function __construct(private string $token, private int $status = Response::HTTP_OK) {}

    /** @param Request $request */
    public function toResponse($request): Response
    {
        return new JsonResponse(
            data: [
                'token' => $this->token,
                'type' => 'Bearer',
            ],
            status: $this->status,
            headers: [],
        );
    }
}

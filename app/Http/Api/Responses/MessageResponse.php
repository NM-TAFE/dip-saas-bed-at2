<?php

declare(strict_types=1);

namespace App\Http\Api\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class MessageResponse implements Responsable
{
    public function __construct(private string $message, private int $status = Response::HTTP_OK) {}

    /** @param Request $request */
    public function toResponse($request): Response
    {
        return new JsonResponse(data: ['message' => $this->message], status: $this->status, headers: []);
    }
}

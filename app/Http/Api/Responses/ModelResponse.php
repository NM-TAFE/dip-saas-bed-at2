<?php

declare(strict_types=1);

namespace App\Http\Api\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class ModelResponse implements Responsable
{
    public function __construct(private JsonResource $data, private int $status = Response::HTTP_OK) {}

    /** @param Request $request */
    public function toResponse($request): Response
    {
        return new JsonResponse(data: $this->data, status: $this->status, headers: []);
    }
}

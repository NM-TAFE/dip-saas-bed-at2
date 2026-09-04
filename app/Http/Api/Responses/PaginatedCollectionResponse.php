<?php

declare(strict_types=1);

namespace App\Http\Api\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class PaginatedCollectionResponse implements Responsable
{
    public function __construct(private AnonymousResourceCollection $data, private int $status = Response::HTTP_OK) {}

    /** @param Request $request */
    public function toResponse($request): Response
    {
        return new JsonResponse(data: $this->data, status: $this->status, headers: []);
    }
}

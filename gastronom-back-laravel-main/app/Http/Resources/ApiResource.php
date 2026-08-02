<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class ApiResource extends JsonResource
{
    private int $status_code = Response::HTTP_OK;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => parent::toArray($request),
        ];
    }

    /**
     * Create a proper JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function toResponse($request)
    {
        $this->additional([
            'success' => $this->status_code < 400,
        ]);

        return parent::toResponse($request)->setStatusCode($this->status_code);
    }

    /**
     * Set the HTTP status code for the response.
     *
     * @return $this
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->status_code = $statusCode;

        return $this;
    }

    /**
     * Add additional meta data to the resource response.
     *
     * @param  array<string, mixed>  $data
     * @return $this
     */
    public function additional(array $data): self
    {
        $this->additional = array_merge($this->additional ?? [], $data);

        return $this;
    }
}

<?php

namespace App\Support;

use App\Http\Resources\ApiResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Http\Response;

class ApiResponse
{
    /**
     * @param  array  $headers
     * @param  number  $options
     */
    public static function success(mixed $data = null): ApiResource
    {
        return ApiResource::make($data);
    }

    /**
     * @param  array  $headers
     * @param  number  $options
     */
    public static function paginator(LengthAwarePaginatorContract $data): ApiResource
    {
        return ApiResource::make($data->items())->additional([
            'paginator' => [
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
                'has_more' => $data->hasMorePages(),
            ],
        ]);
    }

    public static function created(mixed $data = null): ApiResource
    {
        return self::success($data)->setStatusCode(Response::HTTP_CREATED);
    }

    public static function accepted(mixed $data = null): ApiResource
    {
        return self::success($data)->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public static function noContent(): ApiResource
    {
        return self::success()->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    public static function serverError(mixed $data = null, int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): ApiResource
    {
        return ApiResource::make($data)->additional([
            'message' => __('http-statuses.'.$statusCode),
        ])->setStatusCode($statusCode);
    }

    public static function laravelError(string $message, mixed $data = null, int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): ApiResource
    {
        return ApiResource::make($data)->additional([
            'message' => $message,
        ])->setStatusCode($statusCode);
    }

    public static function notFound(mixed $data = null): ApiResource
    {
        return self::serverError($data, Response::HTTP_NOT_FOUND);
    }

    public static function unprocessableEntity(string $message, mixed $data = null): ApiResource
    {
        return self::laravelError($message, $data, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function unauthorized(string $message, ?array $data = null): ApiResource
    {
        return self::laravelError($message, $data, Response::HTTP_UNAUTHORIZED);
    }
}

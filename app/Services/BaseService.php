<?php

namespace App\Services;

use App\Exceptions\InvalidScopeException;
use App\Traits\ApiHelperTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BaseService
{
    use ApiHelperTrait;
    /**
     * Return response for failed operation
     *
     * @param string|null $message
     * @param array $errors
     * @param int $code
     * @return JsonResponse
     */
    public function error(string $message = 'Your Request Is Invalid', array $errors = [], int $code = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $response = [
            'code' => (string) $code,
            'status' => 'failed',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function handleException(\Exception $e, $message = null): JsonResponse
    {
        Log::error($e->getMessage());

        $statusCode = $e instanceof ModelNotFoundException ? 404 : ($e instanceof InvalidScopeException ? $e->getCode() : 500);

        $message = $e instanceof ModelNotFoundException ? __('message.Resource not found') : ($e instanceof InvalidScopeException ? $e->getMessage() : $message);

        return $this->error($message, [], $statusCode);
    }

    protected function withPagination($query, $request) {
        if ($request->has('paginate') && $request->paginate == 'false') {
            return $query->get();
        } else {
            $perPage = $request->per_page ?? 10;

            return $query->skip(($request->page - 1) * $perPage)
                ->take($perPage)
                ->paginate($perPage);
        }
    }

    protected function withTrashed($query, $request) {
        if ($request->has('with_trashed') && $request->with_trashed == 'true') {
            return $query->withTrashed();
        }
        return $query;
    }
}

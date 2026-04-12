<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListPersonRequest;
use App\Http\Resources\PersonListResource;
use App\Http\Resources\PersonResource;
use App\Services\PersonService;
use Illuminate\Http\JsonResponse;

class PersonController extends BaseController
{
    public function __construct(
        private readonly PersonService $personService
    ) {}

    public function index(ListPersonRequest $request): JsonResponse
    {
        return $this->paginate(
            $this->personService->getList($request->validated()),
            PersonListResource::class
        );
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(new PersonResource($this->personService->findById($id)));
    }
}

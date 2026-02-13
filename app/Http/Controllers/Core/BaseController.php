<?php

namespace App\Http\Controllers\Core;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

use Illuminate\Routing\Controller as LaravelController;

abstract class BaseController extends LaravelController
{
    /**
     * Получить все записи с пагинацией
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $relations = $request->input('with', []);
        $filters = $request->except(['per_page', 'with', 'page', 'paginate']);

        // Считываем paginate из запроса, по умолчанию true
        $paginate = $request->has('paginate')
            ? filter_var($request->input('paginate'), FILTER_VALIDATE_BOOLEAN)
            : true;

        $data = $this->getService()->getAll(
            relations: is_array($relations) ? $relations : explode(',', $relations),
            paginate: $paginate,
            perPage: $perPage,
            filters: $filters
        );

        return $this->successResponse($data);
    }


    /**
     * Получить запись по ID
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $relations = $request->input('with', []);

        $item = $this->getService()->getById(
            id: $id,
            relations: is_array($relations) ? $relations : explode(',', $relations)
        );

        return $this->successResponse($item);
    }

    /**
     * Создать новую запись
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $this->validate($request, $this->getValidationRules());
        $item = $this->getService()->create($validatedData);

        return $this->createdResponse($item);
    }

    /**
     * Обновить запись
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $this->validate($request, $this->getUpdateValidationRules($id));
        $item = $this->getService()->update($id, $validatedData);

        return $this->successResponse($item);
    }

    /**
     * Удалить запись
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->getService()->delete($id);

        return $this->noContentResponse();
    }

    /**
     * Успешный JSON ответ
     * @param mixed $data
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse($data, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Ответ для успешного создания
     * @param mixed $data
     * @return JsonResponse
     */
    protected function createdResponse($data): JsonResponse
    {
        return $this->successResponse($data, Response::HTTP_CREATED);
    }

    /**
     * Пустой успешный ответ
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Ответ с ошибкой
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode, array $errors = []): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Правила валидации для обновления
     * По умолчанию использует те же правила, что и для создания
     * @param int $id
     * @return array
     */
    protected function getUpdateValidationRules(int $id): array
    {
        return $this->getValidationRules();
    }

    /**
     * Абстрактный метод для получения сервиса
     * @return mixed
     */
    abstract protected function getService();

    /**
     * Абстрактный метод для получения правил валидации
     * @return array
     */
    abstract protected function getValidationRules();


    // кастомный метод валидации
    protected function validate(Request $request, array $rules): array
    {
        $validator = ValidatorFacade::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}

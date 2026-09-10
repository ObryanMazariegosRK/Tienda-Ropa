<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Category\IGetCategoriesUseCase;
use App\Application\Abstractions\Category\ISaveCategoryUseCase;
use App\Application\Abstractions\Category\IUpdateCategoryUseCase;
use App\Application\Abstractions\Category\IDeleteCategoryUseCase;         
use App\Application\Abstractions\Category\IGetCategoryByIdUseCase;    
use App\Application\Abstractions\Category\IGetCategoriesByParentIdUseCase;
use App\Application\DTOs\Category\SaveCategoryDTO;
use App\Application\DTOs\Category\UpdateCategoryDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;

class CategoryController extends Controller
{
    // Inyectamos la interfaz, laravel se encargara de hacer la instancia de la clase xd
    public function __construct(
        private IGetCategoriesUseCase $getCategoriesUseCase,
        private ISaveCategoryUseCase $saveCategoryUseCase,
        private IUpdateCategoryUseCase $updateCategoryUseCase,
        private IDeleteCategoryUseCase $deleteCategoryUseCase,             
        private IGetCategoryByIdUseCase $getCategoryByIdUseCase,           
        private IGetCategoriesByParentIdUseCase $getCategoriesByParentIdUseCase
    ) {}

    /**
     * El método index es la convención en Laravel para listar recursos (equivalente a un GET /)
     */
    public function index(): JsonResponse
    {
        //Llamos al caso de uso
        $categoriesDTO = $this->getCategoriesUseCase->execute();

        //Devolvemos la respuesta en formato JSON con un código HTTP 200 
        return response()->json($categoriesDTO, 200);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $dto = new SaveCategoryDTO(
                $request->input('name'),
                $request->input('description') ?? '',
                $request->input('parentCategoryId'),
                $request->boolean('isActive', true)
            );

            $categoryDTO = $this->saveCategoryUseCase->execute($dto);
            return response()->json($categoryDTO, 201);

        } catch (\App\Domain\Exceptions\BusinessRuleException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);

        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'No se pudo crear la categoría.'], 500);
        }
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $dto = new UpdateCategoryDTO(
                $id,
                $request->input('name'),
                $request->input('description') ?? '',
                $request->input('parentCategoryId'),
                $request->boolean('isActive', true)
            );

            $categoryDTO = $this->updateCategoryUseCase->execute($dto);
            return response()->json($categoryDTO, 200);

        } catch (\App\Domain\Exceptions\BusinessRuleException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);

        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 404);
        }
    }

    //Para obtener una categoria por su Id
    public function show(int $id): JsonResponse
    {
        try {
            // Intentamos ejecutar el caso de uso
            $categoryDTO = $this->getCategoryByIdUseCase->execute($id);
            return response()->json($categoryDTO, 200);
            
        } catch (\Exception $e) {
            // Si el caso de uso lanza una excepción, la atrapamos y devolvemos 404
            return response()->json([
                'error' => true,
                'message' => $e->getMessage() // "La categoría solicitada no fue encontrada."
            ], 404);
        }

    }

    //Para eliminar 
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deleteCategoryUseCase->execute($id);

            return response()->json([
                'message' => 'Categoría eliminada correctamente.'
            ], 200);

        } catch (\App\Domain\Exceptions\BusinessRuleException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    //Para obtener por el id del padre
    public function getByParent(?int $parentId=null): JsonResponse
    {
        $categoriesDTO=$this->getCategoriesByParentIdUseCase->execute($parentId);
        return response()->json($categoriesDTO, 200);
    }

}
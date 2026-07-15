<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Product\IDeleteProductUseCase;
use App\Application\Abstractions\Product\IGetAllProductsUseCase;
use App\Application\Abstractions\Product\IGetProductByIdUseCase;
use App\Application\Abstractions\Product\IGetProductsByCategoryUseCase;
use App\Application\Abstractions\Product\ISaveProductUseCase;
use App\Application\Abstractions\Product\IUpdateProductUseCase;
use App\Application\DTOs\Product\ProductDTO;
use App\Application\DTOs\Product\SaveProductDTO;
use App\Application\DTOs\Product\UpdateProductDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller{

    public function __construct(
        private ISaveProductUseCase $saveProductUseCase,
        private IGetProductByIdUseCase $getProductByIdUseCase,
        private IUpdateProductUseCase $updateProductUseCase,
        private IDeleteProductUseCase $deleteProductUseCase,
        private IGetAllProductsUseCase $getAllProductsUseCase,
        private IGetProductsByCategoryUseCase $getProductsByCategoryUseCase
    ){}

    public function store(StoreProductRequest $request): JsonResponse{

        try {
            //Transformamos los datos validados del Request al DTO de entrada
            $dto = new SaveProductDTO(
                categoryId: (int) $request->validated('categoryId'),
                name: $request->validated('name'),
                description: $request->validated('description'),
                price: (float) $request->validated('price'),
                offerPrice: $request->validated('offerPrice') !== null ? (float) $request->validated('offerPrice') : null,
                saleType: $request->validated('saleType'),
                status: $request->validated('status'),
                
                //Extraemos las imágenes. Si no hay, mandamos array vacío
                images: $request->file('images', []) 
            );

                   

            //Ejecutamos el Caso de Uso pasando el DTO
            $productDTO = $this->saveProductUseCase->execute($dto);

            //Devolvemos una respuesta estándar en formato JSON con estado 201 
            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente.',
                'data' => $productDTO
            ], 201);

        } catch (Exception $e) {
            //Si algo falla 
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el producto.',
                'error' => $e->getMessage()
            ], 400);
        }

    }

    //Para obtener un producto por su Id
    public function show(int $id): JsonResponse
    {
        try {
            // Intentamos ejecutar el caso de uso
            $productDTO = $this->getProductByIdUseCase->execute($id);
            
            if(!$productDTO){
                return response()->json([
                    'success'=>false,
                    'message'=>'Producto no encontrado',
                ], 404);
            }

            return response()->json([
                'success'=>true,
                'data'=>$productDTO
            ],200);

        } catch (\Exception $e) {
           
            return response()->json([
                'error' => true,
                'message' => $e->getMessage() 
            ], 500);
        }

    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            // Extraemos los arreglos de imágenes del request
            $newImages = $request->file('new_images', []);
            
            // Asegurarnos de que los IDs a eliminar sean un arreglo de enteros
            $deletedImages = array_map('intval', $request->input('deleted_images', []));

            // 2. Armamos el DTO de entrada (Con casteos explícitos)
            $dto = new UpdateProductDTO(
                id: (int) $id,
                categoryId: (int) $request->validated('categoryId'),
                name: $request->validated('name'),
                description: $request->validated('description'),
                price: (float) $request->validated('price'),
                // Validamos si viene nulo para no intentar castear un null a float
                offerPrice: $request->validated('offerPrice') !== null ? (float) $request->validated('offerPrice') : null,
                saleType: $request->validated('saleType'),
                status: $request->validated('status'),
                
                // Pasamos los nuevos arreglos al DTO
                newImages: $newImages,
                deletedImageIds: $deletedImages
            );

            // 3. Ejecutamos el caso de uso
            $productDTO = $this->updateProductUseCase->execute($dto);
            
            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado correctamente.',
                'data' => $productDTO
            ], 200);

        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar actualizar el producto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function destroy(int $id): JsonResponse
    {
        try {
            
            $this->deleteProductUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'El producto fue eliminado exitosamente.'
            ], 200);

        } catch (NotFoundHttpException $e) {
            //Si el producto no existe
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            //Cualquier otro error 
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el producto.',
                'error' => $e->getMessage()
            ], 500);
        }
    } 

    public function index(): JsonResponse
    {
        try {
            //Obtenemos el array de DTOs
            $products = $this->getAllProductsUseCase->execute();

            return response()->json([
                'success' => true,
                'data' => $products
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener el catálogo de productos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByCategory(int $categoryId): JsonResponse
    {
        try {
            $products = $this->getProductsByCategoryUseCase->execute($categoryId);

            return response()->json([
                'success' => true,
                'data' => $products
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener los productos de esta categoría.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

}
<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Product\IDeleteProductUseCase;
use App\Application\Abstractions\Product\IGetAllProductsUseCase;
use App\Application\Abstractions\Product\IGetPaginatedProductsUseCase;
use App\Application\Abstractions\Product\IGetProductByIdUseCase;
use App\Application\Abstractions\Product\IGetProductsByCategoryUseCase;
use App\Application\Abstractions\Product\ISaveProductUseCase;
use App\Application\Abstractions\Product\IUpdateProductUseCase;
use App\Application\DTOs\Product\ProductDTO;
use App\Application\DTOs\Product\SaveProductDTO;
use App\Application\DTOs\Product\UpdateProductDTO;
use App\Domain\Exceptions\BusinessRuleException;
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
        private IGetProductsByCategoryUseCase $getProductsByCategoryUseCase,
        private IGetPaginatedProductsUseCase $getPaginatedProductsUseCase
    ){}

    public function store(StoreProductRequest $request): JsonResponse{

        try {
            $dto = new SaveProductDTO(
                categoryId: (int) $request->validated('categoryId'),
                name: $request->validated('name'),
                description: $request->validated('description'),
                price: (float) $request->validated('price'),
                offerPrice: $request->validated('offerPrice') !== null ? (float) $request->validated('offerPrice') : null,
                saleType: $request->validated('saleType'),
                status: $request->validated('status'),
                images: $request->file('images', []),

                // Solo llegan valores reales cuando saleType === 'auction';
                // en cualquier otro caso, validated() devuelve null y el DTO ya los acepta como opcionales
                auctionDurationAmount: $request->validated('auctionDurationAmount') !== null ? (int) $request->validated('auctionDurationAmount') : null,
                auctionDurationUnit: $request->validated('auctionDurationUnit'),
                auctionMinIncrement: $request->validated('auctionMinIncrement') !== null ? (float) $request->validated('auctionMinIncrement') : null,
                cost: (float) $request->validated('cost'),
            );

            $productDTO = $this->saveProductUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente.',
                'data' => $productDTO
            ], 201);

        } catch (Exception $e) {
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
                deletedImageIds: $deletedImages,
                cost: $request->validated('cost') !== null ? (float) $request->validated('cost') : null,
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

        }
        catch (BusinessRuleException $e) {
            // Regla de negocio: el usuario hizo algo no permitido, no es un error del servidor
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        }
        catch (\Exception $e) {
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);

        } catch (BusinessRuleException $e) {
            // Regla de negocio: el usuario hizo algo no permitido, no es un error del servidor
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el producto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //public function index(): JsonResponse
    //{
        //try {
            //Obtenemos el array de DTOs
            //$products = $this->getAllProductsUseCase->execute();

            //return response()->json([
              //  'success' => true,
                //'data' => $products
            //], 200);

        //} catch (\Exception $e) {
        //    return response()->json([
        //        'success' => false,
        //        'message' => 'Ocurrió un error al obtener el catálogo de productos.',
         //       'error' => $e->getMessage()
         //   ], 500);
       // }
   // }

    public function index(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $perPage = (int) $request->query('per_page', 24);
            $onOffer = filter_var($request->query('on_offer', false), FILTER_VALIDATE_BOOLEAN);

            $result = $this->getPaginatedProductsUseCase->execute($page, $perPage, 'available', null, 'direct', $onOffer);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'meta' => $result['meta'],
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

    public function onAuction(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $perPage = (int) $request->query('per_page', 24);

            $result = $this->getPaginatedProductsUseCase->execute($page, $perPage, 'available', null, 'auction');

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'meta' => $result['meta'],
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    

}
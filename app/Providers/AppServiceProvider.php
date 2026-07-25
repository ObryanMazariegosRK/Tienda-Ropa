<?php

namespace App\Providers;

use App\Application\Abstractions\Address\ICreateAddressUseCase;
use App\Application\Abstractions\Address\IDeleteAddressUseCase;
use App\Application\Abstractions\Address\IListAddressesUseCase;
use App\Application\Abstractions\Address\ISetDefaultAddressUseCase;
use App\Application\Abstractions\Address\IUpdateAddressUseCase;
use App\Application\Abstractions\Banner\IAddMediaToGroupUseCase;
use App\Application\Abstractions\Banner\ICreateBannerGroupUseCase;
use App\Application\Abstractions\Banner\ICreateBannerUseCase;
use App\Application\Abstractions\Banner\IDeleteBannerGroupUseCase;
use App\Application\Abstractions\Banner\IDeleteBannerUseCase;
use App\Application\Abstractions\Banner\IGetActiveBannerGroupUseCase;
use App\Application\Abstractions\Banner\IGetActiveBannerUseCase;
use App\Application\Abstractions\Banner\IListActiveBannersUseCase;
use App\Application\Abstractions\Banner\IListAllBannerGroupsUseCase;
use App\Application\Abstractions\Banner\IListAllBannersUseCase;
use App\Application\Abstractions\Banner\IListBannersUseCase;
use App\Application\Abstractions\Banner\IRemoveMediaFromGroupUseCase;
use App\Application\Abstractions\Banner\IRenameBannerGroupUseCase;
use App\Application\Abstractions\Banner\IReorderBannersUseCase;
use App\Application\Abstractions\Banner\IToggleBannerActiveUseCase;
use App\Application\Abstractions\Banner\IToggleBannerGroupActiveUseCase;
use App\Application\Abstractions\Banner\IUpdateBannerUseCase;
use App\Application\Abstractions\Cart\IAddToCartUseCase;
use App\Application\Abstractions\Cart\IGetCartUseCase;
use App\Application\Abstractions\Cart\IRemoveFromCartUseCase;
use App\Application\Abstractions\Category\IDeleteCategoryUseCase;
use App\Application\Abstractions\Category\IGetCategoriesByParentIdUseCase;
use Illuminate\Support\ServiceProvider;

use App\Application\UseCases\Category\GetCategoriesByParentIdUseCase;

//Importamos las interfaces
use App\Domain\Abstractions\ICategoryRepository;
use App\Application\Abstractions\Category\IGetCategoriesUseCase;
use App\Application\Abstractions\Category\IGetCategoryByIdUseCase;
use App\Application\Abstractions\Category\ISaveCategoryUseCase;
use App\Application\Abstractions\Category\IUpdateCategoryUseCase;
use App\Application\Abstractions\Order\ICheckoutUseCase;
use App\Application\Abstractions\Order\IListAllOrdersUseCase;
use App\Application\Abstractions\Order\IListMyOrdersUseCase;
use App\Application\Abstractions\Order\IUpdateOrderStatusUseCase;
use App\Application\Abstractions\Product\IDeleteProductUseCase;
use App\Application\Abstractions\Product\IGetAllProductsUseCase;
use App\Application\Abstractions\Product\IGetProductByIdUseCase;
use App\Application\Abstractions\Product\IGetProductsByCategoryUseCase;
use App\Application\Abstractions\Product\IImageStorageService;
use App\Application\Abstractions\Product\ISaveProductUseCase;
use App\Application\Abstractions\Product\IUpdateProductUseCase;
use App\Application\Abstractions\User\IForgotPasswordUseCase;
use App\Application\Abstractions\User\IGetProfileUseCase;
use App\Application\Abstractions\User\ILoginUserUseCase;
use App\Application\Abstractions\User\IRegisterUserUseCase;
use App\Application\Abstractions\User\IResendVerificationCodeUseCase;
use App\Application\Abstractions\User\IResetPasswordUseCase;
use App\Application\Abstractions\User\IVerifyEmailUseCase;
use App\Application\UseCases\Address\CreateAddressUseCase;
use App\Application\UseCases\Address\DeleteAddressUseCase;
use App\Application\UseCases\Address\ListAddressesUseCase;
use App\Application\UseCases\Address\SetDefaultAddressUseCase;
use App\Application\UseCases\Address\UpdateAddressUseCase;
use App\Application\UseCases\Banner\AddMediaToGroupUseCase;
use App\Application\UseCases\Banner\CreateBannerGroupUseCase;
use App\Application\UseCases\Banner\CreateBannerUseCase;
use App\Application\UseCases\Banner\DeleteBannerGroupUseCase;
use App\Application\UseCases\Banner\DeleteBannerUseCase;
use App\Application\UseCases\Banner\GetActiveBannerGroupUseCase;
use App\Application\UseCases\Banner\GetActiveBannerUseCase;
use App\Application\UseCases\Banner\ListActiveBannersUseCase;
use App\Application\UseCases\Banner\ListAllBannerGroupsUseCase;
use App\Application\UseCases\Banner\ListAllBannersUseCase;
use App\Application\UseCases\Banner\ListBannersUseCase;
use App\Application\UseCases\Banner\RemoveMediaFromGroupUseCase;
use App\Application\UseCases\Banner\RenameBannerGroupUseCase;
use App\Application\UseCases\Banner\ReorderBannersUseCase;
use App\Application\UseCases\Banner\ToggleBannerActiveUseCase;
use App\Application\UseCases\Banner\ToggleBannerGroupActiveUseCase;
use App\Application\UseCases\Banner\UpdateBannerUseCase;
use App\Application\UseCases\Cart\AddToCartUseCase;
use App\Application\UseCases\Cart\GetCartUseCase;
use App\Application\UseCases\Cart\RemovedFromCartUseCase;
use App\Application\UseCases\Category\DeleteCategoryUseCase;
//Importamos los casos de uso y los repositorios
use App\Data\Repositories\CategoryRepository; 
use App\Application\UseCases\Category\GetCategoriesUseCase;
use App\Application\UseCases\Category\GetCategoryByIdUseCase;
use App\Application\UseCases\Category\SaveCategoryUseCase;
use App\Application\UseCases\Category\UpdateCategoryUseCase;
use App\Application\UseCases\Order\CheckoutUseCase;
use App\Application\UseCases\Order\ListAllOrdersUseCase;
use App\Application\UseCases\Order\ListMyOrdersUseCase;
use App\Application\UseCases\Order\UpdateOrderStatusUseCase;
use App\Application\UseCases\Product\DeleteProductUseCase;
use App\Application\UseCases\Product\GetAllProductsUseCase;
use App\Application\UseCases\Product\GetProductByIdUseCase;
use App\Application\UseCases\Product\GetProductsByCategoryUseCase;
use App\Application\UseCases\Product\SaveProductUseCase;
use App\Application\UseCases\Product\UpdateProductUseCase;
use App\Application\UseCases\User\ForgotPasswordUseCase;
use App\Application\UseCases\User\GetProfileUseCase;
use App\Application\UseCases\User\LoginUserUseCase;
use App\Application\UseCases\User\RegisterUserUseCase;
use App\Application\UseCases\User\ResendVerificationCodeUseCase;
use App\Application\UseCases\User\ResetPasswordUseCase;
use App\Application\UseCases\User\VerifyEmailUseCase;
use App\Data\Repositories\AddressRepository;
use App\Data\Repositories\BannerGroupRepository;
use App\Data\Repositories\BannerRepository;
use App\Data\Repositories\CartRepository;
use App\Data\Repositories\OrderDetailRepository;
use App\Data\Repositories\OrderRepository;
use App\Data\Repositories\ProductRepository;
use App\Data\Repositories\UserRepository;
use App\Domain\Abstractions\IAddressRepository;
use App\Domain\Abstractions\IBannerGroupRepository;
use App\Domain\Abstractions\IBannerRepository;
use App\Domain\Abstractions\ICartRepository;
use App\Domain\Abstractions\IOrderDetailRepository;
use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Abstractions\User\IEmailService;
use App\Domain\Abstractions\User\IPasswordHasher;
use App\Domain\Abstractions\User\IUserRepository;
use App\Http\Controllers\AuthController\GetProfileController;
use App\Infraestructure\Services\LaravelEmailService;
use App\Infraestructure\Services\LaravelPasswordHasher;
use App\Infraestructure\Services\LaravelImageStorageService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /**
         * $this->App es el objeto global del conedor de servicios
         * ->bind(..) es el método que crea el mapeo, recibe dos 
         * parámetros la llave (interfaz) y el valor (clase concreta)
         * ::class es un atajo para no escribir el namespace completo
         */
        $this->app->bind(
            ICategoryRepository::class,
            CategoryRepository::class
        );

     
        $this->app->bind(
            IGetCategoriesUseCase::class,
            GetCategoriesUseCase::class
        );    

        $this->app->bind(
        ISaveCategoryUseCase::class,
        SaveCategoryUseCase::class
        );

        $this->app->bind(
        IUpdateCategoryUseCase::class,
        UpdateCategoryUseCase::class);

        $this->app->bind(
        IDeleteCategoryUseCase::class, 
        DeleteCategoryUseCase::class);

        $this->app->bind(
        IGetCategoryByIdUseCase::class, 
        GetCategoryByIdUseCase::class);

        $this->app->bind(
        IGetCategoriesByParentIdUseCase::class, 
        GetCategoriesByParentIdUseCase::class);

        $this->app->bind(
            IProductRepository::class, 
            ProductRepository::class);


        $this->app->bind(
            ISaveProductUseCase::class, 
            SaveProductUseCase::class);

        $this->app->bind(
            IGetProductByIdUseCase::class,
            GetProductByIdUseCase::class
        );

        $this->app->bind(
            IUpdateProductUseCase::class,
            UpdateProductUseCase::class
        );
        $this->app->bind(
            IDeleteProductUseCase::class,
            DeleteProductUseCase::class
        );
        $this->app->bind(
            IGetAllProductsUseCase::class,
            GetAllProductsUseCase::class
        );
        $this->app->bind(
            IGetProductsByCategoryUseCase::class,
            GetProductsByCategoryUseCase::class
        );

        //AUTH
        $this->app->bind(
            IRegisterUserUseCase::class, 
            RegisterUserUseCase::class
        );

        $this->app->bind(
            IUserRepository::class, 
            UserRepository::class
        );

        $this->app->bind(
            IPasswordHasher::class,
            LaravelPasswordHasher::class
        );
        
        $this->app->bind(
            IEmailService::class,
            LaravelEmailService::class
        );

        $this->app->bind(
            ILoginUserUseCase::class,
            LoginUserUseCase::class
        );

        $this->app->bind(
            IGetProfileUseCase::class,
            GetProfileUseCase::class
        );

        $this->app->bind(
            IVerifyEmailUseCase::class, 
            VerifyEmailUseCase::class
        );

        $this->app->bind(
            IResendVerificationCodeUseCase::class, 
            ResendVerificationCodeUseCase::class
        );

        $this->app->bind(
            IForgotPasswordUseCase::class,
            ForgotPasswordUseCase::class
        );

        $this->app->bind(
            IResetPasswordUseCase::class,
            ResetPasswordUseCase::class
        );

        
        $this->app->bind(IImageStorageService::class, 
        LaravelImageStorageService::class
        );
        
        $this->app->bind(IProductRepository::class, 
        ProductRepository::class);

            /**
        * Para el carrito de compras
        */
        $this->app->bind(ICartRepository::class, 
        CartRepository::class);
        $this->app->bind(IGetCartUseCase::class, 
        GetCartUseCase::class);
        $this->app->bind(IAddToCartUseCase::class, 
        AddToCartUseCase::class);
        $this->app->bind(IRemoveFromCartUseCase::class, 
        RemovedFromCartUseCase::class);


        /**Para las direcciones */
        $this->app->bind(IAddressRepository::class, 
        AddressRepository::class);
        $this->app->bind(IListAddressesUseCase::class, 
        ListAddressesUseCase::class);
        $this->app->bind(ICreateAddressUseCase::class, 
        CreateAddressUseCase::class);
        $this->app->bind(IUpdateAddressUseCase::class, 
        UpdateAddressUseCase::class);
        $this->app->bind(IDeleteAddressUseCase::class, 
        DeleteAddressUseCase::class);
        $this->app->bind(ISetDefaultAddressUseCase::class, 
        SetDefaultAddressUseCase::class);

        /**Para las ordenes */
        $this->app->bind(IOrderRepository::class, 
        OrderRepository::class);
        $this->app->bind(IOrderDetailRepository::class, 
        OrderDetailRepository::class);
        $this->app->bind(ICheckoutUseCase::class, 
        CheckoutUseCase::class);
        $this->app->bind(IListMyOrdersUseCase::class, 
        ListMyOrdersUseCase::class);
        $this->app->bind(IUpdateOrderStatusUseCase::class, 
        UpdateOrderStatusUseCase::class);
        $this->app->bind(IListAllOrdersUseCase::class, 
        ListAllOrdersUseCase::class);

        /**Para los banners */
        $this->app->bind(IBannerGroupRepository::class, 
        BannerGroupRepository::class);
        $this->app->bind(IListAllBannerGroupsUseCase::class, 
        ListAllBannerGroupsUseCase::class);
        $this->app->bind(IGetActiveBannerGroupUseCase::class, 
        GetActiveBannerGroupUseCase::class);
        $this->app->bind(ICreateBannerGroupUseCase::class, 
        CreateBannerGroupUseCase::class);
        $this->app->bind(IAddMediaToGroupUseCase::class, 
        AddMediaToGroupUseCase::class);
        $this->app->bind(IRemoveMediaFromGroupUseCase::class, 
        RemoveMediaFromGroupUseCase::class);
        $this->app->bind(IRenameBannerGroupUseCase::class, 
        RenameBannerGroupUseCase::class);
        $this->app->bind(IDeleteBannerGroupUseCase::class, 
        DeleteBannerGroupUseCase::class);
        $this->app->bind(IToggleBannerGroupActiveUseCase::class, 
        ToggleBannerGroupActiveUseCase::class);

    }




    /**
     * Bootstrap any application services.
     * Método que se ejecuta después de que todos los services
     * providers de la aplicaciónhan sido registrados, se usa para arrancar
     * herramientas que necesiten que las dependnecias ya estén listas
     * Actualmente no necesitamos tener alguna configuracion aqui
     */
    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

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
use App\Application\Abstractions\Product\IDeleteProductUseCase;
use App\Application\Abstractions\Product\IGetAllProductsUseCase;
use App\Application\Abstractions\Product\IGetProductByIdUseCase;
use App\Application\Abstractions\Product\IGetProductsByCategoryUseCase;
use App\Application\Abstractions\Product\ISaveProductUseCase;
use App\Application\Abstractions\Product\IUpdateProductUseCase;
use App\Application\Abstractions\User\IForgotPasswordUseCase;
use App\Application\Abstractions\User\IGetProfileUseCase;
use App\Application\Abstractions\User\ILoginUserUseCase;
use App\Application\Abstractions\User\IRegisterUserUseCase;
use App\Application\Abstractions\User\IResendVerificationCodeUseCase;
use App\Application\Abstractions\User\IResetPasswordUseCase;
use App\Application\Abstractions\User\IVerifyEmailUseCase;
use App\Application\UseCases\Category\DeleteCategoryUseCase;
//Importamos los casos de uso y los repositorios
use App\Data\Repositories\CategoryRepository; 
use App\Application\UseCases\Category\GetCategoriesUseCase;
use App\Application\UseCases\Category\GetCategoryByIdUseCase;
use App\Application\UseCases\Category\SaveCategoryUseCase;
use App\Application\UseCases\Category\UpdateCategoryUseCase;
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
use App\Data\Repositories\ProductRepository;
use App\Data\Repositories\UserRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Abstractions\User\IEmailService;
use App\Domain\Abstractions\User\IPasswordHasher;
use App\Domain\Abstractions\User\IUserRepository;
use App\Http\Controllers\AuthController\GetProfileController;
use App\Infraestructure\Services\LaravelEmailService;
use App\Infraestructure\Services\LaravelPasswordHasher;

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

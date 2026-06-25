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
use App\Application\UseCases\Category\DeleteCategoryUseCase;
//Importamos los casos de uso y los repositorios
use App\Data\Repositories\CategoryRepository; 
use App\Application\UseCases\Category\GetCategoriesUseCase;
use App\Application\UseCases\Category\GetCategoryByIdUseCase;
use App\Application\UseCases\Category\SaveCategoryUseCase;
use App\Application\UseCases\Category\UpdateCategoryUseCase;

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

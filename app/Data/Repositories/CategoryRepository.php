<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\ICategoryRepository;
use App\Domain\Entities\Category as DomainCategory; // Alias para la entidad
use App\Models\Category as EloquentCategory; // Alias para el ORM de Laravel

class CategoryRepository implements ICategoryRepository
{
    /**
     * @return DomainCategory[]
     */
    public function getAll(): array
    {
        //Consulta a la DB, internamente select *from categories
        $models = EloquentCategory::all();
        //Inicializamos un arreglo vacio
        $domainCategories = [];
        
        foreach ($models as $model) {
            //transformamos en entidades todo lo que nos dio la db y lo ingresamos en el arreglo
            $domainCategories[] = $this->toDomainEntity($model);
        }
        
        return $domainCategories;
    }

    public function findById(int $id): ?DomainCategory
    {
       
        $model = EloquentCategory::find($id);
        
        if (!$model) {
            return null;
        }
        
        return $this->toDomainEntity($model);
    }

    public function save(DomainCategory $category): DomainCategory
    {
        $model = EloquentCategory::updateOrCreate(
            //primero arreglo es la condicion de busqueda
            ['id' => $category->getId()], 
            //Dependiendo del resultado crea o actualiza los datos
            [
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'parent_category_id' => $category->getParentCategoryId(), 
                'slug' => $category->getSlug(),
                'is_active' => $category->isActive()
            ]
        );

        return $this->toDomainEntity($model);
    }

    /**
     * Funcion para convertir un modelo de la base de datos en una 
     * entidad de dominio Category
     */
    private function toDomainEntity(EloquentCategory $model): DomainCategory
    {
        return new DomainCategory(
            $model->id,
            $model->name,
            $model->description,
            // Importante: Laravel usa snake_case para las propiedades de la BD por defecto
            $model->parent_category_id, 
            $model->slug,
            (bool) $model->is_active // El cast a bool asegura que si MySQL devuelve 1 o 0, pase como true/false
        );
    }

    public function delete(int $id): void
    {
        //destory busca por el Id y lo elimina
        EloquentCategory::destroy($id);
    }

    /**
     * @return DomainCategory[]
     */
    public function findByParentId(?int $parentId): array
    {
        //SELECT * FROM categories WHERE parent_category_id = ?
        $models = EloquentCategory::where('parent_category_id', $parentId)->get();

        $domainCategories = [];
        foreach ($models as $model) {
            $domainCategories[] = $this->toDomainEntity($model);
        }

        return $domainCategories;
    }


}
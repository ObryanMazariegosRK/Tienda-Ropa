<?php

namespace App\Domain\Abstractions;
use App\Domain\Entities\Category;


interface ICategoryRepository{

    /**
     * En PHP no existe "List<Category>", por lo que indicamos en el comentario 
     * (DocBlock) que devolverá un arreglo de objetos Category para el autocompletado.
     * @return Category[]
    */
    
    public function getAll(): array;
    public function findById(int $id): ?Category;
    public function save(Category $category): Category;
    public function delete(int $id):void;
    /**
     * Busca categorías que pertenezcan a un padre específico
     * @return Category[]
     */
    public function findByParentId(?int $parentId): array;
    

}
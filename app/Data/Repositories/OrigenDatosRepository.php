<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IOrigenDatosRepository;
use Illuminate\Support\Facades\DB;

// Lee la base de la tienda (conexión por defecto) para el ETL del data warehouse
class OrigenDatosRepository implements IOrigenDatosRepository
{
    public function lineasDeVenta(): array
    {
        return DB::table('order_items as i')
            ->join('orders as o', 'o.id', '=', 'i.order_id')
            ->leftJoin('auctions as a', 'a.order_id', '=', 'o.id')
            ->whereNotNull('o.confirmed_at')
            ->orderBy('i.id')
            ->get([
                'i.id as item_id', 'o.id as pedido_id', 'o.public_code', 'o.user_id', 'i.product_id',
                'i.quantity', 'i.unit_price', 'i.unit_cost', 'o.confirmed_at', 'o.status', 'o.updated_at',
                DB::raw('CASE WHEN a.id IS NULL THEN 0 ELSE 1 END as es_subasta'),
            ])->all();
    }

    public function productos(array $ids): array
    {
        if (empty($ids)) return [];

        return DB::table('products as p')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('categories as principal', 'principal.id', '=', 'c.parent_category_id')
            ->whereIn('p.id', $ids)
            ->get([
                'p.id', 'p.name', 'p.created_at',
                DB::raw("COALESCE(c.name, 'Sin categoría') as categoria"),
                DB::raw("COALESCE(principal.name, c.name, 'Sin categoría') as categoria_principal"),
            ])->keyBy('id')->all();
    }

    public function usuarios(array $ids): array
    {
        if (empty($ids)) return [];

        return DB::table('users')->whereIn('id', $ids)->get(['id', 'created_at', 'provider'])->keyBy('id')->all();
    }

    public function subastasConResultado(): array
    {
        $pujas = DB::table('auction_bids')
            ->select('auction_id', DB::raw('COUNT(*) as total_pujas'), DB::raw('COUNT(DISTINCT user_id) as postores'))
            ->groupBy('auction_id');

        // Con ganador pero sin pedido = aún puede confirmarlo o pasar al siguiente postor: se espera
        return DB::table('auctions as a')
            ->leftJoinSub($pujas, 'b', 'b.auction_id', '=', 'a.id')
            ->where(fn($q) => $q->where('a.status', 'cancelled')
                ->orWhere(fn($q) => $q->where('a.status', 'finished')
                    ->where(fn($q) => $q->whereNotNull('a.order_id')->orWhereNull('a.winner_user_id'))))
            ->orderBy('a.id')
            ->get([
                'a.id', 'a.product_id', 'a.starting_price', 'a.current_price', 'a.end_date', 'a.status', 'a.order_id',
                DB::raw('COALESCE(b.total_pujas, 0) as total_pujas'), DB::raw('COALESCE(b.postores, 0) as postores'),
            ])->all();
    }
}

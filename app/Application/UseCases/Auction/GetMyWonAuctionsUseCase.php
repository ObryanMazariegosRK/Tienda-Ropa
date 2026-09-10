<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\IGetMyWonAuctionsUseCase;
use App\Application\DTOs\Auction\WonAuctionDTO;
use App\Models\AuctionModel;

class GetMyWonAuctionsUseCase implements IGetMyWonAuctionsUseCase
{
    public function execute(int $userId): array
    {
        $ganadas = AuctionModel::with('product.images')
            ->where('winner_user_id', $userId)
            ->whereNull('order_id')
            ->get();

        return $ganadas->map(function ($auction) {
            $product = $auction->product;
            $imagen = ($product && $product->images->isNotEmpty()) ? $product->images->first()->image_url : null;

            return new WonAuctionDTO(
                auctionId: $auction->id,
                productId: $auction->product_id,
                productName: $product->name ?? 'Producto',
                productImage: $imagen,
                finalPrice: (float) $auction->current_price
            );
        })->toArray();
    }
}
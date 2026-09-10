<?php
namespace App\Application\UseCases\Auction;

use App\Application\Abstractions\Auction\ICheckoutWonAuctionUseCase;
use App\Application\DTOs\Order\CheckoutResultDTO;
use App\Application\DTOs\Order\OrderDTO;
use App\Application\DTOs\Order\OrderDetailDTO;
use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Abstractions\IAddressRepository;
use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Abstractions\IOrderDetailRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Entities\Order;
use App\Domain\Entities\OrderDetail;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CheckoutWonAuctionUseCase implements ICheckoutWonAuctionUseCase
{
    private const WHATSAPP_NUMBER = '50236666075';

    public function __construct(
        private IAuctionRepository $auctionRepository,
        private IAddressRepository $addressRepository,
        private IOrderRepository $orderRepository,
        private IOrderDetailRepository $orderDetailRepository,
        private IProductRepository $productRepository
    ) {}

    public function execute(int $auctionId, int $userId, int $addressId): CheckoutResultDTO
    {
        $auction = $this->auctionRepository->findById($auctionId);
        if (!$auction) {
            throw new InvalidArgumentException('Subasta no encontrada.');
        }

        if ($auction->getWinnerUserId() !== $userId) {
            throw new InvalidArgumentException('No eres el ganador de esta subasta.');
        }

        if ($auction->hasBeenOrdered()) {
            throw new InvalidArgumentException('Ya generaste el pedido de esta subasta anteriormente.');
        }

        $address = $this->addressRepository->findByIdAndUser($addressId, $userId);
        if (!$address) {
            throw new InvalidArgumentException('La dirección seleccionada no es válida.');
        }

        $product = $this->productRepository->findById($auction->getProductId());

        $order = DB::transaction(function () use ($userId, $address, $auction) {
            $newOrder = new Order(
                id: null,
                userId: $userId,
                addressId: $address->getId(),
                shippingAddress: $address->getAddressLine(),
                total: $auction->getCurrentPrice()
            );

            $createdOrder = $this->orderRepository->create($newOrder);

            $this->orderDetailRepository->createMany([
                new OrderDetail(
                    id: null,
                    orderId: $createdOrder->getId(),
                    productId: $auction->getProductId(),
                    unitPrice: $auction->getCurrentPrice(),
                    quantity: 1
                )
            ]);

            $auction->markAsOrdered($createdOrder->getId());
            $this->auctionRepository->update($auction);

            return $createdOrder;
        });

        $firstImage = $product->getImages()[0] ?? null;
        $productImageUrl = $firstImage ? $firstImage->getImageUrl() : null;

        $orderDTO = new OrderDTO(
            id: $order->getId(),
            status: $order->getStatus()->value,
            total: $order->getTotal(),
            shippingAddress: $order->getShippingAddress(),
            createdAt: $order->getCreatedAt()->format('Y-m-d H:i:s'),
            items: [new OrderDetailDTO(
                productId: $auction->getProductId(),
                productName: $product->getName(),
                productImage: $productImageUrl,
                quantity: 1,
                unitPrice: $auction->getCurrentPrice(),
                subtotal: $auction->getCurrentPrice()
            )]
        );

        $mensaje = "¡Hola! Gané la subasta de *{$product->getName()}* por *Q" . number_format($auction->getCurrentPrice(), 2) . "*.\n\n" .
                   "Mi pedido es el *#{$order->getId()}*.\n\n" .
                   "Dirección de envío: {$order->getShippingAddress()}";

        return new CheckoutResultDTO(
            order: $orderDTO,
            whatsappUrl: 'https://wa.me/' . self::WHATSAPP_NUMBER . '?text=' . rawurlencode($mensaje)
        );
    }
}
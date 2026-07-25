<?php

namespace App\Application\UseCases\Order;

use App\Application\Abstractions\Order\ICheckoutUseCase;
use App\Application\DTOs\Order\CheckoutResultDTO;
use App\Application\DTOs\Order\OrderDTO;
use App\Application\DTOs\Order\OrderDetailDTO;
use App\Domain\Abstractions\ICartRepository;
use App\Domain\Abstractions\IAddressRepository;
use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Abstractions\IOrderDetailRepository;
use App\Domain\Entities\Order;
use App\Domain\Entities\OrderDetail;
use Illuminate\Support\Facades\DB;
use Exception;

class CheckoutUseCase implements ICheckoutUseCase
{
    // Número de WhatsApp del negocio, en formato internacional sin "+" ni espacios
    private const WHATSAPP_NUMBER = '50236666075';

    public function __construct(
        private ICartRepository $cartRepository,
        private IAddressRepository $addressRepository,
        private IOrderRepository $orderRepository,
        private IOrderDetailRepository $orderDetailRepository
    ) {}

    public function execute(int $userId, int $addressId): CheckoutResultDTO
    {
        $address = $this->addressRepository->findByIdAndUser($addressId, $userId);
        if (!$address) {
            throw new Exception('La dirección seleccionada no es válida.');
        }

        $cartRows = $this->cartRepository->findByUserIdWithProductInfo($userId);
        if (empty($cartRows)) {
            throw new Exception('Tu carrito está vacío.');
        }

        $total = array_sum(array_map(fn($row) => $row['cartItem']->getPriceSnapshot(), $cartRows));

        // Todo en una transacción: si algo falla, no queremos una orden a medias
        $order = DB::transaction(function () use ($userId, $address, $total, $cartRows) {
            $newOrder = new Order(
                id: null,
                userId: $userId,
                addressId: $address->getId(),
                shippingAddress: $address->getAddressLine(),
                total: $total
            );

            $createdOrder = $this->orderRepository->create($newOrder);

            $details = array_map(function ($row) use ($createdOrder) {
                return new OrderDetail(
                    id: null,
                    orderId: $createdOrder->getId(),
                    productId: $row['cartItem']->getProductId(),
                    unitPrice: $row['cartItem']->getPriceSnapshot(),
                    quantity: 1
                );
            }, $cartRows);

            $this->orderDetailRepository->createMany($details);
            $this->cartRepository->clearByUserId($userId);

            return $createdOrder;
        });

        // Armamos los items del DTO reutilizando lo que ya teníamos del carrito
        // (evita otra consulta a la BD justo después de crearlos)
        $itemDTOs = array_map(function ($row) {
            return new OrderDetailDTO(
                productId: $row['cartItem']->getProductId(),
                productName: $row['productName'],
                productImage: $row['productImage'],
                quantity: 1,
                unitPrice: $row['cartItem']->getPriceSnapshot(),
                subtotal: $row['cartItem']->getPriceSnapshot()
            );
        }, $cartRows);

        $orderDTO = new OrderDTO(
            id: $order->getId(),
            status: $order->getStatus()->value,
            total: $order->getTotal(),
            shippingAddress: $order->getShippingAddress(),
            createdAt: $order->getCreatedAt()->format('Y-m-d H:i:s'),
            items: $itemDTOs
        );

        return new CheckoutResultDTO(
            order: $orderDTO,
            whatsappUrl: $this->buildWhatsappUrl($orderDTO)
        );
    }

    private function buildWhatsappUrl(OrderDTO $order): string
    {
        $lineas = ["¡Hola! Quiero confirmar mi pedido *#{$order->id}*:", ""];

        foreach ($order->items as $item) {
            $lineas[] = "• {$item->productName} — Q" . number_format($item->unitPrice, 2);
        }

        $lineas[] = "";
        $lineas[] = "*Total: Q" . number_format($order->total, 2) . "*";
        $lineas[] = "";
        $lineas[] = "Dirección de envío: {$order->shippingAddress}";

        $mensaje = implode("\n", $lineas);

        return 'https://wa.me/' . self::WHATSAPP_NUMBER . '?text=' . rawurlencode($mensaje);
    }
}
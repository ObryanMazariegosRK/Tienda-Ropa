<?php

namespace App\Application\UseCases\Order;

use App\Application\Abstractions\Order\ICheckoutUseCase;
use App\Application\DTOs\Order\CheckoutResultDTO;
use App\Application\DTOs\Order\OrderDTO;
use App\Application\DTOs\Order\OrderDetailDTO;
use App\Domain\Abstractions\ICartRepository;
use App\Domain\Abstractions\IAddressRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Abstractions\IOrderDetailRepository;
use App\Domain\Abstractions\IPushNotifier;
use App\Domain\Enum\ProductStatus;
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
        private IOrderDetailRepository $orderDetailRepository,
        private IProductRepository $productRepository,
        private \App\Domain\Abstractions\User\IUserRepository $userRepository,
        private IPushNotifier $pushNotifier,
    ) {}

    public function execute(int $userId, int $addressId, ?string $phone = null): CheckoutResultDTO
    {
        $user = $this->userRepository->findUserById($userId);
        if (!$user) {
            throw new Exception('Usuario no encontrado.');
        }

        // Teléfono de contacto para ESTE pedido:
        // - Si el usuario ya tiene uno, usamos ese (no lo dejamos cambiar aquí).
        // - Si no tiene (usuarios de Google), es obligatorio que venga del checkout,
        //   y lo guardamos en su perfil para no volver a pedirlo.
        $contactPhone = $user->getPhone();
        if ($contactPhone === null) {
            if (empty($phone)) {
                throw new Exception('Necesitamos un teléfono de contacto para coordinar la entrega.');
            }
            $contactPhone = $phone;
            $this->userRepository->updatePhone($userId, $phone);
        }


        $address = $this->addressRepository->findByIdAndUser($addressId, $userId);
        if (!$address) {
            throw new Exception('La dirección seleccionada no es válida.');
        }

        $cartRows = $this->cartRepository->findByUserIdWithProductInfo($userId);
        if (empty($cartRows)) {
            throw new Exception('Tu carrito está vacío.');
        }

        $total = array_sum(array_map(fn($row) => $row['cartItem']->getPriceSnapshot(), $cartRows));

        foreach ($cartRows as $row) {
            $product = $this->productRepository->findById($row['cartItem']->getProductId());
            if (!$product || $product->getStatus()->value !== ProductStatus::AVAILABLE->value) {
                throw new \InvalidArgumentException(
                    "El producto \"{$row['productName']}\" ya no está disponible. Por favor elimínalo de tu carrito."
                );
            }
        }

        // Todo en una transacción: si algo falla, no queremos una orden a medias
        $order = DB::transaction(function () use ($userId, $address, $total, $cartRows, $contactPhone) {
       
            $newOrder = new Order(
                id: null,
                userId: $userId,
                addressId: $address->getId(),
                shippingAddress: $address->getAddressLine(),
                total: $total,
                contactPhone: $contactPhone
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

            foreach ($cartRows as $row) {
                $this->productRepository->updateStatus(
                    $row['cartItem']->getProductId(),
                    ProductStatus::RESERVED->value
                );
            }


            $this->orderDetailRepository->createMany($details);
            $this->cartRepository->clearByUserId($userId);

            return $createdOrder;
        });

        $this->pushNotifier->notificarNuevoPedido($order->getPublicCode(), $total);

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
            items: $itemDTOs,
            confirmedAt: $order->getConfirmedAt()?->format('Y-m-d H:i:s'),
            publicCode: $order->getPublicCode(),
        );

        return new CheckoutResultDTO(
            order: $orderDTO,
            whatsappUrl: $this->buildWhatsappUrl($orderDTO)
        );
    }

    private function buildWhatsappUrl(OrderDTO $order): string
    {
        $lineas = ["¡Hola! Quiero confirmar mi pedido *{$order->publicCode}*:", ""];

        foreach ($order->items as $item) {
            $lineas[] = "• {$item->productName} — Q" . number_format($item->unitPrice, 2);
        }

        $lineas[] = "";
        $lineas[] = "*Total: Q" . number_format($order->total, 2) . "*";
        $lineas[] = "";
        $lineas[] = "Dirección de envío: {$order->shippingAddress}";

        //Link 
        $baseUrl = rtrim(config('app.url'), '/');
        $lineas[] = "";
        $lineas[] = "Ver pedido: {$baseUrl}/pedido/{$order->publicCode}";

        $mensaje = implode("\n", $lineas);

        return 'https://wa.me/' . self::WHATSAPP_NUMBER . '?text=' . rawurlencode($mensaje);
    }
}
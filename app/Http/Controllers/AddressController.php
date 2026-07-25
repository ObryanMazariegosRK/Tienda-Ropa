<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Address\ICreateAddressUseCase;
use App\Application\Abstractions\Address\IUpdateAddressUseCase;
use App\Application\Abstractions\Address\IListAddressesUseCase;
use App\Application\Abstractions\Address\IDeleteAddressUseCase;
use App\Application\Abstractions\Address\ISetDefaultAddressUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        private IListAddressesUseCase $listAddressesUseCase,
        private ICreateAddressUseCase $createAddressUseCase,
        private IUpdateAddressUseCase $updateAddressUseCase,
        private IDeleteAddressUseCase $deleteAddressUseCase,
        private ISetDefaultAddressUseCase $setDefaultAddressUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        $addresses = $this->listAddressesUseCase->execute($request->user()->id);
        return response()->json(['success' => true, 'data' => $addresses], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'addressLine' => ['required', 'string', 'max:500'],
            'isDefault' => ['nullable', 'boolean'],
        ]);

        try {
            $address = $this->createAddressUseCase->execute(
                $request->user()->id,
                $request->input('label'),
                $request->input('addressLine'),
                (bool) $request->input('isDefault', false)
            );

            return response()->json(['success' => true, 'data' => $address], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'addressLine' => ['required', 'string', 'max:500'],
        ]);

        try {
            $address = $this->updateAddressUseCase->execute(
                $request->user()->id,
                $id,
                $request->input('label'),
                $request->input('addressLine')
            );

            return response()->json(['success' => true, 'data' => $address], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $this->deleteAddressUseCase->execute($request->user()->id, $id);
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function setDefault(Request $request, int $id): JsonResponse
    {
        try {
            $address = $this->setDefaultAddressUseCase->execute($request->user()->id, $id);
            return response()->json(['success' => true, 'data' => $address], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
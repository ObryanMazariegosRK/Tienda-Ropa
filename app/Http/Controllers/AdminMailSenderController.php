<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Mail\IDeleteMailSenderUseCase;
use App\Application\Abstractions\Mail\IListMailSendersUseCase;
use App\Application\Abstractions\Mail\ISaveMailSenderUseCase;
use App\Application\Abstractions\Mail\ISendTestMailUseCase;
use App\Application\Abstractions\Mail\ISetActiveMailSenderUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Panel: cuentas de correo con las que se envían los códigos de verificación y recuperación
class AdminMailSenderController extends Controller
{
    public function __construct(
        private IListMailSendersUseCase $listUseCase,
        private ISaveMailSenderUseCase $saveUseCase,
        private IDeleteMailSenderUseCase $deleteUseCase,
        private ISetActiveMailSenderUseCase $setActiveUseCase,
        private ISendTestMailUseCase $testUseCase
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->listUseCase->execute()]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->guardar($request, null);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->guardar($request, $id);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deleteUseCase->execute($id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function setActive(Request $request): JsonResponse
    {
        $request->validate(['id' => ['nullable', 'integer']]);

        try {
            $this->setActiveUseCase->execute($request->input('id'));
            return response()->json(['success' => true, 'data' => $this->listUseCase->execute()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function test(Request $request, int $id): JsonResponse
    {
        $request->validate(['to' => ['required', 'email', 'max:150']], [
            'to.required' => 'Escribe el correo al que se enviará la prueba.',
            'to.email' => 'El correo de destino no es válido.',
        ]);

        try {
            $this->testUseCase->execute($id, $request->input('to'));
            return response()->json(['success' => true, 'data' => $this->listUseCase->execute()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function guardar(Request $request, ?int $id): JsonResponse
    {
        $request->validate([
            'fromName' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'password' => [$id === null ? 'required' : 'nullable', 'string', 'max:255'],
            'host' => ['nullable', 'string', 'max:150'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
        ], [
            'fromName.required' => 'Escribe el nombre que verán los clientes.',
            'email.required' => 'Escribe el correo de la cuenta.',
            'email.email' => 'El correo no es válido.',
            'password.required' => 'Escribe la contraseña de aplicación de la cuenta.',
            'port.between' => 'El puerto debe estar entre 1 y 65535.',
        ]);

        try {
            $sender = $this->saveUseCase->execute(
                $id,
                $request->input('fromName'),
                $request->input('email'),
                $request->input('password'),
                $request->input('host') ?: 'smtp.gmail.com',
                (int) ($request->input('port') ?: 587)
            );
            return response()->json(['success' => true, 'data' => $sender], $id === null ? 201 : 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Banner\IListAllBannerGroupsUseCase;
use App\Application\Abstractions\Banner\ICreateBannerGroupUseCase;
use App\Application\Abstractions\Banner\IAddMediaToGroupUseCase;
use App\Application\Abstractions\Banner\IRemoveMediaFromGroupUseCase;
use App\Application\Abstractions\Banner\IRenameBannerGroupUseCase;
use App\Application\Abstractions\Banner\IDeleteBannerGroupUseCase;
use App\Application\Abstractions\Banner\IToggleBannerGroupActiveUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminBannerController extends Controller
{
    public function __construct(
        private IListAllBannerGroupsUseCase $listAll,
        private ICreateBannerGroupUseCase $create,
        private IAddMediaToGroupUseCase $addMediaUseCase,
        private IRemoveMediaFromGroupUseCase $removeMediaUseCase,
        private IRenameBannerGroupUseCase $renameUseCase,
        private IDeleteBannerGroupUseCase $deleteUseCase,
        private IToggleBannerGroupActiveUseCase $toggleUseCase
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->listAll->execute()], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'media' => ['required', 'array', 'min:1'],
            'media.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm', 'max:15360'],
        ]);

        try {
            $group = $this->create->execute($request->input('name'), $request->file('media'));
            return response()->json(['success' => true, 'data' => $group], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function addMedia(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'media' => ['required', 'array', 'min:1'],
            'media.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm', 'max:15360'],
        ]);

        try {
            $group = $this->addMediaUseCase->execute($id, $request->file('media'));
            return response()->json(['success' => true, 'data' => $group], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function removeMedia(int $groupId, int $mediaId): JsonResponse
    {
        try {
            $group = $this->removeMediaUseCase->execute($groupId, $mediaId);
            return response()->json(['success' => true, 'data' => $group], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rename(Request $request, int $id): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:150']]);

        try {
            $group = $this->renameUseCase->execute($id, $request->input('name'));
            return response()->json(['success' => true, 'data' => $group], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deleteUseCase->execute($id);
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function toggleActive(int $id): JsonResponse
    {
        try {
            $groups = $this->toggleUseCase->execute($id);
            return response()->json(['success' => true, 'data' => $groups], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
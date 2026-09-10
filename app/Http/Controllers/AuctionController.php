<?php
namespace App\Http\Controllers;

use App\Application\Abstractions\Auction\ICheckoutWonAuctionUseCase;
use App\Application\Abstractions\Auction\IDeclineAuctionWinUseCase;
use App\Application\Abstractions\Auction\IExtendAuctionDurationUseCase;
use App\Application\Abstractions\Auction\IGetAuctionStatusUseCase;
use App\Application\Abstractions\Auction\IGetMyActiveBidsCountUseCase;
use App\Application\Abstractions\Auction\IGetMyAuctionBidStatusUseCase;
use App\Application\Abstractions\Auction\IGetMyWonAuctionsUseCase;
use App\Application\Abstractions\Auction\IPlaceBidUseCase;
use App\Application\Abstractions\Auction\IListAuctionBidsUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuctionController extends Controller
{
    public function __construct(
        private IGetAuctionStatusUseCase $getStatus,
        private IPlaceBidUseCase $placeBid,
        private IListAuctionBidsUseCase $listBids,
        private IGetMyWonAuctionsUseCase $getMyWonAuctions,
        private ICheckoutWonAuctionUseCase $checkoutWonAuction,
        private IExtendAuctionDurationUseCase $extendDuration,
        private IDeclineAuctionWinUseCase $declineAuctionWin,
        private IGetMyActiveBidsCountUseCase $getMyActiveBidsCount,
        private IGetMyAuctionBidStatusUseCase $getMyAuctionBidStatus

        ) {}

    // Este es el endpoint de polling — liviano a propósito
    public function status(int $productId): JsonResponse
    {
        $auction = $this->getStatus->execute($productId);
        return response()->json(['success' => true, 'data' => $auction], 200);
    }

    public function bid(Request $request, int $productId): JsonResponse
    {
        $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);

        try {
            $auction = $this->placeBid->execute($productId, $request->user()->id, (float) $request->input('amount'));
            return response()->json(['success' => true, 'data' => $auction], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function bidsHistory(int $productId): JsonResponse
    {
        $bids = $this->listBids->execute($productId);
        return response()->json(['success' => true, 'data' => $bids], 200);
    }

    public function myWins(Request $request): JsonResponse
    {
        $wins = $this->getMyWonAuctions->execute($request->user()->id);
        return response()->json(['success' => true, 'data' => $wins], 200);
    }

    public function checkoutWin(Request $request, int $auctionId): JsonResponse
    {
        $request->validate(['addressId' => ['required', 'integer']]);

        try {
            $result = $this->checkoutWonAuction->execute($auctionId, $request->user()->id, $request->input('addressId'));
            return response()->json(['success' => true, 'data' => $result], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function extendDuration(Request $request, int $auctionId): JsonResponse
    {
        $request->validate(['newEndDate' => ['required', 'date']]);

        try {
            $auction = $this->extendDuration->execute($auctionId, $request->input('newEndDate'));
            return response()->json(['success' => true, 'data' => $auction], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function decline(Request $request, int $auctionId): JsonResponse
    {
        try {
            $this->declineAuctionWin->execute($auctionId, $request->user()->id);
            return response()->json(['success' => true], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function myActiveBidsCount(Request $request): JsonResponse
    {
        $count = $this->getMyActiveBidsCount->execute($request->user()->id);
        return response()->json(['success' => true, 'data' => ['count' => $count]], 200);
    }

    public function myBidStatus(Request $request): JsonResponse
    {
        $status = $this->getMyAuctionBidStatus->execute($request->user()->id);
        return response()->json(['success' => true, 'data' => $status], 200);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransNotificationController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans): JsonResponse
    {
        $data = $request->all();

        if (! $midtrans->tandaTanganValid($data)) {
            Log::warning('Notifikasi Midtrans ditolak: tanda tangan tidak valid', ['order_id' => $data['order_id'] ?? null]);

            return response()->json(['message' => 'Tanda tangan tidak valid'], 403);
        }

        $pembayaran = Payment::where('order_id', $data['order_id'])->first();

        if (! $pembayaran) {
            return response()->json(['message' => 'Order tidak ditemukan'], 404);
        }

        $pembayaran->perbaruiDariMidtrans($data);

        return response()->json(['message' => 'OK']);
    }
}
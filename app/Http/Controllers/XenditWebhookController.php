<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Xendit\XenditClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    /**
     * Handle an incoming Xendit invoice callback.
     */
    public function __invoke(Request $request, XenditClient $xendit): JsonResponse
    {
        abort_unless($xendit->verifyCallbackToken($request->header('x-callback-token')), 403);

        $payload = $request->all();

        $payment = Payment::query()->where('external_id', $payload['external_id'] ?? null)->first();

        if (! $payment) {
            Log::warning('Xendit webhook: unknown external_id', ['payload' => $payload]);

            return response()->json(['received' => true]);
        }

        DB::transaction(function () use ($payment, $payload): void {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            $newStatus = PaymentStatus::fromXenditStatus($payload['status'] ?? '');

            if ($payment->status === $newStatus) {
                return;
            }

            $payment->update([
                'status' => $newStatus,
                'xendit_invoice_id' => $payload['id'] ?? $payment->xendit_invoice_id,
                'payment_method' => $payload['payment_method'] ?? $payload['payment_channel'] ?? $payment->payment_method,
                'paid_at' => $newStatus === PaymentStatus::Paid ? now() : $payment->paid_at,
                'raw_payload' => $payload,
            ]);

            if ($newStatus === PaymentStatus::Paid) {
                $payment->booking->update(['status' => BookingStatus::Confirmed]);
            }
        });

        return response()->json(['received' => true]);
    }
}

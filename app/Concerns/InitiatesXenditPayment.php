<?php

namespace App\Concerns;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Xendit\XenditClient;
use App\Services\Xendit\XenditRequestException;
use Flux\Flux;
use Illuminate\Support\Facades\URL;

trait InitiatesXenditPayment
{
    /**
     * Create a pending payment attempt for the booking and redirect to Xendit to collect payment.
     */
    protected function initiatePayment(Booking $booking): void
    {
        $payment = $this->createPendingPayment($booking);

        try {
            $invoice = app(XenditClient::class)->createInvoice(
                $booking,
                $payment,
                successRedirectUrl: URL::signedRoute('payments.success', ['booking' => $booking->booking_number], now()->addDay()),
                failureRedirectUrl: URL::signedRoute('payments.failed', ['booking' => $booking->booking_number], now()->addDay()),
            );
        } catch (XenditRequestException $e) {
            report($e);
            $payment->update(['status' => PaymentStatus::Failed]);
            Flux::toast(variant: 'danger', text: __('Tidak dapat memulakan pembayaran. Sila cuba lagi.'));

            return;
        }

        $payment->update([
            'xendit_invoice_id' => $invoice['id'],
            'invoice_url' => $invoice['invoice_url'],
            'expiry_date' => $invoice['expiry_date'] ?? null,
        ]);

        $this->redirect($invoice['invoice_url'], navigate: false);
    }

    /**
     * Create a new pending payment attempt for the booking with a unique external id.
     */
    protected function createPendingPayment(Booking $booking): Payment
    {
        $attempt = $booking->payments()->count() + 1;

        return Payment::create([
            'booking_id' => $booking->id,
            'external_id' => "{$booking->booking_number}-P{$attempt}",
            'status' => PaymentStatus::Pending,
            'amount' => $booking->total_price,
            'currency' => config('services.xendit.currency'),
        ]);
    }
}

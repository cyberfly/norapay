<?php

namespace App\Services\Xendit;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class XenditClient
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $callbackVerificationToken,
        private readonly string $currency,
        private readonly int $invoiceDuration,
    ) {}

    /**
     * Create a Xendit-hosted invoice for the given booking/payment attempt.
     *
     * @return array{id: string, invoice_url: string, status: string, expiry_date?: string}
     *
     * @throws XenditRequestException
     */
    public function createInvoice(Booking $booking, Payment $payment, string $successRedirectUrl, string $failureRedirectUrl): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->acceptJson()
                ->timeout(15)
                ->connectTimeout(5)
                ->post('https://api.xendit.co/v2/invoices', [
                    'external_id' => $payment->external_id,
                    'amount' => (float) $booking->total_price,
                    'currency' => $this->currency,
                    'invoice_duration' => $this->invoiceDuration,
                    'description' => "Payment for booking {$booking->booking_number}",
                    'payer_email' => $booking->customer_email,
                    'success_redirect_url' => $successRedirectUrl,
                    'failure_redirect_url' => $failureRedirectUrl,
                    'customer' => array_filter([
                        'given_names' => $booking->customer_name,
                        'mobile_number' => $booking->customer_phone,
                        'email' => $booking->customer_email,
                    ]),
                    'items' => [[
                        'name' => $booking->product_name,
                        'quantity' => $booking->quantity,
                        'price' => (float) $booking->unit_price,
                        'category' => 'Booking',
                    ]],
                ]);
        } catch (ConnectionException $e) {
            throw XenditRequestException::fromConnectionFailure($e);
        }

        if ($response->failed()) {
            throw XenditRequestException::fromResponse($response);
        }

        return $response->json();
    }

    public function verifyCallbackToken(?string $token): bool
    {
        return $token !== null && hash_equals($this->callbackVerificationToken, $token);
    }
}

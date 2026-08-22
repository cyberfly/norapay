<?php

use App\Models\Booking;
use App\Models\Payment;
use App\Services\Xendit\XenditClient;
use App\Services\Xendit\XenditRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function makeXenditClient(): XenditClient
{
    return new XenditClient(
        secretKey: 'xnd_development_secret',
        callbackVerificationToken: 'test-callback-token',
        currency: 'MYR',
        invoiceDuration: 86400,
    );
}

test('createInvoice sends the expected request to xendit', function () {
    Http::fake([
        'https://api.xendit.co/v2/invoices' => Http::response([
            'id' => 'inv_123',
            'invoice_url' => 'https://checkout-staging.xendit.co/web/inv_123',
            'status' => 'PENDING',
        ]),
    ]);

    $booking = Booking::factory()->create(['total_price' => 199.80, 'customer_email' => 'ali@example.com']);
    $payment = Payment::factory()->for($booking)->create(['external_id' => 'BK-TEST-P1']);

    $result = makeXenditClient()->createInvoice($booking, $payment, 'https://app.test/success', 'https://app.test/failure');

    expect($result['id'])->toBe('inv_123');
    expect($result['invoice_url'])->toBe('https://checkout-staging.xendit.co/web/inv_123');

    Http::assertSent(function ($request) use ($booking) {
        return $request->url() === 'https://api.xendit.co/v2/invoices'
            && $request->hasHeader('Authorization')
            && $request['external_id'] === 'BK-TEST-P1'
            && $request['amount'] === 199.80
            && $request['currency'] === 'MYR'
            && $request['payer_email'] === $booking->customer_email
            && $request['success_redirect_url'] === 'https://app.test/success'
            && $request['failure_redirect_url'] === 'https://app.test/failure';
    });
});

test('createInvoice throws on a failed response', function () {
    Http::fake([
        'https://api.xendit.co/v2/invoices' => Http::response(['message' => 'Invalid request'], 400),
    ]);

    $booking = Booking::factory()->create();
    $payment = Payment::factory()->for($booking)->create();

    expect(fn () => makeXenditClient()->createInvoice($booking, $payment, 'https://app.test/success', 'https://app.test/failure'))
        ->toThrow(XenditRequestException::class);
});

test('createInvoice throws on a connection failure', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    $booking = Booking::factory()->create();
    $payment = Payment::factory()->for($booking)->create();

    expect(fn () => makeXenditClient()->createInvoice($booking, $payment, 'https://app.test/success', 'https://app.test/failure'))
        ->toThrow(XenditRequestException::class);
});

test('verifyCallbackToken validates the token', function () {
    $client = makeXenditClient();

    expect($client->verifyCallbackToken('test-callback-token'))->toBeTrue();
    expect($client->verifyCallbackToken('wrong-token'))->toBeFalse();
    expect($client->verifyCallbackToken(null))->toBeFalse();
});

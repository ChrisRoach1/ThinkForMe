<?php

use App\Models\Purchase;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;

Route::view('/', 'welcome')->name('welcome');


Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::view('create-image', 'create-image')
    ->middleware(['auth', 'verified'])
    ->name('CreateImage');


Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('refund', 'Refund')
    ->name('refund');

Route::get('/checkout', function (Request $request) {
    $stripePriceId = env('STRIPE_PRODUCT_KEY', '');

    $quantity = 1;

    return $request->user()->checkout([$stripePriceId => $quantity], [
        'success_url' => route('checkout-success').'?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => route('dashboard'),
        'metadata' => ['user_id' => auth()->id(), 'product_key' => $stripePriceId],
    ]);
})->middleware(['auth', 'verified'])->name('checkout');


Route::get('/checkout/success', function (Request $request) {
    $sessionId = $request->get('session_id');

    if ($sessionId === null) {
        return;
    }

    $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId);

    if ($session->payment_status !== 'paid') {
        return;
    }

    return view('checkout.success');
})->middleware(['auth', 'verified'])->name('checkout-success');

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\Api\Admin\V1\ClubController as AdminClubController;
use App\Http\Controllers\Api\Admin\V1\DashboardController;
use App\Http\Controllers\Api\Admin\V1\GeographyController;
use App\Http\Controllers\Api\Admin\V1\SettlementController;
use App\Http\Controllers\Api\Club\V1\BookingController as ClubBookingController;
use App\Http\Controllers\Api\Club\V1\StaffController;
use App\Http\Controllers\Api\Club\V1\VenueController as ClubVenueController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\VenueController;
use App\Http\Controllers\Api\V1\WaitlistController;
use App\Http\Controllers\Webhooks\FatoraWebhookController;
use App\Http\Controllers\Webhooks\MtnWebhookController;
use App\Http\Controllers\Webhooks\SamaPayWebhookController;
use App\Http\Controllers\Webhooks\SyriatelWebhookController;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API — V1
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    // Auth — public
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('otp/send', [AuthController::class, 'sendOtp']);
        Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
        Route::post('google', [AuthController::class, 'googleSignIn']);
    });

    // Venues — public
    Route::get('venues', [VenueController::class, 'index']);
    Route::get('venues/search', [VenueController::class, 'search']);
    Route::get('venues/{venue}', [VenueController::class, 'show']);
    Route::get('venues/{venue}/slots', [VenueController::class, 'availableSlots']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Profile
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::put('profile/notifications', [ProfileController::class, 'updateNotifications']);

        // Bookings
        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{booking}', [BookingController::class, 'show']);
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        // Payments
        Route::post('payments/initiate', [PaymentController::class, 'initiate']);
        Route::post('payments/mtn/confirm', [PaymentController::class, 'confirmMtn']);
        Route::post('payments/syriatel/confirm', [PaymentController::class, 'confirmSyriatel']);

        // Reviews
        Route::post('reviews', [ReviewController::class, 'store']);
        Route::put('reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);

        // Waitlist
        Route::get('waitlist', [WaitlistController::class, 'index']);
        Route::post('waitlist', [WaitlistController::class, 'store']);
        Route::delete('waitlist/{waitlist}', [WaitlistController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin API — V1
|--------------------------------------------------------------------------
*/
Route::prefix('admin/v1')->middleware(['auth:sanctum'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index']);

    // Clubs
    Route::get('clubs', [AdminClubController::class, 'index']);
    Route::post('clubs/{club}/approve', [AdminClubController::class, 'approve']);
    Route::post('clubs/{club}/reject', [AdminClubController::class, 'reject']);

    // Settlements
    Route::get('settlements', [SettlementController::class, 'index']);
    Route::post('settlements', [SettlementController::class, 'store']);
    Route::get('settlements/{settlement}', [SettlementController::class, 'show']);

    // Geography
    Route::put('cities/{city}/activate', [GeographyController::class, 'activateCity']);
});

/*
|--------------------------------------------------------------------------
| Club Manager API — V1
|--------------------------------------------------------------------------
*/
Route::prefix('club/v1')->middleware(['auth:sanctum'])->group(function () {

    // Venues
    Route::get('clubs/{club}/venues', [ClubVenueController::class, 'index']);
    Route::post('clubs/{club}/venues', [ClubVenueController::class, 'store']);
    Route::put('clubs/{club}/venues/{venue}', [ClubVenueController::class, 'update']);
    Route::delete('clubs/{club}/venues/{venue}', [ClubVenueController::class, 'destroy']);

    // Staff
    Route::get('clubs/{club}/staff', [StaffController::class, 'index']);
    Route::post('clubs/{club}/staff', [StaffController::class, 'store']);
    Route::delete('clubs/{club}/staff/{userId}', [StaffController::class, 'destroy']);

    // Bookings (read-only for club)
    Route::get('clubs/{club}/bookings', [ClubBookingController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| Payment Webhooks — no auth, signature-verified
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->group(function () {
    Route::post('mtn/callback', [MtnWebhookController::class, 'callback'])->name('webhooks.mtn.callback');
    Route::post('syriatel/callback', [SyriatelWebhookController::class, 'callback'])->name('webhooks.syriatel.callback');
    Route::post('fatora/callback', [FatoraWebhookController::class, 'callback'])->name('webhooks.fatora.callback');
    Route::post('samapay/callback', [SamaPayWebhookController::class, 'callback'])->name('webhooks.samapay.callback');
});

/*
|--------------------------------------------------------------------------
| Health Checks
|--------------------------------------------------------------------------
*/
Route::get('/health/queue', function () {
    try {
        Redis::connection()->ping();

        return response()->json([
            'success' => true,
            'queue' => 'healthy',
            'redis' => 'connected',
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'queue' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 503);
    }
});

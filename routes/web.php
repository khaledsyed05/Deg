<?php

use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\ClubController as AdminClubController;
use App\Http\Controllers\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GeographyController as AdminGeographyController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PlatformWhatsAppController as AdminPlatformWhatsAppController;
use App\Http\Controllers\Admin\PlayerController as AdminPlayerController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\SettlementController as AdminSettlementController;
use App\Http\Controllers\Admin\VenueController as AdminVenueController;
use App\Http\Controllers\Admin\WhatsAppController as AdminWhatsAppController;
use App\Http\Controllers\Club\Auth\ClubLoginController;
use App\Http\Controllers\Club\BookingController as ClubBookingController;
use App\Http\Controllers\Club\DashboardController as ClubDashboardController;
use App\Http\Controllers\Club\FinanceController as ClubFinanceController;
use App\Http\Controllers\Club\PlayerController as ClubPlayerController;
use App\Http\Controllers\Club\PromotionController as ClubPromotionController;
use App\Http\Controllers\Club\ReportController as ClubReportController;
use App\Http\Controllers\Club\ReviewController as ClubReviewController;
use App\Http\Controllers\Club\SettingController as ClubSettingController;
use App\Http\Controllers\Club\SettlementController as ClubSettlementController;
use App\Http\Controllers\Club\VenueAvailabilityController as ClubVenueAvailabilityController;
use App\Http\Controllers\Club\VenueController as ClubVenueController;
use App\Http\Controllers\Marketing\HomeController;
use App\Http\Controllers\Marketing\AboutController;
use App\Http\Controllers\Marketing\LegalController;
use App\Http\Controllers\Marketing\ContactController;
use App\Http\Controllers\Marketing\ForVenuesController;
use Dedoc\Scramble\Scramble;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');
Route::get('/for-venues', [ForVenuesController::class, 'index'])->name('for-venues');

/* Design Variations */
Route::get('/v/bold', fn () => view('marketing.variations.bold'))->name('v.bold');
Route::get('/v/editorial', fn () => view('marketing.variations.editorial'))->name('v.editorial');
Route::get('/v/minimal', fn () => view('marketing.variations.minimal'))->name('v.minimal');
Route::get('/v/stadium', fn () => view('marketing.variations.stadium'))->name('v.stadium');
Route::get('/v', fn () => view('marketing.variations.selector'))->name('v.selector');

Route::post('/locale', function (Request $request) {
    $locale = $request->input('locale', 'ar');
    if (! in_array($locale, ['ar', 'en'])) {
        $locale = 'ar';
    }
    session(['locale' => $locale]);

    return redirect()->back();
})->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Admin Panel (Inertia)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminLoginController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminLoginController::class, 'login']);
    Route::post('logout', [AdminLoginController::class, 'logout'])->name('logout');
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::prefix('clubs')->name('clubs.')->group(function () {
        Route::get('/', [AdminClubController::class, 'index'])->name('index');
        Route::get('{club}', [AdminClubController::class, 'show'])->name('show');
        Route::get('{club}/edit', [AdminClubController::class, 'edit'])->name('edit');
        Route::put('{club}', [AdminClubController::class, 'update'])->name('update');
        Route::post('{club}/approve', [AdminClubController::class, 'approve'])->name('approve');
        Route::post('{club}/reject', [AdminClubController::class, 'reject'])->name('reject');
        Route::post('{club}/suspend', [AdminClubController::class, 'suspend'])->name('suspend');
        Route::post('{club}/unsuspend', [AdminClubController::class, 'unsuspend'])->name('unsuspend');
    });

    Route::prefix('settlements')->name('settlements.')->group(function () {
        Route::get('/', [AdminSettlementController::class, 'index'])->name('index');
        Route::get('datatables', [AdminSettlementController::class, 'datatables'])->name('datatables');
        Route::post('/', [AdminSettlementController::class, 'store'])->name('store');
        Route::get('{settlement}', [AdminSettlementController::class, 'show'])->name('show');
        Route::post('{settlement}/mark-as-paid', [AdminSettlementController::class, 'markAsPaid'])->name('mark-as-paid');
        Route::put('{settlement}/notes', [AdminSettlementController::class, 'updateNotes'])->name('update-notes');
        Route::get('{settlement}/export-excel', [AdminSettlementController::class, 'exportExcel'])->name('export-excel');
        Route::get('{settlement}/export-pdf', [AdminSettlementController::class, 'exportPdf'])->name('export-pdf');
    });

    Route::prefix('geography')->name('geography.')->group(function () {
        Route::get('countries', [AdminGeographyController::class, 'countries'])->name('countries');
        Route::get('countries/create', [AdminGeographyController::class, 'createCountry'])->name('countries.create');
        Route::post('countries', [AdminGeographyController::class, 'storeCountry'])->name('countries.store');
        Route::get('countries/{country}/edit', [AdminGeographyController::class, 'editCountry'])->name('countries.edit');
        Route::put('countries/{country}', [AdminGeographyController::class, 'updateCountry'])->name('countries.update');
        Route::delete('countries/{country}', [AdminGeographyController::class, 'destroyCountry'])->name('countries.destroy');

        Route::get('states', [AdminGeographyController::class, 'states'])->name('states');
        Route::get('states/create', [AdminGeographyController::class, 'createState'])->name('states.create');
        Route::post('states', [AdminGeographyController::class, 'storeState'])->name('states.store');
        Route::get('states/{state}/edit', [AdminGeographyController::class, 'editState'])->name('states.edit');
        Route::put('states/{state}', [AdminGeographyController::class, 'updateState'])->name('states.update');
        Route::delete('states/{state}', [AdminGeographyController::class, 'destroyState'])->name('states.destroy');

        Route::get('cities', [AdminGeographyController::class, 'cities'])->name('cities');
        Route::get('cities/create', [AdminGeographyController::class, 'createCity'])->name('cities.create');
        Route::post('cities', [AdminGeographyController::class, 'storeCity'])->name('cities.store');
        Route::get('cities/{city}/edit', [AdminGeographyController::class, 'editCity'])->name('cities.edit');
        Route::put('cities/{city}', [AdminGeographyController::class, 'updateCity'])->name('cities.update');
        Route::delete('cities/{city}', [AdminGeographyController::class, 'destroyCity'])->name('cities.destroy');
        Route::get('cities/datatables', [AdminGeographyController::class, 'citiesDatatables'])->name('cities.datatables');
        Route::post('cities/{city}/toggle', [AdminGeographyController::class, 'toggleCity'])->name('cities.toggle');
    });

    Route::get('profile', [AdminProfileController::class, 'show'])->name('profile');
    Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');

    Route::resource('venues', AdminVenueController::class);

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [AdminBookingController::class, 'index'])->name('index');
        Route::get('calendar', [AdminBookingController::class, 'calendar'])->name('calendar');
        Route::get('export', [AdminBookingController::class, 'export'])->name('export');
        Route::get('{booking}', [AdminBookingController::class, 'show'])->name('show');
        Route::post('{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('cancel');
        Route::post('{booking}/modify', [AdminBookingController::class, 'modify'])->name('modify');
        Route::post('{booking}/resend-confirmation', [AdminBookingController::class, 'resendConfirmation'])->name('resend-confirmation');
        Route::post('{booking}/send-reminder', [AdminBookingController::class, 'sendReminder'])->name('send-reminder');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('commissions', [AdminCommissionController::class, 'index'])->name('commissions');
        Route::put('commissions/default', [AdminCommissionController::class, 'updateDefaultRate'])->name('commissions.update-default');
        Route::post('commissions/override', [AdminCommissionController::class, 'storeOverride'])->name('commissions.store-override');
        Route::delete('commissions/override/{club}', [AdminCommissionController::class, 'removeOverride'])->name('commissions.remove-override');

        Route::get('system', [AdminSettingsController::class, 'index'])->name('system');
        Route::put('system', [AdminSettingsController::class, 'update'])->name('system.update');
        Route::post('system/maintenance-mode', [AdminSettingsController::class, 'toggleMaintenanceMode'])->name('system.maintenance-mode');
    });

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('revenue', [AdminAnalyticsController::class, 'revenue'])->name('revenue');
        Route::get('revenue/export', [AdminAnalyticsController::class, 'exportRevenue'])->name('revenue.export');
        Route::get('bookings', [AdminAnalyticsController::class, 'bookings'])->name('bookings');
        Route::get('bookings/export', [AdminAnalyticsController::class, 'exportBookings'])->name('bookings.export');
        Route::get('players', [AdminAnalyticsController::class, 'players'])->name('players');
        Route::get('players/export', [AdminAnalyticsController::class, 'exportPlayers'])->name('players.export');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [AdminPaymentController::class, 'index'])->name('index');
        Route::get('failed', [AdminPaymentController::class, 'failedPayments'])->name('failed');
        Route::get('export', [AdminPaymentController::class, 'export'])->name('export');
        Route::post('bulk-retry', [AdminPaymentController::class, 'bulkRetry'])->name('bulk-retry');
        Route::get('{payment}', [AdminPaymentController::class, 'show'])->name('show');
        Route::post('{payment}/retry', [AdminPaymentController::class, 'retry'])->name('retry');
        Route::post('{payment}/refund', [AdminPaymentController::class, 'refund'])->name('refund');
    });

    Route::prefix('players')->name('players.')->group(function () {
        Route::get('/', [AdminPlayerController::class, 'index'])->name('index');
        Route::get('{player}', [AdminPlayerController::class, 'show'])->name('show');
        Route::get('{player}/edit', [AdminPlayerController::class, 'edit'])->name('edit');
        Route::put('{player}', [AdminPlayerController::class, 'update'])->name('update');
        Route::post('{player}/block', [AdminPlayerController::class, 'block'])->name('block');
        Route::post('{player}/unblock', [AdminPlayerController::class, 'unblock'])->name('unblock');
    });

    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::prefix('platform')->name('platform.')->group(function () {
            Route::get('/', [AdminPlatformWhatsAppController::class, 'show'])->name('show');
            Route::post('init', [AdminPlatformWhatsAppController::class, 'initiate'])->name('initiate');
            Route::post('test-send', [AdminPlatformWhatsAppController::class, 'testSend'])->name('test-send');
            Route::delete('/', [AdminPlatformWhatsAppController::class, 'disconnect'])->name('disconnect');
        });
        Route::get('clubs/{club}', [AdminWhatsAppController::class, 'show'])->name('show');
        Route::post('clubs/{club}/init', [AdminWhatsAppController::class, 'initiate'])->name('initiate');
        Route::post('clubs/{club}/test-send', [AdminWhatsAppController::class, 'testSend'])->name('test-send');
        Route::delete('clubs/{club}', [AdminWhatsAppController::class, 'disconnect'])->name('disconnect');
    });
});

/*
|--------------------------------------------------------------------------
| Club Panel (Inertia)
|--------------------------------------------------------------------------
*/
Route::prefix('club')->name('club.')->group(function () {
    Route::get('login', [ClubLoginController::class, 'showLogin'])->name('login');
    Route::post('login', [ClubLoginController::class, 'login']);
    Route::post('logout', [ClubLoginController::class, 'logout'])->name('logout');

    Route::get('forgot-password', [ClubLoginController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('forgot-password', [ClubLoginController::class, 'sendResetOtp']);

    Route::get('reset-password', [ClubLoginController::class, 'showResetPassword'])->name('reset-password');
    Route::post('reset-password', [ClubLoginController::class, 'resetPassword']);
});

Route::prefix('club')->name('club.')->middleware(['auth', 'club'])->group(function () {
    Route::get('/', [ClubDashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard', [ClubDashboardController::class, 'index']);

    Route::prefix('venues')->name('venues.')->group(function () {
        Route::get('/', [ClubVenueController::class, 'index'])->name('index');
        Route::get('create', [ClubVenueController::class, 'create'])->name('create');
        Route::post('/', [ClubVenueController::class, 'store'])->name('store');
        Route::get('{venue}', [ClubVenueController::class, 'show'])->name('show');
        Route::get('{venue}/edit', [ClubVenueController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '{venue}', [ClubVenueController::class, 'update'])->name('update');
        Route::delete('{venue}', [ClubVenueController::class, 'destroy'])->name('destroy');

        Route::get('{venue}/availability', [ClubVenueAvailabilityController::class, 'index'])->name('availability');
        Route::post('{venue}/availability/block', [ClubVenueAvailabilityController::class, 'blockDate'])->name('availability.block');
        Route::delete('{venue}/availability/block/{block}', [ClubVenueAvailabilityController::class, 'unblockDate'])->name('availability.unblock');
        Route::post('{venue}/availability/special-price', [ClubVenueAvailabilityController::class, 'setSpecialPrice'])->name('availability.special-price');
        Route::delete('{venue}/availability/special-price/{special}', [ClubVenueAvailabilityController::class, 'deleteSpecialPrice'])->name('availability.special-price.delete');
    });

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [ClubBookingController::class, 'index'])->name('index');
        Route::get('calendar', [ClubBookingController::class, 'calendar'])->name('calendar');
        Route::get('create', [ClubBookingController::class, 'create'])->name('create');
        Route::post('/', [ClubBookingController::class, 'store'])->name('store');
        Route::get('search-players', [ClubBookingController::class, 'searchPlayers'])->name('search-players');
        Route::post('check-availability', [ClubBookingController::class, 'checkAvailability'])->name('check-availability');
        Route::get('{booking}', [ClubBookingController::class, 'show'])->name('show');
        Route::post('{booking}/cancel', [ClubBookingController::class, 'cancel'])->name('cancel');
        Route::post('{booking}/complete', [ClubBookingController::class, 'complete'])->name('complete');
        Route::post('{booking}/no-show', [ClubBookingController::class, 'markNoShow'])->name('no-show');
    });
    Route::prefix('promotions')->name('promotions.')->group(function () {
        Route::get('/', [ClubPromotionController::class, 'index'])->name('index');
        Route::get('create', [ClubPromotionController::class, 'create'])->name('create');
        Route::get('expiring-soon', [ClubPromotionController::class, 'getExpiringSoon'])->name('expiring-soon');
        Route::post('/', [ClubPromotionController::class, 'store'])->name('store');
        Route::post('bulk', [ClubPromotionController::class, 'bulkCreate'])->name('bulk');
        Route::get('{promotion:slug}', [ClubPromotionController::class, 'show'])->name('show');
        Route::get('{promotion:slug}/edit', [ClubPromotionController::class, 'edit'])->name('edit');
        Route::get('{promotion:slug}/clone', [ClubPromotionController::class, 'clone'])->name('clone');
        Route::put('{promotion:slug}', [ClubPromotionController::class, 'update'])->name('update');
        Route::delete('{promotion:slug}', [ClubPromotionController::class, 'destroy'])->name('destroy');
        Route::post('{promotion:slug}/toggle', [ClubPromotionController::class, 'toggleStatus'])->name('toggle');
    });
    Route::prefix('players')->name('players.')->group(function () {
        Route::get('/', [ClubPlayerController::class, 'index'])->name('index');
        Route::put('notes/{note}', [ClubPlayerController::class, 'updateNote'])->name('notes.update');
        Route::delete('notes/{note}', [ClubPlayerController::class, 'destroyNote'])->name('notes.destroy');
        Route::get('{player}', [ClubPlayerController::class, 'show'])->name('show');
        Route::post('{player}/notes', [ClubPlayerController::class, 'storeNote'])->name('notes.store');
    });
    Route::prefix('finances')->name('finances.')->group(function () {
        Route::get('/', [ClubFinanceController::class, 'index'])->name('index');
        Route::prefix('settlements')->name('settlements.')->group(function () {
            Route::get('/', [ClubSettlementController::class, 'index'])->name('index');
            Route::post('/', [ClubSettlementController::class, 'store'])->name('store');
            Route::get('{settlement}', [ClubSettlementController::class, 'show'])->name('show');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ClubReportController::class, 'index'])->name('index');
            Route::get('export/csv', [ClubReportController::class, 'exportCsv'])->name('export.csv');
            Route::get('export/pdf', [ClubReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel', [ClubReportController::class, 'exportExcel'])->name('export.excel');
        });
    });
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [ClubReviewController::class, 'index'])->name('index');
        Route::get('{review}', [ClubReviewController::class, 'show'])->name('show');
        Route::post('{review}/reply', [ClubReviewController::class, 'storeReply'])->name('reply.store');
        Route::put('{review}/reply', [ClubReviewController::class, 'updateReply'])->name('reply.update');
        Route::delete('{review}/reply', [ClubReviewController::class, 'destroyReply'])->name('reply.destroy');
        Route::post('{review}/toggle', [ClubReviewController::class, 'toggleStatus'])->name('toggle');
    });
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [ClubSettingController::class, 'index'])->name('index');
        Route::post('general', [ClubSettingController::class, 'updateGeneral'])->name('update.general');
        Route::post('hours', [ClubSettingController::class, 'updateBusinessHours'])->name('update.hours');
        Route::post('booking', [ClubSettingController::class, 'updateBookingRules'])->name('update.booking');
        Route::post('payment', [ClubSettingController::class, 'updatePaymentMethods'])->name('update.payment');
        Route::post('notifications', [ClubSettingController::class, 'updateNotifications'])->name('update.notifications');
    });
});

if (app()->environment('local') && class_exists(Scramble::class)) {
    Scramble::registerUiRoute('api/documentation');
    Scramble::registerJsonSpecificationRoute('api/documentation.json');
}

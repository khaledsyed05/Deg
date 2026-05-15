<?php

use App\Http\Controllers\Api\Admin\V1\ClubController as AdminClubController;
use App\Http\Controllers\Api\Admin\V1\DashboardController;
use App\Http\Controllers\Api\Admin\V1\GeographyController;
use App\Http\Controllers\Api\Admin\V1\SettlementController;
use App\Http\Controllers\Api\Club\V1\BookingController as ClubBookingController;
use App\Http\Controllers\Api\Club\V1\StaffController;
use App\Http\Controllers\Api\Club\V1\VenueController as ClubVenueController;
use App\Http\Controllers\Api\V1\Admin\AppVersionController;
use App\Http\Controllers\Api\V1\Admin\FinancialController;
use App\Http\Controllers\Api\V1\Admin\RefundController;
use App\Http\Controllers\Api\V1\Admin\ReportController;
use App\Http\Controllers\Api\V1\Admin\SystemController;
use App\Http\Controllers\Api\V1\Admin\TicketController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\App\AppMetadataController;
use App\Http\Controllers\Api\V1\App\AppStartupController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\Chat\ConversationController as ChatConversationController;
use App\Http\Controllers\Api\V1\Chat\MessageController as ChatMessageController;
use App\Http\Controllers\Api\V1\Chat\PusherAuthController as ChatPusherAuthController;
use App\Http\Controllers\Api\V1\Club\ClubController as PublicClubController;
use App\Http\Controllers\Api\V1\Club\ManageBookingController;
use App\Http\Controllers\Api\V1\Club\MyEventController;
use App\Http\Controllers\Api\V1\Club\MyPromotionController;
use App\Http\Controllers\Api\V1\Club\MyReviewController;
use App\Http\Controllers\Api\V1\Club\MyVenueController;
use App\Http\Controllers\Api\V1\Club\ScheduleController;
use App\Http\Controllers\Api\V1\Club\UpdateController;
use App\Http\Controllers\Api\V1\Content\ContentController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\Emergency\EmergencyController;
use App\Http\Controllers\Api\V1\Event\EventController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\Football\FavoriteTeamsController;
use App\Http\Controllers\Api\V1\Football\FollowedLeaguesController;
use App\Http\Controllers\Api\V1\Football\LeaguesController;
use App\Http\Controllers\Api\V1\Football\LiveScoreController;
use App\Http\Controllers\Api\V1\Football\MatchesController;
use App\Http\Controllers\Api\V1\Football\NotificationSettingsController;
use App\Http\Controllers\Api\V1\Football\TeamsController;
use App\Http\Controllers\Api\V1\Geography\GeographyController as PublicGeographyController;
use App\Http\Controllers\Api\V1\GroupBookingController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Payment\BankTransferController;
use App\Http\Controllers\Api\V1\Payment\CashController;
use App\Http\Controllers\Api\V1\Payment\MtnCashController;
use App\Http\Controllers\Api\V1\Payment\SyriatelCashController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\SocialController;
use App\Http\Controllers\Api\V1\SportsProfileController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SupportController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\VenueController;
use App\Http\Controllers\Api\V1\WaitlistController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Webhooks\BankCallbackController;
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

    // Auth — public (rate-limited per Sprint 8 B1)
    Route::prefix('auth')->group(function () {
        Route::post('otp/send', [AuthController::class, 'sendOtp'])
            ->middleware('throttle:auth-otp-send');
        Route::post('otp/verify', [AuthController::class, 'verifyOtp'])
            ->middleware('throttle:auth-otp-verify');
        Route::post('otp/resend', [AuthController::class, 'resendOtp'])
            ->middleware('throttle:auth-otp-send');
        Route::post('google', [AuthController::class, 'googleSignIn'])
            ->middleware('throttle:auth-otp-send');
    });

    // Venues — public (specific paths before {venue} wildcard)
    Route::get('venues', [VenueController::class, 'index']);
    Route::get('venues/search', [VenueController::class, 'search']);
    Route::get('venues/nearby', [VenueController::class, 'nearby']);
    Route::get('venues/featured', [VenueController::class, 'featured']);
    Route::get('venues/popular', [VenueController::class, 'popular']);
    Route::get('venues/recently-viewed', [VenueController::class, 'recentlyViewed'])
        ->middleware('auth:sanctum');
    Route::get('venues/clusters', [PublicGeographyController::class, 'venueClusters']);
    Route::get('venues/by-bounds', [VenueController::class, 'byBounds'])
        ->middleware('auth:sanctum');
    Route::get('venues/{venue}', [VenueController::class, 'show']);
    Route::get('venues/{venue}/availability', [VenueController::class, 'availability']);
    Route::get('venues/{venue}/reviews', [VenueController::class, 'reviews']);
    Route::get('venues/{venue}/slots', [VenueController::class, 'availableSlots']);
    Route::get('venues/{slug}/similar', [VenueController::class, 'similar']);
    Route::get('venues/{slug}/photos', [VenueController::class, 'photos']);

    // Categories — public
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);

    // Geography — canonical paths (per BACKEND_REQUIREMENTS.md)
    // (venues/clusters is registered above with the other venues/* routes
    // so it's matched before venues/{venue})
    Route::get('cities', [PublicGeographyController::class, 'popularCities']);
    Route::get('cities/{id}', [PublicGeographyController::class, 'show'])->whereNumber('id');
    Route::get('cities/{id}/neighborhoods', [PublicGeographyController::class, 'neighborhoods'])->whereNumber('id');

    // Geography — kept (no canonical mobile equivalent; web admin uses
    // these for country/state pickers in venue/club registration forms.
    // Cities/popular, cities/{id}, and venues/clusters were removed in
    // Sprint 8 — callers must use the canonical paths above.)
    Route::prefix('geography')->group(function () {
        Route::get('countries', [PublicGeographyController::class, 'countries']);
        Route::get('countries/{iso2}/states', [PublicGeographyController::class, 'statesByCountry']);
        Route::get('states/{stateId}/cities', [PublicGeographyController::class, 'citiesByState'])->whereNumber('stateId');
        Route::post('detect', [PublicGeographyController::class, 'detect']);
    });

    // App metadata — public (Phase 14)
    Route::prefix('app')->group(function () {
        Route::get('version', [AppMetadataController::class, 'version']);
        Route::get('feature-flags', [AppMetadataController::class, 'featureFlags']);
        Route::get('maintenance', [AppMetadataController::class, 'maintenance']);
        Route::get('config', [AppMetadataController::class, 'config']);
        Route::get('health', [AppMetadataController::class, 'health']);

        // LD-029 — single startup aggregator: base URL + version verdict + maintenance
        Route::post('startup', AppStartupController::class)->name('app.startup');
    });

    // Phase 16 — Clubs (public)
    Route::prefix('clubs')->group(function () {
        Route::get('/{id}', [PublicClubController::class, 'show'])->whereNumber('id');
        Route::get('/{id}/venues', [PublicClubController::class, 'venues'])->whereNumber('id');
        Route::get('/{id}/reviews', [PublicClubController::class, 'reviews'])->whereNumber('id');
        Route::get('/{id}/contact', [PublicClubController::class, 'contact'])->whereNumber('id');
        Route::get('/{id}/feed', [PublicClubController::class, 'feed'])->whereNumber('id');
    });

    // Phase 16 — Events (public)
    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']);
        Route::get('/{id}', [EventController::class, 'show'])->whereNumber('id');
        Route::get('/{id}/participants', [EventController::class, 'participants'])->whereNumber('id');
        Route::get('/{id}/results', [EventController::class, 'results'])->whereNumber('id');
    });

    // Phase 17 — Content & Media (public)
    Route::prefix('content')->group(function () {
        Route::get('banners', [ContentController::class, 'banners']);
        Route::get('featured', [ContentController::class, 'featured']);
        Route::get('blog', [ContentController::class, 'blog']);
        Route::get('tips', [ContentController::class, 'tips']);
        Route::get('videos', [ContentController::class, 'videos']);
    });

    // Phase 17 — Emergency & Safety (public)
    Route::prefix('emergency')->group(function () {
        Route::get('contacts', [EmergencyController::class, 'contacts']);
        Route::get('safety-guide', [EmergencyController::class, 'safetyGuide']);
    });

    // Phase 18 — Reviews guidelines (public)
    Route::get('reviews/guidelines', [ReviewController::class, 'guidelines']);

    // Promotions — public (specific paths before {code} wildcard)
    Route::get('promotions', [PromotionController::class, 'index']);
    Route::get('promotions/featured', [PromotionController::class, 'featured']);
    Route::get('promotions/venue/{slug}', [PromotionController::class, 'forVenue']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('auth/complete-profile', [AuthController::class, 'completeProfile']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);

        // Profile
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update'])
            ->middleware('throttle:profile-mutations');
        Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar'])
            ->middleware('throttle:profile-mutations');
        Route::delete('profile/avatar', [ProfileController::class, 'deleteAvatar'])
            ->middleware('throttle:profile-mutations');
        Route::put('profile/notifications', [ProfileController::class, 'updateNotifications'])
            ->middleware('throttle:profile-mutations');

        // Devices
        Route::post('devices', [DeviceController::class, 'register']);

        // Favorites
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('venues/{venue}/favorite', [FavoriteController::class, 'store']);
        Route::delete('venues/{venue}/favorite', [FavoriteController::class, 'destroy']);

        // Bookings — specific paths before {booking} wildcard
        Route::post('bookings/check-availability', [BookingController::class, 'checkAvailability']);
        Route::post('bookings/calculate-price', [BookingController::class, 'calculatePrice']);
        Route::post('bookings/group', [GroupBookingController::class, 'store']);
        Route::get('bookings/upcoming', [BookingController::class, 'upcoming']);
        Route::get('bookings/past', [BookingController::class, 'past']);
        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store'])
            ->middleware('throttle:bookings-create');
        Route::get('bookings/{booking}', [BookingController::class, 'show']);
        Route::get('bookings/{booking}/receipt', [BookingController::class, 'receipt']);
        Route::post('bookings/{booking}/checkin', [BookingController::class, 'checkin']);
        Route::put('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::post('bookings/{id}/invite', [GroupBookingController::class, 'invite'])->whereNumber('id');
        Route::put('bookings/{id}/payment-share', [GroupBookingController::class, 'updatePaymentShare'])->whereNumber('id');

        // Phase 14 — critical risk-mitigation endpoints
        Route::put('bookings/{id}/reschedule', [BookingController::class, 'reschedule'])->whereNumber('id');
        Route::post('bookings/{id}/refund', [BookingController::class, 'requestRefund'])->whereNumber('id');
        Route::post('venues/{slug}/report', [VenueController::class, 'report']);
        Route::post('reviews/{id}/report', [ReviewController::class, 'report'])->whereNumber('id');
        Route::get('support/tickets/{id}', [SupportController::class, 'showTicket'])->whereNumber('id');
        Route::post('support/tickets/{id}/reply', [SupportController::class, 'replyTicket'])->whereNumber('id');

        // Payments — rate-limited per Sprint 8 B1 (POST routes only;
        // GET status/history endpoints are read-only and unrestricted)
        Route::middleware('throttle:payments')->group(function () {
            // Payments — unified (legacy)
            Route::post('payments/initiate', [PaymentController::class, 'initiate']);
            Route::post('payments/mtn/confirm', [PaymentController::class, 'confirmMtn']);
            Route::post('payments/syriatel/confirm', [PaymentController::class, 'confirmSyriatel']);

            // Payments — Syriatel Cash (Phase 4)
            Route::post('payments/syriatel/initiate', [SyriatelCashController::class, 'initiate']);
            Route::post('payments/syriatel/verify', [SyriatelCashController::class, 'verify']);
            Route::post('payments/syriatel/resend', [SyriatelCashController::class, 'resend']);
            Route::post('payments/syriatel/cancel/{payment}', [SyriatelCashController::class, 'cancel']);

            // Payments — MTN Cash (Phase 4)
            Route::post('payments/mtn/initiate', [MtnCashController::class, 'initiate']);
            Route::post('payments/mtn/verify', [MtnCashController::class, 'verify']);
            Route::post('payments/mtn/resend', [MtnCashController::class, 'resend']);
            Route::post('payments/mtn/cancel/{payment}', [MtnCashController::class, 'cancel']);

            // Payments — Bank Transfer (Phase 4, backed by Fatora gateway)
            Route::post('payments/bank/initiate', [BankTransferController::class, 'initiate']);
            Route::post('payments/bank/cancel/{payment}', [BankTransferController::class, 'cancel']);

            // Payments — Cash at Venue (Phase 4)
            Route::post('payments/cash/confirm', [CashController::class, 'confirm']);
        });

        Route::get('payments/syriatel/status/{payment}', [SyriatelCashController::class, 'status']);
        Route::get('payments/mtn/status/{payment}', [MtnCashController::class, 'status']);
        Route::get('payments/bank/view', [BankTransferController::class, 'view']);
        Route::get('payments/bank/success', [BankTransferController::class, 'success']);
        Route::get('payments/cash/instructions', [CashController::class, 'instructions']);

        // Payments — common (Phase 4)
        Route::get('payments/methods', [App\Http\Controllers\Api\V1\Payment\PaymentController::class, 'methods']);
        Route::get('payments/history', [App\Http\Controllers\Api\V1\Payment\PaymentController::class, 'history']);
        Route::get('payments/{payment}/receipt', [App\Http\Controllers\Api\V1\Payment\PaymentController::class, 'receipt']);

        // Promotions — authenticated
        Route::get('promotions/my-history', [PromotionController::class, 'myHistory']);
        Route::post('promotions/{code}/validate', [PromotionController::class, 'validateCode']);
        Route::get('promotions/{code}', [PromotionController::class, 'show']);

        // Reviews
        Route::get('reviews/my-reviews', [ReviewController::class, 'myReviews']);
        Route::get('reviews/pending', [ReviewController::class, 'pending']);
        Route::post('reviews', [ReviewController::class, 'store']);
        Route::put('reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);
        Route::post('reviews/{review}/helpful', [ReviewController::class, 'helpful']);

        // Waitlist
        Route::get('waitlist', [WaitlistController::class, 'index']);
        Route::post('waitlist', [WaitlistController::class, 'store']);
        Route::delete('waitlist/{waitlist}', [WaitlistController::class, 'destroy']);

        // Notifications — specific paths before {id} wildcard
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread', [NotificationController::class, 'unread']);
        Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('notifications/clear-all', [NotificationController::class, 'clearAll']);
        Route::get('notifications/settings', [NotificationController::class, 'getSettings']);
        Route::put('notifications/settings', [NotificationController::class, 'updateSettings']);
        Route::put('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

        // Settings
        Route::get('settings', [SettingsController::class, 'index']);
        Route::put('settings', [SettingsController::class, 'update']);
        Route::put('settings/language', [SettingsController::class, 'updateLanguage']);
        Route::put('settings/privacy', [SettingsController::class, 'updatePrivacy']);
        Route::get('settings/data-export', [SettingsController::class, 'requestDataExport']);
        Route::delete('settings/delete-account', [SettingsController::class, 'deleteAccount']);

        // Player profile & stats (Phase 7)
        Route::get('profile/stats', [PlayerController::class, 'stats']);
        Route::get('profile/achievements', [PlayerController::class, 'achievements']);
        Route::get('profile/history', [PlayerController::class, 'history']);
        Route::post('profile/bio', [PlayerController::class, 'updateBio']);
        Route::get('leaderboard', [PlayerController::class, 'leaderboard']);
        Route::get('players/{id}', [PlayerController::class, 'show']);

        // Advanced search (Phase 7)
        Route::post('search/saved', [SearchController::class, 'saveSearch']);
        Route::get('search/saved', [SearchController::class, 'getSavedSearches']);
        Route::delete('search/saved/{id}', [SearchController::class, 'deleteSavedSearch']);
        Route::post('venues/compare', [SearchController::class, 'compareVenues']);

        // Social features (Phase 7)
        Route::post('friends/invite', [SocialController::class, 'inviteFriend']);
        Route::get('friends/bookings', [SocialController::class, 'friendsBookings']);
        Route::post('share/booking', [SocialController::class, 'shareBooking']);
        Route::post('referrals/code', [SocialController::class, 'generateReferralCode']);

        // Support & feedback (Phase 7)
        Route::post('support/ticket', [SupportController::class, 'createTicket']);
        Route::get('support/tickets', [SupportController::class, 'myTickets']);
        Route::get('support/faq', [SupportController::class, 'faq']);
        Route::post('feedback', [SupportController::class, 'submitFeedback']);

        // Recurring bookings & subscriptions (Phase 8)
        Route::get('recurring/next-charges', [SubscriptionController::class, 'nextCharges']);
        Route::get('subscriptions', [SubscriptionController::class, 'index']);
        Route::post('subscriptions', [SubscriptionController::class, 'store']);
        Route::get('subscriptions/{id}', [SubscriptionController::class, 'show'])->whereNumber('id');
        Route::put('subscriptions/{id}', [SubscriptionController::class, 'update'])->whereNumber('id');
        Route::put('subscriptions/{id}/pause', [SubscriptionController::class, 'pause'])->whereNumber('id');
        Route::put('subscriptions/{id}/resume', [SubscriptionController::class, 'resume'])->whereNumber('id');
        Route::delete('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->whereNumber('id');
        Route::get('subscriptions/{id}/history', [SubscriptionController::class, 'history'])->whereNumber('id');
        Route::get('subscriptions/{id}/upcoming', [SubscriptionController::class, 'upcoming'])->whereNumber('id');
        Route::put('subscriptions/{id}/instances/{instanceId}/skip', [SubscriptionController::class, 'skipInstance'])
            ->whereNumber('id')->whereNumber('instanceId');
        Route::post('subscriptions/{id}/payment-method', [SubscriptionController::class, 'updatePaymentMethod'])
            ->whereNumber('id');

        // Teams (Phase 9)
        Route::get('teams', [TeamController::class, 'index']);
        Route::post('teams', [TeamController::class, 'store']);
        Route::get('teams/invite/{code}', [TeamController::class, 'useInvite'])
            ->where('code', '[A-Za-z0-9]+');
        Route::get('teams/{id}', [TeamController::class, 'show'])->whereNumber('id');
        Route::put('teams/{id}', [TeamController::class, 'update'])->whereNumber('id');
        Route::delete('teams/{id}', [TeamController::class, 'destroy'])->whereNumber('id');
        Route::post('teams/{id}/kick', [TeamController::class, 'kick'])->whereNumber('id');
        Route::post('teams/{id}/transfer-captain', [TeamController::class, 'transferCaptain'])
            ->whereNumber('id');
        Route::post('teams/{id}/invite', [TeamController::class, 'generateInvite'])
            ->whereNumber('id')
            ->middleware('throttle:team-invites');

        // Sports Profile (Phase 9 — Sprint 5)
        Route::prefix('sports-profile')->group(function (): void {
            Route::get('me', [SportsProfileController::class, 'me']);
            Route::get('weekly-activity', [SportsProfileController::class, 'weeklyActivity']);
        });

        // Chat (Phase 10 — Sprint 7; rate-limited per Sprint 8 B1)
        Route::get('chat/unread-summary', [ChatConversationController::class, 'unreadSummary']);
        Route::get('conversations', [ChatConversationController::class, 'index']);
        Route::get('conversations/{id}', [ChatConversationController::class, 'show'])
            ->whereNumber('id');
        Route::get('conversations/{id}/messages', [ChatConversationController::class, 'messages'])
            ->whereNumber('id');
        Route::post('conversations/{id}/mute', [ChatConversationController::class, 'mute'])
            ->whereNumber('id')
            ->middleware('throttle:default-mutations');
        Route::post('conversations/{id}/leave', [ChatConversationController::class, 'leave'])
            ->whereNumber('id')
            ->middleware('throttle:default-mutations');
        Route::post('messages', [ChatMessageController::class, 'store'])
            ->middleware('throttle:chat-send');
        Route::post('messages/{id}/mark-read', [ChatMessageController::class, 'markRead'])
            ->whereNumber('id')
            ->middleware('throttle:chat-mark-read');
        Route::post('pusher/auth', [ChatPusherAuthController::class, 'auth'])
            ->middleware('throttle:pusher-auth');

        // Phase 18 — Profile / Auth / Devices
        Route::put('profile/phone-number/initiate', [ProfileController::class, 'initiatePhoneChange']);
        Route::put('profile/phone-number/verify', [ProfileController::class, 'verifyPhoneChange']);
        Route::get('auth/sessions', [AuthController::class, 'sessions']);
        Route::delete('auth/sessions/{id}', [AuthController::class, 'revokeSession'])->whereNumber('id');
        Route::delete('devices/{id}', [DeviceController::class, 'destroy'])->whereNumber('id');

        // Phase 18 — Promotions extras
        Route::post('promotions/qr-redeem', [PromotionController::class, 'qrRedeem']);
        Route::delete('promotions/applied', [PromotionController::class, 'removeApplied']);

        // Phase 18 — Reviews extras
        Route::post('reviews/{id}/photos', [ReviewController::class, 'uploadPhotos'])->whereNumber('id');

        // Phase 18 — Bookings + Teams extras
        Route::post('bookings/{id}/split-payment', [BookingController::class, 'splitPayment'])->whereNumber('id');
        Route::put('teams/{id}/leave', [TeamController::class, 'leave'])->whereNumber('id');

        // Phase 17 — Emergency (auth)
        Route::post('emergency/report', [EmergencyController::class, 'report']);
        Route::post('emergency/share-location', [EmergencyController::class, 'shareLocation']);

        // Phase 16 — Clubs (auth)
        Route::get('clubs/followed', [PublicClubController::class, 'followed']);
        Route::post('clubs/{id}/follow', [PublicClubController::class, 'follow'])->whereNumber('id');
        Route::delete('clubs/{id}/follow', [PublicClubController::class, 'unfollow'])->whereNumber('id');

        // Phase 16 — Events (auth)
        Route::get('events/registered', [EventController::class, 'myRegistrations']);
        Route::post('events/{id}/register', [EventController::class, 'register'])->whereNumber('id');
        Route::delete('events/{id}/registration', [EventController::class, 'cancelRegistration'])->whereNumber('id');

        // Wallet & Credits (Phase 10) + Top-up Payment Integration (Phase 11)
        Route::prefix('wallet')->group(function () {
            Route::get('/', [WalletController::class, 'index']);
            Route::get('account', [WalletController::class, 'account']);
            Route::get('settings', [WalletController::class, 'getSettings']);
            Route::put('settings', [WalletController::class, 'updateSettings']);
            Route::get('transactions', [WalletController::class, 'transactions']);
            Route::post('pay-booking', [WalletController::class, 'payBooking'])
                ->middleware('throttle:payments');
            Route::post('transfer', [WalletController::class, 'transfer'])
                ->middleware('throttle:payments');
            Route::post('redeem', [WalletController::class, 'redeem'])
                ->middleware('throttle:default-mutations');
            Route::get('expiring', [WalletController::class, 'expiring']);
            Route::post('withdraw', [WalletController::class, 'withdraw'])
                ->middleware('throttle:payments');

            // Top-up flow (Phase 11)
            Route::post('topup', [WalletController::class, 'topup'])
                ->middleware('throttle:payments');
            Route::post('topup/verify', [WalletController::class, 'verifyTopup'])
                ->middleware('throttle:payments');
            Route::post('topup/resend-otp', [WalletController::class, 'resendTopupOtp'])
                ->middleware('throttle:default-mutations');
            Route::get('topup/status/{paymentId}', [WalletController::class, 'topupStatus'])->whereNumber('paymentId');
            Route::post('topup/cancel/{paymentId}', [WalletController::class, 'cancelTopup'])
                ->whereNumber('paymentId')
                ->middleware('throttle:default-mutations');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Football — Phase 12 (mixed public + auth)
    |--------------------------------------------------------------------------
    */
    Route::prefix('football')->group(function () {
        // Schedule & Fixtures (public, with optional auth for "favorites" endpoints)
        Route::get('matches/today', [MatchesController::class, 'today']);
        Route::get('matches/upcoming', [MatchesController::class, 'upcoming']);
        Route::get('matches/yesterday', [MatchesController::class, 'yesterday']);

        // Live Score (public)
        Route::get('live/matches', [LiveScoreController::class, 'liveMatches']);
        Route::get('live/matches/{fixtureId}', [LiveScoreController::class, 'show'])->whereNumber('fixtureId');
        Route::get('live/matches/{fixtureId}/events', [LiveScoreController::class, 'events'])->whereNumber('fixtureId');
        Route::get('live/matches/{fixtureId}/statistics', [LiveScoreController::class, 'statistics'])->whereNumber('fixtureId');
        Route::get('live/matches/{fixtureId}/lineups', [LiveScoreController::class, 'lineups'])->whereNumber('fixtureId');

        // Leagues (public)
        Route::get('leagues', [LeaguesController::class, 'index']);
        Route::get('leagues/{code}', [LeaguesController::class, 'show']);
        Route::get('leagues/{code}/standings', [LeaguesController::class, 'standings']);
        Route::get('leagues/{code}/scorers', [LeaguesController::class, 'scorers']);
        Route::get('leagues/{code}/matches', [LeaguesController::class, 'matches']);
        Route::get('leagues/{code}/schedule', [LeaguesController::class, 'schedule']);
        Route::get('leagues/{code}/teams', [TeamsController::class, 'byLeague']);

        // Teams (public)
        Route::get('teams/search', [TeamsController::class, 'search']);
        Route::get('teams/popular', [TeamsController::class, 'popular']);
        Route::get('teams/{id}', [TeamsController::class, 'show'])->whereNumber('id');
        Route::get('teams/{id}/matches/recent', [TeamsController::class, 'recentMatches'])->whereNumber('id');
        Route::get('teams/{id}/matches/upcoming', [TeamsController::class, 'upcomingMatches'])->whereNumber('id');
        Route::get('teams/{id}/squad', [TeamsController::class, 'squad'])->whereNumber('id');

        // Match details (catch-all by external_id, must be after specific routes)
        Route::get('matches/{externalId}', [MatchesController::class, 'show'])->whereNumber('externalId');

        // Auth-required endpoints
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('matches/today/favorites', [MatchesController::class, 'todayFavorites']);
            Route::get('matches/upcoming/favorites', [MatchesController::class, 'upcomingFavorites']);
            Route::get('live/matches/favorites', [LiveScoreController::class, 'liveMatchesFavorites']);
            Route::get('live/quota-status', [LiveScoreController::class, 'quotaStatus']);

            // Favorite Teams
            Route::prefix('favorites')->group(function () {
                Route::get('teams', [FavoriteTeamsController::class, 'index']);
                Route::post('teams', [FavoriteTeamsController::class, 'store']);
                Route::put('teams/reorder', [FavoriteTeamsController::class, 'reorder']);
                Route::delete('teams/{teamId}', [FavoriteTeamsController::class, 'destroy'])->whereNumber('teamId');
                Route::get('teams/matches/today', [FavoriteTeamsController::class, 'matchesToday']);
                Route::get('teams/matches/upcoming', [FavoriteTeamsController::class, 'matchesUpcoming']);

                // Followed Leagues
                Route::get('leagues', [FollowedLeaguesController::class, 'index']);
                Route::post('leagues', [FollowedLeaguesController::class, 'store']);
                Route::put('leagues/reorder', [FollowedLeaguesController::class, 'reorder']);
                Route::delete('leagues/{leagueId}', [FollowedLeaguesController::class, 'destroy'])->whereNumber('leagueId');
                Route::get('leagues/matches', [FollowedLeaguesController::class, 'matches']);
            });

            // Notification Settings
            Route::prefix('notifications/settings')->group(function () {
                Route::get('/', [NotificationSettingsController::class, 'index']);
                Route::put('/', [NotificationSettingsController::class, 'update']);
                Route::put('reminders', [NotificationSettingsController::class, 'updateReminders']);
                Route::put('goals', [NotificationSettingsController::class, 'updateGoals']);
                Route::put('results', [NotificationSettingsController::class, 'updateResults']);
                Route::put('quiet-hours', [NotificationSettingsController::class, 'updateQuietHours']);
            });
        });
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

    // ===== Phase 19: Admin Dashboard (35 endpoints) =====
    Route::middleware('role:admin')->group(function () {
        // Dashboard
        Route::get('dashboard/stats', [App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'stats']);
        Route::get('dashboard/revenue', [App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'revenue']);
        Route::get('dashboard/bookings-trend', [App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'bookingsTrend']);
        Route::get('dashboard/top-venues', [App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'topVenues']);
        Route::get('dashboard/user-growth', [App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'userGrowth']);

        // Users
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{id}', [UserController::class, 'show'])->whereNumber('id');
        Route::put('users/{id}', [UserController::class, 'update'])->whereNumber('id');
        Route::post('users/{id}/ban', [UserController::class, 'ban'])->whereNumber('id');
        Route::post('users/{id}/unban', [UserController::class, 'unban'])->whereNumber('id');

        // Venues
        Route::get('venues/pending-approval', [App\Http\Controllers\Api\V1\Admin\VenueController::class, 'pending']);
        Route::post('venues/{id}/approve', [App\Http\Controllers\Api\V1\Admin\VenueController::class, 'approve'])->whereNumber('id');
        Route::post('venues/{id}/reject', [App\Http\Controllers\Api\V1\Admin\VenueController::class, 'reject'])->whereNumber('id');
        Route::put('venues/{id}/feature', [App\Http\Controllers\Api\V1\Admin\VenueController::class, 'toggleFeatured'])->whereNumber('id');

        // Refunds
        Route::get('refunds/pending', [RefundController::class, 'pending']);
        Route::post('refunds/{id}/approve', [RefundController::class, 'approve'])->whereNumber('id');
        Route::post('refunds/{id}/reject', [RefundController::class, 'reject'])->whereNumber('id');

        // Reports
        Route::get('reports/venues', [ReportController::class, 'venues']);
        Route::post('reports/venues/{id}/take-action', [ReportController::class, 'venueAction'])->whereNumber('id');
        Route::get('reports/reviews', [ReportController::class, 'reviews']);
        Route::post('reports/reviews/{id}/take-action', [ReportController::class, 'reviewAction'])->whereNumber('id');

        // Tickets
        Route::get('tickets', [TicketController::class, 'index']);
        Route::post('tickets/{id}/assign', [TicketController::class, 'assign'])->whereNumber('id');
        Route::post('tickets/{id}/reply', [TicketController::class, 'reply'])->whereNumber('id');

        // Content
        Route::post('banners', [App\Http\Controllers\Api\V1\Admin\ContentController::class, 'createBanner']);
        Route::put('banners/{id}', [App\Http\Controllers\Api\V1\Admin\ContentController::class, 'updateBanner'])->whereNumber('id');
        Route::post('blog', [App\Http\Controllers\Api\V1\Admin\ContentController::class, 'createBlogArticle']);
        Route::post('promotions', [App\Http\Controllers\Api\V1\Admin\ContentController::class, 'createPromotion']);

        // Financial reports
        Route::get('reports/revenue', [FinancialController::class, 'revenue']);
        Route::get('reports/payments', [FinancialController::class, 'payments']);
        Route::get('reports/wallet-flow', [FinancialController::class, 'walletFlow']);

        // System
        Route::put('app/maintenance', [SystemController::class, 'toggleMaintenance']);
        Route::post('app/feature-flags/{key}', [SystemController::class, 'toggleFeatureFlag']);
        Route::post('notifications/broadcast', [SystemController::class, 'broadcast']);
        Route::get('audit-log', [SystemController::class, 'auditLog']);

        // App Versions
        Route::get('app-versions', [AppVersionController::class, 'index']);
        Route::post('app-versions', [AppVersionController::class, 'store']);
        Route::get('app-versions/{id}', [AppVersionController::class, 'show'])->whereNumber('id');
        Route::put('app-versions/{id}', [AppVersionController::class, 'update'])->whereNumber('id');
        Route::delete('app-versions/{id}', [AppVersionController::class, 'destroy'])->whereNumber('id');
        Route::post('app-versions/{id}/activate', [AppVersionController::class, 'activate'])->whereNumber('id');
    });
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

    // ===== Phase 19: Club Dashboard (28 endpoints) =====
    Route::middleware('club.access')->group(function () {
        // Dashboard
        Route::get('dashboard/stats', [App\Http\Controllers\Api\V1\Club\DashboardController::class, 'stats']);
        Route::get('dashboard/bookings-trend', [App\Http\Controllers\Api\V1\Club\DashboardController::class, 'bookingsTrend']);
        Route::get('dashboard/top-venues', [App\Http\Controllers\Api\V1\Club\DashboardController::class, 'topVenues']);
        Route::get('dashboard/recent-activity', [App\Http\Controllers\Api\V1\Club\DashboardController::class, 'recentActivity']);

        // My venues
        Route::get('venues', [MyVenueController::class, 'index']);
        Route::post('venues', [MyVenueController::class, 'store']);
        Route::put('venues/{id}', [MyVenueController::class, 'update'])->whereNumber('id');
        Route::post('venues/{id}/photos', [MyVenueController::class, 'uploadPhotos'])->whereNumber('id');
        Route::delete('venues/{id}/photos/{photoId}', [MyVenueController::class, 'deletePhoto'])->whereNumber('id')->whereNumber('photoId');

        // Bookings management
        Route::get('bookings', [ManageBookingController::class, 'index']);
        Route::get('bookings/{id}', [ManageBookingController::class, 'show'])->whereNumber('id');
        Route::post('bookings/{id}/check-in', [ManageBookingController::class, 'checkIn'])->whereNumber('id');
        Route::post('bookings/{id}/no-show', [ManageBookingController::class, 'noShow'])->whereNumber('id');
        Route::post('bookings/{id}/complete', [ManageBookingController::class, 'complete'])->whereNumber('id');

        // Schedule
        Route::get('venues/{id}/schedule', [ScheduleController::class, 'index'])->whereNumber('id');
        Route::post('venues/{id}/block-slot', [ScheduleController::class, 'blockSlot'])->whereNumber('id');
        Route::delete('venues/{id}/block-slot/{blockId}', [ScheduleController::class, 'unblockSlot'])->whereNumber('id')->whereNumber('blockId');

        // Events
        Route::get('events', [MyEventController::class, 'index']);
        Route::post('events', [MyEventController::class, 'store']);
        Route::put('events/{id}', [MyEventController::class, 'update'])->whereNumber('id');
        Route::post('events/{id}/results', [MyEventController::class, 'publishResults'])->whereNumber('id');

        // Promotions
        Route::get('promotions', [MyPromotionController::class, 'index']);
        Route::post('promotions', [MyPromotionController::class, 'store']);

        // Updates / feed
        Route::post('updates', [UpdateController::class, 'store']);
        Route::delete('updates/{id}', [UpdateController::class, 'destroy'])->whereNumber('id');

        // Reviews
        Route::get('reviews', [MyReviewController::class, 'index']);
        Route::post('reviews/{id}/respond', [MyReviewController::class, 'respond'])->whereNumber('id');

        // Financial
        Route::get('financial/summary', [App\Http\Controllers\Api\V1\Club\FinancialController::class, 'summary']);
    });
});

/*
|--------------------------------------------------------------------------
| Payment Webhooks — no auth, signature-verified
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->group(function () {
    Route::post('mtn/callback', [MtnWebhookController::class, 'callback'])
        ->middleware('verify.webhook:mtn')
        ->name('webhooks.mtn.callback');
    Route::post('syriatel/callback', [SyriatelWebhookController::class, 'callback'])
        ->middleware('verify.webhook:syriatel')
        ->name('webhooks.syriatel.callback');
    Route::post('fatora/callback', [FatoraWebhookController::class, 'callback'])->name('webhooks.fatora.callback');
    Route::post('bank/callback', [FatoraWebhookController::class, 'callback'])->name('webhooks.bank.callback');
    // Phase 11 — unified bank callback for both booking + wallet top-up
    Route::post('bank-callback', [BankCallbackController::class, 'handle'])->name('webhooks.bank-callback');
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

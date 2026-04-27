<?php

namespace App\Providers;

use App\Models\Club;
use App\Repositories\Contracts\AppEnvironmentRepositoryInterface;
use App\Repositories\Contracts\AppPlatformRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Repositories\Contracts\CommissionConfigRepositoryInterface;
use App\Repositories\Contracts\CompetitionRepositoryInterface;
use App\Repositories\Contracts\ContentPageRepositoryInterface;
use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Repositories\Contracts\OtpChallengeRepositoryInterface;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\PlayerEventRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Repositories\Contracts\SavedVenueRepositoryInterface;
use App\Repositories\Contracts\SettlementItemRepositoryInterface;
use App\Repositories\Contracts\SettlementRepositoryInterface;
use App\Repositories\Contracts\SlotReservationRepositoryInterface;
use App\Repositories\Contracts\SocialIdentityRepositoryInterface;
use App\Repositories\Contracts\SportCategoryRepositoryInterface;
use App\Repositories\Contracts\StateRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\VenueCategoryRepositoryInterface;
use App\Repositories\Contracts\VenueFlashDealRepositoryInterface;
use App\Repositories\Contracts\VenuePricingTierRepositoryInterface;
use App\Repositories\Contracts\VenueRepositoryInterface;
use App\Repositories\Contracts\VenueWaitlistRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use App\Repositories\Eloquent\AppEnvironmentRepository;
use App\Repositories\Eloquent\AppPlatformRepository;
use App\Repositories\Eloquent\BookingRepository;
use App\Repositories\Eloquent\CityRepository;
use App\Repositories\Eloquent\ClubRepository;
use App\Repositories\Eloquent\CommissionConfigRepository;
use App\Repositories\Eloquent\CompetitionRepository;
use App\Repositories\Eloquent\ContentPageRepository;
use App\Repositories\Eloquent\CountryRepository;
use App\Repositories\Eloquent\OtpChallengeRepository;
use App\Repositories\Eloquent\PaymentMethodRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\PlayerEventRepository;
use App\Repositories\Eloquent\ReviewRepository;
use App\Repositories\Eloquent\SavedVenueRepository;
use App\Repositories\Eloquent\SettlementItemRepository;
use App\Repositories\Eloquent\SettlementRepository;
use App\Repositories\Eloquent\SlotReservationRepository;
use App\Repositories\Eloquent\SocialIdentityRepository;
use App\Repositories\Eloquent\SportCategoryRepository;
use App\Repositories\Eloquent\StateRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\VenueCategoryRepository;
use App\Repositories\Eloquent\VenueFlashDealRepository;
use App\Repositories\Eloquent\VenuePricingTierRepository;
use App\Repositories\Eloquent\VenueRepository;
use App\Repositories\Eloquent\VenueWaitlistRepository;
use App\Repositories\Eloquent\WalletRepository;
use App\Repositories\Eloquent\WalletTransactionRepository;
use App\Services\Auth\FirebaseAuthService;
use App\Services\Notification\BaileysService;
use App\Services\Notification\FcmService;
use App\Services\Notification\SmsService;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsService::class, fn () => new SmsService(
            apiUrl: config('services.sms.url', ''),
            apiKey: config('services.sms.key', ''),
            senderId: config('services.sms.sender_id', 'DaqEhjezly'),
        ));

        $this->app->singleton(BaileysService::class, fn () => new BaileysService(
            serviceUrl: config('services.whatsapp.base_url', ''),
            apiKey: config('services.whatsapp.api_key', ''),
        ));

        $this->app->singleton(FcmService::class, fn () => new FcmService(
            projectId: config('services.firebase.project_id', ''),
            serviceAccountJson: config('services.firebase.service_account', '{}'),
        ));

        $this->app->singleton(FirebaseAuthService::class, fn () => new FirebaseAuthService(
            projectId: config('services.firebase.project_id', ''),
        ));

        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ClubRepositoryInterface::class, ClubRepository::class);
        $this->app->bind(VenueRepositoryInterface::class, VenueRepository::class);
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);
        $this->app->bind(SlotReservationRepositoryInterface::class, SlotReservationRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(PaymentMethodRepositoryInterface::class, PaymentMethodRepository::class);
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->bind(WalletTransactionRepositoryInterface::class, WalletTransactionRepository::class);
        $this->app->bind(CommissionConfigRepositoryInterface::class, CommissionConfigRepository::class);
        $this->app->bind(SettlementRepositoryInterface::class, SettlementRepository::class);
        $this->app->bind(SettlementItemRepositoryInterface::class, SettlementItemRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, ReviewRepository::class);
        $this->app->bind(SavedVenueRepositoryInterface::class, SavedVenueRepository::class);
        $this->app->bind(OtpChallengeRepositoryInterface::class, OtpChallengeRepository::class);
        $this->app->bind(SocialIdentityRepositoryInterface::class, SocialIdentityRepository::class);
        $this->app->bind(VenueCategoryRepositoryInterface::class, VenueCategoryRepository::class);
        $this->app->bind(SportCategoryRepositoryInterface::class, SportCategoryRepository::class);
        $this->app->bind(VenuePricingTierRepositoryInterface::class, VenuePricingTierRepository::class);
        $this->app->bind(AppPlatformRepositoryInterface::class, AppPlatformRepository::class);
        $this->app->bind(AppEnvironmentRepositoryInterface::class, AppEnvironmentRepository::class);
        $this->app->bind(ContentPageRepositoryInterface::class, ContentPageRepository::class);
        $this->app->bind(CompetitionRepositoryInterface::class, CompetitionRepository::class);
        $this->app->bind(VenueFlashDealRepositoryInterface::class, VenueFlashDealRepository::class);
        $this->app->bind(VenueWaitlistRepositoryInterface::class, VenueWaitlistRepository::class);
        $this->app->bind(PlayerEventRepositoryInterface::class, PlayerEventRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(StateRepositoryInterface::class, StateRepository::class);
        $this->app->bind(CityRepositoryInterface::class, CityRepository::class);
    }

    public function boot(): void
    {
        Inertia::share([
            'auth' => fn () => [
                'user' => request()->user() ? [
                    'id' => request()->user()->id,
                    'name' => request()->user()->name,
                    'email' => request()->user()->email,
                ] : null,
            ],
            'flash' => fn () => [
                'success' => session('success'),
                'error' => session('error'),
                'qr_code' => session('qr_code'),
            ],
            'locale' => fn () => app()->getLocale(),
            'club' => function () {
                $user = request()->user();
                if (! $user || ! $user->hasRole('club_manager')) {
                    return null;
                }
                $club = Club::where('owner_id', $user->id)->first()
                    ?? $user->clubs()->first();

                return $club ? [
                    'id' => $club->id,
                    'name' => $club->getTranslation('name', app()->getLocale()) ?: $club->name,
                    'status' => $club->status?->value,
                ] : null;
            },
        ]);
    }
}

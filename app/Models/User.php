<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\FcmPlatform;
use App\Enums\UserRole;
use App\Models\Football\League;
use App\Models\Football\MatchReminderSent;
use App\Models\Football\UserMatchNotificationSettings;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'blocked_at' => 'datetime',
            'unblocked_at' => 'datetime',
            'google2fa_enabled_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'account_status' => AccountStatus::class,
            'fcm_platform' => FcmPlatform::class,
            'notifications_push_enabled' => 'boolean',
            'notifications_sms_enabled' => 'boolean',
            'notifications_reminders_enabled' => 'boolean',
            'date_of_birth' => 'date',
            'privacy_settings' => 'array',
            'preferences' => 'array',
            'interests' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            NotificationSetting::createDefaultsForUser($user->id);
            PlayerStats::firstOrCreate(['user_id' => $user->id]);
        });
    }

    public function defaultState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'default_state_id');
    }

    public function defaultCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'default_city_id');
    }

    public function socialIdentities(): HasMany
    {
        return $this->hasMany(SocialIdentity::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function walletOrCreate(): Wallet
    {
        return Wallet::forUser($this);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function savedVenues(): HasMany
    {
        return $this->hasMany(SavedVenue::class);
    }

    public function favoriteVenues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'saved_venues')->withPivot('created_at');
    }

    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'club_user');
    }

    public function followedClubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'club_followers')
            ->withPivot(['notify_updates', 'notify_events', 'notify_promotions'])
            ->withTimestamps();
    }

    public function clubFollows(): HasMany
    {
        return $this->hasMany(ClubFollower::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(VenueWaitlist::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function notificationSettings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }

    public function playerStats(): HasOne
    {
        return $this->hasOne(PlayerStats::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function favoriteTeams(): BelongsToMany
    {
        return $this->belongsToMany(Football\Team::class, 'user_favorite_teams', 'user_id', 'team_id')
            ->withPivot(['display_order', 'notify_matches', 'notify_goals', 'notify_results'])
            ->withTimestamps()
            ->orderBy('user_favorite_teams.display_order');
    }

    public function followedLeagues(): BelongsToMany
    {
        return $this->belongsToMany(League::class, 'user_followed_leagues', 'user_id', 'league_id')
            ->withPivot(['display_order', 'notify_matches'])
            ->withTimestamps()
            ->orderBy('user_followed_leagues.display_order');
    }

    public function matchNotificationSettings(): HasOne
    {
        return $this->hasOne(UserMatchNotificationSettings::class);
    }

    public function matchRemindersSent(): HasMany
    {
        return $this->hasMany(MatchReminderSent::class);
    }

    public function getOrCreateMatchNotificationSettings(): UserMatchNotificationSettings
    {
        return $this->matchNotificationSettings()->firstOrCreate();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['role', 'status', 'joined_at'])
            ->wherePivot('status', 'active');
    }

    public function generateReferralCode(): string
    {
        if ($this->referral_code) {
            return $this->referral_code;
        }

        $base = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->name ?? 'USER') ?: 'USER', 0, 5));

        do {
            $code = 'REF-'.$base.'-'.random_int(1000, 9999);
        } while (self::where('referral_code', $code)->exists());

        $this->forceFill(['referral_code' => $code])->save();

        return $code;
    }

    public function getNotificationSetting(string $type): ?NotificationSetting
    {
        return $this->notificationSettings()
            ->where('notification_type', $type)
            ->first();
    }

    public function wantsNotification(string $type, string $channel = 'push'): bool
    {
        $setting = $this->getNotificationSetting($type);

        if (! $setting) {
            NotificationSetting::createDefaultsForUser($this->id);
            $setting = $this->getNotificationSetting($type);
        }

        return $setting?->shouldSend($channel) ?? false;
    }

    public function getLanguage(): string
    {
        return $this->preferred_language ?: 'ar';
    }

    public function setLanguage(string $language): void
    {
        $this->forceFill(['preferred_language' => $language])->save();
        app()->setLocale($language);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPrivacySettings(): array
    {
        return array_merge([
            'profile_visibility' => 'public',
            'show_phone_number' => false,
            'show_bookings' => true,
            'show_reviews' => true,
            'allow_marketing' => false,
            'data_sharing' => false,
        ], $this->privacy_settings ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreferences(): array
    {
        return array_merge([
            'currency' => 'SYP',
            'distance_unit' => 'km',
            'time_format' => '24h',
            'push_sound' => true,
            'push_vibrate' => true,
        ], $this->preferences ?? []);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function getAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar') ?: null;
    }
}

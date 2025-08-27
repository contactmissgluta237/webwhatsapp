<?php

namespace App\Models;

use App\Enums\ExternalTransactionType;
use App\Enums\TransactionStatus;
use App\Models\Geography\Country;
use App\Traits\HasMediaCollections;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * == Properties ==
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone_number
 * @property string|null $address
 * @property string $password
 * @property int|null $country_id
 * @property string $currency
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $phone_verified_at
 * @property Carbon|null $last_login_at
 * @property bool $is_active
 * @property string|null $affiliation_code
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * == Accessors ==
 * @property-read string $full_name
 * @property-read string $avatar_url
 *
 * == Relationships ==
 * @property-read Customer|null $customer
 * @property-read Country|null $country
 * @property-read Wallet|null $wallet
 * @property-read Collection|ExternalTransaction[] $createdExternalTransactions
 * @property-read Collection|ExternalTransaction[] $approvedExternalTransactions
 * @property-read Collection|InternalTransaction[] $createdInternalTransactions
 * @property-read Collection|InternalTransaction[] $receivedInternalTransactions
 * @property-read Collection|UserSubscription[] $subscriptions
 * @property-read UserSubscription|null $activeSubscription
 * @property-read Collection|UserProduct[] $userProducts
 * @property-read Collection|WhatsAppAccount[] $whatsappAccounts
 *
 * @method MorphMany|PushSubscription active()
 */
class User extends Authenticatable implements HasMedia
{
    use HasApiTokens;

    use HasFactory;
    use HasMediaCollections;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'last_name',
        'first_name',
        'email',
        'phone_number',
        'address',
        'password',
        'is_active',
        'country_id',
        'currency',
        'referrer_id',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->affiliation_code)) {
                $user->affiliation_code = self::generateUniqueAffiliationCode();
            }
        });
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->hasMedia('avatar')) {
            return $this->getFirstMediaUrl('avatar');
        }

        return $this->generateDefaultAvatar();
    }

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function whatsAppAccounts(): HasMany
    {
        return $this->hasMany(WhatsAppAccount::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function referredUsers(): HasMany
    {
        return $this->referrals();
    }

    public function createdExternalTransactions(): HasMany
    {
        return $this->hasMany(ExternalTransaction::class, 'created_by');
    }

    public function approvedExternalTransactions(): HasMany
    {
        return $this->hasMany(ExternalTransaction::class, 'approved_by');
    }

    public function createdInternalTransactions(): HasMany
    {
        return $this->hasMany(InternalTransaction::class, 'created_by');
    }

    public function receivedInternalTransactions(): HasMany
    {
        return $this->hasMany(InternalTransaction::class, 'recipient_user_id');
    }

    public function pushSubscriptions(): MorphMany
    {
        return $this->morphMany(PushSubscription::class, 'subscribable');
    }

    public function userProducts(): HasMany
    {
        return $this->hasMany(UserProduct::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->latest();
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function messageUsageLogs(): HasMany
    {
        return $this->hasMany(\App\Models\MessageUsageLog::class);
    }

    // ================================================================================
    // TRAIT METHODS
    // ================================================================================

    public function getImageCollections(): array
    {
        return ['avatar'];
    }

    public function requiresMainImage(): bool
    {
        return false;
    }

    public function supportsMultipleImages(): bool
    {
        return false;
    }

    public function getImageIdentifier(): string
    {
        return $this->full_name ?? 'User #'.$this->id;
    }

    // ================================================================================
    // STATIC METHODS
    // ================================================================================

    public static function findByEmailOrPhone(string $identifier): ?self
    {
        return self::where('email', $identifier)
            ->orWhere('phone_number', $identifier)
            ->first();
    }

    public static function findByAffiliationCode(string $code): ?self
    {
        return self::where('affiliation_code', $code)->first();
    }

    protected static function generateUniqueAffiliationCode(): string
    {
        do {
            $code = Str::random(4);
        } while (self::where('affiliation_code', $code)->exists());

        return strtoupper($code);
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function hasAvatar(): bool
    {
        return $this->hasMedia('avatar');
    }

    public function totalRecharged(): float
    {
        if (! $this->wallet) {
            return 0.0;
        }

        return (float) ExternalTransaction::where('wallet_id', $this->wallet->id)
            ->where('transaction_type', ExternalTransactionType::RECHARGE())
            ->where('status', TransactionStatus::COMPLETED())
            ->sum('amount') ?: 0.0;
    }

    public function totalWithdrawn(): float
    {
        if (! $this->wallet) {
            return 0.0;
        }

        return (float) ExternalTransaction::where('wallet_id', $this->wallet->id)
            ->where('transaction_type', ExternalTransactionType::WITHDRAWAL())
            ->where('status', TransactionStatus::COMPLETED())
            ->sum('amount') ?: 0.0;
    }

    public function totalReferralEarnings(): float
    {
        return 0.0;
    }

    /**
     * Check if the user has active push subscriptions.
     */
    public function hasPushSubscriptions(): bool
    {
        return PushSubscription::where('subscribable_type', self::class)
            ->where('subscribable_id', $this->id)
            ->active()
            ->exists();
    }

    /**
     * Get active push subscriptions.
     *
     * @return Collection<int, PushSubscription>
     */
    public function getActivePushSubscriptions(): Collection
    {
        return PushSubscription::where('subscribable_type', self::class)
            ->where('subscribable_id', $this->id)
            ->active()
            ->get();
    }

    // ================================================================================
    // PACKAGE METHODS
    // ================================================================================

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription !== null;
    }

    public function getCurrentPackage(): ?Package
    {
        return $this->activeSubscription?->package;
    }

    public function hasTrialAvailable(): bool
    {
        return ! $this->subscriptions()
            ->whereHas('package', fn ($q) => $q->where('name', 'trial'))
            ->exists();
    }

    public function canUpgradePackage(): bool
    {
        return $this->hasActiveSubscription();
    }

    public function getRemainingMessages(): int
    {
        $subscription = $this->activeSubscription;

        return $subscription ? $subscription->getRemainingMessages() : 0;
    }

    public function hasRemainingMessages(): bool
    {
        $subscription = $this->activeSubscription;

        return $subscription ? $subscription->hasRemainingMessages() : false;
    }

    public function getCurrentUsageTracker(): ?WhatsAppAccountUsage
    {
        // Cette méthode pourrait être obsolète avec la nouvelle architecture
        // Retourner null car on track maintenant par compte WhatsApp
        return null;
    }

    // ================================================================================
    // PROTECTED METHODS
    // ================================================================================

    protected function generateDefaultAvatar(): string
    {
        $initials = $this->getUserInitials();
        $params = http_build_query([
            'name' => $initials,
            'color' => '7F9CF5',
            'background' => 'EBF4FF',
            'size' => 200,
            'font-size' => 0.6,
            'rounded' => true,
        ]);

        return "https://ui-avatars.com/api/?{$params}";
    }

    protected function getUserInitials(): string
    {
        $firstInitial = $this->first_name ? strtoupper($this->first_name[0]) : '';
        $lastInitial = $this->last_name ? strtoupper($this->last_name[0]) : '';

        return $firstInitial.$lastInitial ?: 'U'; // Default to 'U' if no names
    }
}

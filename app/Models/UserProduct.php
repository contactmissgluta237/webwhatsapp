<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasMediaCollections;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property float $price
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|WhatsAppAccount[] $whatsappAccounts
 */
final class UserProduct extends Model implements HasMedia
{
    use HasFactory;
    use HasMediaCollections;

    protected $table = 'user_products';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ================================================================================
    // RELATIONSHIPS
    // ================================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappAccounts(): BelongsToMany
    {
        return $this->belongsToMany(
            WhatsAppAccount::class,
            'whatsapp_account_products',
            'user_product_id',
            'whatsapp_account_id',
        );
    }

    // ================================================================================
    // MEDIA COLLECTIONS
    // ================================================================================

    public function requiresMainImage(): bool
    {
        return false;
    }

    public function supportsMultipleImages(): bool
    {
        return true;
    }

    public function getImageIdentifier(): string
    {
        return "user_product_{$this->id}";
    }

    // ================================================================================
    // PUBLIC METHODS
    // ================================================================================

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function getFormattedPrice(): string
    {
        return number_format((float) $this->price, 0, ',', ' ').' XAF';
    }

    public function hasImages(): bool
    {
        return $this->hasMedia('medias');
    }

    public function getMainImageUrl(): ?string
    {
        return $this->getFirstMediaUrl('medias');
    }

    public function getAllImageUrls(): array
    {
        return $this->getMedia('medias')->map(fn ($media) => $media->getUrl())->toArray();
    }
}

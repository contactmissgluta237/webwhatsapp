<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * == Properties ==
 *
 * @property int $id
 * @property int $whatsapp_account_id
 * @property bool $enable_email_notifications
 * @property string|null $notification_email
 * @property bool $enable_whatsapp_notifications
 * @property string|null $notification_whatsapp_number
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * == Relationships ==
 * @property-read WhatsAppAccount $whatsappAccount
 */
final class WhatsAppAccountSetting extends Model
{
    protected $table = 'whatsapp_account_settings';

    protected $fillable = [
        'whatsapp_account_id',
        'enable_email_notifications',
        'notification_email',
        'enable_whatsapp_notifications',
        'notification_whatsapp_number',
    ];

    protected $casts = [
        'enable_email_notifications' => 'boolean',
        'enable_whatsapp_notifications' => 'boolean',
    ];

    /**
     * Clean and validate WhatsApp number format
     */
    public function setNotificationWhatsappNumberAttribute(?string $value): void
    {
        if (empty($value)) {
            $this->attributes['notification_whatsapp_number'] = null;

            return;
        }

        // Clean: remove spaces, dashes, parentheses
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $value);

        // Add + if missing
        if (! str_starts_with($cleaned, '+')) {
            $cleaned = '+'.$cleaned;
        }

        // Validate format (+ followed by 8-15 digits)
        if (! preg_match('/^\+\d{8,15}$/', $cleaned)) {
            throw new \InvalidArgumentException('Invalid WhatsApp number format');
        }

        $this->attributes['notification_whatsapp_number'] = $cleaned;
    }

    /**
     * Check if any notification is enabled
     */
    public function hasNotificationsEnabled(): bool
    {
        return $this->enable_email_notifications || $this->enable_whatsapp_notifications;
    }

    /**
     * Get the WhatsApp account that owns this setting
     */
    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class);
    }
}

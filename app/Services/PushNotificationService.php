<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PushNotificationDTO;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

final class PushNotificationService
{
    public function __construct(
        private readonly WebPushConfigurationService $configService
    ) {}

    public function sendNotification(User $user, PushNotificationDTO $notification): void
    {
        // Use direct model query instead of relationship with active() scope
        /** @var Collection<int, PushSubscription> $subscriptions */
        $subscriptions = PushSubscription::where('subscribable_type', User::class)
            ->where('subscribable_id', $user->id)
            ->active()
            ->get();

        if ($subscriptions->isEmpty()) {
            Log::info("No push subscriptions found for user {$user->id}");

            return;
        }

        Log::info("Sending push notification to {$subscriptions->count()} subscription(s) for user {$user->id}");

        $webPush = $this->configService->createWebPushInstance();
        $payload = $this->encodePayload($notification);

        $this->queueNotifications($webPush, $subscriptions, $payload, $user->id);
        $this->processNotificationReports($webPush, $user->id);
    }

    private function encodePayload(PushNotificationDTO $notification): string
    {
        $payload = json_encode($notification->toArray());

        if ($payload === false) {
            throw new \RuntimeException('Failed to encode push notification payload');
        }

        return $payload;
    }

    /**
     * @param  Collection<int, PushSubscription>  $subscriptions
     */
    private function queueNotifications(WebPush $webPush, Collection $subscriptions, string $payload, int $userId): void
    {
        foreach ($subscriptions as $subscription) {
            try {
                $webPushSubscription = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->p256dh_key,
                    'authToken' => $subscription->auth_token,
                ]);

                $webPush->queueNotification($webPushSubscription, $payload);
                Log::info("Notification queued for subscription {$subscription->id}");

            } catch (\Exception $e) {
                Log::error('Error preparing push notification', [
                    'user_id' => $userId,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function processNotificationReports(WebPush $webPush, int $userId): void
    {
        try {
            $reports = $webPush->flush();
            $reportsArray = iterator_to_array($reports);

            $this->handleIndividualReports($reportsArray, $userId);
            $this->logSummaryReport($reportsArray, $userId);

        } catch (\Exception $e) {
            $this->logCriticalError($e, $userId);
        }
    }

    /**
     * @param  array<mixed>  $reports
     */
    private function handleIndividualReports(array $reports, int $userId): void
    {
        foreach ($reports as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                $this->logSuccessfulReport($report, $endpoint, $userId);
            } else {
                $this->logFailedReport($report, $endpoint, $userId);
                $this->handleExpiredSubscription($report, $endpoint);
            }
        }
    }

    private function logSuccessfulReport(mixed $report, string $endpoint, int $userId): void
    {
        Log::info('✅ Push notification sent successfully', [
            'endpoint' => substr($endpoint, 0, 50).'...',
            'user_id' => $userId,
            'response_status' => $report->getResponse()?->getStatusCode(),
        ]);
    }

    private function logFailedReport(mixed $report, string $endpoint, int $userId): void
    {
        Log::warning('❌ Failed to send push notification', [
            'endpoint' => substr($endpoint, 0, 50).'...',
            'status_code' => $report->getResponse() ? $report->getResponse()->getStatusCode() : 'N/A',
            'reason' => $report->getReason(),
            'user_id' => $userId,
        ]);
    }

    private function handleExpiredSubscription(mixed $report, string $endpoint): void
    {
        if ($report->getResponse() && in_array($report->getResponse()->getStatusCode(), [400, 404, 410])) {
            $deleted = PushSubscription::where('endpoint', $endpoint)->delete();
            Log::info('🗑️ Push subscription deleted (expired)', [
                'endpoint' => substr($endpoint, 0, 50).'...',
                'deleted' => $deleted,
            ]);
        }
    }

    /**
     * @param  array<mixed>  $reports
     */
    private function logSummaryReport(array $reports, int $userId): void
    {
        $successfulReports = array_filter($reports, fn ($r) => $r->isSuccess());
        $failedReports = array_filter($reports, fn ($r) => ! $r->isSuccess());

        Log::info('📊 Push notification sending summary', [
            'user_id' => $userId,
            'total_sent' => count($reports),
            'successful' => count($successfulReports),
            'failed' => count($failedReports),
        ]);
    }

    private function logCriticalError(\Exception $e, int $userId): void
    {
        Log::error('❌ Critical error sending push notifications', [
            'user_id' => $userId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

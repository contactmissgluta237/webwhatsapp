<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\UsageSubscriptionTracker;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackageSubscriptionCycleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed les packages de base
        $this->seed(\Database\Seeders\PackagesSeeder::class);
    }

    #[Test]
    public function it_creates_tracker_with_correct_cycle_dates_based_on_subscription_start()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');

        // Abonnement qui commence le 15 janvier
        Carbon::setTestNow('2025-01-15 10:00:00');

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $tracker = $subscription->getOrCreateCurrentCycleTracker();

        // Le cycle doit commencer le 15 janvier et finir le 15 février
        $this->assertEquals('2025-01-15', $tracker->cycle_start_date->format('Y-m-d'));
        $this->assertEquals('2025-02-15', $tracker->cycle_end_date->format('Y-m-d'));
    }

    #[Test]
    public function it_finds_current_cycle_tracker_correctly()
    {
        $user = User::factory()->create();
        $package = Package::findByName('starter');

        // Abonnement qui commence le 10 janvier
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => Carbon::parse('2025-01-10'),
            'ends_at' => Carbon::parse('2025-02-10'),
            'status' => 'active',
        ]);

        // Créer tracker pour le cycle janvier
        $tracker = $subscription->getOrCreateCurrentCycleTracker();

        // Test : on est le 25 janvier (dans le cycle)
        Carbon::setTestNow('2025-01-25 15:30:00');

        $currentTracker = $subscription->getCurrentCycleTracker();
        $this->assertNotNull($currentTracker);
        $this->assertEquals($tracker->id, $currentTracker->id);
    }

    #[Test]
    public function it_does_not_find_current_cycle_tracker_when_outside_cycle()
    {
        $user = User::factory()->create();
        $package = Package::findByName('starter');

        // Abonnement qui commence le 10 janvier
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => Carbon::parse('2025-01-10'),
            'ends_at' => Carbon::parse('2025-02-10'),
            'status' => 'active',
        ]);

        // Créer tracker pour le cycle janvier (10 jan - 10 fév)
        $subscription->getOrCreateCurrentCycleTracker();

        // Test : on est le 15 février (après le cycle)
        Carbon::setTestNow('2025-02-15 10:00:00');

        $currentTracker = $subscription->getCurrentCycleTracker();
        $this->assertNull($currentTracker);
    }

    #[Test]
    public function it_can_reset_expired_cycles_and_create_new_ones()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');

        // Abonnement qui commence le 5 janvier
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => Carbon::parse('2025-01-05'),
            'ends_at' => Carbon::parse('2025-02-05'),
            'status' => 'active',
        ]);

        // Créer tracker pour le premier cycle (5 jan - 5 fév)
        $firstTracker = $subscription->getOrCreateCurrentCycleTracker();
        $firstTracker->incrementUsage(50); // Utiliser quelques messages

        // Simuler qu'on est le 10 février (cycle expiré le 5 février)
        Carbon::setTestNow('2025-02-10 10:00:00');

        // Vérifier qu'il y a bien un tracker expiré
        $expiredTrackers = UsageSubscriptionTracker::where('cycle_end_date', '<=', now()->toDateString())->get();
        $this->assertCount(1, $expiredTrackers);

        // Reset les cycles expirés (doit créer un nouveau cycle 5 fév - 5 mars)
        $resetCount = UsageSubscriptionTracker::resetExpiredCycles();

        $this->assertEquals(1, $resetCount);

        // Debug: voir tous les trackers créés
        $allTrackers = UsageSubscriptionTracker::where('user_subscription_id', $subscription->id)->get();
        $this->assertTrue($allTrackers->count() >= 2, 'Should have at least 2 trackers after reset');

        // Vérifier qu'un nouveau tracker a été créé avec la bonne date de début
        $newTracker = UsageSubscriptionTracker::where('user_subscription_id', $subscription->id)
            ->whereDate('cycle_start_date', '2025-02-05')
            ->first();

        $this->assertNotNull($newTracker, 'New tracker should exist with cycle_start_date = 2025-02-05');
        $this->assertEquals('2025-03-05', $newTracker->cycle_end_date->format('Y-m-d'));
        $this->assertEquals(0, $newTracker->messages_used);
        $this->assertEquals($package->messages_limit, $newTracker->messages_remaining);
    }

    #[Test]
    public function it_calculates_daily_average_correctly_for_cycle()
    {
        $user = User::factory()->create();
        $package = Package::findByName('business');

        // Abonnement qui commence le 1er janvier
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => Carbon::parse('2025-01-01'),
            'ends_at' => Carbon::parse('2025-02-01'),
            'status' => 'active',
        ]);

        $tracker = $subscription->getOrCreateCurrentCycleTracker();
        $tracker->update(['messages_used' => 100]); // 100 messages utilisés

        // Test : on est le 10 janvier (10 jours écoulés incluant le 1er et 10, 100 messages)
        Carbon::setTestNow('2025-01-10 12:00:00');

        $dailyAverage = $tracker->getDailyAverage();
        $this->assertEquals(100 / 10, $dailyAverage, '', 0.01);

        // Test de projection pour tout le cycle (31 jours)
        $projected = $tracker->getProjectedUsage();
        $expected = ceil((100 / 10) * 31);
        $this->assertEquals($expected, $projected);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon
        parent::tearDown();
    }
}

<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAffiliationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_user_gets_an_affiliation_code_on_creation(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->affiliation_code);
        $this->assertIsString($user->affiliation_code);
        $this->assertEquals(4, strlen($user->affiliation_code));
    }

    #[Test]
    public function affiliation_codes_are_unique(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertNotEquals($user1->affiliation_code, $user2->affiliation_code);
    }

    #[Test]
    public function a_user_can_have_a_referrer(): void
    {
        $referrerUser = User::factory()->create();
        $referredUser = User::factory()->create(['referrer_id' => $referrerUser->id]);

        $this->assertTrue($referredUser->referrer->is($referrerUser));
    }

    #[Test]
    public function a_referrer_can_have_multiple_referrals(): void
    {
        $referrerUser = User::factory()->create();

        $referredUser1 = User::factory()->create(['referrer_id' => $referrerUser->id]);
        $referredUser2 = User::factory()->create(['referrer_id' => $referrerUser->id]);

        $this->assertCount(2, $referrerUser->referrals);
        $this->assertTrue($referrerUser->referrals->contains($referredUser1));
        $this->assertTrue($referrerUser->referrals->contains($referredUser2));
    }

    #[Test]
    public function affiliation_code_is_not_overwritten_if_provided(): void
    {
        $customCode = 'MYCUSTOMCODE';
        $user = User::factory()->create(['affiliation_code' => $customCode]);

        $this->assertEquals($customCode, $user->affiliation_code);
    }
}

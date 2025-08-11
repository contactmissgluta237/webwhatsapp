<?php

namespace Tests\Unit\Services\Auth;

use App\Enums\LoginChannel;
use App\Exceptions\Auth\OtpDeliveryException;
use App\Exceptions\UserNotFoundException;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\SMS\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;
    private SmsServiceInterface $smsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsService = $this->createMock(SmsServiceInterface::class);
        $this->otpService = new OtpService($this->smsService);
    }

    /** @test */
    public function it_generates_and_stores_otp_for_email()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'John',
        ]);

        $result = $this->otpService->sendOtp('test@example.com', LoginChannel::EMAIL(), 'password_reset');

        $this->assertTrue($result);
        Mail::assertSent(OtpMail::class);

        // Vérifier que l'OTP est en cache
        $cacheKey = 'otp_'.md5('test@example.com');
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_generates_and_stores_otp_for_phone()
    {
        $user = User::factory()->create([
            'phone_number' => '+237655332183',
            'first_name' => 'John',
        ]);

        $this->smsService->expects($this->once())
            ->method('sendSms')
            ->with('+237655332183', $this->stringContains('Your verification code is'))
            ->willReturn(true);

        $result = $this->otpService->sendOtp('+237655332183', LoginChannel::PHONE(), 'password_reset');

        $this->assertTrue($result);

        // Vérifier que l'OTP est en cache
        $cacheKey = 'otp_'.md5('+237655332183');
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_throws_exception_when_user_not_found()
    {
        $this->expectException(UserNotFoundException::class);

        $this->otpService->sendOtp('nonexistent@example.com', LoginChannel::EMAIL());
    }

    /** @test */
    public function it_throws_exception_when_sms_fails()
    {
        $user = User::factory()->create([
            'phone_number' => '+237655332183',
            'first_name' => 'John',
        ]);

        $this->smsService->expects($this->once())
            ->method('sendSms')
            ->willReturn(false);

        $this->expectException(OtpDeliveryException::class);

        $this->otpService->sendOtp('+237655332183', LoginChannel::PHONE());
    }

    /** @test */
    public function it_verifies_valid_otp()
    {
        $identifier = 'test@example.com';
        $otp = '123456';

        // Stocker l'OTP en cache
        $cacheKey = 'otp_'.md5($identifier);
        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        $result = $this->otpService->verifyOtp($identifier, $otp);

        $this->assertTrue($result);
        // Vérifier que l'OTP a été supprimé du cache après vérification
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_rejects_invalid_otp()
    {
        $identifier = 'test@example.com';
        $correctOtp = '123456';
        $wrongOtp = '654321';

        // Stocker l'OTP en cache
        $cacheKey = 'otp_'.md5($identifier);
        Cache::put($cacheKey, $correctOtp, now()->addMinutes(10));

        $result = $this->otpService->verifyOtp($identifier, $wrongOtp);

        $this->assertFalse($result);
        // Vérifier que l'OTP est toujours en cache après échec
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_rejects_expired_otp()
    {
        $identifier = 'test@example.com';
        $otp = '123456';

        $result = $this->otpService->verifyOtp($identifier, $otp);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_determines_channel_correctly()
    {
        $emailChannel = $this->otpService->determineChannel('test@example.com');
        $phoneChannel = $this->otpService->determineChannel('+237655332183');

        $this->assertEquals(LoginChannel::EMAIL(), $emailChannel);
        $this->assertEquals(LoginChannel::PHONE(), $phoneChannel);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_masks_email_identifier()
    {
        $masked = $this->otpService->maskIdentifier('john.doe@example.com');

        $this->assertEquals('j*******@e******.com', $masked);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_masks_phone_identifier()
    {
        $masked = $this->otpService->maskIdentifier('+237655332183');

        $this->assertEquals('+237*****2183', $masked);
    }

    /** @test */
    public function it_invalidates_otp()
    {
        $identifier = 'test@example.com';
        $otp = '123456';

        // Stocker l'OTP en cache
        $cacheKey = 'otp_'.md5($identifier);
        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        $result = $this->otpService->invalidateOtp($identifier);

        $this->assertTrue($result);
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_generates_reset_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $token = $this->otpService->generateResetToken('test@example.com');

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // SHA-256 hash length
    }
}

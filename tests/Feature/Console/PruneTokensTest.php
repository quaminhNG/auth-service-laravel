<?php

namespace Tests\Feature\Console;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PruneTokensTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_prune_command_deletes_expired_and_revoked_tokens()
    {
        $user = User::factory()->create();
        $validToken = RefreshToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(7), 
            'is_revoked' => false
        ]);
        $expiredToken = RefreshToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subDay(),
            'is_revoked' => false
        ]);
        $revokedToken = RefreshToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(7), 
            'is_revoked' => true
        ]);
        $this->artisan('app:cleanup-tokens')
                ->expectsOutput('Đã dọn dẹp token rác thành công!')
                ->assertExitCode(0);

        $this->assertDatabaseHas('refresh_tokens', ['id' => $validToken->id]);

        $this->assertDatabaseMissing('refresh_tokens', ['id' => $expiredToken->id]);
        $this->assertDatabaseMissing('refresh_tokens', ['id' => $revokedToken->id]);
    }
}

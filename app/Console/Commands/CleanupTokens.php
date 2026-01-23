<?php

namespace App\Console\Commands;

use App\Models\RefreshToken;
use Illuminate\Console\Command;

class CleanupTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        RefreshToken::where(
            'expires_at', '<', now())
            ->orWhere('is_revoked', true)
            ->delete();
        $this->info('Đã dọn dẹp token rác thành công!');
    }
}

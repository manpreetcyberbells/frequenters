<?php

namespace App\Jobs;

use App\Models\CollabPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireCollabPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $count = CollabPost::where('is_active', true)->where('expires_at', '<=', now())->update(['is_active' => false]);
        Log::info("ExpireCollabPostsJob: Deactivated {$count} expired collab posts.");
    }
}
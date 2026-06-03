<?php

namespace App\Events;

use App\Models\CollabPost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollabPostCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CollabPost $post
    ) {}
}
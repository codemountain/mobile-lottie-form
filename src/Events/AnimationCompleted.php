<?php

namespace CodeMountain\LottieForm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnimationCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $reason,
        public ?string $id = null
    ) {}
}

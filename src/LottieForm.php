<?php

namespace CodeMountain\LottieForm;

use Illuminate\Support\Str;

class LottieForm
{
    protected ?string $animationPath = null;

    protected string $backgroundColor = '#00000080';

    protected float $size = 0.4;

    protected string $position = 'center';

    protected int $fadeInDuration = 300;

    protected bool $autoClose = true;

    protected bool $looping = false;

    protected ?int $duration = null;

    protected bool $tapToDismiss = true;

    protected bool $fullScreen = false;

    protected ?string $id = null;

    /** @var array<string, string> */
    protected array $textFields = [];

    /**
     * Start building a Lottie animation overlay.
     *
     * @param  string  $animationPath  Filename of the .lottie file in resources/animations/
     */
    public function show(string $animationPath): self
    {
        $this->reset();
        $this->animationPath = $animationPath;
        $this->id = Str::uuid()->toString();

        return $this;
    }

    /**
     * Set the overlay background color (hex with optional alpha).
     */
    public function backgroundColor(string $color): self
    {
        $this->backgroundColor = $color;

        return $this;
    }

    /**
     * Set the animation size relative to screen width (0.1 - 1.0).
     */
    public function size(float $size): self
    {
        $this->size = max(0.1, min(1.0, $size));

        return $this;
    }

    /**
     * Set the animation position: 'center', 'top', or 'bottom'.
     */
    public function position(string $position): self
    {
        $this->position = in_array($position, ['center', 'top', 'bottom']) ? $position : 'center';

        return $this;
    }

    /**
     * Set the fade-in duration in milliseconds.
     */
    public function fadeInDuration(int $ms): self
    {
        $this->fadeInDuration = max(0, $ms);

        return $this;
    }

    /**
     * Auto-dismiss after a single play cycle.
     */
    public function autoClose(bool $autoClose = true): self
    {
        $this->autoClose = $autoClose;

        return $this;
    }

    /**
     * Loop the animation continuously.
     */
    public function looping(bool $looping = true): self
    {
        $this->looping = $looping;

        return $this;
    }

    /**
     * Auto-dismiss after duration in milliseconds.
     */
    public function duration(int $ms): self
    {
        $this->duration = $ms;

        return $this;
    }

    /**
     * Expand the animation to fill the entire screen.
     */
    public function fullScreen(bool $fullScreen = true): self
    {
        $this->fullScreen = $fullScreen;

        return $this;
    }

    /**
     * Allow tapping the overlay to dismiss.
     */
    public function tapToDismiss(bool $tapToDismiss = true): self
    {
        $this->tapToDismiss = $tapToDismiss;

        return $this;
    }

    /**
     * Replace a named text layer in the animation.
     *
     * Requires the .lottie file to contain a text layer with the given name.
     * Ask your designer to name text layers in After Effects (e.g. "title", "subtitle").
     */
    public function textField(string $layerName, string $value): self
    {
        $this->textFields[$layerName] = $value;

        return $this;
    }

    /**
     * Set a custom ID for tracking which animation completed.
     */
    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Present the animation overlay.
     */
    public function play(): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        nativephp_call('LottieForm.ShowAnimation', json_encode([
            'animationPath' => $this->animationPath,
            'backgroundColor' => $this->backgroundColor,
            'size' => $this->size,
            'position' => $this->position,
            'fadeInDuration' => $this->fadeInDuration,
            'autoClose' => $this->autoClose,
            'looping' => $this->looping,
            'duration' => $this->duration,
            'tapToDismiss' => $this->tapToDismiss,
            'fullScreen' => $this->fullScreen,
            'id' => $this->id,
            'textFields' => ! empty($this->textFields) ? $this->textFields : null,
        ]));

        $this->reset();
    }

    /**
     * Dismiss the currently visible animation overlay.
     */
    public function dismiss(): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        nativephp_call('LottieForm.Dismiss', '{}');
    }

    protected function reset(): void
    {
        $this->animationPath = null;
        $this->backgroundColor = '#00000080';
        $this->size = 0.4;
        $this->position = 'center';
        $this->fadeInDuration = 300;
        $this->autoClose = true;
        $this->looping = false;
        $this->duration = null;
        $this->tapToDismiss = true;
        $this->fullScreen = false;
        $this->id = null;
        $this->textFields = [];
    }
}

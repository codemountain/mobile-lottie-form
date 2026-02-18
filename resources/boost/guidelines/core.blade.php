## codemountain/mobile-lottie-form

A NativePHP Mobile plugin for displaying native Lottie animation overlays from PHP/Livewire.

### Installation

```bash
composer require codemountain/mobile-lottie-form
```

### PHP Usage (Livewire/Blade)

Use the `LottieForm` facade:

@verbatim
<code-snippet name="Using LottieForm Facade" lang="php">
use CodeMountain\LottieForm\Facades\LottieForm;
// Display a Lottie animation overlay with default settings
LottieForm::show('checked.lottie')->play();
// Customized animation
LottieForm::show('success.lottie')
    ->backgroundColor('#00000066')
    ->size(0.5)
    ->autoClose()
    ->tapToDismiss()
    ->duration(3000)
    ->id('report-success')
    ->play();
// Dismiss programmatically
LottieForm::dismiss();
</code-snippet>
@endverbatim

### Available Methods

- `LottieForm::show(string $animationPath)`: Start building an animation overlay (pass .lottie filename)
- `->backgroundColor(string $hex)`: Set overlay background (supports #RRGGBB and #RRGGBBAA)
- `->size(float $size)`: Animation size relative to screen (0.1-1.0)
- `->autoClose(bool $autoClose)`: Auto-dismiss after single play
- `->looping(bool $looping)`: Loop the animation continuously
- `->duration(int $ms)`: Auto-dismiss after milliseconds
- `->tapToDismiss(bool $tapToDismiss)`: Allow tap to dismiss
- `->id(string $id)`: Set tracking identifier
- `->play()`: Present the animation overlay
- `LottieForm::dismiss()`: Dismiss the current overlay

### Events

- `AnimationStarted`: Listen with `#[OnNative(AnimationStarted::class)]`
- `AnimationCompleted`: Listen with `#[OnNative(AnimationCompleted::class)]`

@verbatim
<code-snippet name="Listening for LottieForm Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use CodeMountain\LottieForm\Events\AnimationStarted;
use CodeMountain\LottieForm\Events\AnimationCompleted;

#[OnNative(AnimationStarted::class)]
public function handleAnimationStarted($animationPath, $id = null)
{
    // Handle the event
}

#[OnNative(AnimationCompleted::class)]
public function handleAnimationCompleted($reason, $id = null)
{
    // reason: "completed", "tapped", "timeout", or "programmatic"
}
</code-snippet>
@endverbatim

### JavaScript Usage (Vue/React/Inertia)

@verbatim
<code-snippet name="Using LottieForm in JavaScript" lang="javascript">
import { lottieForm } from '@codemountain/mobile-lottie-form';
// Show a success animation
await lottieForm.show('checked.lottie').play();
// Customized animation
await lottieForm.show('success.lottie')
    .backgroundColor('#00000066')
    .size(0.5)
    .autoClose()
    .duration(3000)
    .play();
// Dismiss programmatically
await lottieForm.dismiss();
</code-snippet>
@endverbatim

### Setup

Place `.lottie` animation files in your Laravel app's `resources/animations/` directory. They are automatically copied to native assets during the build process.

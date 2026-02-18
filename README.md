# LottieForm

A NativePHP Mobile plugin that displays native Lottie animation overlays from PHP/Livewire.

Show success checkmarks, celebration effects, loading spinners, or any `.lottie` animation as a fullscreen overlay on top of your app's WebView. Animations render natively on both iOS and Android with smooth fade-in, tap-to-dismiss, auto-close, and full control over size, position, and background color.

Drop your `.lottie` files in `resources/animations/`, call the fluent PHP API, and you're done.

## Installation

```bash
composer require codemountain/mobile-lottie-form
```

Register the plugin:

```bash
# Publish the plugins provider (first time only)
php artisan vendor:publish --tag=nativephp-plugins-provider

# Register the plugin
php artisan native:plugin:register codemountain/mobile-lottie-form

# Verify registration
php artisan native:plugin:list
```

## Publishing Animations

This plugin includes a bundled `checked.lottie` animation (a checkmark success animation). Publish it to your project:

```bash
# Publish animations only
php artisan vendor:publish --tag=lottie-form-animations

# Publish everything
php artisan vendor:publish --tag=lottie-form
```

This copies the bundled animations to `resources/animations/`.

## Adding Animations

Place your `.lottie` files in `resources/animations/` (alongside any published ones):

```
resources/
  animations/
    checked.lottie    ← published from plugin
    success.lottie    ← your own
    confetti.lottie   ← your own
```

All `.lottie` files in this directory are automatically copied to both iOS and Android builds at compile time.

### Custom Animations Directory

To use a different directory, set `LOTTIE_FORM_ANIMATIONS` in your `.env`:

```env
LOTTIE_FORM_ANIMATIONS=resources/lottie
```

Falls back to `resources/animations` if unset or the path doesn't exist.

## Usage

### PHP (Livewire/Blade)

```php
use CodeMountain\LottieForm\Facades\LottieForm;

// Simple — show and auto-dismiss after one play cycle
LottieForm::show('checked.lottie')->play();

// Customized
LottieForm::show('checked.lottie')
    ->backgroundColor('#F26E3680')
    ->size(0.6)
    ->position('center')
    ->fadeInDuration(900)
    ->autoClose()
    ->tapToDismiss()
    ->id('report-success')
    ->play();

// Looping animation with manual dismiss
LottieForm::show('loading.lottie')
    ->looping()
    ->autoClose(false)
    ->tapToDismiss(false)
    ->play();

// Dismiss programmatically
LottieForm::dismiss();
```

### JavaScript (Vue/React/Inertia)

```javascript
import { lottieForm } from '@codemountain/mobile-lottie-form';

// Simple
await lottieForm.show('checked.lottie').play();

// Customized
await lottieForm.show('checked.lottie')
    .backgroundColor('#F26E3680')
    .size(0.6)
    .position('center')
    .fadeInDuration(900)
    .autoClose()
    .tapToDismiss()
    .id('report-success')
    .play();

// Dismiss
await lottieForm.dismiss();
```

## Configuration Options

| Method | Default | Description |
|---|---|---|
| `backgroundColor(string)` | `#00000080` | Overlay background color (hex, supports alpha) |
| `size(float)` | `0.4` | Animation size relative to screen width (0.1 - 1.0) |
| `position(string)` | `center` | Vertical position: `center`, `top`, or `bottom` |
| `fadeInDuration(int)` | `300` | Fade-in duration in milliseconds |
| `autoClose(bool)` | `true` | Dismiss automatically after one play cycle |
| `looping(bool)` | `false` | Loop the animation continuously |
| `duration(int)` | `null` | Auto-dismiss after N milliseconds (independent of animation) |
| `tapToDismiss(bool)` | `true` | Allow tapping the overlay to dismiss |
| `id(string)` | auto-generated | Identifier returned in events to track which animation completed |

## Events

### AnimationStarted

Fired when the overlay appears and the animation begins playing.

```php
use Native\Mobile\Attributes\OnNative;
use CodeMountain\LottieForm\Events\AnimationStarted;

#[OnNative(AnimationStarted::class)]
public function handleAnimationStarted(string $animationPath, ?string $id = null): void
{
    // Overlay is now visible
}
```

### AnimationCompleted

Fired when the animation is dismissed for any reason.

```php
use Native\Mobile\Attributes\OnNative;
use CodeMountain\LottieForm\Events\AnimationCompleted;

#[OnNative(AnimationCompleted::class)]
public function handleAnimationCompleted(string $reason, ?string $id = null): void
{
    // $reason: 'completed', 'tapped', 'timeout', 'programmatic', or 'error'
}
```

## Example: Success Animation After Form Submit

```php
use CodeMountain\LottieForm\Events\AnimationCompleted;
use CodeMountain\LottieForm\Facades\LottieForm;

public function submit(): void
{
    // ... save data ...

    LottieForm::show('checked.lottie')
        ->backgroundColor('#F26E3680')
        ->size(0.6)
        ->fadeInDuration(600)
        ->autoClose()
        ->id('form-success')
        ->play();
}

#[OnNative(AnimationCompleted::class)]
public function handleAnimationCompleted(string $reason, ?string $id = null): void
{
    if ($id === 'form-success') {
        $this->redirect(route('home'), navigate: false);
    }
}
```

## Native Dependencies

| Platform | Package | Version |
|---|---|---|
| iOS | [lottie-spm](https://github.com/airbnb/lottie-spm) | 4.6.0 |
| Android | [lottie-compose](https://github.com/airbnb/lottie-android) | 6.7.1 |

## License

MIT

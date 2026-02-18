<?php

namespace CodeMountain\LottieForm\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \CodeMountain\LottieForm\LottieForm show(string $animationPath)
 * @method static \CodeMountain\LottieForm\LottieForm backgroundColor(string $color)
 * @method static \CodeMountain\LottieForm\LottieForm size(float $size)
 * @method static \CodeMountain\LottieForm\LottieForm position(string $position)
 * @method static \CodeMountain\LottieForm\LottieForm fadeInDuration(int $ms)
 * @method static \CodeMountain\LottieForm\LottieForm autoClose(bool $autoClose = true)
 * @method static \CodeMountain\LottieForm\LottieForm looping(bool $looping = true)
 * @method static \CodeMountain\LottieForm\LottieForm duration(int $ms)
 * @method static \CodeMountain\LottieForm\LottieForm tapToDismiss(bool $tapToDismiss = true)
 * @method static \CodeMountain\LottieForm\LottieForm id(string $id)
 * @method static void play()
 * @method static void dismiss()
 *
 * @see \CodeMountain\LottieForm\LottieForm
 */
class LottieForm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CodeMountain\LottieForm\LottieForm::class;
    }
}

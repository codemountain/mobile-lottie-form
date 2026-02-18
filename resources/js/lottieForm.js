/**
 * LottieForm Plugin for NativePHP Mobile
 *
 * @example
 * import { lottieForm } from '@codemountain/mobile-lottie-form';
 *
 * // Show a success animation
 * await lottieForm.show('checked.lottie').play();
 *
 * // Dismiss programmatically
 * await lottieForm.dismiss();
 */

const baseUrl = '/_native/api/call';

async function bridgeCall(method, params = {}) {
    const response = await fetch(baseUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ method, params })
    });

    const result = await response.json();

    if (result.status === 'error') {
        throw new Error(result.message || 'Native call failed');
    }

    const nativeResponse = result.data;
    if (nativeResponse && nativeResponse.data !== undefined) {
        return nativeResponse.data;
    }

    return nativeResponse;
}

function createBuilder(animationPath) {
    const config = {
        animationPath,
        backgroundColor: '#00000080',
        size: 0.4,
        position: 'center',
        fadeInDuration: 300,
        autoClose: true,
        looping: false,
        duration: null,
        tapToDismiss: true,
        id: crypto.randomUUID(),
    };

    return {
        backgroundColor(color) {
            config.backgroundColor = color;
            return this;
        },
        size(size) {
            config.size = Math.max(0.1, Math.min(1.0, size));
            return this;
        },
        position(pos) {
            config.position = ['center', 'top', 'bottom'].includes(pos) ? pos : 'center';
            return this;
        },
        fadeInDuration(ms) {
            config.fadeInDuration = Math.max(0, ms);
            return this;
        },
        autoClose(value = true) {
            config.autoClose = value;
            return this;
        },
        looping(value = true) {
            config.looping = value;
            return this;
        },
        duration(ms) {
            config.duration = ms;
            return this;
        },
        tapToDismiss(value = true) {
            config.tapToDismiss = value;
            return this;
        },
        id(id) {
            config.id = id;
            return this;
        },
        async play() {
            return bridgeCall('LottieForm.ShowAnimation', config);
        }
    };
}

export function show(animationPath) {
    return createBuilder(animationPath);
}

export async function dismiss() {
    return bridgeCall('LottieForm.Dismiss', {});
}

export const lottieForm = {
    show,
    dismiss
};

export default lottieForm;

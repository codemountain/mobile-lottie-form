package ca.codemountain.plugins.lottieform

import android.animation.Animator
import android.animation.AnimatorListenerAdapter
import android.graphics.Color
import android.graphics.Typeface
import android.os.Handler
import android.os.Looper
import android.util.Log
import android.view.Gravity
import android.view.ViewGroup
import android.widget.FrameLayout
import androidx.fragment.app.FragmentActivity
import com.airbnb.lottie.FontAssetDelegate
import com.airbnb.lottie.LottieAnimationView
import com.airbnb.lottie.LottieComposition
import com.airbnb.lottie.LottieCompositionFactory
import com.airbnb.lottie.LottieDrawable
import com.airbnb.lottie.LottieListener
import com.airbnb.lottie.TextDelegate
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject

object LottieFormFunctions {

    private const val TAG = "LottieFormFunctions"
    private const val FADE_OUT_DURATION = 200L

    private var currentOverlay: FrameLayout? = null
    private var currentId: String? = null
    private var dismissHandler: Handler? = null
    private var dismissRunnable: Runnable? = null
    private var isDismissing = false

    class ShowAnimation(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val animationPath = parameters["animationPath"] as? String
                ?: return BridgeResponse.error(BridgeError.ExecutionFailed("animationPath is required"))

            val backgroundColor = parameters["backgroundColor"] as? String ?: "#00000080"
            val size = (parameters["size"] as? Number)?.toDouble() ?: 0.4
            val position = parameters["position"] as? String ?: "center"
            val fadeInDuration = (parameters["fadeInDuration"] as? Number)?.toLong() ?: 300L
            val autoClose = parameters["autoClose"] as? Boolean ?: true
            val looping = parameters["looping"] as? Boolean ?: false
            val duration = (parameters["duration"] as? Number)?.toLong()
            val tapToDismiss = parameters["tapToDismiss"] as? Boolean ?: true
            val fullScreen = parameters["fullScreen"] as? Boolean ?: false
            val id = parameters["id"] as? String ?: ""

            @Suppress("UNCHECKED_CAST")
            val textFields = parameters["textFields"] as? Map<String, String>

            val clampedSize = size.coerceIn(0.1, 1.0)
            val assetPath = "animations/$animationPath"

            Log.d(TAG, "ShowAnimation: path=$assetPath, id=$id, size=$clampedSize, position=$position, fadeIn=$fadeInDuration")

            // Verify asset exists before attempting to load
            val assetExists = try {
                activity.assets.open(assetPath).use { true }
            } catch (e: Exception) {
                false
            }

            if (!assetExists) {
                Log.e(TAG, "ShowAnimation: Asset not found: $assetPath")
                return BridgeResponse.error(BridgeError.ExecutionFailed("Animation file not found: $assetPath"))
            }

            activity.runOnUiThread {
                removeCurrentOverlaySilently()

                currentId = id
                isDismissing = false

                val decorView = activity.window.decorView as? ViewGroup
                if (decorView == null) {
                    Log.e(TAG, "ShowAnimation: Could not get decor view")
                    return@runOnUiThread
                }

                // Resolve gravity from position param
                val gravity = when (position) {
                    "top" -> Gravity.CENTER_HORIZONTAL or Gravity.TOP
                    "bottom" -> Gravity.CENTER_HORIZONTAL or Gravity.BOTTOM
                    else -> Gravity.CENTER
                }

                val overlay = FrameLayout(activity).apply {
                    layoutParams = FrameLayout.LayoutParams(
                        ViewGroup.LayoutParams.MATCH_PARENT,
                        ViewGroup.LayoutParams.MATCH_PARENT
                    )
                    setBackgroundColor(parseHexColor(backgroundColor))
                    alpha = 0f
                    isClickable = true
                    isFocusable = true
                }

                val animationView = LottieAnimationView(activity).apply {
                    layoutParams = if (fullScreen) {
                        FrameLayout.LayoutParams(
                            ViewGroup.LayoutParams.MATCH_PARENT,
                            ViewGroup.LayoutParams.MATCH_PARENT
                        )
                    } else {
                        val screenWidth = activity.resources.displayMetrics.widthPixels
                        val animSize = (screenWidth * clampedSize).toInt()
                        FrameLayout.LayoutParams(animSize, animSize).apply {
                            this.gravity = gravity
                        }
                    }
                    repeatCount = if (looping) LottieDrawable.INFINITE else 0
                }

                // Load composition async with failure handling (prevents crash)
                val lottieTask = LottieCompositionFactory.fromAsset(activity, assetPath)

                // Fallback to system default font when animation references missing fonts
                animationView.setFontAssetDelegate(object : FontAssetDelegate() {
                    override fun fetchFont(fontFamily: String): Typeface {
                        return Typeface.DEFAULT
                    }
                })

                lottieTask.addListener(object : LottieListener<LottieComposition> {
                    override fun onResult(composition: LottieComposition) {
                        animationView.setComposition(composition)

                        // Apply dynamic text fields if provided
                        if (!textFields.isNullOrEmpty()) {
                            val textDelegate = TextDelegate(animationView)
                            for ((layerName, value) in textFields) {
                                textDelegate.setText(layerName, value)
                            }
                            animationView.setTextDelegate(textDelegate)
                        }

                        // Start playback after fade-in
                        overlay.animate()
                            .alpha(1f)
                            .setDuration(fadeInDuration)
                            .setListener(object : AnimatorListenerAdapter() {
                                override fun onAnimationEnd(animation: Animator) {
                                    animationView.playAnimation()

                                    val payload = JSONObject().apply {
                                        put("animationPath", animationPath)
                                        put("id", id)
                                    }
                                    NativeActionCoordinator.dispatchEvent(
                                        activity,
                                        "CodeMountain\\LottieForm\\Events\\AnimationStarted",
                                        payload.toString()
                                    )
                                }
                            })
                            .start()
                    }
                })

                lottieTask.addFailureListener(object : LottieListener<Throwable> {
                    override fun onResult(error: Throwable) {
                        Log.e(TAG, "ShowAnimation: Failed to parse composition: ${error.message}")
                        // Clean up overlay on failure instead of crashing
                        removeCurrentOverlaySilently()

                        val payload = JSONObject().apply {
                            put("reason", "error")
                            put("id", id)
                        }
                        NativeActionCoordinator.dispatchEvent(
                            activity,
                            "CodeMountain\\LottieForm\\Events\\AnimationCompleted",
                            payload.toString()
                        )
                    }
                })

                overlay.addView(animationView)

                if (tapToDismiss) {
                    overlay.setOnClickListener {
                        dismissOverlay(activity, "tapped")
                    }
                }

                if (autoClose && !looping) {
                    animationView.addAnimatorListener(object : AnimatorListenerAdapter() {
                        override fun onAnimationEnd(animation: Animator) {
                            dismissOverlay(activity, "completed")
                        }
                    })
                }

                currentOverlay = overlay
                decorView.addView(overlay)

                // Duration-based auto-dismiss timer
                if (duration != null && duration > 0) {
                    val handler = Handler(Looper.getMainLooper())
                    val runnable = Runnable {
                        dismissOverlay(activity, "timeout")
                    }
                    handler.postDelayed(runnable, duration)
                    dismissHandler = handler
                    dismissRunnable = runnable
                }
            }

            return BridgeResponse.success(mapOf(
                "status" to "animation_shown",
                "id" to id
            ))
        }
    }

    class Dismiss(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            if (currentOverlay == null) {
                return BridgeResponse.success(mapOf("status" to "no_overlay"))
            }

            activity.runOnUiThread {
                dismissOverlay(activity, "programmatic")
            }

            return BridgeResponse.success(mapOf(
                "status" to "dismissed"
            ))
        }
    }

    private fun parseHexColor(hex: String): Int {
        return try {
            val cleaned = hex.removePrefix("#")
            when (cleaned.length) {
                6 -> Color.parseColor("#$cleaned")
                8 -> {
                    val r = cleaned.substring(0, 2)
                    val g = cleaned.substring(2, 4)
                    val b = cleaned.substring(4, 6)
                    val a = cleaned.substring(6, 8)
                    Color.parseColor("#$a$r$g$b")
                }
                else -> Color.parseColor("#80000000")
            }
        } catch (e: Exception) {
            Color.parseColor("#80000000")
        }
    }

    private fun dismissOverlay(activity: FragmentActivity, reason: String) {
        val overlay = currentOverlay ?: return
        if (isDismissing) return
        isDismissing = true

        val id = currentId ?: ""

        cancelDurationTimer()

        val animationView = findLottieView(overlay)
        animationView?.cancelAnimation()

        overlay.animate()
            .alpha(0f)
            .setDuration(FADE_OUT_DURATION)
            .setListener(object : AnimatorListenerAdapter() {
                override fun onAnimationEnd(animation: Animator) {
                    (overlay.parent as? ViewGroup)?.removeView(overlay)

                    currentOverlay = null
                    currentId = null
                    isDismissing = false

                    val payload = JSONObject().apply {
                        put("reason", reason)
                        put("id", id)
                    }
                    NativeActionCoordinator.dispatchEvent(
                        activity,
                        "CodeMountain\\LottieForm\\Events\\AnimationCompleted",
                        payload.toString()
                    )
                }
            })
            .start()
    }

    private fun removeCurrentOverlaySilently() {
        val overlay = currentOverlay ?: return
        cancelDurationTimer()
        findLottieView(overlay)?.cancelAnimation()
        overlay.animate().cancel()
        (overlay.parent as? ViewGroup)?.removeView(overlay)
        currentOverlay = null
        currentId = null
        isDismissing = false
    }

    private fun cancelDurationTimer() {
        dismissRunnable?.let { dismissHandler?.removeCallbacks(it) }
        dismissHandler = null
        dismissRunnable = null
    }

    private fun findLottieView(container: ViewGroup): LottieAnimationView? {
        for (i in 0 until container.childCount) {
            val child = container.getChildAt(i)
            if (child is LottieAnimationView) return child
        }
        return null
    }
}

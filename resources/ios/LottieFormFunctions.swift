import UIKit
import Lottie

enum LottieFormFunctions {

    // MARK: - State Management

    static var overlayWindow: UIWindow?
    static var currentId: String?
    static var dismissTimer: DispatchWorkItem?

    // MARK: - ShowAnimation

    class ShowAnimation: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let animationPath = parameters["animationPath"] as? String else {
                return BridgeResponse.error(code: "INVALID_PARAMETERS", message: "animationPath is required")
            }

            let backgroundColor = parameters["backgroundColor"] as? String ?? "#00000080"
            let size = parameters["size"] as? Double ?? 0.4
            let position = parameters["position"] as? String ?? "center"
            let fadeInDuration = parameters["fadeInDuration"] as? Int ?? 300
            let autoClose = parameters["autoClose"] as? Bool ?? true
            let looping = parameters["looping"] as? Bool ?? false
            let duration = parameters["duration"] as? Int
            let tapToDismiss = parameters["tapToDismiss"] as? Bool ?? true
            let id = parameters["id"] as? String ?? UUID().uuidString
            let textFields = parameters["textFields"] as? [String: String]

            let clampedSize = max(0.1, min(1.0, size))

            // Strip .lottie extension — DotLottieFile.named() expects the bare name
            let animationName: String
            if animationPath.hasSuffix(".lottie") {
                animationName = String(animationPath.dropLast(7))
            } else {
                animationName = animationPath
            }

            LottieFormFunctions.currentId = id

            DispatchQueue.main.async {
                LottieFormFunctions.teardownOverlayImmediate()
                LottieFormFunctions.currentId = id

                LottieFormFunctions.presentOverlay(
                    animationName: animationName,
                    animationPath: animationPath,
                    backgroundColor: backgroundColor,
                    size: clampedSize,
                    position: position,
                    fadeInDuration: fadeInDuration,
                    autoClose: autoClose,
                    looping: looping,
                    duration: duration,
                    tapToDismiss: tapToDismiss,
                    textFields: textFields,
                    id: id
                )
            }

            return BridgeResponse.success(data: [
                "status": "presenting",
                "id": id
            ])
        }
    }

    // MARK: - Dismiss

    class Dismiss: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard LottieFormFunctions.overlayWindow != nil,
                  let id = LottieFormFunctions.currentId else {
                return BridgeResponse.success(data: ["status": "no_overlay"])
            }

            DispatchQueue.main.async {
                LottieFormFunctions.dismissOverlay(reason: "programmatic", id: id)
            }

            return BridgeResponse.success(data: [
                "status": "dismissed",
                "id": id
            ])
        }
    }

    // MARK: - Overlay Presentation

    static func presentOverlay(
        animationName: String,
        animationPath: String,
        backgroundColor: String,
        size: Double,
        position: String,
        fadeInDuration: Int,
        autoClose: Bool,
        looping: Bool,
        duration: Int?,
        tapToDismiss: Bool,
        textFields: [String: String]?,
        id: String
    ) {
        guard let windowScene = UIApplication.shared.connectedScenes
            .compactMap({ $0 as? UIWindowScene })
            .first else {
            LaravelBridge.shared.send?(
                "CodeMountain\\LottieForm\\Events\\AnimationCompleted",
                ["reason": "error", "id": id]
            )
            return
        }

        let window = UIWindow(windowScene: windowScene)
        window.windowLevel = .alert + 100
        window.backgroundColor = .clear

        let overlayVC = LottieOverlayViewController()
        overlayVC.animationName = animationName
        overlayVC.animationPath = animationPath
        overlayVC.overlayBackgroundColor = hexToUIColor(backgroundColor)
        overlayVC.animationSize = size
        overlayVC.animationPosition = position
        overlayVC.fadeInMs = fadeInDuration
        overlayVC.autoClose = autoClose
        overlayVC.looping = looping
        overlayVC.duration = duration
        overlayVC.tapToDismiss = tapToDismiss
        overlayVC.textFields = textFields
        overlayVC.animationId = id

        window.rootViewController = overlayVC
        window.makeKeyAndVisible()
        window.alpha = 0

        overlayWindow = window

        let fadeSeconds = Double(fadeInDuration) / 1000.0
        UIView.animate(withDuration: fadeSeconds) {
            window.alpha = 1.0
        } completion: { _ in
            LaravelBridge.shared.send?(
                "CodeMountain\\LottieForm\\Events\\AnimationStarted",
                ["animationPath": animationPath, "id": id]
            )
        }

        if let duration = duration, duration > 0 {
            let timer = DispatchWorkItem {
                guard LottieFormFunctions.currentId == id else { return }
                LottieFormFunctions.dismissOverlay(reason: "timeout", id: id)
            }
            dismissTimer = timer
            DispatchQueue.main.asyncAfter(
                deadline: .now() + .milliseconds(duration),
                execute: timer
            )
        }
    }

    // MARK: - Dismiss Helpers

    static func dismissOverlay(reason: String, id: String) {
        dismissTimer?.cancel()
        dismissTimer = nil

        guard let window = overlayWindow else { return }

        UIView.animate(withDuration: 0.2, animations: {
            window.alpha = 0
        }, completion: { _ in
            window.isHidden = true
            window.rootViewController = nil
            overlayWindow = nil
            currentId = nil

            LaravelBridge.shared.send?(
                "CodeMountain\\LottieForm\\Events\\AnimationCompleted",
                ["reason": reason, "id": id]
            )
        })
    }

    static func teardownOverlayImmediate() {
        dismissTimer?.cancel()
        dismissTimer = nil

        if let window = overlayWindow {
            window.isHidden = true
            window.rootViewController = nil
            overlayWindow = nil
        }

        currentId = nil
    }

    // MARK: - Hex Color Parsing

    static func hexToUIColor(_ hex: String) -> UIColor {
        var hexString = hex.trimmingCharacters(in: .whitespacesAndNewlines)
        if hexString.hasPrefix("#") {
            hexString.removeFirst()
        }

        var r: CGFloat = 0
        var g: CGFloat = 0
        var b: CGFloat = 0
        var a: CGFloat = 1.0

        let scanner = Scanner(string: hexString)
        var hexNumber: UInt64 = 0

        if scanner.scanHexInt64(&hexNumber) {
            switch hexString.count {
            case 6:
                r = CGFloat((hexNumber & 0xFF0000) >> 16) / 255.0
                g = CGFloat((hexNumber & 0x00FF00) >> 8) / 255.0
                b = CGFloat(hexNumber & 0x0000FF) / 255.0
            case 8:
                r = CGFloat((hexNumber & 0xFF000000) >> 24) / 255.0
                g = CGFloat((hexNumber & 0x00FF0000) >> 16) / 255.0
                b = CGFloat((hexNumber & 0x0000FF00) >> 8) / 255.0
                a = CGFloat(hexNumber & 0x000000FF) / 255.0
            default:
                break
            }
        }

        return UIColor(red: r, green: g, blue: b, alpha: a)
    }
}

// MARK: - Lottie Overlay View Controller

class LottieOverlayViewController: UIViewController {

    var animationName: String = ""
    var animationPath: String = ""
    var overlayBackgroundColor: UIColor = UIColor.black.withAlphaComponent(0.5)
    var animationSize: Double = 0.4
    var animationPosition: String = "center"
    var fadeInMs: Int = 300
    var autoClose: Bool = true
    var looping: Bool = false
    var duration: Int?
    var tapToDismiss: Bool = true
    var textFields: [String: String]?
    var animationId: String = ""

    private var animationView: LottieAnimationView?

    override func viewDidLoad() {
        super.viewDidLoad()

        view.backgroundColor = overlayBackgroundColor

        if tapToDismiss {
            let tapGesture = UITapGestureRecognizer(target: self, action: #selector(handleTap))
            view.addGestureRecognizer(tapGesture)
        }

        loadAndPlayAnimation()
    }

    private func loadAndPlayAnimation() {
        // Use DotLottieFile.named() — same pattern as nativephp-loader
        // This searches the entire bundle without needing a specific subdirectory,
        // which handles iOS bundle flattening where resources lose their directory structure
        Task {
            do {
                let dotLottieFile = try await DotLottieFile.named(animationName)
                await MainActor.run {
                    self.setupAnimationView(with: dotLottieFile)
                }
            } catch {
                print("LottieForm: Failed to load .lottie file '\(animationName)': \(error.localizedDescription)")
                await MainActor.run {
                    LottieFormFunctions.dismissOverlay(reason: "error", id: self.animationId)
                }
            }
        }
    }

    private func setupAnimationView(with dotLottieFile: DotLottieFile) {
        let lottieView = LottieAnimationView()
        lottieView.loadAnimation(from: dotLottieFile)
        lottieView.contentMode = .scaleAspectFit
        lottieView.backgroundBehavior = .pauseAndRestore
        lottieView.translatesAutoresizingMaskIntoConstraints = false
        lottieView.loopMode = looping ? .loop : .playOnce

        // Apply dynamic text fields if provided
        if let textFields = textFields, !textFields.isEmpty {
            lottieView.textProvider = DictionaryTextProvider(textFields)
        }

        view.addSubview(lottieView)

        let screenWidth = UIScreen.main.bounds.width
        let animationDimension = screenWidth * CGFloat(animationSize)

        // Horizontal: always centered
        lottieView.centerXAnchor.constraint(equalTo: view.centerXAnchor).isActive = true
        lottieView.widthAnchor.constraint(equalToConstant: animationDimension).isActive = true
        lottieView.heightAnchor.constraint(equalToConstant: animationDimension).isActive = true

        // Vertical: based on position
        switch animationPosition {
        case "top":
            lottieView.topAnchor.constraint(equalTo: view.safeAreaLayoutGuide.topAnchor, constant: 40).isActive = true
        case "bottom":
            lottieView.bottomAnchor.constraint(equalTo: view.safeAreaLayoutGuide.bottomAnchor, constant: -40).isActive = true
        default:
            lottieView.centerYAnchor.constraint(equalTo: view.centerYAnchor).isActive = true
        }

        self.animationView = lottieView

        lottieView.play { [weak self] completed in
            guard let self = self, completed else { return }

            if self.autoClose && !self.looping {
                let id = self.animationId
                DispatchQueue.main.async {
                    LottieFormFunctions.dismissOverlay(reason: "completed", id: id)
                }
            }
        }
    }

    @objc private func handleTap() {
        guard tapToDismiss else { return }
        LottieFormFunctions.dismissOverlay(reason: "tapped", id: animationId)
    }
}

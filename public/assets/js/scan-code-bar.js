// Prevent duplicate declaration by checking if the module already exists
if (typeof window.BarcodeScannerModule === "undefined") {
    console.log("Initializing BarcodeScannerModule");

    window.BarcodeScannerModule = (function () {
        let isProcessing = false;
        let lastBarcode = null;
        let lastScanTime = 0;

        function createScannerUI() {
            console.log("Creating scanner UI");
            const scannerContainer = document.createElement("div");
            scannerContainer.id = "scanner-container";
            scannerContainer.style.position = "fixed";
            scannerContainer.style.top = "0";
            scannerContainer.style.left = "0";
            scannerContainer.style.width = "100%";
            scannerContainer.style.height = "100%";
            scannerContainer.style.zIndex = "9999";
            scannerContainer.style.background = "rgba(0,0,0,0.8)";
            scannerContainer.style.display = "flex";
            scannerContainer.style.flexDirection = "column";
            scannerContainer.style.alignItems = "center";
            scannerContainer.style.justifyContent = "center";

            const scannerTitle = document.createElement("h3");
            scannerTitle.textContent = "Scanner un code-barres";
            scannerTitle.style.color = "white";
            scannerTitle.style.marginBottom = "10px";
            scannerContainer.appendChild(scannerTitle);

            const scannerViewport = document.createElement("div");
            scannerViewport.id = "scanner-viewport";
            scannerViewport.style.width = "100%";
            scannerViewport.style.maxWidth = "400px";
            scannerViewport.style.height = "300px";
            scannerViewport.style.border = "2px solid white";
            scannerViewport.style.borderRadius = "8px";
            scannerViewport.style.overflow = "hidden";
            scannerContainer.appendChild(scannerViewport);

            const closeButton = document.createElement("button");
            closeButton.textContent = "✖️ Annuler";
            closeButton.style.marginTop = "15px";
            closeButton.style.padding = "8px 20px";
            closeButton.style.borderRadius = "4px";
            closeButton.style.background = "#dc3545";
            closeButton.style.color = "white";
            closeButton.style.border = "none";
            closeButton.style.cursor = "pointer";
            closeButton.addEventListener("click", function () {
                console.log(
                    "Close button clicked, stopping Quagga and removing scanner UI",
                );
                Quagga.stop();
                removeScannerFromDOM();
            });
            scannerContainer.appendChild(closeButton);

            return { container: scannerContainer, viewport: scannerViewport };
        }

        function removeScannerFromDOM() {
            console.log("Attempting to remove scanner from DOM");
            const container = document.getElementById("scanner-container");
            if (container && document.body.contains(container)) {
                console.log("Scanner container found, removing it");
                document.body.removeChild(container);
                console.log("Scanner container removed successfully");
            } else {
                console.log("Scanner container not found or not in DOM");
            }
        }

        function handleBarcodeDetection(result) {
            console.log("Barcode detected:", result.codeResult.code);
            const barcode = result.codeResult.code;
            const currentTime = new Date().getTime();

            if (
                isProcessing ||
                (lastBarcode === barcode && currentTime - lastScanTime < 2000)
            ) {
                console.log(
                    "Ignoring duplicate scan or processing in progress",
                );
                return;
            }

            console.log("Processing barcode:", barcode);
            isProcessing = true;
            lastBarcode = barcode;
            lastScanTime = currentTime;

            if (barcode) {
                // Stop Quagga first
                console.log("Stopping Quagga");
                try {
                    Quagga.stop();
                    console.log("Quagga stopped successfully");
                } catch (e) {
                    console.error("Error stopping Quagga:", e);
                }

                // Then remove the UI
                console.log("Removing scanner UI");
                removeScannerFromDOM();

                try {
                    console.log("Dispatching barcode to Livewire");
                    Livewire.dispatch("barcode-scanned", [
                        {
                            barcode: barcode,
                        },
                    ]);
                    console.log("Dispatch complete");
                } catch (error) {
                    console.error("Error dispatching to Livewire:", error);
                    alert(
                        "Erreur lors du traitement du code-barres: " +
                            error.message,
                    );
                } finally {
                    console.log("Resetting processing flag");
                    setTimeout(() => {
                        isProcessing = false;
                        console.log("Processing flag reset");
                    }, 500);
                }
            } else {
                console.log("No valid barcode detected");
                isProcessing = false;
            }
        }

        function initializeQuagga(scannerViewport) {
            console.log("Initializing Quagga");
            const quaggaConfig = {
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: scannerViewport,
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: "environment",
                    },
                },
                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "code_39_reader",
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",
                        "upc_e_reader",
                        "i2of5_reader",
                    ],
                    debug: {
                        showCanvas: true,
                        showPatches: true,
                        showFoundPatches: true,
                        showSkeleton: true,
                        showLabels: true,
                        showPatchLabels: true,
                        showRemainingPatchLabels: true,
                        boxFromPatches: {
                            showTransformed: true,
                            showTransformedBox: true,
                            showBB: true,
                        },
                    },
                },
                locate: true,
            };

            Quagga.init(quaggaConfig, function (err) {
                if (err) {
                    console.error("Error initializing Quagga:", err);
                    removeScannerFromDOM();
                    return;
                }
                console.log("Quagga initialized successfully, starting Quagga");
                Quagga.start();
            });

            Quagga.onDetected(handleBarcodeDetection);
        }

        return {
            init: function () {
                console.log("Scanner initialization started");
                const ui = createScannerUI();
                document.body.appendChild(ui.container);
                console.log("Scanner UI added to DOM");
                initializeQuagga(ui.viewport);
            },
        };
    })();

    // Define the global function that's called from the blade file
    window.initBarcodeScanner = function () {
        console.log("initBarcodeScanner called");
        window.BarcodeScannerModule.init();
    };
} else {
    console.log("BarcodeScannerModule already exists, not reinitializing");
}

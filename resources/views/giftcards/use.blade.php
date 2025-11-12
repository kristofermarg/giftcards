<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white shadow rounded-xl px-6 py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Nýta gjafakort</h1>
                <p class="mt-2 text-gray-600">
                    Sláðu inn kóðann á gjafakortinu og upphæðina sem viðskiptavinurinn vill nýta. Upphæðin er dregin strax frá og nýja inneignin sýnd hér að neðan.
                </p>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-lg bg-green-50 text-green-800 border border-green-100 px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-red-50 text-red-800 border border-red-100 px-4 py-3">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('giftcards.use.store') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="giftcard-code" class="block text-sm font-semibold text-gray-700 mb-1">
                        Gjafakortakóði
                    </label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input
                            id="giftcard-code"
                            type="text"
                            name="code"
                            inputmode="text"
                            autocomplete="off"
                            value="{{ old('code', $result['code'] ?? '') }}"
                            class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3 font-mono uppercase tracking-wider"
                            placeholder="Skannaðu eða sláðu inn kóðann"
                            required
                        >
                        <button
                            type="button"
                            id="toggle-scanner"
                            class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-indigo-700 font-semibold hover:bg-indigo-100 transition"
                        >
                            Skannaðu kóða
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Kóðar eru ekki hástafa- eða lágstafasæknir. Skönnun er valfrjáls—þú getur alltaf slegið kóðann inn handvirkt.
                    </p>
                </div>

                <div id="scanner-panel" class="hidden rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-4">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div>
                            <p class="text-sm font-semibold text-indigo-900">Bein skönnun</p>
                            <p class="text-xs text-indigo-700">Leyfðu aðgang að myndavélinni til að skanna strikamerki á síma eða spjaldtölvum.</p>
                        </div>
                        <button
                            type="button"
                            id="close-scanner"
                            class="text-indigo-700 text-sm font-medium hover:underline"
                        >
                            Hætta við skönnun
                        </button>
                    </div>
                    <div id="scanner-view" class="aspect-video rounded-lg bg-black overflow-hidden relative">
                        <div id="scanner-overlay" class="absolute inset-0 flex items-center justify-center">
                            <p class="text-sm text-white/70">Bíður eftir myndavél...</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-indigo-800">
                        Einugni stuðningur er við skönnun með vefmyndavélum á tækjum með HTTPS-tengingu
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="giftcard-amount" class="block text-sm font-semibold text-gray-700 mb-1">
                            Upphæð
                        </label>
                        <input
                            id="giftcard-amount"
                            name="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            value="{{ old('amount') }}"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                            placeholder="0.00"
                            required
                        >
                        <p class="mt-2 text-sm text-gray-500">
                            Upphæðin er tekin af gjafakortinu í gjaldmiðli þess (sýnt eftir að upphæð hefur verið dregin af).
                        </p>
                    </div>
                    <div>
                        <label for="giftcard-reference" class="block text-sm font-semibold text-gray-700 mb-1">
                            Tilvísun (valfrjálst)
                        </label>
                        <input
                            id="giftcard-reference"
                            name="reference"
                            type="text"
                            value="{{ old('reference') }}"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                            placeholder="e.g. Order #123"
                        >
                        <p class="mt-2 text-sm text-gray-500">
                            Hentugt til að tengja nýtinguna aftur við pöntun eða kassakvittun.
                        </p>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg bg-indigo-600 px-6 py-3 text-white font-semibold hover:bg-indigo-500 transition"
                    >
                        Nýta upphæð
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="mt-10 border-t border-gray-100 pt-6">
                    <h2 class="text-xl font-semibold text-gray-900">Nýjasta aðgerð</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border border-gray-100 px-4 py-3 bg-gray-50">
                            <dt class="text-sm text-gray-500">Gjafakortakóði</dt>
                            <dd class="text-lg font-semibold text-gray-900 font-mono tracking-wider">{{ $result['code'] }}</dd>
                        </div>
                        <div class="rounded-lg border border-gray-100 px-4 py-3 bg-gray-50">
                            <dt class="text-sm text-gray-500">Upphæð sem nýtt var</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $result['amount_text'] }}</dd>
                        </div>
                        <div class="rounded-lg border border-gray-100 px-4 py-3 bg-gray-50">
                            <dt class="text-sm text-gray-500">Inneign</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $result['balance_text'] }}</dd>
                        </div>
                        @if (!empty($result['reference']))
                            <div class="rounded-lg border border-gray-100 px-4 py-3 bg-gray-50">
                                <dt class="text-sm text-gray-500">Tilvísun</dt>
                                <dd class="text-lg font-semibold text-gray-900">{{ $result['reference'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('toggle-scanner');
            const closeButton = document.getElementById('close-scanner');
            const panel = document.getElementById('scanner-panel');
            const scannerView = document.getElementById('scanner-view');
            const overlay = document.getElementById('scanner-overlay');
            const codeInput = document.getElementById('giftcard-code');
            const defaultButtonLabel = toggleButton?.textContent ?? 'Scan code';

            const nativeSupported = 'BarcodeDetector' in window;
            const barcodeDetector = nativeSupported
                ? new BarcodeDetector({
                    formats: [
                        'code_128',
                        'code_39',
                        'code_39_vin',
                        'ean_8',
                        'ean_13',
                        'upc_a',
                        'upc_e',
                    ],
                })
                : null;

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            let videoEl = null;
            let mediaStream = null;
            let detectionFrame = null;
            let detectionActive = false;

            let quaggaLoader = null;
            let quaggaHandler = null;
            let quaggaActive = false;

            let activeMode = null; // 'native' | 'quagga' | null

            const ensureVideoElement = () => {
                if (videoEl || !scannerView) {
                    return videoEl;
                }
                videoEl = document.createElement('video');
                videoEl.setAttribute('playsinline', '');
                videoEl.setAttribute('autoplay', '');
                videoEl.muted = true;
                videoEl.className = 'w-full h-full object-cover';
                scannerView.insertBefore(videoEl, overlay ?? null);
                return videoEl;
            };

            const resetUi = () => {
                overlay?.classList.remove('hidden');
                panel?.classList.add('hidden');
                if (toggleButton) {
                    toggleButton.removeAttribute('disabled');
                    toggleButton.textContent = defaultButtonLabel;
                }
                activeMode = null;
            };

            const stopNativeScanner = () => {
                detectionActive = false;
                if (detectionFrame) {
                    cancelAnimationFrame(detectionFrame);
                    detectionFrame = null;
                }
                if (mediaStream) {
                    mediaStream.getTracks().forEach(track => track.stop());
                    mediaStream = null;
                }
                if (videoEl) {
                    videoEl.pause();
                    videoEl.srcObject = null;
                }
            };

            const stopQuaggaScanner = () => {
                if (window.Quagga && quaggaActive) {
                    if (quaggaHandler) {
                        window.Quagga.offDetected(quaggaHandler);
                        quaggaHandler = null;
                    }
                    window.Quagga.stop();
                }
                quaggaActive = false;
            };

            const stopScanner = () => {
                if (activeMode === 'native') {
                    stopNativeScanner();
                } else if (activeMode === 'quagga') {
                    stopQuaggaScanner();
                }
                resetUi();
            };

            const handleDetectionResult = value => {
                if (!value) {
                    return;
                }
                codeInput.value = String(value).trim();
                codeInput.focus();
                stopScanner();
            };

            const runDetectorLoop = async () => {
                if (!detectionActive || !barcodeDetector || !videoEl) {
                    return;
                }

                if (videoEl.readyState < 2) {
                    detectionFrame = requestAnimationFrame(runDetectorLoop);
                    return;
                }

                if (canvas.width !== videoEl.videoWidth || canvas.height !== videoEl.videoHeight) {
                    canvas.width = videoEl.videoWidth;
                    canvas.height = videoEl.videoHeight;
                }

                ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);

                try {
                    const codes = await barcodeDetector.detect(canvas);
                    if (Array.isArray(codes) && codes.length > 0) {
                        handleDetectionResult(codes[0]?.rawValue ?? '');
                        return;
                    }
                } catch (error) {
                    console.error('Barcode detection failed', error);
                }

                if (detectionActive) {
                    detectionFrame = requestAnimationFrame(runDetectorLoop);
                }
            };

            const startNativeScanner = () => {
                const video = ensureVideoElement();
                if (!video) {
                    alert('Unable to initialise the video area.');
                    resetUi();
                    return;
                }

                const getStream = navigator.mediaDevices?.getUserMedia?.({
                    video: { facingMode: 'environment' },
                });

                if (!getStream) {
                    alert('Unable to access the camera. Allow permissions or type the code manually.');
                    resetUi();
                    return;
                }

                getStream.then(stream => {
                    mediaStream = stream;
                    video.srcObject = stream;
                    video.play().catch(() => {});
                    overlay?.classList.add('hidden');
                    detectionActive = true;
                    activeMode = 'native';
                    runDetectorLoop();
                    if (toggleButton) {
                        toggleButton.textContent = 'Stop scanning';
                        toggleButton.removeAttribute('disabled');
                    }
                }).catch(error => {
                    console.error('Unable to access the camera', error);
                    alert('Unable to access the camera. Allow permissions or type the code manually.');
                    resetUi();
                });
            };

            const loadQuagga = () => {
                if (window.Quagga) {
                    return Promise.resolve(window.Quagga);
                }
                if (quaggaLoader) {
                    return quaggaLoader;
                }
                quaggaLoader = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js';
                    script.async = true;
                    script.crossOrigin = 'anonymous';
                    script.onload = () => {
                        if (window.Quagga) {
                            resolve(window.Quagga);
                        } else {
                            reject(new Error('Quagga failed to load.'));
                        }
                    };
                    script.onerror = () => reject(new Error('Quagga script failed to load.'));
                    document.head.appendChild(script);
                });
                return quaggaLoader;
            };

            const startQuaggaScanner = () => {
                loadQuagga()
                    .then(Quagga => {
                        Quagga.init({
                            inputStream: {
                                type: 'LiveStream',
                                target: scannerView,
                                constraints: {
                                    facingMode: 'environment',
                                },
                            },
                            decoder: {
                                readers: [
                                    'code_128_reader',
                                    'ean_reader',
                                    'ean_8_reader',
                                    'code_39_reader',
                                    'code_39_vin_reader',
                                    'upc_reader',
                                    'upc_e_reader',
                                ],
                            },
                            locate: true,
                        }, err => {
                            if (err) {
                                console.error('Quagga init failed', err);
                                alert('Unable to start the scanner. Please type the code manually.');
                                resetUi();
                                return;
                            }

                            overlay?.classList.add('hidden');
                            activeMode = 'quagga';
                            quaggaActive = true;
                            if (toggleButton) {
                                toggleButton.textContent = 'Stop scanning';
                                toggleButton.removeAttribute('disabled');
                            }

                            quaggaHandler = data => {
                                if (!data?.codeResult?.code) {
                                    return;
                                }
                                handleDetectionResult(data.codeResult.code);
                            };

                            Quagga.onDetected(quaggaHandler);
                            Quagga.start();
                        });
                    })
                    .catch(error => {
                        console.error(error);
                        alert('Unable to load the scanning library. Please enter the code manually.');
                        resetUi();
                    });
            };

            const startScanner = () => {
                panel?.classList.remove('hidden');
                overlay?.classList.remove('hidden');
                if (toggleButton) {
                    toggleButton.textContent = 'Starting camera...';
                    toggleButton.setAttribute('disabled', 'disabled');
                }

                if (nativeSupported && barcodeDetector) {
                    startNativeScanner();
                } else {
                    startQuaggaScanner();
                }
            };

            toggleButton?.addEventListener('click', event => {
                event.preventDefault();
                const isHidden = panel?.classList.contains('hidden');
                if (isHidden) {
                    startScanner();
                } else {
                    stopScanner();
                }
            });

            closeButton?.addEventListener('click', event => {
                event.preventDefault();
                stopScanner();
            });

            window.addEventListener('beforeunload', () => stopScanner());
        });
    </script>
</x-app-layout>

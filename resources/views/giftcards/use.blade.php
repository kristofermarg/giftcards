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
                        <div class="absolute inset-0 flex items-center justify-center">
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js" integrity="sha512-c1uknS9s/6D7B6rFeoJEBhDigw1kLT69aGSDxvndlwaKICeJvo2aUYTbwdSCZH/5LiCFYI8a9kaI0s5momkGLg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('toggle-scanner');
            const closeButton = document.getElementById('close-scanner');
            const panel = document.getElementById('scanner-panel');
            const scannerView = document.getElementById('scanner-view');
            const codeInput = document.getElementById('giftcard-code');
            const defaultButtonLabel = toggleButton?.textContent ?? 'Scan code';

            let quaggaReady = false;
            let detectionHandler = null;

            const stopScanner = () => {
                if (window.Quagga && quaggaReady) {
                    if (detectionHandler) {
                        Quagga.offDetected(detectionHandler);
                    }
                    Quagga.stop();
                }
                quaggaReady = false;
                detectionHandler = null;

                panel?.classList.add('hidden');
                if (toggleButton) {
                    toggleButton.removeAttribute('disabled');
                    toggleButton.textContent = defaultButtonLabel;
                }
            };

            const handleDetection = result => {
                if (!result?.codeResult?.code) {
                    return;
                }
                codeInput.value = result.codeResult.code.trim();
                codeInput.focus();
                stopScanner();
            };

            const startScanner = () => {
                if (!window.Quagga) {
                    alert('Scanning library failed to load. Please type the code instead.');
                    return;
                }

                panel?.classList.remove('hidden');
                if (toggleButton) {
                    toggleButton.textContent = 'Starting camera...';
                    toggleButton.setAttribute('disabled', 'disabled');
                }

                detectionHandler = handleDetection;

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
                        console.error(err);
                        alert('Unable to access the camera. Please allow permissions or enter the code manually.');
                        stopScanner();
                        return;
                    }

                    Quagga.start();
                    Quagga.onDetected(detectionHandler);
                    quaggaReady = true;
                    if (toggleButton) {
                        toggleButton.textContent = 'Stop scanning';
                        toggleButton.removeAttribute('disabled');
                    }
                });
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

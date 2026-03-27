
<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4">Escanear Código QR</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Escanea el código QR del laboratorio para registrar tu entrada o salida.
            </p>

            <!-- Scanner container -->
            <div id="reader" class="w-full max-w-md mx-auto mb-4"></div>

            <!-- Result message -->
            <div id="result" class="hidden mt-4 p-4 rounded-lg"></div>
        </div>
    </div>

    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resultDiv = document.getElementById('result');

            function onScanSuccess(decodedText, decodedResult) {
                // Detener el scanner temporalmente
                html5QrcodeScanner.pause(true);

                // Mostrar loading
                resultDiv.className = 'mt-4 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800';
                resultDiv.innerHTML = '<p class="text-blue-800 dark:text-blue-200">Procesando...</p>';
                resultDiv.classList.remove('hidden');

                // Enviar el código escaneado al servidor
                fetch('{{ route("filament.teacher.api.scan") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        qr_code: decodedText
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.className = 'mt-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800';
                        resultDiv.innerHTML = `
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-green-800 dark:text-green-200 font-semibold">${data.message}</p>
                            </div>
                        `;

                        // Reanudar scanner después de 3 segundos
                        setTimeout(() => {
                            resultDiv.classList.add('hidden');
                            html5QrcodeScanner.resume();
                        }, 3000);
                    } else {
                        resultDiv.className = 'mt-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
                        resultDiv.innerHTML = `
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-red-800 dark:text-red-200 font-semibold">${data.message}</p>
                            </div>
                        `;

                        // Reanudar scanner después de 3 segundos
                        setTimeout(() => {
                            resultDiv.classList.add('hidden');
                            html5QrcodeScanner.resume();
                        }, 3000);
                    }
                })
                .catch(error => {
                    resultDiv.className = 'mt-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
                    resultDiv.innerHTML = `
                        <p class="text-red-800 dark:text-red-200">Error: ${error.message}</p>
                    `;

                    setTimeout(() => {
                        resultDiv.classList.add('hidden');
                        html5QrcodeScanner.resume();
                    }, 3000);
                });
            }

            function onScanFailure(error) {
                // Handle scan failure, usually better to ignore these
            }

            const html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0
                },
                false
            );

            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        });
    </script>
    @endpush
</x-filament-panels::page>


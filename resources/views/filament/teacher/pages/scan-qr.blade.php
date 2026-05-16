
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
            const fallbackIndexUrl = @js(\App\Filament\Teacher\Resources\ReservationResource::getUrl('index'));

            function closeScanner() {
                try {
                    html5QrcodeScanner.clear();
                } catch (_) {
                    // No-op: el scanner puede ya estar detenido.
                }
            }

            function showStatus(type, message) {
                const styles = {
                    info: 'mt-4 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200',
                    success: 'mt-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
                    error: 'mt-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
                };

                resultDiv.className = styles[type] ?? styles.info;
                resultDiv.innerHTML = `<p class="font-semibold">${message}</p>`;
                resultDiv.classList.remove('hidden');
            }

            function onScanSuccess(decodedText, decodedResult) {
                // Detener el scanner temporalmente
                html5QrcodeScanner.pause(true);

                // Mostrar loading
                showStatus('info', 'Procesando...');

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
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'No se pudo procesar el escaneo.');
                    }
                    return data;
                })
                .then(data => {
                    if (data.success) {
                        const successMessage = `${data.message} Estado: ${data.reservation_status ?? 'actualizado'}.`;
                        showStatus('success', successMessage);
                        alert(successMessage);
                        closeScanner();

                        // Redirigir a la reservación confirmada (o listado como fallback)
                        setTimeout(() => {
                            window.location.href = data.view_url || data.index_url || fallbackIndexUrl;
                        }, 900);
                    } else {
                        showStatus('error', data.message || 'No se pudo confirmar la reservación.');
                        alert(data.message || 'No se pudo confirmar la reservación.');

                        // Reanudar scanner después de 3 segundos
                        setTimeout(() => {
                            resultDiv.classList.add('hidden');
                            html5QrcodeScanner.resume();
                        }, 3000);
                    }
                })
                .catch(error => {
                    const message = `Error: ${error.message}`;
                    showStatus('error', message);
                    alert(message);

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


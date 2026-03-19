<x-filament::page>
    <div class="grid grid-cols-1 gap-6">

        {{-- Header con información principal --}}
        <div class="rounded-lg p-4 flex items-center justify-between shadow-sm mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                    <x-heroicon-o-book-open class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                        {{ $record->subject->name }}
                    </h1>

                </div>
            </div>

        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            {{ $this->infolist }}
        </div>

    </div>

</x-filament::page>

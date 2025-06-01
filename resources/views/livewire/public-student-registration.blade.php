<div class="max-w-3xl mx-auto p-6 bg-white dark:bg-gray-700 text-dark dark:text-white rounded shadow">
    <h3 class="text-xl font-bold mb-4 text-center">Registro de Alumno</h3>
    <hr><br>
    @if (session()->has('success'))
        <div class="bg-green-100 dark:bg-green-200 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    <form wire:submit.prevent="create">
        {{ $this->form->render() }}
        <button type="submit"
                class="mt-4 px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Registrar
        </button>
    </form>
</div>

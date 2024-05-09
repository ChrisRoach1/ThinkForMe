<x-app-layout>
    <x-slot:title>Create Image</x-slot:title>

    <div class="p-4 sm:p-8 sm:rounded-lg">
        <div>
            <livewire:create-image />
        </div>
    </div>

    <div class="p-4 sm:p-10 mx-auto max-w-6xl sm:py-35 lg:py-35">
        <div>
            <livewire:list-images />
        </div>
    </div>
</x-app-layout>

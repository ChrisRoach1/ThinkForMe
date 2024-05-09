<x-app-layout>
    <x-slot:title>Dashboard</x-slot:title>


    <div class="p-4 sm:p-8 sm:rounded-lg">
        <div>
            <livewire:create-project />
        </div>
    </div>
    <div>
        <div class="p-4 sm:p-8 sm:rounded-lg">
            <div>
                <livewire:list-projects />
            </div>
        </div>
    </div>
</x-app-layout>

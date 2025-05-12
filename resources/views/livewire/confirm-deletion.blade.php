@props(['object' => '', 'confirmingDeleteId'=> ''])

<div>
    @if ($confirmingDeleteId)
    <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
            <h2 class="text-lg font-semibold mb-4">Confirm Delete</h2>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this {{ $object }}?</p>
            <div class="flex justify-end gap-3">
                <button wire:click="$set('confirmingDeleteId', null)"
                    class="px-4 py-2 bg-gray-300 rounded-md">Cancel</button>
                <button wire:click="deleteTenant" class="px-4 py-2 bg-red-600 text-white rounded-md">Delete</button>
            </div>
        </div>
    </div>
    @endif

</div>

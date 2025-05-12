<!-- resources/views/livewire/campaign-uploader.blade.php -->
<div>
    <!-- Card Section -->
    <div class="max-w-2xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Card -->
        <div class="bg-white rounded-xl shadow-xs p-4 sm:p-7 dark:bg-neutral-900">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-neutral-200">
                    3cx Integration
                </h2>
                <p class="text-sm text-gray-600 dark:text-neutral-400">
                    Add The Your Pbx Cradentials
                </p>
            </div>

            <!-- Flash Messages -->
            @if (session()->has('message'))
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                {{ session('message') }}
            </div>
            @endif

            @if (session()->has('error'))
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
            @endif

            <form wire:submit.prevent="makeIntegration">
                <!-- 3cx Integration -->
                <div
                    class="py-6 first:pt-0 last:pb-0 border-t first:border-transparent border-gray-200 dark:border-neutral-700 dark:first:border-transparent">
                    <label class="block text-sm font-medium mb-3 dark:text-white">

                    </label>

                    <div class="space-y-4">
                        <!-- Pbx Url -->
                        <div>
                            <label for="pbxUrl"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Pbx Url
                            </label>
                            <input type="text" id="pbxUrl" wire:model="pbxUrl"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                placeholder="Enter Pbx Url"

                                >
                            @error('pbxUrl') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Client Id -->
                        <div>
                            <label for="clientId"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Client Id
                            </label>
                            <input type="text" id="clientId" wire:model="clientId"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                placeholder="Enter Client Id"

                                >
                            @error('clientId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Client Secret -->
                        <div>
                            <label for="ClientSecret"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Client Secret
                            </label>
                            <input type="text" id="ClientSecret" wire:model="ClientSecret"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                placeholder="Enter Client Secret"
                                 
                                >
                            @error('ClientSecret') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>


                    </div>
                </div>
                <!-- End Campaign Information Section -->

                <div class="mt-8 flex justify-end gap-x-2">

                    <button type="submit"
                        class="py-2 px-4 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-700 dark:hover:bg-gray-800 cursor-pointer"
                       >
                       Integrate
                    </button>
                </div>
                </div>
                <!-- End Contacts File Upload Section -->

                <!-- Action Buttons -->

            </form>
        </div>
        <!-- End Card -->
    </div>
    <!-- End Card Section -->
</div>

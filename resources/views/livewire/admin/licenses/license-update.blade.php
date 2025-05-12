<div>
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Update License - {{$license->tenant->name}}</h2>
            </div>

            <div class="p-6 dark:bg-gray-800">
                <form wire:submit.prevent="update" class="space-y-8">


                    <!-- License Details -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600">
                        <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">License Details</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- License Key -->
                            <div>
                                <label for="license_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">License Key</label>
                                <div class="flex">
                                    <input
                                        type="text"
                                        id="license_key"
                                        wire:model.defer="license_key"
                                        class="flex-1 rounded-l-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:focus:border-indigo-300 dark:focus:ring-indigo-200"
                                        placeholder="Enter or generate a license key"
                                    >
                                    <button
                                        type="button"
                                        wire:click="generateLicenseKey"
                                        wire:loading.attr="disabled"
                                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-r-md hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-800 transition-colors"
                                    >
                                        <span wire:loading.remove wire:target="generateLicenseKey">Generate</span>
                                        <span wire:loading wire:target="generateLicenseKey">
                                            <svg class="animate-spin h-5 w-5 text-gray-700 dark:text-gray-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                                @error('license_key') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">License Status</label>
                                <div class="flex items-center space-x-6">
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model="is_active" name="is_active" value="1" class="form-radio h-5 w-5 text-indigo-600 transition duration-150 ease-in-out">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Active</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model="is_active" name="is_active" value="0" class="form-radio h-5 w-5 text-indigo-600 transition duration-150 ease-in-out">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Inactive</span>
                                    </label>
                                </div>
                                @error('is_active') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <!-- Valid From -->
                            <div>
                                <label for="valid_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valid From</label>
                                <input
                                    type="date"
                                    id="valid_from"
                                    wire:model="valid_from"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:focus:border-indigo-300 dark:focus:ring-indigo-200"
                                >
                                @error('valid_from') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <!-- Valid Until -->
                            <div>
                                <label for="valid_until" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valid Until</label>
                                <input
                                    type="date"
                                    id="valid_until"
                                    wire:model.defer="valid_until"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:focus:border-indigo-300 dark:focus:ring-indigo-200"
                                >
                                @error('valid_until') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- License Limits -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600">
                        <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-4">License Limits</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Max Campaigns -->
                            <div>
                                <label for="max_campaigns" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Campaigns</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input
                                        type="number"
                                        id="max_campaigns"
                                        wire:model.defer="max_campaigns"
                                        min="0"
                                        class="block w-full pr-12 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:focus:border-indigo-300 dark:focus:ring-indigo-200"
                                    >
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <div class="h-full py-0 pl-2 pr-3 border-transparent bg-transparent text-gray-500 dark:text-gray-400 sm:text-sm rounded-md">
                                            <span>campaigns</span>
                                        </div>
                                    </div>
                                </div>
                                @error('max_campaigns') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <!-- Max Agents -->
                            <div>
                                <label for="max_agents" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Agents</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input
                                        type="number"
                                        id="max_agents"
                                        wire:model.defer="max_agents"
                                        min="0"
                                        class="block w-full pr-12 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:focus:border-indigo-300 dark:focus:ring-indigo-200"
                                    >
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <div class="h-full py-0 pl-2 pr-3 border-transparent bg-transparent text-gray-500 dark:text-gray-400 sm:text-sm rounded-md">
                                            <span>agents</span>
                                        </div>
                                    </div>
                                </div>
                                @error('max_agents') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <!-- Max Providers -->
                            <div>
                                <label for="max_providers" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Providers</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input
                                        type="number"
                                        id="max_providers"
                                        wire:model.defer="max_providers"
                                        min="0"
                                        class="block w-full pr-12 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:focus:border-indigo-300 dark:focus:ring-indigo-200"
                                    >
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <div class="h-full py-0 pl-2 pr-3 border-transparent bg-transparent text-gray-500 dark:text-gray-400 sm:text-sm rounded-md">
                                            <span>providers</span>
                                        </div>
                                    </div>
                                </div>
                                @error('max_providers') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <!-- Max Distribution Calls -->
                            <div>
                                <label for="max_dist_calls" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Distribution Calls</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input
                                        type="number"
                                        id="max_dist_calls"
                                        wire:model.defer="max_dist_calls"
                                        min="0"
                                        class="block w-full pr-12 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:focus:border-indigo-300 dark:focus:ring-indigo-200"
                                    >
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <div class="h-full py-0 pl-2 pr-3 border-transparent bg-transparent text-gray-500 dark:text-gray-400 sm:text-sm rounded-md">
                                            <span>calls</span>
                                        </div>
                                    </div>
                                </div>
                                @error('max_dist_calls') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <!-- Max Dial Calls -->
                            <div>
                                <label for="max_dial_calls" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Dial Calls</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input
                                        type="number"
                                        id="max_dial_calls"
                                        wire:model.defer="max_dial_calls"
                                        min="0"
                                        class="block w-full pr-12 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:focus:border-indigo-300 dark:focus:ring-indigo-200"
                                    >
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <div class="h-full py-0 pl-2 pr-3 border-transparent bg-transparent text-gray-500 dark:text-gray-400 sm:text-sm rounded-md">
                                            <span>calls</span>
                                        </div>
                                    </div>
                                </div>
                                @error('max_dial_calls') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <!-- Max Contacts Per Campaign -->
                            <div>
                                <label for="max_contacts_per_campaign" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Contacts Per Campaign</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input
                                        type="number"
                                        id="max_contacts_per_campaign"
                                        wire:model.defer="max_contacts_per_campaign"
                                        min="0"
                                        class="block w-full pr-12 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:focus:border-indigo-300 dark:focus:ring-indigo-200"
                                    >
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <div class="h-full py-0 pl-2 pr-3 border-transparent bg-transparent text-gray-500 dark:text-gray-400 sm:text-sm rounded-md">
                                            <span>contacts</span>
                                        </div>
                                    </div>
                                </div>
                                @error('max_contacts_per_campaign') <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-3">
                        <a href="{{ route('admin.license.list') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            <span wire:loading.remove wire:target="store">Update License</span>
                            <span wire:loading wire:target="store">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Updating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

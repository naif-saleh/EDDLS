<!-- resources/views/livewire/campaign-uploader.blade.php -->
<div>
    <!-- Card Section -->
    <div class="max-w-2xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Card -->
        <div class="bg-white rounded-xl shadow-xs p-4 sm:p-7 dark:bg-neutral-900">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-neutral-200">
                    Campaign Updation
                </h2>
                <p class="text-sm text-gray-600 dark:text-neutral-400">
                    Update {{ $campaignName }} Campaign Information
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

            <form wire:submit.prevent="updateCampaign({{ $campaign->id }})">
                <!-- Campaign Information Section -->
                <div
                    class="py-6 first:pt-0 last:pb-0 border-t first:border-transparent border-gray-200 dark:border-neutral-700 dark:first:border-transparent">
                    <label class="block text-sm font-medium mb-3 dark:text-white">
                        Campaign Information
                    </label>

                    <div class="space-y-4">
                        <!-- Campaign Name -->
                        <div>
                            <label for="campaignName"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Campaign Name
                            </label>
                            <input type="text" id="campaignName" wire:model="campaignName"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                placeholder="Enter campaign name">
                            @error('campaignName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Campaign Type -->
                        <div>
                            <label for="campaignType"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Campaign Type
                            </label>
                            <select id="campaignType" wire:model="campaignType"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white">
                                <option value="dialer">Dialer</option>
                                <option value="distributor">Distributor</option>
                            </select>
                            @error('campaignType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Campaign Status -->
                        <div>
                            <label for="campaignStatus"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Campaign Status
                            </label>
                            <select id="campaignStatus" wire:model="campaignStatus"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            @error('campaignStatus') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Start Time -->
                        <div>
                            <label for="campaignStart"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Campaign Start Time
                            </label>
                            <input type="datetime-local" id="campaignStart" wire:model="campaignStart"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white">
                            @error('campaignStart') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- End Time -->
                        <div>
                            <label for="campaignEnd"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Campaign End Time
                            </label>
                            <input type="datetime-local" id="campaignEnd" wire:model="campaignEnd"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white">
                            @error('campaignEnd') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                <!-- End Campaign Information Section -->
                <!-- Action Buttons -->
                <div class="mt-8 flex justify-end gap-x-2">
                    <a href='{{ route('tenant.dialer.provider.campaigns.list', ['provider'=> $provider->slug, 'tenant'
                        => $provider->tenant->slug]) }}' type="button"
                        class="py-2 px-4 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700
                        hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                        dark:bg-neutral-800 dark:border-neutral-600 dark:text-white dark:hover:bg-neutral-700">
                        Cancel
                    </a>

                    <button type="submit"
                        class="py-2 px-4 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-700 dark:hover:bg-gray-800 cursor-pointer"
                       >
                       Update Campaign
                    </button>
                </div>

            </form>
        </div>
        <!-- End Card -->
    </div>
    <!-- End Card Section -->
</div>
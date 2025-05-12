<!-- resources/views/livewire/campaign-uploader.blade.php -->
<div>
    <!-- Card Section -->
    <div class="max-w-2xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Card -->
        <div class="bg-white rounded-xl shadow-xs p-4 sm:p-7 dark:bg-neutral-900">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-neutral-200">
                    Campaign Addition
                </h2>
                <p class="text-sm text-gray-600 dark:text-neutral-400">
                    Add The Campaign Information
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

            <form wire:submit.prevent="createDistCampaign">
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

                <!-- Contacts File Upload Section -->
                <div class="py-6 border-t border-gray-200 dark:border-neutral-700">
                    <label class="block text-sm font-medium mb-3 dark:text-white">
                        Contacts File Upload (CSV)
                    </label>

                    <!-- File Upload Component -->
                    <div x-data="{
        isUploading: false,
        progress: 0,
        isHovering: false,
        fileName: '',
        fileSize: '',
        pollingIntervalId: null,

        init() {
            // Listen for Livewire file upload progress
            this.$wire.on('upload:progress', (event) => {
                if (event.name === 'csvFile') {
                    this.progress = event.progress;
                }
            });

            this.$watch('$wire.isProcessing', (value) => {
                if (value) {
                    this.isUploading = true;
                } else {
                    this.isUploading = false;
                }
            });

            this.$watch('$wire.progress', (value) => {
                this.progress = value;
            });

            // Watch for polling interval changes
            this.$watch('$wire.pollingInterval', (interval) => {
                // Clear any existing interval
                if (this.pollingIntervalId) {
                    clearInterval(this.pollingIntervalId);
                    this.pollingIntervalId = null;
                }

                // Set new polling interval if provided
                if (interval) {
                    this.pollingIntervalId = setInterval(() => {
                        this.$wire.checkProgress();
                    }, interval);
                }
            });

            // Clean up interval when component is destroyed
            this.$cleanup = () => {
                if (this.pollingIntervalId) {
                    clearInterval(this.pollingIntervalId);
                }
            };
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.fileName = file.name;
                this.fileSize = this.formatFileSize(file.size);
                this.isUploading = true;
            }
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' bytes';
            else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            else return (bytes / 1048576).toFixed(1) + ' MB';
        }
    }" @dragover.prevent="isHovering = true" @dragleave.prevent="isHovering = false" @drop.prevent="isHovering = false"
                        class="mb-4">
                        <!-- File Input -->
                        <div class="relative flex flex-col items-center p-6 border-2 border-dashed rounded-lg transition-all duration-200"
                            :class="{
            'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20': isHovering,
            'border-gray-300 bg-white dark:border-neutral-700 dark:bg-neutral-800': !isHovering,
            'opacity-50': isUploading || $wire.isProcessing
        }">
                            <input type="file" id="csvFile" wire:model="csvFile" accept=".csv,.txt"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                x-on:change="handleFileSelect" :disabled="isUploading || $wire.isProcessing">

                            <div class="flex flex-col items-center justify-center text-center">
                                <!-- Upload Icon -->
                                <div
                                    class="flex justify-center items-center w-14 h-14 mb-4 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>

                                <!-- Text -->
                                <h3 class="mb-2 text-lg font-medium text-gray-700 dark:text-gray-200">
                                    <span x-show="!isUploading && !$wire.isProcessing">Drop your CSV file here or click
                                        to browse</span>
                                    <span x-show="isUploading && !$wire.isProcessing">Uploading...</span>
                                    <span x-show="$wire.isProcessing">Processing contacts...</span>
                                </h3>

                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <p x-show="!fileName && !isUploading && !$wire.isProcessing">
                                        CSV file should contain phone numbers
                                    </p>
                                    <p x-show="fileName && !$wire.isProcessing" class="font-medium">
                                        <span x-text="fileName"></span> (<span x-text="fileSize"></span>)
                                    </p>
                                    <p x-show="$wire.isProcessing">
                                        Processing contacts: <span x-text="`${$wire.progress}%`"></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div x-show="isUploading || $wire.isProcessing" class="mt-3">
                            <div class="bg-gray-200 rounded-full h-2 overflow-hidden dark:bg-neutral-700">
                                <div class="h-full bg-blue-600 transition-all duration-300 rounded-full"
                                    :style="`width: ${$wire.isProcessing ? $wire.progress : progress}%`"></div>
                            </div>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center">
                                <span x-show="!$wire.isProcessing">Uploading: <span
                                        x-text="`${progress}%`"></span></span>
                                <span x-show="$wire.isProcessing">
                                    <span
                                        x-text="`Processed ${$wire.totalContacts > 0 ? Math.round($wire.progress / 100 * $wire.totalContacts) : 0} of ${$wire.totalContacts} contacts`"></span>
                                </span>
                            </div>
                        </div>

                        @error('csvFile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <!-- End File Upload Component -->

                    <div
                        class="mt-2 px-3 py-2 bg-yellow-50 text-yellow-700 text-sm rounded-md dark:bg-yellow-900/30 dark:text-yellow-500">
                        <p class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Upload a CSV file with phone numbers. Large files will be processed in batches to avoid
                            timeout issues.
                        </p>
                    </div>
                </div>
                <!-- End Contacts File Upload Section -->

                <!-- Action Buttons -->
                <div class="mt-8 flex justify-end gap-x-2">
                    {{-- @if (Route::currentRouteName() === 'tenant.dialer.providers')
                    <a href='{{ route('tenant.dialer.provider.campaigns.list', ['provider'=> $provider->slug, 'tenant'
                        => $provider->tenant->slug]) }}' type="button"
                        class="py-2 px-4 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700
                        hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                        dark:bg-neutral-800 dark:border-neutral-600 dark:text-white dark:hover:bg-neutral-700">
                        Cancel
                    </a>
                    @else
                    <button wire:click='back()' type="button"
                        class="py-2 px-4 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700
                        hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                        dark:bg-neutral-800 dark:border-neutral-600 dark:text-white dark:hover:bg-neutral-700">
                        Cancel
                    </button>
                    @endif --}}
                    <button type="submit"
                        class="py-2 px-4 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-700 dark:hover:bg-gray-800 cursor-pointer"
                        :disabled="isUploading || $wire.isProcessing">
                        <span x-show="!isUploading && !$wire.isProcessing">Create Campaign</span>
                        <span x-show="isUploading || $wire.isProcessing">Processing...</span>
                    </button>
                </div>
            </form>
        </div>
        <!-- End Card -->
    </div>
    <!-- End Card Section -->
</div>

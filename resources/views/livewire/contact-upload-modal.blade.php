{{-- File: resources/views/livewire/contact-upload-modal.blade.php --}}
<div>
    @if ($showModal)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-lg w-full dark:bg-neutral-800">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Upload Contacts CSV
                </h3>
                <button type="button" wire:click="$set('showModal', false)"
                    class="text-gray-400 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-200">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="py-4 border-t border-gray-200 dark:border-neutral-700">
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
                        Livewire.on('upload:progress', (event) => {
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
                        this.$destroy = () => {
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
                        }
                    },
            
                    formatFileSize(bytes) {
                        if (bytes < 1024) return bytes + ' bytes';
                        else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                        else return (bytes / 1048576).toFixed(1) + ' MB';
                    }
                }" @dragover.prevent="isHovering = true" @dragleave.prevent="isHovering = false"
                    @drop.prevent="isHovering = false" class="mb-4">
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
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
                                    CSV file should contain file_name, provider_name, provider_extension, phone_number,
                                    start_time, end_time
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
                            <span x-show="!$wire.isProcessing">Uploading: <span x-text="`${progress}%`"></span></span>
                            <span x-show="$wire.isProcessing">
                                <span
                                    x-text="`Processed ${$wire.totalContacts > 0 ? Math.round($wire.progress / 100 * $wire.totalContacts) : 0} of ${$wire.totalContacts} contacts`"></span>
                            </span>
                        </div>
                    </div>

                    @error('csvFile')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <button wire:click="processCSV" type="button"
                        class="py-1.5 px-3 inline-flex items-center justify-center gap-x-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-neutral-800 dark:text-neutral-200 dark:border-neutral-700 dark:hover:bg-neutral-700"
                        x-show="fileName && !isUploading && !$wire.isProcessing">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5">
                            <path fill-rule="evenodd"
                                d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875ZM12.75 12a.75.75 0 0 0-1.5 0v2.25H9a.75.75 0 0 0 0 1.5h2.25V18a.75.75 0 0 0 1.5 0v-2.25H15a.75.75 0 0 0 0-1.5h-2.25V12Z"
                                clip-rule="evenodd" />
                            <path
                                d="M14.25 5.25a5.23 5.23 0 0 0-1.279-3.434 9.768 9.768 0 0 1 6.963 6.963A5.23 5.23 0 0 0 16.5 7.5h-1.875a.375.375 0 0 1-.375-.375V5.25Z" />
                        </svg>
                        Process File
                    </button>
                </div>

                <!-- Success Message -->
                <div x-data="{ show: false }" x-show="show"
                    x-init="$el.addEventListener('import-completed', () => { show = true; setTimeout(() => { show = false }, 5000); })"
                    class="mt-4 p-3 bg-green-100 text-green-700 rounded-md dark:bg-green-900/30 dark:text-green-300"
                    style="display: none;">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <p>File processed successfully!</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 border-t border-gray-200 pt-4 flex justify-end dark:border-neutral-700">
                <button type="button" wire:click="$set('showModal', false)"
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 dark:bg-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-600">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
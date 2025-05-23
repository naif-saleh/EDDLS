<div>
    <!-- Error Message -->
    @if (session()->has('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative dark:bg-red-900 dark:border-red-700 dark:text-red-300" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Table Section -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Card -->
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div
                        class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-800 dark:border-neutral-700">
                        <!-- Header -->
                        <div
                            class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-neutral-200">
                                    Skipped Numbers
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">
                                    Manage and view skipped numbers by provider.
                                </p>
                            </div>

                            <div>
                                <div class="inline-flex gap-x-2">
                                    <button wire:click="downloadCsv"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-50 dark:bg-transparent dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="7 10 12 15 17 10" />
                                            <line x1="12" y1="15" x2="12" y2="3" />
                                        </svg>
                                        Download CSV
                                    </button>


                                </div>
                            </div>
                        </div>
                        <!-- End Header -->

                        <!-- Filters -->
                        <div
                            class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
                            <div class="flex items-center gap-x-2">
                                <div>
                                    <label for="provider"
                                        class="text-xs font-medium text-gray-800 dark:text-neutral-200">Provider</label>
                                    <select id="provider" wire:model.live="selectedProvider"
                                        class="py-2 px-3 pe-9 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-300">
                                        <option value="">All Providers</option>
                                        @foreach($providers as $provider)
                                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="perPage"
                                        class="text-xs font-medium text-gray-800 dark:text-neutral-200">Per page</label>
                                    <select id="perPage" wire:model.live="perPage"
                                        class="py-2 px-3 pe-9 block w-24 border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-300">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                           
                        </div>
                        <!-- End Filters -->

                        <!-- Table -->
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Phone Number
                                            </span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Provider
                                            </span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                File Name
                                            </span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Row Number
                                            </span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Skip Reason
                                            </span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Created At
                                            </span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-start">
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                                                Actions
                                            </span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                @forelse($skippedNumbers as $skipped)
                                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $skipped->phone_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $skipped->provider->name ?? 'N/A' }}
                                        @if($skipped->provider && $skipped->provider->extension)
                                            <span class="text-xs text-gray-500 dark:text-neutral-400">({{ $skipped->provider->extension }})</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $skipped->file_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $skipped->row_number ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-normal text-sm">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                            {{ $skipped->skip_reason }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                        {{ $skipped->created_at->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                        @if($skipped->raw_data)
                                        <button 
                                            @click="$dispatch('open-modal', { rawData: '{{ addslashes(json_encode($skipped->raw_data)) }}' })"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                            View Details
                                        </button>
                                        @else
                                        <span class="text-gray-400 dark:text-neutral-500">No Details</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-neutral-400">
                                        No skipped numbers found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <!-- End Table -->


                        <!-- Footer -->
                        <div
                            class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">
                                    <span class="font-semibold text-gray-800 dark:text-neutral-200">{{$totalSkipped}}</span> results
                                </p>
                            </div>

                            <div>
                                {{ $skippedNumbers->links() }}
                            </div>
                        </div>
                        <!-- End Footer -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Card -->
    </div>
    <!-- End Table Section -->

    <!-- Modal for Raw Data -->
    <div x-data="{ open: false, rawData: null }" x-on:open-modal.window="open = true; rawData = $event.detail.rawData"
        x-on:keydown.escape.window="open = false" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">

        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"
                @click="open = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full dark:bg-neutral-800">

                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-neutral-200"
                                id="modal-title">
                                Raw Data Details
                            </h3>
                            <div class="mt-4">
                                <pre x-text="JSON.stringify(JSON.parse(rawData), null, 2)"
                                    class="bg-gray-100 p-4 rounded-md overflow-auto max-h-80 text-sm text-gray-800 dark:bg-neutral-700 dark:text-neutral-200"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="open = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-neutral-700 dark:border-neutral-600 dark:text-neutral-200 dark:hover:bg-neutral-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

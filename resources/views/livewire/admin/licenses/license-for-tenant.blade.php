<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white dark:bg-neutral-900 rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">License Information</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $license->tenant->name }}</p>
            </div>
            {{-- <button wire:click="exportLicensePDF"
                class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700 dark:focus:text-neutral-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                Export PDF
            </button> --}}
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div class="flex flex-col space-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">License Key</span>
                        <span
                            class="text-gray-800 dark:text-gray-200 font-mono bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded text-sm">{{
                            $license->license_key }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Valid From</span>
                        <span class="text-gray-800 dark:text-gray-200">{{ $license->valid_from }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Valid Until</span>
                        <span class="text-gray-800 dark:text-gray-200">{{ $license->valid_until }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</span>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $license->is_active ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                            {{ $license->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm text-left">
                            <thead
                                class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-2 rounded-tl-lg">Resource</th>
                                    <th scope="col" class="px-4 py-2 text-right rounded-tr-lg">Limit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
                                    <th scope="row"
                                        class="px-4 py-2 font-medium {{ $license->max_campaigns == 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }} whitespace-nowrap">
                                        Max Campaigns
                                    </th>
                                    <td class="px-4 py-2 text-right {{ $license->max_campaigns == 0 ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }}  font-medium">
                                        {{ $license->max_campaigns }}
                                    </td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
                                    <th scope="row"
                                        class="px-4 py-2 font-medium {{ $license->max_agents == 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }} whitespace-nowrap">
                                        Max Agents
                                    </th>
                                    <td class="px-4 py-2 text-right {{ $license->max_agents == 0 ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }} text-gray-800 dark:text-gray-200 font-medium">
                                        {{ $license->max_agents }}
                                    </td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
                                    <th scope="row"
                                        class="px-4 py-2 font-medium {{ $license->max_providers == 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }} whitespace-nowrap">
                                        Max Providers
                                    </th>
                                    <td class="px-4 py-2 text-right {{ $license->max_providers == 0 ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }} font-medium">
                                        {{ $license->max_providers }}
                                    </td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
                                    <th scope="row"
                                        class="px-4 py-2 font-medium {{ $license->max_dist_calls == 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }} whitespace-nowrap">
                                        Max Distributor Calls
                                    </th>
                                    <td class="px-4 py-2 text-right {{ $license->max_dist_calls == 0 ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }} font-medium">
                                        {{ $license->max_dist_calls }}
                                    </td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
                                    <th scope="row"
                                        class="px-4 py-2 font-medium {{ $license->max_dial_calls == 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }} whitespace-nowrap">
                                        Max Dialer Calls
                                    </th>
                                    <td class="px-4 py-2 text-right {{ $license->max_dial_calls == 0 ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }} font-medium">
                                        {{ $license->max_dial_calls }}
                                    </td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800">
                                    <th scope="row"
                                        class="px-4 py-2 font-medium {{ $license->max_contacts_per_campaign == 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }} whitespace-nowrap rounded-bl-lg">
                                        Max Contacts Per Campaign
                                    </th>
                                    <td
                                        class="px-4 py-2 text-right {{ $license->max_contacts_per_campaign == 0 ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }} font-medium rounded-br-lg">
                                        {{ $license->max_contacts_per_campaign }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

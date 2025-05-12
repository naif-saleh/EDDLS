<!-- Card Section -->
<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
    <!-- Main Stats Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Agents & Providers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Agents Card -->
            <x-statics-card title="Total Agents" :count="$totalAgents" :active="$totalActiveAgents"
                :inactive="$totalInactiveAgents" icon="users" color="blue">
            </x-statics-card>

            <!-- Providers Card -->
            <x-statics-card title="Total Providers" :count="$totalProvider" :active="$totalActiveProvider"
                :inactive="$totalInactiveProvider" icon="building" color="indigo">
            </x-statics-card>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Distributor Campaigns Stats -->
        <div
            class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-800 overflow-hidden">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-neutral-200 mb-3">
                    Distributor Campaign Status
                </h3>

                <div class="grid grid-cols-3 gap-4">
                    <!-- Total Campaigns -->
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-neutral-400">Total</p>
                        <p class="text-xl font-semibold text-gray-800 dark:text-neutral-200"> {{ $totalDistCampaign }}
                        </p>
                    </div>

                    <!-- Active Campaigns -->
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-neutral-400">Active</p>
                        <p class="text-xl font-semibold text-green-600 dark:text-green-500">{{ $totalDistActiveCampaign
                            }}</p>
                    </div>

                    <!-- Inactive Campaigns -->
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-neutral-400">Inactive</p>
                        <p class="text-xl font-semibold text-orange-500 dark:text-orange-400">{{
                            $totalDistInactiveCampaign }}</p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-5">
                    <div class="flex justify-between text-xs mb-1">
                        <span>Progress</span>
                        <span>{{ $totalDistCampaign > 0 ? round(($totalDistCompeletedCampaign / $totalDistCampaign) *
                            100) : 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-neutral-700">
                        <div class="bg-blue-600 h-2.5 rounded-full"
                            style="width: {{ $totalDistCampaign > 0 ? ($totalDistCompeletedCampaign / $totalDistCampaign) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>

                <!-- Campaign Status Indicators -->
                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-3 text-center">
                        <span class="text-xs text-gray-700 dark:text-neutral-300">Completed</span>
                        <p class="text-lg font-semibold text-green-700 dark:text-green-400">{{
                            $totalDistCompeletedCampaign }}</p>
                    </div>
                    <div class="rounded-lg bg-yellow-100 dark:bg-yellow-900/30 p-3 text-center">
                        <span class="text-xs text-gray-700 dark:text-neutral-300">In Progress</span>
                        <p class="text-lg font-semibold text-yellow-700 dark:text-yellow-400">{{
                            $totalDistNotCompletedCampaigns }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-100 dark:bg-neutral-800 p-3 text-center">
                        <span class="text-xs text-gray-700 dark:text-neutral-300">Not Started</span>
                        <p class="text-lg font-semibold text-gray-700 dark:text-gray-400">{{
                            $totalDistNotStartedCampaign }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialer Campaigns Stats -->
        <div
            class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-800 overflow-hidden">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-neutral-200 mb-3">
                    Dialer Campaign Status
                </h3>

                <div class="grid grid-cols-3 gap-4">
                    <!-- Total Campaigns -->
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-neutral-400">Total</p>
                        <p class="text-xl font-semibold text-gray-800 dark:text-neutral-200">{{ $totalDialCampaign }}
                        </p>
                    </div>

                    <!-- Active Campaigns -->
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-neutral-400">Active</p>
                        <p class="text-xl font-semibold text-green-600 dark:text-green-500">{{ $totalDialActiveCampaign
                            }}</p>
                    </div>

                    <!-- Inactive Campaigns -->
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-neutral-400">Inactive</p>
                        <p class="text-xl font-semibold text-orange-500 dark:text-orange-400">{{
                            $totalDialInactiveCampaign }}</p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-5">
                    <div class="flex justify-between text-xs mb-1">
                        <span>Progress</span>
                        <span>{{ $totalDialCampaign > 0 ? round(($totalDialCompeletedCampaign / $totalDialCampaign) *
                            100) : 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-neutral-700">
                        <div class="bg-blue-600 h-2.5 rounded-full"
                            style="width: {{ $totalDialCampaign > 0 ? ($totalDialCompeletedCampaign / $totalDialCampaign) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>

                <!-- Campaign Status Indicators -->
                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-3 text-center">
                        <span class="text-xs text-gray-700 dark:text-neutral-300">Completed</span>
                        <p class="text-lg font-semibold text-green-700 dark:text-green-400">{{
                            $totalDialCompeletedCampaign }}</p>
                    </div>
                    <div class="rounded-lg bg-yellow-100 dark:bg-yellow-900/30 p-3 text-center">
                        <span class="text-xs text-gray-700 dark:text-neutral-300">In Progress</span>
                        <p class="text-lg font-semibold text-yellow-700 dark:text-yellow-400">{{
                            $totalDialNotCompletedCampaigns }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-100 dark:bg-neutral-800 p-3 text-center">
                        <span class="text-xs text-gray-700 dark:text-neutral-300">Not Started</span>
                        <p class="text-lg font-semibold text-gray-700 dark:text-gray-400">{{
                            $totalDialNotStartedCampaign }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Analytics Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
        <!-- Conversion Rate Card -->
        @livewire('systems.campaign.campaign-chart')
    </div>
</div>
<!-- End Card Section -->

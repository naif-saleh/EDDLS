<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
 <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<div class="bg-neutral-100 dark:bg-neutral-900 min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-5xl space-y-6">

        <!-- Header Section -->
        <div class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow p-6 text-center">
            <h1 class="text-3xl font-bold text-red-600 mb-2">
                <i class="fas fa-database mr-2"></i>Database Not Found
            </h1>
            <p class="text-gray-600 dark:text-gray-300">The requested tenant database could not be accessed.</p>
        </div>

        <!-- Info Grid -->
        <div class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Info Box -->
                <div class="flex flex-col items-center text-center space-y-3">
                    <div class="bg-red-100 dark:bg-red-900 text-red-500 rounded-full w-16 h-16 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold dark:text-white">Database Error</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">The tenant database doesn't exist or cannot be accessed.</p>
                </div>
                <!-- Info Box -->
                <div class="flex flex-col items-center text-center space-y-3">
                    <div class="bg-blue-100 dark:bg-blue-900 text-blue-500 rounded-full w-16 h-16 flex items-center justify-center">
                        <i class="fas fa-server text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold dark:text-white">Connection Issue</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Unable to establish a connection with the tenant's database.</p>
                </div>
                <!-- Info Box -->
                <div class="flex flex-col items-center text-center space-y-3">
                    <div class="bg-yellow-100 dark:bg-yellow-900 text-yellow-500 rounded-full w-16 h-16 flex items-center justify-center">
                        <i class="fas fa-cogs text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold dark:text-white">Setup Required</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">This tenant may need proper database setup and configuration.</p>
                </div>
            </div>
        </div>

        <!-- Main Illustration -->
        <div class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow p-6 flex flex-col items-center space-y-4">
            <div class="relative w-32 h-32">
                <div class="rounded-full bg-red-100 dark:bg-red-900 w-full h-full flex items-center justify-center">
                    <i class="fas fa-database text-5xl text-red-400 dark:text-red-300"></i>
                </div>
                <div class="absolute -top-2 -right-2 w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center">
                    <i class="fas fa-times text-xl"></i>
                </div>
            </div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Tenant Database Not Found</h2>
            <p class="text-gray-600 dark:text-gray-400 text-center max-w-md">The database for this tenant hasn't been provisioned yet or may be experiencing issues.</p>
        </div>

        <!-- Actions -->
        <div class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow p-6 flex flex-col md:flex-row justify-center items-center space-y-4 md:space-y-0 md:space-x-4">
            <a href="{{ route('tenant.dashboard', ['tenant' => auth()->user()->tenant->slug]) }}"
               class="bg-gray-700 hover:bg-gray-800 text-white px-6 py-2 rounded-lg flex items-center transition">
                <i class="fas fa-sync-alt mr-2"></i> Try Again
            </a>
            <a href="https://ecolor.com.sa/en/" target="_blank"
               class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg flex items-center transition">
                <i class="fas fa-headset mr-2"></i> Contact Support
            </a>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
            <p>If this issue persists, please contact your system administrator.</p>
            <p class="mt-1">Error code: <strong>TENANT-DB-404</strong></p>
        </div>
    </div>
</div>

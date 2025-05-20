<x-layouts.app :title="__('Tenant Database Not Found')">
     
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
 <div class="bg-neutral-200 dark:bg-neutral-800 min-h-screen">
    <div class="flex flex-col items-center justify-center min-h-screen p-4">
        <!-- Top Section - Header -->
        <div class="w-full max-w-4xl bg-white border border-gray-200 rounded-t-xl shadow-2xs bg-neutral-200 dark:bg-neutral-800 dark:border-neutral-700 p-6 text-center">
            <h1 class="text-3xl font-bold text-red-600 mb-2">
                <i class="fas fa-database mr-2"></i>Database Not Found
            </h1>
            <p class="text-gray-600 dark:text-gray-300 mb-0">The requested tenant database could not be accessed.</p>
        </div>

        <!-- Middle Section - Illustration Grid -->
        <div class="w-full max-w-4xl bg-white border border-gray-200 border-t-0 border-b-0 shadow-2xs bg-neutral-200 dark:bg-neutral-800 dark:border-neutral-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Icon 1 -->
                <div class="bg-neutral-200 dark:bg-neutral-800 rounded-lg p-6 text-center flex flex-col items-center">
                    <div class="bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400 rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-lg mb-2 dark:text-gray-200">Database Error</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">The tenant database doesn't exist or cannot be accessed.</p>
                </div>
                
                <!-- Icon 2 -->
                <div class="bg-neutral-200 dark:bg-neutral-800 rounded-lg p-6 text-center flex flex-col items-center">
                    <div class="bg-blue-100 dark:bg-blue-900/30 text-blue-500 dark:text-blue-400 rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-server text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-lg mb-2 dark:text-gray-200">Connection Issue</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Unable to establish a connection with the tenant's database.</p>
                </div>
                
                <!-- Icon 3 -->
                <div class="bg-neutral-200 dark:bg-neutral-800 rounded-lg p-6 text-center flex flex-col items-center">
                    <div class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-500 dark:text-yellow-400 rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-cogs text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-lg mb-2 dark:text-gray-200">Setup Required</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">This tenant may need proper database setup and configuration.</p>
                </div>
            </div>

            <!-- Main Illustration -->
            <div class="flex justify-center mb-6">
                <div class="bg-neutral-200 dark:bg-neutral-800 rounded-lg p-6 flex justify-center items-center w-full max-w-lg">
                    <div class="text-center">
                        <div class="flex justify-center mb-4">
                            <div class="relative">
                                <div class="w-32 h-32 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                    <i class="fas fa-database text-5xl text-red-400 dark:text-red-300"></i>
                                </div>
                                <div class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-10 h-10 flex items-center justify-center">
                                    <i class="fas fa-times text-xl"></i>
                                </div>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-gray-200 dark:text-gray-800 mb-2">Tenant Database Not Found</h2>
                        <p class="text-gray-600 dark:text-gray-400">The database for this tenant hasn't been provisioned yet or may be experiencing issues.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section - Actions -->
        <div class="w-full max-w-4xl bg-white border border-gray-200 rounded-b-xl shadow-2xs bg-neutral-200 dark:bg-neutral-800 dark:border-neutral-700 p-6">
            <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
               
                <a href="{{route('tenant.dashboard', ['tenant' => auth()->user()->tenant->slug])}}" class="bg-gray-600 hover:bg-gray-700 text-white py-2 px-6 rounded-lg text-center transition duration-300 flex items-center justify-center">
                    <i class="fas fa-sync-alt mr-2"></i> Try Again
                </a>
                <a href="https://ecolor.com.sa/en/" target="_blank" class="bg-green-600 hover:bg-green-700 text-white py-2 px-6 rounded-lg text-center transition duration-300 flex items-center justify-center">
                    <i class="fas fa-headset mr-2"></i> Contact Support
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-500 dark:text-gray-400 text-sm">
            <p>If this issue persists, please contact your system administrator.</p>
            <p class="mt-2">Error code: TENANT-DB-404</p>
        </div>
    </div>

    <script>
        // Optional: You could add some animations or interactions here
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded');
        });
    </script>
</div>

  </x-layouts.app>
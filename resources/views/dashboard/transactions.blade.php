<x-layouts.dashboard title="Transactions - TelconGH">
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Transactions
                </h2>
            </div>
        </div>

        <div class="mt-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Transaction History
                    </h3>
                    <div class="mt-2 max-w-xl text-sm text-gray-500">
                        <p>View and manage all your network service transactions.</p>
                    </div>
                    <div class="my-2">
                        <livewire:dashboard.transactions />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-layouts.dashboard>

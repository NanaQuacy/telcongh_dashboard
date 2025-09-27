<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
            <h3 class="text-base sm:text-lg font-medium text-gray-900">Product Portfolio</h3>
            <button class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-dark transition-colors w-full sm:w-auto">
                Add Product
            </button>
        </div>
    </div>
    
    <!-- Desktop Table -->
    <div class="hidden sm:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PRD001</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Product A</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">100</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$150.25</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$15,025</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">+2.5%</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button class="text-primary hover:text-primary-dark mr-3">Edit</button>
                        <button class="text-red-600 hover:text-red-800">Remove</button>
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PRD002</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Product B</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">50</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$245.80</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$12,290</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">-1.2%</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button class="text-primary hover:text-primary-dark mr-3">Edit</button>
                        <button class="text-red-600 hover:text-red-800">Remove</button>
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PRD003</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Product C</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">75</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$330.15</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$24,761</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">+0.8%</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button class="text-primary hover:text-primary-dark mr-3">Edit</button>
                        <button class="text-red-600 hover:text-red-800">Remove</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Mobile Cards -->
    <div class="sm:hidden divide-y divide-gray-200">
        <div class="p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-900">PRD001</span>
                    <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full">+2.5%</span>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">$15,025</p>
                    <p class="text-xs text-gray-500">100 units</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-3">Product A • $150.25</p>
            <div class="flex space-x-2">
                <button class="flex-1 bg-primary text-white px-3 py-2 rounded-md text-xs font-medium">Edit</button>
                <button class="flex-1 bg-red-600 text-white px-3 py-2 rounded-md text-xs font-medium">Remove</button>
            </div>
        </div>
        
        <div class="p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-900">PRD002</span>
                    <span class="text-xs text-red-600 bg-red-100 px-2 py-1 rounded-full">-1.2%</span>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">$12,290</p>
                    <p class="text-xs text-gray-500">50 units</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-3">Product B • $245.80</p>
            <div class="flex space-x-2">
                <button class="flex-1 bg-primary text-white px-3 py-2 rounded-md text-xs font-medium">Edit</button>
                <button class="flex-1 bg-red-600 text-white px-3 py-2 rounded-md text-xs font-medium">Remove</button>
            </div>
        </div>
        
        <div class="p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-900">PRD003</span>
                    <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full">+0.8%</span>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">$24,761</p>
                    <p class="text-xs text-gray-500">75 units</p>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-3">Product C • $330.15</p>
            <div class="flex space-x-2">
                <button class="flex-1 bg-primary text-white px-3 py-2 rounded-md text-xs font-medium">Edit</button>
                <button class="flex-1 bg-red-600 text-white px-3 py-2 rounded-md text-xs font-medium">Remove</button>
            </div>
        </div>
    </div>
</div>

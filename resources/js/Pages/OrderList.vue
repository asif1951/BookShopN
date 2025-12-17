<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-100 p-6">
            <Head title="Order List" />
            
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                {{ isAdmin ? 'Order List' : 'My Orders' }}
                            </h1>
                            <p class="text-gray-600 mt-2">
                                {{ isAdmin ? 'Manage and track all customer orders from here.' : 'View and track your orders from here.' }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Total Orders</p>
                                <p class="text-2xl font-bold text-indigo-600">{{ orders.total }}</p>
                            </div>
                            <!-- PDF Download Button with Date Range Modal Trigger -->
                            <button
                                @click="showDateRangeModal = true"
                                :disabled="isGeneratingPDF"
                                class="flex items-center space-x-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isGeneratingPDF" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>{{ isGeneratingPDF ? 'Generating PDF...' : 'Download PDF' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Date Range Modal -->
                <div v-if="showDateRangeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div class="mt-3">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                Select Date Range for PDF
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- All Orders Option -->
                                <div>
                                    <label class="flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            v-model="orderSelectionType"
                                            value="all"
                                            class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300"
                                        />
                                        <span class="text-sm font-medium text-gray-700">All Orders</span>
                                    </label>
                                </div>
                                
                                <!-- Date Range Option -->
                                <div>
                                    <label class="flex items-center space-x-2 mb-2">
                                        <input
                                            type="radio"
                                            v-model="orderSelectionType"
                                            value="dateRange"
                                            class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300"
                                        />
                                        <span class="text-sm font-medium text-gray-700">Select by Date Range</span>
                                    </label>
                                    
                                    <div v-if="orderSelectionType === 'dateRange'" class="space-y-4 pl-6 border-l-2 border-gray-200">
                                        <!-- Start Date -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Start Date
                                            </label>
                                            <input
                                                v-model="dateRange.startDate"
                                                type="date"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                            />
                                        </div>
                                        
                                        <!-- End Date -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                End Date
                                            </label>
                                            <input
                                                v-model="dateRange.endDate"
                                                type="date"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                            />
                                        </div>
                                        
                                        <!-- Quick Selection Buttons -->
                                        <div class="grid grid-cols-2 gap-2">
                                            <button
                                                @click="setDateRange('today')"
                                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md transition"
                                            >
                                                Today
                                            </button>
                                            <button
                                                @click="setDateRange('yesterday')"
                                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md transition"
                                            >
                                                Yesterday
                                            </button>
                                            <button
                                                @click="setDateRange('thisWeek')"
                                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md transition"
                                            >
                                                This Week
                                            </button>
                                            <button
                                                @click="setDateRange('thisMonth')"
                                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md transition"
                                            >
                                                This Month
                                            </button>
                                            <button
                                                @click="setDateRange('lastMonth')"
                                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md transition"
                                            >
                                                Last Month
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Selected Range Info -->
                                <div v-if="orderSelectionType === 'dateRange' && (dateRange.startDate || dateRange.endDate)" 
                                     class="p-3 bg-blue-50 rounded-md text-sm text-blue-700">
                                    <p v-if="dateRange.startDate && dateRange.endDate">
                                        Selected: {{ formatDateDisplay(dateRange.startDate) }} to {{ formatDateDisplay(dateRange.endDate) }}
                                    </p>
                                    <p v-else-if="dateRange.startDate">
                                        From: {{ formatDateDisplay(dateRange.startDate) }} onwards
                                    </p>
                                    <p v-else-if="dateRange.endDate">
                                        Until: {{ formatDateDisplay(dateRange.endDate) }}
                                    </p>
                                </div>
                                
                                <!-- Preview Orders Button -->
                                <div v-if="filteredOrders.length > 0" class="border-t border-gray-200 pt-4">
                                    <button
                                        @click="showPreview = true"
                                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition flex items-center justify-center space-x-2"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Preview {{ filteredOrders.length }} Orders</span>
                                    </button>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                    <button
                                        @click="showDateRangeModal = false"
                                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="generatePDFWithDateRange"
                                        :disabled="isGeneratingPDF || (orderSelectionType === 'dateRange' && !dateRange.startDate && !dateRange.endDate)"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {{ isGeneratingPDF ? 'Generating...' : 'Generate PDF' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Modal -->
                <div v-if="showPreview" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
                        <div class="mt-3">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">
                                    Preview Orders ({{ filteredOrders.length }} orders)
                                </h3>
                                <button
                                    @click="showPreview = false"
                                    class="text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto max-h-96">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Order ID
                                            </th>
                                            <th v-if="isAdmin" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Customer
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Amount
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Payment
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="order in filteredOrders.slice(0, 50)" :key="order.id" class="hover:bg-gray-50">
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">#{{ order.id }}</p>
                                                </div>
                                            </td>
                                            <td v-if="isAdmin" class="px-4 py-2 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ order.user?.name || 'N/A' }}</div>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <p class="text-sm font-semibold text-gray-900">${{ order.total_amount }}</p>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <span class="text-xs px-2 py-1 rounded-full" :class="getPaymentStatusClass(order.payment_status)">
                                                    {{ order.payment_status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(order.status)">
                                                    {{ order.status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                {{ formatDate(order.created_at) }}
                                            </td>
                                        </tr>
                                        <tr v-if="filteredOrders.length > 50">
                                            <td :colspan="isAdmin ? 6 : 5" class="px-4 py-3 text-center text-sm text-gray-500">
                                                ... and {{ filteredOrders.length - 50 }} more orders
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-4 flex justify-between items-center">
                                <div class="text-sm text-gray-500">
                                    Showing first 50 of {{ filteredOrders.length }} orders
                                </div>
                                <div class="flex space-x-3">
                                    <button
                                        @click="showPreview = false"
                                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition"
                                    >
                                        Close Preview
                                    </button>
                                    <button
                                        @click="generatePDFWithDateRange"
                                        :disabled="isGeneratingPDF"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {{ isGeneratingPDF ? 'Generating...' : 'Generate PDF Now' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Details Modal -->
                <div v-if="selectedOrder" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
                        <div class="mt-3">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">
                                    Order Details - #{{ selectedOrder.id }}
                                </h3>
                                <button
                                    @click="selectedOrder = null"
                                    class="text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Order Information -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-gray-700 mb-3">Order Information</h4>
                                    <div class="space-y-2">
                                        <p><span class="font-medium">Order ID:</span> #{{ selectedOrder.id }}</p>
                                        <p><span class="font-medium">Transaction ID:</span> {{ selectedOrder.transaction_id || 'N/A' }}</p>
                                        <p><span class="font-medium">Order Date:</span> {{ formatDate(selectedOrder.created_at) }}</p>
                                        <p><span class="font-medium">Status:</span> 
                                            <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(selectedOrder.status)">
                                                {{ selectedOrder.status }}
                                            </span>
                                        </p>
                                        <p><span class="font-medium">Payment Method:</span> {{ selectedOrder.payment_method }}</p>
                                        <p><span class="font-medium">Payment Status:</span> 
                                            <span class="px-2 py-1 text-xs rounded-full" :class="getPaymentStatusClass(selectedOrder.payment_status)">
                                                {{ selectedOrder.payment_status }}
                                            </span>
                                        </p>
                                        <p><span class="font-medium">Total Amount:</span> <span class="font-bold">${{ selectedOrder.total_amount }}</span></p>
                                    </div>
                                </div>
                                
                                <!-- Customer Information -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-gray-700 mb-3">Customer Information</h4>
                                    <div class="space-y-2">
                                        <p><span class="font-medium">Name:</span> {{ selectedOrder.user?.name || 'N/A' }}</p>
                                        <p><span class="font-medium">Email:</span> {{ selectedOrder.user?.email || 'N/A' }}</p>
                                        <div v-if="selectedOrder.shipping_address">
                                            <p class="font-medium">Shipping Address:</p>
                                            <p class="text-sm">{{ selectedOrder.shipping_address.address_line1 }}</p>
                                            <p class="text-sm" v-if="selectedOrder.shipping_address.address_line2">{{ selectedOrder.shipping_address.address_line2 }}</p>
                                            <p class="text-sm">{{ selectedOrder.shipping_address.city }}, {{ selectedOrder.shipping_address.state }}</p>
                                            <p class="text-sm">{{ selectedOrder.shipping_address.country }} - {{ selectedOrder.shipping_address.postal_code }}</p>
                                            <p class="text-sm"><span class="font-medium">Phone:</span> {{ selectedOrder.shipping_address.phone || 'N/A' }}</p>
                                        </div>
                                        <div v-else>
                                            <p class="text-gray-500">No shipping address provided</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Order Items -->
                                <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-gray-700 mb-3">Order Items</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="item in selectedOrder.items" :key="item.id">
                                                    <td class="px-4 py-2">
                                                        <div class="font-medium text-gray-900">{{ item.book?.title || 'Unknown Book' }}</div>
                                                        <div class="text-sm text-gray-500">by {{ item.book?.author || 'Unknown Author' }}</div>
                                                    </td>
                                                    <td class="px-4 py-2">${{ item.book?.price || '0.00' }}</td>
                                                    <td class="px-4 py-2">{{ item.quantity }}</td>
                                                    <td class="px-4 py-2 font-medium">${{ (parseFloat(item.book?.price || 0) * item.quantity).toFixed(2) }}</td>
                                                </tr>
                                                <tr class="bg-gray-50">
                                                    <td colspan="3" class="px-4 py-2 text-right font-medium">Subtotal:</td>
                                                    <td class="px-4 py-2 font-medium">${{ selectedOrder.total_amount }}</td>
                                                </tr>
                                                <tr class="bg-gray-50">
                                                    <td colspan="3" class="px-4 py-2 text-right font-medium">Tax (0%):</td>
                                                    <td class="px-4 py-2 font-medium">$0.00</td>
                                                </tr>
                                                <tr class="bg-gray-100">
                                                    <td colspan="3" class="px-4 py-2 text-right font-bold">Grand Total:</td>
                                                    <td class="px-4 py-2 font-bold">${{ selectedOrder.total_amount }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end space-x-3">
                                <button
                                    @click="printSingleOrder(selectedOrder)"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition flex items-center space-x-2"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                    <span>Print Invoice</span>
                                </button>
                                <button
                                    @click="downloadOrderPDF(selectedOrder)"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition flex items-center space-x-2"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span>Download PDF</span>
                                </button>
                                <button
                                    @click="selectedOrder = null"
                                    class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards - শুধু Admin এর জন্য -->
                <div v-if="isAdmin" class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <span class="text-2xl">🛒</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-2xl font-bold text-orange-500">{{ statusCounts.pending }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <span class="text-2xl">✅</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Confirmed</p>
                                <p class="text-2xl font-bold text-blue-500">{{ statusCounts.confirmed }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <span class="text-2xl">⚡</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Processing</p>
                                <p class="text-2xl font-bold text-purple-500">{{ statusCounts.processing }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <span class="text-2xl">🚚</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Shipped</p>
                                <p class="text-2xl font-bold text-green-500">{{ statusCounts.shipped }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-teal-500">
                        <div class="flex items-center">
                            <div class="p-2 bg-teal-100 rounded-lg">
                                <span class="text-2xl">🎉</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Delivered</p>
                                <p class="text-2xl font-bold text-teal-500">{{ statusCounts.delivered }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <span class="text-2xl">❌</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Cancelled</p>
                                <p class="text-2xl font-bold text-red-500">{{ statusCounts.cancelled }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ isAdmin ? 'All Orders (Oldest First)' : 'My Orders' }}
                        </h2>
                        <div class="text-sm text-gray-500">
                            Showing {{ orders.from }}-{{ orders.to }} of {{ orders.total }} orders
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Order ID
                                    </th>
                                    <!-- Customer Column - সব User এর জন্য -->
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Customer
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Items
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Payment
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <!-- Actions Column - শুধু Admin এর জন্য -->
                                    <th v-if="isAdmin" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <!-- Print Column - সকল User এর জন্য -->
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 transition-colors">
                                    <!-- Order ID with clickable details -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="cursor-pointer hover:text-blue-600 transition" @click="showOrderDetails(order)">
                                            <p class="text-sm font-medium text-gray-900">#{{ order.id }}</p>
                                            <p class="text-xs text-gray-500 mt-1">Click for details</p>
                                        </div>
                                    </td>
                                    <!-- Customer Data - সব User এর জন্য -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ order.user?.name || 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ order.user?.email || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs">
                                            <div v-for="item in order.items" :key="item.id" class="mb-1 flex justify-between">
                                                <span class="truncate">{{ item.book?.title || 'Unknown Book' }}</span>
                                                <span class="ml-2 text-gray-500">×{{ item.quantity }}</span>
                                            </div>
                                            <p class="text-xs text-gray-400 mt-1">
                                                Total Items: {{ getTotalItems(order) }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-semibold text-gray-900">${{ order.total_amount }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-xs font-medium text-gray-700 capitalize">
                                                {{ order.payment_method }}
                                            </span>
                                            <span class="text-xs px-2 py-1 rounded-full" :class="getPaymentStatusClass(order.payment_status)">
                                                {{ order.payment_status }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(order.status)">
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <!-- Actions - শুধু Admin এর জন্য -->
                                    <td v-if="isAdmin" class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <!-- Status Change Dropdown -->
                                            <select 
                                                v-model="order.status" 
                                                @change="updateOrderStatus(order)"
                                                class="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                                :class="getStatusClass(order.status)"
                                            >
                                                <option value="pending" class="bg-yellow-100 text-yellow-800">Pending</option>
                                                <option value="confirmed" class="bg-blue-100 text-blue-800">Confirmed</option>
                                                <option value="processing" class="bg-purple-100 text-purple-800">Processing</option>
                                                <option value="shipped" class="bg-green-100 text-green-800">Shipped</option>
                                                <option value="delivered" class="bg-teal-100 text-teal-800">Delivered</option>
                                                <option value="cancelled" class="bg-red-100 text-red-800">Cancelled</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(order.created_at) }}
                                    </td>
                                    <!-- Action Buttons with tooltips -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <!-- Details Button -->
                                            <button
                                                @click="showOrderDetails(order)"
                                                title="View Details"
                                                class="relative text-gray-600 hover:text-blue-700 transition-colors p-2 hover:bg-blue-50 rounded group"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                    View Details
                                                </div>
                                            </button>
                                            
                                            <!-- Print Button -->
                                            <button
                                                @click="() => printSingleOrder(order)"
                                                title="Print this order"
                                                class="relative text-gray-600 hover:text-blue-700 transition-colors p-2 hover:bg-blue-50 rounded group"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                                </svg>
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                    Print Invoice
                                                </div>
                                            </button>
                                            
                                            <!-- Download PDF Button with detailed tooltip -->
                                            <button
                                                @click="() => downloadOrderPDF(order)"
                                                title="Download PDF Invoice"
                                                class="relative text-gray-600 hover:text-red-700 transition-colors p-2 hover:bg-red-50 rounded group"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                    <div class="text-center">
                                                        <div class="font-semibold">Download PDF</div>
                                                        <div class="text-gray-300">Invoice with professional design</div>
                                                        <div class="text-gray-300">Tax: 0% | All details included</div>
                                                    </div>
                                                </div>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="orders.data.length === 0" class="text-center py-12">
                        <div class="text-6xl mb-4">📦</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                        <p class="text-gray-500">
                            {{ isAdmin ? 'There are no orders in the system yet.' : 'You have not placed any orders yet.' }}
                        </p>
                    </div>

                    <!-- DYNAMIC PAGINATION WITH PAGE INPUT -->
                    <div v-if="orders.data.length > 0" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between items-center sm:hidden">
                            <button
                                @click="previousPage"
                                :disabled="!orders.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Previous
                            </button>
                            <div class="text-sm text-gray-700">
                                Page <span class="font-medium">{{ orders.current_page }}</span> of <span class="font-medium">{{ orders.last_page }}</span>
                            </div>
                            <button
                                @click="nextPage"
                                :disabled="!orders.next_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Next
                            </button>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ orders.from }}</span>
                                    to
                                    <span class="font-medium">{{ orders.to }}</span>
                                    of
                                    <span class="font-medium">{{ orders.total }}</span>
                                    results
                                </p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <!-- Page Input -->
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-700">Go to page:</span>
                                    <div class="relative">
                                        <input
                                            v-model="pageForm.page"
                                            type="number"
                                            min="1"
                                            :max="orders.last_page"
                                            class="w-16 px-2 py-1 border border-gray-300 rounded-md text-sm text-center focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                            @keypress="handlePageInputKeypress"
                                            @blur="goToSpecificPage"
                                        >
                                        <span class="absolute right-2 top-1 text-xs text-gray-500">/{{ orders.last_page }}</span>
                                    </div>
                                    <button
                                        @click="goToSpecificPage"
                                        class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition"
                                    >
                                        Go
                                    </button>
                                </div>

                                <!-- Pagination Navigation -->
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <!-- Previous Page Link -->
                                    <button
                                        @click="previousPage"
                                        :disabled="!orders.prev_page_url"
                                        :class="{'opacity-50 cursor-not-allowed pointer-events-none': !orders.prev_page_url}"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <!-- Dynamic Page Numbers -->
                                    <template v-for="pageNumber in pageNumbers" :key="pageNumber">
                                        <span 
                                            v-if="pageNumber === '...'"
                                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                        >
                                            ...
                                        </span>
                                        <button
                                            v-else
                                            @click="goToPage(pageNumber)"
                                            :class="{
                                                'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': pageNumber === orders.current_page,
                                                'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': pageNumber !== orders.current_page
                                            }"
                                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                        >
                                            {{ pageNumber }}
                                        </button>
                                    </template>

                                    <!-- Next Page Link -->
                                    <button
                                        @click="nextPage"
                                        :disabled="!orders.next_page_url"
                                        :class="{'opacity-50 cursor-not-allowed pointer-events-none': !orders.next_page_url}"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span class="sr-only">Next</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

export default {
    components: {
        Head,
        AuthenticatedLayout
    },
    props: {
        orders: {
            type: Object,
            default: () => ({})
        },
        isAdmin: {
            type: Boolean,
            default: false
        }
    },
    setup(props) {
        // Page input form
        const pageForm = useForm({
            page: props.orders.current_page || 1
        })

        // PDF Generation State
        const isGeneratingPDF = ref(false)
        const showDateRangeModal = ref(false)
        const showPreview = ref(false)
        const orderSelectionType = ref('all') // 'all' or 'dateRange'
        
        // Date Range State
        const dateRange = ref({
            startDate: '',
            endDate: ''
        })

        // Selected Order for Details Modal
        const selectedOrder = ref(null)

        // Filtered orders for preview
        const filteredOrders = computed(() => {
            if (orderSelectionType.value === 'all') {
                return props.orders.data
            } else if (orderSelectionType.value === 'dateRange') {
                return props.orders.data.filter(order => {
                    const orderDate = new Date(order.created_at)
                    
                    if (dateRange.value.startDate && dateRange.value.endDate) {
                        const start = new Date(dateRange.value.startDate)
                        const end = new Date(dateRange.value.endDate)
                        end.setHours(23, 59, 59, 999)
                        
                        return orderDate >= start && orderDate <= end
                    } else if (dateRange.value.startDate) {
                        const start = new Date(dateRange.value.startDate)
                        return orderDate >= start
                    } else if (dateRange.value.endDate) {
                        const end = new Date(dateRange.value.endDate)
                        end.setHours(23, 59, 59, 999)
                        return orderDate <= end
                    }
                    
                    return false // No dates selected
                })
            }
            return []
        })

        // Calculate order status counts from all data (not just current page)
        const statusCounts = computed(() => {
            const counts = {
                pending: 0,
                confirmed: 0,
                processing: 0,
                shipped: 0,
                delivered: 0,
                cancelled: 0
            }
            
            props.orders.data?.forEach(order => {
                if (counts.hasOwnProperty(order.status)) {
                    counts[order.status]++
                }
            })
            
            return counts
        })

        // Calculate payment status counts
        const paymentStatusCounts = computed(() => {
            const counts = {
                pending: 0,
                paid: 0,
                failed: 0,
                refunded: 0
            }
            
            props.orders.data?.forEach(order => {
                if (counts.hasOwnProperty(order.payment_status)) {
                    counts[order.payment_status]++
                }
            })
            
            return counts
        })

        // Get total items in an order
        const getTotalItems = (order) => {
            return order.items?.reduce((total, item) => total + item.quantity, 0) || 0
        }

        // Status badge classes
        const getStatusClass = (status) => {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                confirmed: 'bg-blue-100 text-blue-800',
                processing: 'bg-purple-100 text-purple-800',
                shipped: 'bg-green-100 text-green-800',
                delivered: 'bg-teal-100 text-teal-800',
                cancelled: 'bg-red-100 text-red-800'
            }
            return classes[status] || 'bg-gray-100 text-gray-800'
        }

        // Payment status badge classes
        const getPaymentStatusClass = (paymentStatus) => {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                paid: 'bg-green-100 text-green-800',
                failed: 'bg-red-100 text-red-800',
                refunded: 'bg-gray-100 text-gray-800'
            }
            return classes[paymentStatus] || 'bg-gray-100 text-gray-800'
        }

        // Format date
        const formatDate = (dateString) => {
            const date = new Date(dateString)
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        }

        // Format date for PDF (without time)
        const formatDateForPDF = (dateString) => {
            const date = new Date(dateString)
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            })
        }

        // Format date for display
        const formatDateDisplay = (dateString) => {
            const date = new Date(dateString)
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            })
        }

        // Show order details modal
        const showOrderDetails = (order) => {
            selectedOrder.value = order
        }

        // Custom alert function for better UX
        const showAlert = (message, type = 'info') => {
            alert(message)
        }

        // Set date range based on predefined options
        const setDateRange = (rangeType) => {
            const today = new Date()
            const start = new Date()
            const end = new Date()

            switch (rangeType) {
                case 'today':
                    dateRange.value.startDate = today.toISOString().split('T')[0]
                    dateRange.value.endDate = today.toISOString().split('T')[0]
                    break
                    
                case 'yesterday':
                    start.setDate(today.getDate() - 1)
                    end.setDate(today.getDate() - 1)
                    dateRange.value.startDate = start.toISOString().split('T')[0]
                    dateRange.value.endDate = end.toISOString().split('T')[0]
                    break
                    
                case 'thisWeek':
                    start.setDate(today.getDate() - today.getDay())
                    dateRange.value.startDate = start.toISOString().split('T')[0]
                    dateRange.value.endDate = today.toISOString().split('T')[0]
                    break
                    
                case 'thisMonth':
                    start.setDate(1)
                    dateRange.value.startDate = start.toISOString().split('T')[0]
                    dateRange.value.endDate = today.toISOString().split('T')[0]
                    break
                    
                case 'lastMonth':
                    start.setMonth(today.getMonth() - 1, 1)
                    end.setMonth(today.getMonth(), 0)
                    dateRange.value.startDate = start.toISOString().split('T')[0]
                    dateRange.value.endDate = end.toISOString().split('T')[0]
                    break
            }
        }

        // Generate PDF with date range
        const generatePDFWithDateRange = async () => {
            if (orderSelectionType.value === 'dateRange' && !dateRange.value.startDate && !dateRange.value.endDate) {
                showAlert('Please select a date range or choose "All Orders".', 'warning')
                return
            }

            isGeneratingPDF.value = true
            
            try {
                // Validate date range
                if (orderSelectionType.value === 'dateRange' && dateRange.value.startDate && dateRange.value.endDate) {
                    const start = new Date(dateRange.value.startDate)
                    const end = new Date(dateRange.value.endDate)
                    
                    if (start > end) {
                        showAlert('Start date cannot be after end date.', 'error')
                        isGeneratingPDF.value = false
                        return
                    }
                }

                // Get orders to export
                const ordersToExport = filteredOrders.value

                if (ordersToExport.length === 0) {
                    showAlert('No orders found in the selected range.', 'warning')
                    isGeneratingPDF.value = false
                    return
                }

                // Generate PDF with filtered orders
                await generateClientSidePDF(ordersToExport)
                
            } catch (error) {
                console.error('PDF generation failed:', error)
                showAlert('Failed to generate PDF. Please try again.', 'error')
            } finally {
                isGeneratingPDF.value = false
                showDateRangeModal.value = false
                showPreview.value = false
            }
        }

        // Client-side PDF generation using jsPDF with UTF-8 support - PROFESSIONAL DESIGN
        const generateClientSidePDF = async (ordersToExport) => {
            try {
                // Check if jsPDF is available
                if (typeof window.jspdf === 'undefined') {
                    // Load jsPDF dynamically
                    await loadJSPDF()
                }

                const { jsPDF } = window.jspdf
                const doc = new jsPDF('p', 'mm', 'a4')
                
                let yPos = 20
                const pageWidth = doc.internal.pageSize.getWidth()
                const margin = 20
                const contentWidth = pageWidth - (margin * 2)
                
                // Professional Header with Dark Blue Color
                doc.setFillColor(30, 58, 138) // Professional dark blue
                doc.rect(0, 0, pageWidth, 35, 'F')
                
                // Header text
                doc.setFontSize(22)
                doc.setFont("helvetica", "bold")
                doc.setTextColor(255, 255, 255)
                doc.text(props.isAdmin ? "ORDER MANAGEMENT REPORT" : "PURCHASE HISTORY", pageWidth / 2, 20, { align: 'center' })
                
                // Subtitle
                doc.setFontSize(10)
                doc.setFont("helvetica", "normal")
                doc.text("Book Store - Official Report", pageWidth / 2, 27, { align: 'center' })
                
                // Report info
                doc.setFontSize(9)
                doc.setTextColor(200, 200, 200)
                doc.text(`Generated: ${new Date().toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })}`, margin, 32)
                
                let dateRangeText = ''
                if (orderSelectionType.value === 'dateRange') {
                    if (dateRange.value.startDate && dateRange.value.endDate) {
                        dateRangeText = `${formatDateDisplay(dateRange.value.startDate)} - ${formatDateDisplay(dateRange.value.endDate)}`
                    } else if (dateRange.value.startDate) {
                        dateRangeText = `From ${formatDateDisplay(dateRange.value.startDate)}`
                    } else if (dateRange.value.endDate) {
                        dateRangeText = `Until ${formatDateDisplay(dateRange.value.endDate)}`
                    }
                    doc.text(`Period: ${dateRangeText}`, pageWidth - margin, 32, { align: 'right' })
                } else {
                    doc.text(`Period: All Records`, pageWidth - margin, 32, { align: 'right' })
                }
                
                yPos = 45
                
                // Summary section - Professional minimalist design
                if (props.isAdmin && statusCounts.value) {
                    doc.setFontSize(12)
                    doc.setFont("helvetica", "bold")
                    doc.setTextColor(30, 58, 138)
                    doc.text("SUMMARY OVERVIEW", margin, yPos)
                    yPos += 8
                    
                    // Summary stats in a clean table format
                    doc.setFontSize(9)
                    doc.setFont("helvetica", "normal")
                    doc.setTextColor(100, 100, 100)
                    
                    const summaryData = [
                        { label: 'Total Orders', value: ordersToExport.length, color: [30, 58, 138] },
                        { label: 'Pending', value: statusCounts.value.pending, color: [245, 158, 11] },
                        { label: 'Confirmed', value: statusCounts.value.confirmed, color: [59, 130, 246] },
                        { label: 'Processing', value: statusCounts.value.processing, color: [139, 92, 246] },
                        { label: 'Shipped', value: statusCounts.value.shipped, color: [16, 185, 129] },
                        { label: 'Delivered', value: statusCounts.value.delivered, color: [14, 165, 233] },
                        { label: 'Cancelled', value: statusCounts.value.cancelled || 0, color: [239, 68, 68] }
                    ]
                    
                    let xPos = margin
                    const boxWidth = (contentWidth - 30) / 7 // 7 boxes with spacing
                    
                    for (let i = 0; i < summaryData.length; i++) {
                        // Box with subtle border
                        doc.setDrawColor(summaryData[i].color[0], summaryData[i].color[1], summaryData[i].color[2])
                        doc.setLineWidth(0.3)
                        doc.rect(xPos, yPos, boxWidth, 15)
                        
                        // Value
                        doc.setFontSize(10)
                        doc.setFont("helvetica", "bold")
                        doc.setTextColor(summaryData[i].color[0], summaryData[i].color[1], summaryData[i].color[2])
                        doc.text(summaryData[i].value.toString(), xPos + boxWidth/2, yPos + 7, { align: 'center' })
                        
                        // Label
                        doc.setFontSize(7)
                        doc.setTextColor(100, 100, 100)
                        doc.text(summaryData[i].label.toUpperCase(), xPos + boxWidth/2, yPos + 12, { align: 'center' })
                        
                        xPos += boxWidth + 5
                    }
                    
                    yPos += 25
                }
                
                // Report metadata
                const totalAmount = ordersToExport.reduce((sum, order) => sum + parseFloat(order.total_amount || 0), 0).toFixed(2)
                doc.setFontSize(10)
                doc.setTextColor(30, 58, 138)
                doc.text(`Total Amount: $${totalAmount} | ${ordersToExport.length} Orders`, margin, yPos)
                yPos += 10
                
                // Add subtle divider
                doc.setDrawColor(200, 200, 200)
                doc.setLineWidth(0.5)
                doc.line(margin, yPos, pageWidth - margin, yPos)
                yPos += 15
                
                // Order details table with professional design
                doc.setFontSize(13)
                doc.setFont("helvetica", "bold")
                doc.setTextColor(30, 58, 138)
                doc.text("ORDER DETAILS", margin, yPos)
                yPos += 10
                
                // Table headers with professional gray background
                doc.setFillColor(249, 250, 251)
                doc.rect(margin, yPos, contentWidth, 8, 'F')
                doc.setDrawColor(209, 213, 219)
                doc.rect(margin, yPos, contentWidth, 8, 'S')
                
                doc.setTextColor(55, 65, 81)
                doc.setFontSize(9)
                doc.setFont("helvetica", "bold")
                
                const headers = ["Order ID", props.isAdmin ? "Customer" : "", "Amount", "Status", "Date"]
                const colWidths = [25, props.isAdmin ? 45 : 0, 25, 25, 40]
                
                // Adjust colWidths if not admin
                if (!props.isAdmin) {
                    colWidths[1] = 0
                    colWidths[3] = 35
                    colWidths[4] = 55
                }
                
                // Draw table header text
                let xPos = margin
                headers.forEach((header, index) => {
                    if (colWidths[index] > 0 && header !== "") {
                        doc.text(header, xPos + 3, yPos + 5.5)
                        xPos += colWidths[index]
                    }
                })
                
                yPos += 10
                
                // Table rows with alternating colors
                doc.setFont("helvetica", "normal")
                doc.setFontSize(8)
                
                ordersToExport.forEach((order, index) => {
                    // Check if we need a new page
                    if (yPos > 270) {
                        doc.addPage()
                        yPos = 20
                        // Add header to new page
                        doc.setFillColor(30, 58, 138)
                        doc.rect(0, 0, pageWidth, 15, 'F')
                        doc.setFontSize(10)
                        doc.setFont("helvetica", "bold")
                        doc.setTextColor(255, 255, 255)
                        doc.text("ORDER MANAGEMENT REPORT - CONTINUED", pageWidth / 2, 10, { align: 'center' })
                        yPos = 25
                    }
                    
                    // Alternate row colors for readability
                    if (index % 2 === 0) {
                        doc.setFillColor(250, 250, 250)
                        doc.rect(margin, yPos - 1, contentWidth, 7, 'F')
                    }
                    
                    xPos = margin
                    
                    // Order ID
                    doc.setTextColor(30, 58, 138)
                    doc.setFont("helvetica", "bold")
                    doc.text(`#${order.id}`, xPos + 3, yPos + 4.5)
                    xPos += colWidths[0]
                    
                    // Customer name (if admin)
                    if (props.isAdmin && colWidths[1] > 0) {
                        const customerName = order.user?.name || 'N/A'
                        const cleanCustomerName = customerName.replace(/[^\x00-\x7F]/g, '')
                        doc.setTextColor(55, 65, 81)
                        doc.setFont("helvetica", "normal")
                        doc.text(cleanCustomerName.substring(0, 20), xPos + 3, yPos + 4.5)
                        xPos += colWidths[1]
                    }
                    
                    // Amount
                    doc.setTextColor(16, 185, 129)
                    doc.setFont("helvetica", "bold")
                    doc.text(`$${order.total_amount}`, xPos + 3, yPos + 4.5)
                    xPos += colWidths[2]
                    
                    // Status with colored indicator
                    doc.setTextColor(55, 65, 81)
                    const statusColors = {
                        pending: [245, 158, 11],
                        confirmed: [59, 130, 246],
                        processing: [139, 92, 246],
                        shipped: [16, 185, 129],
                        delivered: [14, 165, 233],
                        cancelled: [239, 68, 68]
                    }
                    const color = statusColors[order.status] || [100, 100, 100]
                    doc.setFillColor(color[0], color[1], color[2], 0.1)
                    doc.setDrawColor(color[0], color[1], color[2])
                    doc.setLineWidth(0.3)
                    doc.roundedRect(xPos + 3, yPos - 0.5, 18, 5, 1, 1, 'FD')
                    doc.setTextColor(color[0], color[1], color[2])
                    doc.setFontSize(7)
                    doc.text(order.status.charAt(0).toUpperCase(), xPos + 12, yPos + 3.5, { align: 'center' })
                    doc.setFontSize(8)
                    xPos += colWidths[3]
                    
                    // Date
                    doc.setTextColor(107, 114, 128)
                    doc.text(formatDateForPDF(order.created_at), xPos + 3, yPos + 4.5)
                    
                    yPos += 7
                })
                
                // Add footer with professional information
                const totalPages = doc.internal.getNumberOfPages()
                for (let i = 1; i <= totalPages; i++) {
                    doc.setPage(i)
                    
                    // Page number
                    doc.setFontSize(8)
                    doc.setTextColor(107, 114, 128)
                    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, doc.internal.pageSize.getHeight() - 10, { align: 'center' })
                    
                    // Footer line
                    doc.setDrawColor(229, 231, 235)
                    doc.setLineWidth(0.5)
                    doc.line(margin, doc.internal.pageSize.getHeight() - 15, pageWidth - margin, doc.internal.pageSize.getHeight() - 15)
                    
                    // Company info
                    doc.setFontSize(7)
                    doc.text("Book Store Ltd. | Professional Invoice System", margin, doc.internal.pageSize.getHeight() - 5)
                    doc.text(`Report ID: BS-${Date.now().toString().slice(-8)}`, pageWidth - margin, doc.internal.pageSize.getHeight() - 5, { align: 'right' })
                }
                
                // Generate filename with date range
                const timestamp = new Date().toISOString().slice(0, 10)
                let fileName = props.isAdmin ? `order-report-${timestamp}` : `my-orders-${timestamp}`
                
                // Add date range to filename if specified
                if (orderSelectionType.value === 'dateRange') {
                    if (dateRange.value.startDate && dateRange.value.endDate) {
                        const start = dateRange.value.startDate.replace(/-/g, '')
                        const end = dateRange.value.endDate.replace(/-/g, '')
                        fileName += `_${start}-${end}`
                    } else if (dateRange.value.startDate) {
                        const start = dateRange.value.startDate.replace(/-/g, '')
                        fileName += `_from-${start}`
                    } else if (dateRange.value.endDate) {
                        const end = dateRange.value.endDate.replace(/-/g, '')
                        fileName += `_until-${end}`
                    }
                } else {
                    fileName += '_complete'
                }
                
                fileName += '.pdf'
                
                // Save the PDF
                doc.save(fileName)
                
                showAlert('Professional PDF report downloaded successfully!', 'success')
                
            } catch (error) {
                console.error('Client-side PDF generation failed:', error)
                // Fallback to printable version
                generatePrintableVersion(ordersToExport)
            }
        }

        // Fallback printable version with professional design
        const generatePrintableVersion = (ordersToExport) => {
            if (confirm('Would you like to generate a printable version instead?')) {
                const ordersData = ordersToExport
                const printWindow = window.open('', '_blank')
                
                const printContent = generatePrintableContent(ordersData)
                
                printWindow.document.write(printContent)
                printWindow.document.close()
            }
        }

        // Generate printable content with professional design
        const generatePrintableContent = (ordersData) => {
            const dateRangeText = orderSelectionType.value === 'dateRange' && (dateRange.value.startDate || dateRange.value.endDate) ? 
                `Date Range: ${dateRange.value.startDate ? formatDateDisplay(dateRange.value.startDate) : 'Beginning'} to ${dateRange.value.endDate ? formatDateDisplay(dateRange.value.endDate) : 'Now'}` : 
                'All Orders'
            
            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${props.isAdmin ? 'Professional Order Report' : 'My Order History'}</title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
                        
                        body { 
                            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                            margin: 0; 
                            padding: 30px; 
                            background: #f8fafc;
                            color: #1e293b;
                        }
                        
                        .report-container {
                            max-width: 1200px;
                            margin: 0 auto;
                            background: white;
                            border-radius: 12px;
                            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                            overflow: hidden;
                        }
                        
                        .report-header { 
                            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
                            color: white;
                            padding: 40px;
                            position: relative;
                        }
                        
                        .report-header::after {
                            content: '';
                            position: absolute;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            height: 4px;
                            background: linear-gradient(90deg, #3b82f6, #10b981, #8b5cf6);
                        }
                        
                        .report-header h1 { 
                            font-size: 32px;
                            margin: 0 0 10px 0;
                            font-weight: 700;
                            letter-spacing: -0.5px;
                        }
                        
                        .report-header .subtitle {
                            font-size: 16px;
                            opacity: 0.9;
                            margin-bottom: 20px;
                        }
                        
                        .report-meta {
                            display: flex;
                            justify-content: space-between;
                            flex-wrap: wrap;
                            gap: 20px;
                            margin-top: 20px;
                            padding-top: 20px;
                            border-top: 1px solid rgba(255, 255, 255, 0.2);
                        }
                        
                        .meta-item {
                            flex: 1;
                            min-width: 200px;
                        }
                        
                        .meta-label {
                            font-size: 12px;
                            text-transform: uppercase;
                            letter-spacing: 1px;
                            opacity: 0.7;
                            margin-bottom: 5px;
                        }
                        
                        .meta-value {
                            font-size: 16px;
                            font-weight: 600;
                        }
                        
                        .summary-cards {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                            gap: 20px;
                            margin: 30px;
                            padding: 0;
                        }
                        
                        .summary-card {
                            background: white;
                            border-radius: 8px;
                            padding: 20px;
                            text-align: center;
                            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                            border: 1px solid #e2e8f0;
                            transition: transform 0.2s ease;
                        }
                        
                        .summary-card:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        }
                        
                        .summary-card .count {
                            font-size: 28px;
                            font-weight: 700;
                            margin-bottom: 8px;
                        }
                        
                        .summary-card .label {
                            font-size: 12px;
                            text-transform: uppercase;
                            letter-spacing: 1px;
                            color: #64748b;
                            font-weight: 600;
                        }
                        
                        .table-container {
                            margin: 30px;
                            border-radius: 8px;
                            overflow: hidden;
                            border: 1px solid #e2e8f0;
                        }
                        
                        .data-table { 
                            width: 100%; 
                            border-collapse: collapse;
                            font-size: 14px;
                        }
                        
                        .data-table th { 
                            background: #f1f5f9;
                            color: #475569;
                            padding: 16px 12px;
                            text-align: left;
                            font-weight: 600;
                            text-transform: uppercase;
                            font-size: 12px;
                            letter-spacing: 0.5px;
                            border-bottom: 2px solid #e2e8f0;
                        }
                        
                        .data-table td { 
                            padding: 14px 12px;
                            border-bottom: 1px solid #f1f5f9;
                            vertical-align: middle;
                        }
                        
                        .data-table tr:hover {
                            background-color: #f8fafc;
                        }
                        
                        .data-table tr:last-child td {
                            border-bottom: none;
                        }
                        
                        .status-indicator {
                            padding: 6px 12px;
                            border-radius: 20px;
                            font-size: 11px;
                            font-weight: 600;
                            display: inline-block;
                            text-align: center;
                            min-width: 90px;
                        }
                        
                        .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
                        .status-confirmed { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
                        .status-processing { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
                        .status-shipped { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
                        .status-delivered { background: #cffafe; color: #0e7490; border: 1px solid #a5f3fc; }
                        .status-cancelled { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
                        
                        .report-footer { 
                            background: #f8fafc;
                            padding: 30px;
                            text-align: center;
                            border-top: 1px solid #e2e8f0;
                            margin-top: 40px;
                        }
                        
                        .report-footer p {
                            margin: 5px 0;
                            color: #64748b;
                            font-size: 13px;
                        }
                        
                        .print-btn { 
                            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
                            color: white; 
                            border: none; 
                            padding: 14px 32px; 
                            border-radius: 8px; 
                            cursor: pointer; 
                            margin: 30px auto;
                            display: block;
                            font-size: 15px;
                            font-weight: 600;
                            transition: all 0.2s ease;
                            box-shadow: 0 2px 4px rgba(30, 58, 138, 0.2);
                        }
                        
                        .print-btn:hover {
                            transform: translateY(-1px);
                            box-shadow: 0 4px 8px rgba(30, 58, 138, 0.3);
                        }
                        
                        .tax-note {
                            background: #f0f9ff;
                            border: 1px solid #bae6fd;
                            border-radius: 8px;
                            padding: 15px;
                            margin: 20px 30px;
                            text-align: center;
                            color: #0369a1;
                            font-weight: 500;
                        }
                        
                        @media print {
                            body { 
                                background: none;
                                margin: 10mm;
                                padding: 0;
                            }
                            .no-print { display: none !important; }
                            .report-container {
                                box-shadow: none;
                                border-radius: 0;
                                max-width: 100%;
                            }
                            .print-btn { display: none !important; }
                            .data-table { 
                                font-size: 12px;
                            }
                            .summary-card {
                                break-inside: avoid;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="report-container">
                        <div class="report-header">
                            <h1>${props.isAdmin ? '📊 PROFESSIONAL ORDER REPORT' : '📦 MY ORDER HISTORY'}</h1>
                            <div class="subtitle">Book Store - Comprehensive Order Management</div>
                            
                            <div class="report-meta">
                                <div class="meta-item">
                                    <div class="meta-label">Generated On</div>
                                    <div class="meta-value">${new Date().toLocaleDateString('en-US', { 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}</div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-label">Report Period</div>
                                    <div class="meta-value">${dateRangeText}</div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-label">Total Orders</div>
                                    <div class="meta-value">${ordersData.length} orders</div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-label">Report ID</div>
                                    <div class="meta-value">BS-${Date.now().toString().slice(-8)}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tax-note">
                            <strong>Tax Information:</strong> All prices include 0% tax as per current regulations. This is a tax-exempt invoice.
                        </div>
                        
                        ${props.isAdmin ? `
                        <div class="summary-cards">
                            <div class="summary-card">
                                <div class="count">${statusCounts.value.pending}</div>
                                <div class="label">Pending</div>
                            </div>
                            <div class="summary-card">
                                <div class="count">${statusCounts.value.confirmed}</div>
                                <div class="label">Confirmed</div>
                            </div>
                            <div class="summary-card">
                                <div class="count">${statusCounts.value.processing}</div>
                                <div class="label">Processing</div>
                            </div>
                            <div class="summary-card">
                                <div class="count">${statusCounts.value.shipped}</div>
                                <div class="label">Shipped</div>
                            </div>
                            <div class="summary-card">
                                <div class="count">${statusCounts.value.delivered}</div>
                                <div class="label">Delivered</div>
                            </div>
                            <div class="summary-card">
                                <div class="count">${statusCounts.value.cancelled || 0}</div>
                                <div class="label">Cancelled</div>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="10%">Order ID</th>
                                        ${props.isAdmin ? '<th width="20%">Customer</th>' : ''}
                                        <th width="10%">Items</th>
                                        <th width="15%">Amount (USD)</th>
                                        <th width="15%">Payment</th>
                                        <th width="10%">Status</th>
                                        <th width="20%">Order Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${ordersData.map(order => {
                                        // Clean customer name
                                        let customerName = order.user?.name || 'N/A'
                                        customerName = customerName.replace(/[^\x00-\x7F]/g, ' ')
                                        
                                        return `
                                            <tr>
                                                <td><strong style="color: #1e3a8a;">#${order.id}</strong></td>
                                                ${props.isAdmin ? `<td>${customerName}<br><small>${order.user?.email || ''}</small></td>` : ''}
                                                <td>
                                                    <strong>${getTotalItems(order)} items</strong>
                                                </td>
                                                <td><strong style="color: #10b981;">$${order.total_amount}</strong></td>
                                                <td>
                                                    ${order.payment_method}<br>
                                                    <small>${order.payment_status}</small>
                                                </td>
                                                <td>
                                                    <span class="status-indicator status-${order.status}">${order.status.toUpperCase()}</span>
                                                </td>
                                                <td>${formatDateForPDF(order.created_at)}<br><small>${formatDate(order.created_at).split(',')[1]}</small></td>
                                            </tr>
                                        `
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="report-footer">
                            <p><strong>Book Store Ltd.</strong> | Professional Book Retailer</p>
                            <p>123 Book Street, Knowledge City, BK 10101 | Phone: +1 (555) 123-4567</p>
                            <p>Email: invoices@bookstore.com | Website: www.bookstore.com</p>
                            <p>© ${new Date().getFullYear()} Book Store Ltd. All rights reserved. This is an official document.</p>
                        </div>
                        
                        <button class="print-btn no-print" onclick="window.print();">
                            🖨️ Print Professional Report
                        </button>
                    </div>
                    
                    <script>
                        window.onload = function() {
                            // Auto-print after 1 second
                            setTimeout(() => {
                                window.print();
                            }, 1000);
                        }
                        
                        // Close window after print
                        window.onafterprint = function() {
                            setTimeout(() => {
                                window.close();
                            }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `
        }

        // Load jsPDF dynamically
        const loadJSPDF = () => {
            return new Promise((resolve, reject) => {
                if (typeof window.jspdf !== 'undefined') {
                    resolve()
                    return
                }

                const script = document.createElement('script')
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'
                script.onload = () => resolve()
                script.onerror = () => reject()
                document.head.appendChild(script)
            })
        }

        // Update order status
        const updateOrderStatus = async (order) => {
            try {
                const response = await axios.put(`/orders/${order.id}/status`, {
                    status: order.status
                })
                
                if (response.data.success) {
                    showAlert('Order status updated and email notification sent!', 'success')
                    // Refresh orders
                    window.location.reload()
                }
            } catch (error) {
                console.error('Failed to update order status:', error)
                showAlert('Failed to update order status: ' + (error.response?.data?.error || error.message), 'error')
            }
        }

        // Print single order - Professional Design
        const printSingleOrder = (order) => {
            console.log('Printing order:', order)
            
            const printWindow = window.open('', '_blank', 'width=900,height=700')
            
            if (!printWindow) {
                showAlert('Please allow popups for this site to print the invoice.', 'warning')
                return
            }
            
            // Format date for display
            const formatDateForDisplay = (dateString) => {
                try {
                    const date = new Date(dateString)
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })
                } catch (e) {
                    return dateString
                }
            }
            
            // Tax is 0%
            const subtotal = parseFloat(order.total_amount)
            const tax = 0
            
            // Clean data to prevent issues
            const cleanText = (text) => {
                if (!text) return 'N/A'
                return String(text).replace(/[^\x00-\x7F]/g, ' ')
            }
            
            // Get shipping address safely
            const shippingAddress = order.shipping_address || {}
            
            // Generate item rows HTML
            const itemsHtml = order.items && order.items.length > 0 ? 
                order.items.map(item => {
                    const itemPrice = parseFloat(item.book?.price) || 0
                    const itemQuantity = parseInt(item.quantity) || 0
                    const itemTotal = itemPrice * itemQuantity
                    
                    return `
                        <tr>
                            <td>
                                <strong>${cleanText(item.book?.title) || 'Unknown Book'}</strong><br>
                                <small class="text-gray-600">${cleanText(item.book?.author) || 'Unknown Author'}</small>
                            </td>
                            <td class="text-center">${itemQuantity}</td>
                            <td class="text-right">$${itemPrice.toFixed(2)}</td>
                            <td class="text-right">$${itemTotal.toFixed(2)}</td>
                        </tr>
                    `
                }).join('') : 
                '<tr><td colspan="4" class="text-center py-4 text-gray-500">No items found</td></tr>'
            
            // Generate shipping address HTML
            const shippingHtml = shippingAddress.address_line1 ? 
                `<div class="space-y-1">
                    <p class="font-semibold text-gray-700">Shipping Address:</p>
                    <p>${cleanText(shippingAddress.address_line1)}</p>
                    ${shippingAddress.address_line2 ? `<p>${cleanText(shippingAddress.address_line2)}</p>` : ''}
                    <p>${cleanText(shippingAddress.city || '')}, ${cleanText(shippingAddress.state || '')}</p>
                    <p>${cleanText(shippingAddress.country || '')} - ${cleanText(shippingAddress.postal_code || '')}</p>
                    <p><span class="font-medium">Phone:</span> ${cleanText(shippingAddress.phone) || 'N/A'}</p>
                </div>` : 
                '<p class="text-gray-500">No shipping address provided</p>'
            
            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Invoice #${order.id} - Book Store</title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
                        
                        body { 
                            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
                            margin: 0; 
                            padding: 40px; 
                            color: #1e293b;
                            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                            min-height: 100vh;
                        }
                        
                        .invoice-wrapper { 
                            max-width: 800px; 
                            margin: 0 auto; 
                            background: white; 
                            border-radius: 16px;
                            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                            overflow: hidden;
                            position: relative;
                        }
                        
                        .invoice-wrapper::before {
                            content: '';
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            height: 6px;
                            background: linear-gradient(90deg, #1e3a8a, #3b82f6, #10b981);
                        }
                        
                        .invoice-header { 
                            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
                            padding: 40px;
                            color: white;
                            position: relative;
                            overflow: hidden;
                        }
                        
                        .invoice-header::after {
                            content: '';
                            position: absolute;
                            top: 0;
                            right: 0;
                            width: 200px;
                            height: 200px;
                            background: rgba(255, 255, 255, 0.1);
                            border-radius: 50%;
                            transform: translate(30%, -30%);
                        }
                        
                        .invoice-title {
                            font-size: 36px;
                            margin: 0 0 10px 0;
                            font-weight: 800;
                            letter-spacing: -0.5px;
                            position: relative;
                            z-index: 1;
                        }
                        
                        .invoice-subtitle {
                            font-size: 16px;
                            opacity: 0.9;
                            margin-bottom: 20px;
                            position: relative;
                            z-index: 1;
                        }
                        
                        .invoice-number {
                            background: rgba(255, 255, 255, 0.2);
                            padding: 8px 20px;
                            border-radius: 20px;
                            display: inline-block;
                            font-weight: 600;
                            position: relative;
                            z-index: 1;
                        }
                        
                        .invoice-content {
                            padding: 40px;
                        }
                        
                        .invoice-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                            gap: 30px;
                            margin-bottom: 40px;
                        }
                        
                        .info-section {
                            background: #f8fafc;
                            border-radius: 12px;
                            padding: 24px;
                            border-left: 4px solid #3b82f6;
                        }
                        
                        .info-title {
                            font-size: 18px;
                            font-weight: 700;
                            color: #1e293b;
                            margin: 0 0 16px 0;
                            padding-bottom: 12px;
                            border-bottom: 2px solid #e2e8f0;
                        }
                        
                        .info-item {
                            margin: 12px 0;
                            display: flex;
                        }
                        
                        .info-label {
                            font-weight: 600;
                            color: #475569;
                            min-width: 140px;
                        }
                        
                        .info-value {
                            color: #1e293b;
                            flex: 1;
                        }
                        
                        .status-badge {
                            padding: 8px 16px;
                            border-radius: 20px;
                            font-size: 12px;
                            font-weight: 700;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            display: inline-block;
                        }
                        
                        .status-pending { background: #fef3c7; color: #92400e; }
                        .status-confirmed { background: #dbeafe; color: #1e40af; }
                        .status-processing { background: #f3e8ff; color: #6b21a8; }
                        .status-shipped { background: #d1fae5; color: #065f46; }
                        .status-delivered { background: #cffafe; color: #0e7490; }
                        .status-cancelled { background: #fee2e2; color: #991b1b; }
                        
                        .items-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 30px 0;
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
                        }
                        
                        .items-table th {
                            background: #1e3a8a;
                            color: white;
                            padding: 16px;
                            text-align: left;
                            font-weight: 600;
                            text-transform: uppercase;
                            font-size: 12px;
                            letter-spacing: 1px;
                        }
                        
                        .items-table td {
                            padding: 16px;
                            border-bottom: 1px solid #e2e8f0;
                        }
                        
                        .items-table tr:last-child td {
                            border-bottom: none;
                        }
                        
                        .items-table tr:hover {
                            background-color: #f8fafc;
                        }
                        
                        .total-section {
                            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                            padding: 24px;
                            border-radius: 12px;
                            margin-top: 30px;
                        }
                        
                        .total-row {
                            display: flex;
                            justify-content: space-between;
                            margin: 12px 0;
                            font-size: 16px;
                        }
                        
                        .grand-total {
                            font-size: 24px;
                            font-weight: 800;
                            color: #1e3a8a;
                            border-top: 2px solid #cbd5e1;
                            padding-top: 16px;
                            margin-top: 16px;
                        }
                        
                        .tax-notice {
                            background: #f0f9ff;
                            border: 1px solid #bae6fd;
                            border-radius: 8px;
                            padding: 16px;
                            margin-top: 30px;
                            text-align: center;
                            color: #0369a1;
                            font-weight: 600;
                        }
                        
                        .invoice-footer {
                            background: #f8fafc;
                            padding: 30px 40px;
                            border-top: 1px solid #e2e8f0;
                            margin-top: 40px;
                        }
                        
                        .footer-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                            gap: 20px;
                            text-align: center;
                        }
                        
                        .footer-item h4 {
                            margin: 0 0 8px 0;
                            color: #475569;
                            font-size: 14px;
                            font-weight: 600;
                        }
                        
                        .footer-item p {
                            margin: 4px 0;
                            color: #64748b;
                            font-size: 13px;
                        }
                        
                        .print-button {
                            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
                            color: white;
                            border: none;
                            padding: 16px 32px;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 16px;
                            font-weight: 600;
                            display: block;
                            margin: 30px auto;
                            transition: all 0.2s ease;
                            box-shadow: 0 4px 6px rgba(30, 58, 138, 0.2);
                        }
                        
                        .print-button:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 6px 12px rgba(30, 58, 138, 0.3);
                        }
                        
                        @media print {
                            body {
                                background: none;
                                margin: 0;
                                padding: 20mm;
                            }
                            .no-print { display: none !important; }
                            .print-button { display: none !important; }
                            .invoice-wrapper {
                                max-width: 100%;
                                margin: 0;
                                box-shadow: none;
                                border-radius: 0;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="invoice-wrapper">
                        <div class="invoice-header">
                            <h1 class="invoice-title">INVOICE</h1>
                            <div class="invoice-subtitle">Book Store | Official Commercial Invoice</div>
                            <div class="invoice-number">INV-${order.id.toString().padStart(6, '0')}</div>
                        </div>
                        
                        <div class="invoice-content">
                            <div class="invoice-grid">
                                <div class="info-section">
                                    <h3 class="info-title">Invoice Details</h3>
                                    <div class="info-item">
                                        <span class="info-label">Invoice Number:</span>
                                        <span class="info-value">#${cleanText(order.id)}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Transaction ID:</span>
                                        <span class="info-value">${cleanText(order.transaction_id) || 'N/A'}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Invoice Date:</span>
                                        <span class="info-value">${formatDateForDisplay(order.created_at)}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Order Status:</span>
                                        <span class="info-value"><span class="status-badge status-${cleanText(order.status)}">${cleanText(order.status)}</span></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Payment Status:</span>
                                        <span class="info-value"><span class="status-badge status-${cleanText(order.payment_status)}">${cleanText(order.payment_status)}</span></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Payment Method:</span>
                                        <span class="info-value">${cleanText(order.payment_method)}</span>
                                    </div>
                                </div>
                                
                                <div class="info-section">
                                    <h3 class="info-title">${props.isAdmin ? 'Customer Information' : 'Billing Information'}</h3>
                                    <div class="info-item">
                                        <span class="info-label">Customer Name:</span>
                                        <span class="info-value">${cleanText(order.user?.name) || 'N/A'}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Email Address:</span>
                                        <span class="info-value">${cleanText(order.user?.email) || 'N/A'}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Shipping Address:</span>
                                        <span class="info-value">
                                            ${shippingHtml}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <h2 style="color: #1e3a8a; margin: 30px 0 20px 0; font-size: 24px; font-weight: 700;">Order Items</h2>
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th width="50%">Item Description</th>
                                        <th width="10%">Quantity</th>
                                        <th width="20%">Unit Price</th>
                                        <th width="20%">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsHtml}
                                </tbody>
                            </table>
                            
                            <div class="total-section">
                                <div class="total-row">
                                    <span><strong>Subtotal:</strong></span>
                                    <span><strong>$${subtotal.toFixed(2)}</strong></span>
                                </div>
                                <div class="total-row">
                                    <span><strong>Tax (0%):</strong></span>
                                    <span><strong>$${tax.toFixed(2)}</strong></span>
                                </div>
                                <div class="total-row grand-total">
                                    <span><strong>GRAND TOTAL:</strong></span>
                                    <span><strong>$${parseFloat(order.total_amount).toFixed(2)}</strong></span>
                                </div>
                            </div>
                            
                            <div class="tax-notice">
                                <strong>TAX INFORMATION:</strong> This invoice is issued with 0% tax rate as per applicable regulations. All amounts are in USD.
                            </div>
                        </div>
                        
                        <div class="invoice-footer">
                            <div class="footer-grid">
                                <div class="footer-item">
                                    <h4>Payment Terms</h4>
                                    <p>Due upon receipt</p>
                                    <p>Net 0 days</p>
                                </div>
                                <div class="footer-item">
                                    <h4>Contact Information</h4>
                                    <p>support@bookstore.com</p>
                                    <p>+1 (555) 123-4567</p>
                                </div>
                                <div class="footer-item">
                                    <h4>Legal Information</h4>
                                    <p>Tax ID: 00-0000000</p>
                                    <p>VAT: Not applicable</p>
                                </div>
                            </div>
                            <p style="text-align: center; margin-top: 20px; color: #64748b; font-size: 13px;">
                                Thank you for your business. This is an electronically generated invoice, no signature required.
                            </p>
                        </div>
                        
                        <button class="print-button no-print" onclick="window.print();">
                            🖨️ Print Professional Invoice
                        </button>
                        
                        <script>
                            window.onload = function() {
                                var printButton = document.querySelector('.print-button');
                                if (printButton) {
                                    printButton.focus();
                                }
                            };
                            
                            window.onafterprint = function() {
                                setTimeout(function() {
                                    window.close();
                                }, 1000);
                            };
                        <\/script>
                    </div>
                </body>
                </html>
            `

            printWindow.document.write(printContent)
            printWindow.document.close()
            printWindow.focus()
        }

        // Download single order as PDF - Professional Design
        const downloadOrderPDF = async (order) => {
            try {
                if (typeof window.jspdf === 'undefined') {
                    await loadJSPDF()
                }

                const { jsPDF } = window.jspdf
                const doc = new jsPDF('p', 'mm', 'a4')
                
                let yPos = 20
                const pageWidth = doc.internal.pageSize.getWidth()
                const margin = 20
                const contentWidth = pageWidth - (margin * 2)
                
                // Professional Header with Dark Blue
                doc.setFillColor(30, 58, 138)
                doc.rect(0, 0, pageWidth, 60, 'F')
                
                // Company Logo/Icon
                doc.setFontSize(36)
                doc.setTextColor(255, 255, 255)
                doc.text("📚", margin, 35)
                
                // Header text
                doc.setFontSize(28)
                doc.setFont("helvetica", "bold")
                doc.text("INVOICE", margin + 20, 35)
                
                doc.setFontSize(12)
                doc.setFont("helvetica", "normal")
                doc.text("Book Store | Professional Invoice", margin + 20, 42)
                doc.text(`Invoice #${order.id}`, pageWidth - margin, 35, { align: 'right' })
                doc.text(`Date: ${formatDateForPDF(order.created_at)}`, pageWidth - margin, 42, { align: 'right' })
                
                yPos = 70
                
                // Invoice Details Box
                doc.setFillColor(249, 250, 251)
                doc.roundedRect(margin, yPos, contentWidth, 25, 5, 5, 'F')
                doc.setDrawColor(209, 213, 219)
                doc.roundedRect(margin, yPos, contentWidth, 25, 5, 5, 'S')
                
                doc.setFontSize(14)
                doc.setFont("helvetica", "bold")
                doc.setTextColor(30, 58, 138)
                doc.text("COMMERCIAL INVOICE", margin + 10, yPos + 10)
                
                doc.setFontSize(10)
                doc.setTextColor(107, 114, 128)
                doc.text(`Status: ${order.status.toUpperCase()} | Payment: ${order.payment_status.toUpperCase()}`, margin + 10, yPos + 18)
                doc.text(`Transaction: ${order.transaction_id || 'N/A'}`, pageWidth - margin - 10, yPos + 18, { align: 'right' })
                
                yPos += 35
                
                // Two column layout
                const colWidth = contentWidth / 2 - 10
                
                // Left Column - Billing Information
                doc.setFontSize(12)
                doc.setFont("helvetica", "bold")
                doc.setTextColor(30, 58, 138)
                doc.text("BILLING INFORMATION", margin, yPos)
                yPos += 8
                
                doc.setFontSize(10)
                doc.setFont("helvetica", "normal")
                doc.setTextColor(55, 65, 81)
                doc.text(`Customer: ${order.user?.name || 'N/A'}`, margin, yPos)
                yPos += 6
                doc.text(`Email: ${order.user?.email || 'N/A'}`, margin, yPos)
                yPos += 10
                
                if (order.shipping_address) {
                    doc.text("Shipping Address:", margin, yPos)
                    yPos += 6
                    doc.setFontSize(9)
                    doc.text(`${order.shipping_address.address_line1 || ''}`, margin + 5, yPos)
                    yPos += 5
                    if (order.shipping_address.address_line2) {
                        doc.text(`${order.shipping_address.address_line2}`, margin + 5, yPos)
                        yPos += 5
                    }
                    doc.text(`${order.shipping_address.city || ''}, ${order.shipping_address.state || ''}`, margin + 5, yPos)
                    yPos += 5
                    doc.text(`${order.shipping_address.country || ''} - ${order.shipping_address.postal_code || ''}`, margin + 5, yPos)
                    yPos += 5
                    doc.text(`Phone: ${order.shipping_address.phone || 'N/A'}`, margin + 5, yPos)
                    yPos += 10
                } else {
                    doc.text("Shipping: Not specified", margin, yPos)
                    yPos += 15
                }
                
                // Right Column - Invoice Details
                doc.setFontSize(12)
                doc.setFont("helvetica", "bold")
                doc.setTextColor(30, 58, 138)
                doc.text("INVOICE DETAILS", margin + colWidth + 20, yPos - 38)
                
                doc.setFontSize(10)
                doc.setFont("helvetica", "normal")
                doc.setTextColor(55, 65, 81)
                doc.text(`Invoice Date: ${formatDate(order.created_at)}`, margin + colWidth + 20, yPos - 30)
                doc.text(`Payment Method: ${order.payment_method}`, margin + colWidth + 20, yPos - 24)
                doc.text(`Order Status: ${order.status.toUpperCase()}`, margin + colWidth + 20, yPos - 18)
                doc.text(`Payment Status: ${order.payment_status.toUpperCase()}`, margin + colWidth + 20, yPos - 12)
                
                // Items Table
                doc.setFontSize(14)
                doc.setFont("helvetica", "bold")
                doc.setTextColor(30, 58, 138)
                doc.text("ORDER ITEMS", margin, yPos)
                yPos += 10
                
                // Table Header
                doc.setFillColor(30, 58, 138)
                doc.rect(margin, yPos, contentWidth, 8, 'F')
                doc.setTextColor(255, 255, 255)
                doc.setFontSize(10)
                doc.setFont("helvetica", "bold")
                
                const headers = ["Item Description", "Qty", "Price", "Total"]
                const headerWidths = [contentWidth * 0.5, contentWidth * 0.1, contentWidth * 0.2, contentWidth * 0.2]
                
                let xPos = margin
                headers.forEach((header, index) => {
                    doc.text(header, xPos + headerWidths[index]/2, yPos + 5, { align: 'center' })
                    xPos += headerWidths[index]
                })
                
                yPos += 10
                
                // Table Rows
                doc.setFont("helvetica", "normal")
                doc.setTextColor(55, 65, 81)
                
                order.items?.forEach((item, index) => {
                    if (yPos > 250) {
                        doc.addPage()
                        yPos = 20
                    }
                    
                    // Alternate row colors
                    if (index % 2 === 0) {
                        doc.setFillColor(250, 250, 250)
                        doc.rect(margin, yPos - 2, contentWidth, 8, 'F')
                    }
                    
                    xPos = margin
                    
                    // Item name
                    const itemName = item.book?.title || 'Unknown Book'
                    let displayName = itemName
                    if (displayName.length > 30) {
                        displayName = displayName.substring(0, 30) + '...'
                    }
                    doc.text(displayName, xPos + 5, yPos + 5)
                    xPos += headerWidths[0]
                    
                    // Quantity
                    const itemQuantity = parseInt(item.quantity) || 1
                    doc.text(itemQuantity.toString(), xPos + headerWidths[1]/2, yPos + 5, { align: 'center' })
                    xPos += headerWidths[1]
                    
                    // Price
                    const itemPrice = parseFloat(item.book?.price) || 0
                    doc.text(`$${itemPrice.toFixed(2)}`, xPos + headerWidths[2]/2, yPos + 5, { align: 'center' })
                    xPos += headerWidths[2]
                    
                    // Total
                    const itemTotal = itemPrice * itemQuantity
                    doc.setFont("helvetica", "bold")
                    doc.setTextColor(16, 185, 129)
                    doc.text(`$${itemTotal.toFixed(2)}`, xPos + headerWidths[3]/2, yPos + 5, { align: 'center' })
                    doc.setTextColor(55, 65, 81)
                    doc.setFont("helvetica", "normal")
                    
                    yPos += 8
                })
                
                yPos += 10
                
                // Totals Section
                doc.setFillColor(249, 250, 251)
                doc.roundedRect(margin, yPos, contentWidth, 40, 5, 5, 'F')
                doc.setDrawColor(209, 213, 219)
                doc.roundedRect(margin, yPos, contentWidth, 40, 5, 5, 'S')
                
                // Subtotal
                doc.setFontSize(12)
                doc.setFont("helvetica", "bold")
                doc.setTextColor(55, 65, 81)
                doc.text("Subtotal:", margin + contentWidth - 80, yPos + 12)
                doc.text(`$${parseFloat(order.total_amount).toFixed(2)}`, margin + contentWidth - 20, yPos + 12, { align: 'right' })
                
                // Tax 0%
                doc.text("Tax (0%):", margin + contentWidth - 80, yPos + 22)
                doc.text("$0.00", margin + contentWidth - 20, yPos + 22, { align: 'right' })
                
                // Grand Total
                doc.setFontSize(16)
                doc.setTextColor(30, 58, 138)
                doc.text("GRAND TOTAL:", margin + contentWidth - 80, yPos + 35)
                doc.text(`$${parseFloat(order.total_amount).toFixed(2)}`, margin + contentWidth - 20, yPos + 35, { align: 'right' })
                
                yPos += 50
                
                // Tax Notice
                doc.setFontSize(10)
                doc.setFont("helvetica", "bold")
                doc.setTextColor(30, 58, 138)
                doc.text("TAX INFORMATION", margin, yPos)
                yPos += 7
                
                doc.setFontSize(9)
                doc.setFont("helvetica", "normal")
                doc.setTextColor(107, 114, 128)
                doc.text("• This invoice is issued with 0% tax rate as per applicable regulations", margin + 5, yPos)
                yPos += 5
                doc.text("• All amounts are in United States Dollars (USD)", margin + 5, yPos)
                yPos += 5
                doc.text("• This is an electronically generated invoice, no signature required", margin + 5, yPos)
                
                // Footer
                doc.setFontSize(8)
                doc.setTextColor(156, 163, 175)
                doc.text("Thank you for your business with Book Store", pageWidth / 2, doc.internal.pageSize.getHeight() - 15, { align: 'center' })
                doc.text(`Invoice generated: ${new Date().toLocaleDateString()} | Professional Invoice System`, margin, doc.internal.pageSize.getHeight() - 10)
                doc.text(`Page 1 of 1 | Invoice ID: INV-${order.id}`, pageWidth - margin, doc.internal.pageSize.getHeight() - 10, { align: 'right' })
                
                // Save PDF
                const fileName = `invoice-${order.id}-professional-${new Date().toISOString().slice(0, 10)}.pdf`
                doc.save(fileName)
                
                showAlert('Professional invoice PDF downloaded successfully!', 'success')
                
            } catch (error) {
                console.error('Failed to generate invoice PDF:', error)
                showAlert('PDF generation failed. Opening printable version instead.', 'warning')
                printSingleOrder(order)
            }
        }

        // Pagination functions
        const previousPage = () => {
            if (props.orders.prev_page_url) {
                router.visit(props.orders.prev_page_url, {
                    preserveState: true,
                    preserveScroll: true
                })
            }
        }

        const nextPage = () => {
            if (props.orders.next_page_url) {
                router.visit(props.orders.next_page_url, {
                    preserveState: true,
                    preserveScroll: true
                })
            }
        }

        const goToPage = (page) => {
            const url = new URL(props.orders.first_page_url)
            url.searchParams.set('page', page)
            router.visit(url.toString(), {
                preserveState: true,
                preserveScroll: true
            })
        }

        // Go to specific page from input
        const goToSpecificPage = () => {
            let pageNumber = parseInt(pageForm.page)
            
            if (isNaN(pageNumber) || pageNumber < 1) {
                pageNumber = 1
            }
            
            if (pageNumber > props.orders.last_page) {
                pageNumber = props.orders.last_page
            }
            
            goToPage(pageNumber)
            pageForm.page = pageNumber
        }

        // Handle page input keypress
        const handlePageInputKeypress = (event) => {
            if (event.key === 'Enter') {
                goToSpecificPage()
            }
        }

        // Update page input when current page changes
        watch(() => props.orders.current_page, (newPage) => {
            pageForm.page = newPage
        })

        // Generate page numbers for pagination
        const pageNumbers = computed(() => {
            if (!props.orders.links) return []
            
            const current = props.orders.current_page
            const last = props.orders.last_page
            const delta = 2
            const range = []
            const rangeWithDots = []

            for (let i = 1; i <= last; i++) {
                if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
                    range.push(i)
                }
            }

            let prev = 0
            for (let i of range) {
                if (prev) {
                    if (i - prev === 2) {
                        rangeWithDots.push(prev + 1)
                    } else if (i - prev !== 1) {
                        rangeWithDots.push('...')
                    }
                }
                rangeWithDots.push(i)
                prev = i
            }

            return rangeWithDots
        })

        return {
            pageForm,
            isGeneratingPDF,
            showDateRangeModal,
            showPreview,
            orderSelectionType,
            dateRange,
            filteredOrders,
            selectedOrder,
            statusCounts,
            paymentStatusCounts,
            getTotalItems,
            getStatusClass,
            getPaymentStatusClass,
            formatDate,
            formatDateDisplay,
            showOrderDetails,
            setDateRange,
            generatePDFWithDateRange,
            updateOrderStatus,
            printSingleOrder,
            downloadOrderPDF,
            previousPage,
            nextPage,
            goToPage,
            goToSpecificPage,
            handlePageInputKeypress,
            pageNumbers
        }
    }
}
</script>
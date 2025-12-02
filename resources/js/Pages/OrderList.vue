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
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">#{{ order.id }}</p>
                                            <p class="text-xs text-gray-500 mt-1">Transaction: {{ order.transaction_id || 'N/A' }}</p>
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

        // Client-side PDF generation using jsPDF with UTF-8 support
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
                
                // Add header with date range info - Using English text only
                doc.setFontSize(16)
                doc.setFont("helvetica", "bold")
                doc.setTextColor(0, 0, 0)
                doc.text(props.isAdmin ? "ORDERS REPORT" : "MY ORDERS", margin, yPos)
                
                yPos += 8
                doc.setFontSize(10)
                doc.setFont("helvetica", "normal")
                
                // Add date range info if applicable
                let dateRangeText = ''
                if (orderSelectionType.value === 'dateRange') {
                    if (dateRange.value.startDate && dateRange.value.endDate) {
                        dateRangeText = `${formatDateDisplay(dateRange.value.startDate)} - ${formatDateDisplay(dateRange.value.endDate)}`
                    } else if (dateRange.value.startDate) {
                        dateRangeText = `From ${formatDateDisplay(dateRange.value.startDate)}`
                    } else if (dateRange.value.endDate) {
                        dateRangeText = `Until ${formatDateDisplay(dateRange.value.endDate)}`
                    }
                    doc.text(`Date Range: ${dateRangeText}`, margin, yPos)
                    yPos += 5
                } else {
                    doc.text(`All Orders`, margin, yPos)
                    yPos += 5
                }
                
                doc.text(`Generated on: ${new Date().toLocaleDateString()}`, margin, yPos)
                doc.text(`Total Orders: ${ordersToExport.length}`, pageWidth - margin - doc.getTextWidth(`Total Orders: ${ordersToExport.length}`), yPos)
                
                yPos += 15
                
                // Add summary section for admin
                if (props.isAdmin && statusCounts.value) {
                    doc.setFontSize(12)
                    doc.setFont("helvetica", "bold")
                    doc.text("Order Summary", margin, yPos)
                    yPos += 8
                    
                    doc.setFontSize(10)
                    doc.setFont("helvetica", "normal")
                    const summaryPoints = [
                        `Pending: ${statusCounts.value.pending}`,
                        `Confirmed: ${statusCounts.value.confirmed}`,
                        `Processing: ${statusCounts.value.processing}`,
                        `Shipped: ${statusCounts.value.shipped}`,
                        `Delivered: ${statusCounts.value.delivered}`,
                        `Cancelled: ${statusCounts.value.cancelled || 0}`
                    ]
                    
                    summaryPoints.forEach(point => {
                        if (yPos > 270) {
                            doc.addPage()
                            yPos = 20
                        }
                        doc.text(`• ${point}`, margin + 5, yPos)
                        yPos += 6
                    })
                    
                    yPos += 5
                }
                
                // Add table
                doc.setFontSize(12)
                doc.setFont("helvetica", "bold")
                doc.text("Order Details", margin, yPos)
                yPos += 10
                
                // Table headers
                doc.setFontSize(10)
                const headers = ["Order ID", props.isAdmin ? "Customer" : "", "Amount", "Payment", "Status", "Date"]
                const colWidths = [20, props.isAdmin ? 40 : 0, 25, 30, 35, 40]
                
                // Adjust colWidths if not admin
                if (!props.isAdmin) {
                    colWidths[1] = 0 // Remove customer column width
                    // Redistribute the remaining width
                    const totalUsedWidth = colWidths.reduce((a, b) => a + b, 0)
                    const remainingWidth = contentWidth - totalUsedWidth
                    // Add remaining width to date column
                    colWidths[5] += remainingWidth
                }
                
                // Draw table header
                let xPos = margin
                headers.forEach((header, index) => {
                    if (colWidths[index] > 0 && header !== "") {
                        doc.setFillColor(240, 240, 240)
                        doc.rect(xPos, yPos, colWidths[index], 8, 'F')
                        doc.setTextColor(0, 0, 0)
                        doc.setFont("helvetica", "bold")
                        
                        // Adjust text position based on column
                        let textX = xPos + 3
                        let textY = yPos + 5
                        
                        doc.text(header, textX, textY)
                        xPos += colWidths[index]
                    }
                })
                
                yPos += 8
                
                // Draw table rows
                doc.setFont("helvetica", "normal")
                doc.setFontSize(9)
                
                ordersToExport.forEach((order, index) => {
                    // Check if we need a new page
                    if (yPos > 270) {
                        doc.addPage()
                        yPos = 20
                    }
                    
                    // Alternate row colors
                    if (index % 2 === 0) {
                        doc.setFillColor(250, 250, 250)
                        doc.rect(margin, yPos - 1, contentWidth, 7, 'F')
                    }
                    
                    xPos = margin
                    // Handle book titles - remove non-ASCII characters to prevent encoding issues
                    const customerName = props.isAdmin ? (order.user?.name || 'N/A') : ''
                    const cleanCustomerName = customerName.replace(/[^\x00-\x7F]/g, '') // Remove non-ASCII
                    
                    const rowData = [
                        `#${order.id}`,
                        props.isAdmin ? cleanCustomerName : '',
                        `$${order.total_amount}`,
                        order.payment_status,
                        order.status,
                        formatDateForPDF(order.created_at)
                    ]
                    
                    rowData.forEach((data, colIndex) => {
                        if (colWidths[colIndex] > 0 && headers[colIndex] !== "") {
                            // Truncate long text
                            let displayText = data
                            const maxChars = Math.floor(colWidths[colIndex] / 1.5)
                            if (displayText.length > maxChars) {
                                displayText = displayText.substring(0, maxChars) + '...'
                            }
                            
                            doc.setTextColor(0, 0, 0)
                            
                            // Status color coding
                            if (colIndex === 4) { // Status column
                                if (order.status === 'pending') doc.setTextColor(255, 193, 7)
                                else if (order.status === 'confirmed') doc.setTextColor(13, 110, 253)
                                else if (order.status === 'processing') doc.setTextColor(111, 66, 193)
                                else if (order.status === 'shipped') doc.setTextColor(25, 135, 84)
                                else if (order.status === 'delivered') doc.setTextColor(32, 201, 151)
                                else if (order.status === 'cancelled') doc.setTextColor(220, 53, 69)
                            }
                            
                            doc.text(displayText, xPos + 3, yPos + 4)
                            xPos += colWidths[colIndex]
                        }
                    })
                    
                    yPos += 7
                    
                    // Add item details below each order - শুধু quantity দেখাবো
                    if (yPos > 250) {
                        doc.addPage()
                        yPos = 20
                    }
                    
                    // Add total items information (শুধু মোট আইটেম সংখ্যা)
                    doc.setFontSize(8)
                    doc.setTextColor(100, 100, 100)
                    doc.text(`  • Total Items: ${getTotalItems(order)}`, margin + 5, yPos + 4)
                    yPos += 8
                })
                
                // Add footer
                const totalPages = doc.internal.getNumberOfPages()
                for (let i = 1; i <= totalPages; i++) {
                    doc.setPage(i)
                    doc.setFontSize(8)
                    doc.setTextColor(150, 150, 150)
                    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, doc.internal.pageSize.getHeight() - 10, { align: 'center' })
                    doc.text(`Generated from ${window.location.hostname}`, margin, doc.internal.pageSize.getHeight() - 10)
                }
                
                // Generate filename with date range
                const timestamp = new Date().toISOString().slice(0, 10)
                let fileName = props.isAdmin ? `orders-report-${timestamp}` : `my-orders-${timestamp}`
                
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
                    fileName += '_all-orders'
                }
                
                fileName += '.pdf'
                
                // Save the PDF
                doc.save(fileName)
                
                showAlert('PDF downloaded successfully!', 'success')
                
            } catch (error) {
                console.error('Client-side PDF generation failed:', error)
                // Fallback to printable version
                generatePrintableVersion(ordersToExport)
            }
        }

        // Fallback printable version
        const generatePrintableVersion = (ordersToExport) => {
            if (confirm('Would you like to generate a printable version instead?')) {
                const ordersData = ordersToExport
                const printWindow = window.open('', '_blank')
                
                const printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>${props.isAdmin ? 'Orders Report' : 'My Orders'}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                            .header h1 { color: #333; margin: 0; }
                            .header p { color: #666; margin: 5px 0; }
                            .summary { display: flex; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; }
                            .summary-item { margin-right: 20px; }
                            .table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
                            .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
                            .table th { background-color: #f5f5f5; font-weight: bold; }
                            .status { padding: 2px 8px; border-radius: 12px; font-size: 11px; display: inline-block; }
                            .status-pending { background-color: #fef3cd; color: #856404; }
                            .status-confirmed { background-color: #cce7ff; color: #004085; }
                            .status-processing { background-color: #e2d9f3; color: #491c8c; }
                            .status-shipped { background-color: #d1e7dd; color: #0f5132; }
                            .status-delivered { background-color: #d1ecf1; color: #0c5460; }
                            .status-cancelled { background-color: #f8d7da; color: #721c24; }
                            .footer { margin-top: 30px; text-align: center; color: #666; font-size: 11px; }
                            @media print {
                                body { margin: 10mm; }
                                .no-print { display: none; }
                                .table { font-size: 11px; }
                            }
                            .print-button { 
                                background: #dc3545; 
                                color: white; 
                                border: none; 
                                padding: 10px 20px; 
                                border-radius: 5px; 
                                cursor: pointer; 
                                margin: 20px auto;
                                display: block;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h1>${props.isAdmin ? 'ORDERS REPORT' : 'MY ORDERS'}</h1>
                            <p>Generated on ${new Date().toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}</p>
                            ${orderSelectionType.value === 'dateRange' && (dateRange.value.startDate || dateRange.value.endDate) ? 
                                `<p>Date Range: ${dateRange.value.startDate ? formatDateDisplay(dateRange.value.startDate) : 'Beginning'} to ${dateRange.value.endDate ? formatDateDisplay(dateRange.value.endDate) : 'Now'}</p>` : 
                                '<p>All Orders</p>'}
                            <p>Total Orders: ${ordersData.length} orders</p>
                        </div>
                        
                        ${props.isAdmin ? `
                        <div class="summary">
                            <div class="summary-item"><strong>Pending:</strong> ${statusCounts.value.pending}</div>
                            <div class="summary-item"><strong>Confirmed:</strong> ${statusCounts.value.confirmed}</div>
                            <div class="summary-item"><strong>Processing:</strong> ${statusCounts.value.processing}</div>
                            <div class="summary-item"><strong>Shipped:</strong> ${statusCounts.value.shipped}</div>
                            <div class="summary-item"><strong>Delivered:</strong> ${statusCounts.value.delivered}</div>
                            <div class="summary-item"><strong>Cancelled:</strong> ${statusCounts.value.cancelled || 0}</div>
                        </div>
                        ` : ''}
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="10%">Order ID</th>
                                    ${props.isAdmin ? '<th width="20%">Customer</th>' : ''}
                                    <th width="10%">Total Items</th>
                                    <th width="10%">Amount</th>
                                    <th width="15%">Payment</th>
                                    <th width="10%">Status</th>
                                    <th width="15%">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${ordersData.map(order => {
                                    // Clean customer name
                                    let customerName = order.user?.name || 'N/A'
                                    customerName = customerName.replace(/[^\x00-\x7F]/g, ' ')
                                    
                                    return `
                                        <tr>
                                            <td><strong>#${order.id}</strong><br><small>${order.transaction_id || 'N/A'}</small></td>
                                            ${props.isAdmin ? `<td>${customerName}<br><small>${order.user?.email || ''}</small></td>` : ''}
                                            <td>
                                                <strong>${getTotalItems(order)} items</strong>
                                            </td>
                                            <td><strong>$${order.total_amount}</strong></td>
                                            <td>
                                                ${order.payment_method}<br>
                                                <span class="status status-${order.payment_status}">${order.payment_status}</span>
                                            </td>
                                            <td>
                                                <span class="status status-${order.status}">${order.status}</span>
                                            </td>
                                            <td>${formatDateForPDF(order.created_at)}</td>
                                        </tr>
                                    `
                                }).join('')}
                            </tbody>
                        </table>
                        
                        <div class="footer">
                            <p>Report generated from ${window.location.hostname}</p>
                            <p>© ${new Date().getFullYear()} All rights reserved</p>
                        </div>
                        
                        <button class="print-button no-print" onclick="window.print();">Print Report</button>
                        
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

                printWindow.document.write(printContent)
                printWindow.document.close()
            }
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
                    console.log('Order status updated successfully')
                    alert('Order status updated successfully!')
                }
            } catch (error) {
                console.error('Failed to update order status:', error)
                
                if (error.response?.status === 404) {
                    alert('Error: Route not found. Please check the server configuration.')
                } else if (error.response?.status === 403) {
                    alert('Error: You do not have permission to update order status.')
                } else {
                    alert('Failed to update order status. Please try again.')
                }
                
                router.reload()
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
            statusCounts,
            paymentStatusCounts,
            getTotalItems,
            getStatusClass,
            getPaymentStatusClass,
            formatDate,
            formatDateDisplay,
            setDateRange,
            generatePDFWithDateRange,
            updateOrderStatus,
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
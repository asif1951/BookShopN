<!-- resources/js/pages/AuthorDetails.vue -->
<template>
    <Head title="Author Details" />
    
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Author Details</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Author Details -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <div class="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-6">
                        <!-- Author Photo -->
                        <div class="flex-shrink-0">
                            <img
                                :src="author.photo ? `/storage/${author.photo}` : 'https://via.placeholder.com/200x250?text=No+Photo'"
                                :alt="author.name"
                                class="w-48 h-60 object-cover rounded-lg shadow-md"
                            />
                        </div>
                        
                        <!-- Author Info -->
                        <div class="flex-1 text-center md:text-left">
                            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ author.name }}</h1>
                            <div class="prose max-w-none">
                                <p class="text-gray-700 text-lg leading-relaxed whitespace-pre-line">
                                    {{ author.bio || 'No biography available.' }}
                                </p>
                            </div>
                            
                            <!-- Author Stats -->
                            <div class="mt-6 grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-indigo-600">{{ books.length }}</p>
                                    <p class="text-gray-600">Total Books</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-green-600">${{ totalBookValue.toFixed(2) }}</p>
                                    <p class="text-gray-600">Total Value</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-blue-600">{{ inStockBooks }}</p>
                                    <p class="text-gray-600">In Stock</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Author's Books -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Books by {{ author.name }}</h2>
                    
                    <div v-if="books.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
                        <p class="text-gray-500 text-lg">No books found by this author.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <div
                            v-for="book in books"
                            :key="book.id"
                            class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden group"
                        >
                            <div class="relative">
                                <img
                                    :src="book.photo ? `/storage/${book.photo}` : 'https://via.placeholder.com/300x400?text=No+Image'"
                                    :alt="book.title"
                                    class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div class="absolute top-2 right-2">
                                    <span 
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="book.stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ book.stock > 0 ? 'In Stock' : 'Out of Stock' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h3 
                                    @click="viewBookDetails(book)"
                                    class="font-semibold text-lg text-gray-800 mb-2 line-clamp-2 hover:text-indigo-600 cursor-pointer"
                                >
                                    {{ book.title }}
                                </h3>
                                
                                <div class="space-y-1 text-sm text-gray-600 mb-3">
                                    <p><span class="font-medium">Category:</span> {{ book.category || 'N/A' }}</p>
                                    <p><span class="font-medium">Publisher:</span> {{ book.publisher || 'N/A' }}</p>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <p class="text-indigo-600 font-bold text-lg">${{ book.price }}</p>
                                    <p class="text-sm" :class="book.stock > 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ book.stock }} left
                                    </p>
                                </div>
                                
                                <!-- Add to Cart Button -->
                                <button
                                    v-if="book.stock > 0"
                                    @click="addToCart(book)"
                                    class="w-full mt-3 bg-green-500 text-white py-2 rounded-md hover:bg-green-600 transition-colors duration-200 font-medium"
                                >
                                    Add to Cart
                                </button>
                                <button
                                    v-else
                                    disabled
                                    class="w-full mt-3 bg-gray-400 text-white py-2 rounded-md cursor-not-allowed font-medium"
                                >
                                    Out of Stock
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    author: {
        type: Object,
        required: true
    },
    books: {
        type: Array,
        default: () => []
    }
})

// Computed properties for author stats
const totalBookValue = computed(() => {
    return props.books.reduce((total, book) => total + (book.price * book.stock), 0)
})

const inStockBooks = computed(() => {
    return props.books.filter(book => book.stock > 0).length
})

// Add to cart function
const addToCart = (book) => {
    let cart = JSON.parse(localStorage.getItem('cart')) || []
    const exists = cart.find((item) => item.id === book.id)
    
    if (!exists) {
        // Add quantity property to book object
        const bookWithQuantity = { ...book, quantity: 1 }
        cart.push(bookWithQuantity)
        localStorage.setItem('cart', JSON.stringify(cart))
        
        // Dispatch event to update cart count in navbar
        window.dispatchEvent(new Event('storage'))
        
        // Show success message
        alert(`"${book.title}" added to cart!`)
    } else {
        alert(`"${book.title}" is already in your cart!`)
    }
}

// View book details
const viewBookDetails = (book) => {
    router.visit(`/books/${book.id}`)
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
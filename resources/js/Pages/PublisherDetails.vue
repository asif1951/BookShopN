<!-- resources/js/pages/PublisherDetails.vue -->
<template>
    <Head :title="`${publisher.name} Books`" />
    
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ publisher.name }} Books</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Publisher Info -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ publisher.name }}</h1>
                    <p class="text-gray-600 text-lg">{{ publisher.description || 'No description available.' }}</p>
                    
                    <!-- Publisher Stats -->
                    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
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
                        <div class="text-center">
                            <p class="text-2xl font-bold text-purple-600">{{ uniqueAuthors }}</p>
                            <p class="text-gray-600">Unique Authors</p>
                        </div>
                    </div>
                </div>

                <!-- Publisher Books -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Books by {{ publisher.name }}</h2>
                    
                    <div v-if="books.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
                        <p class="text-gray-500 text-lg">No books found from this publisher.</p>
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
                                    <p><span class="font-medium">Author:</span> {{ book.author || 'N/A' }}</p>
                                    <p><span class="font-medium">Category:</span> {{ book.category || 'N/A' }}</p>
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
    publisher: {
        type: Object,
        required: true
    },
    books: {
        type: Array,
        default: () => []
    }
})

// Computed properties for publisher stats
const totalBookValue = computed(() => {
    return props.books.reduce((total, book) => total + (book.price * book.stock), 0)
})

const inStockBooks = computed(() => {
    return props.books.filter(book => book.stock > 0).length
})

const uniqueAuthors = computed(() => {
    const authors = props.books.map(book => book.author).filter(Boolean)
    return new Set(authors).size
})

// Add to cart function
const addToCart = (book) => {
    let cart = JSON.parse(localStorage.getItem('cart')) || []
    const exists = cart.find((item) => item.id === book.id)
    
    if (!exists) {
        const bookWithQuantity = { ...book, quantity: 1 }
        cart.push(bookWithQuantity)
        localStorage.setItem('cart', JSON.stringify(cart))
        window.dispatchEvent(new Event('storage'))
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
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'

const page = usePage()
const books = ref([])
const cartItems = ref([])
const categories = ref([])
const categoriesLoading = ref(false)

// Initialize books from props
const initBooks = () => {
  books.value = page.props.books || []
}
initBooks()
watch(() => page.props.books, initBooks)

// Load cart from localStorage
const loadCart = () => {
  const savedCart = localStorage.getItem('cart')
  if (savedCart) {
    cartItems.value = JSON.parse(savedCart)
  } else {
    cartItems.value = []
  }
  console.log('🔄 Cart loaded:', cartItems.value)
}

// Save cart to localStorage
const saveCart = () => {
  localStorage.setItem('cart', JSON.stringify(cartItems.value))
  window.dispatchEvent(new Event('storage'))
  window.dispatchEvent(new Event('cartUpdated'))
  window.dispatchEvent(new CustomEvent('cartChange', { detail: cartItems.value }))
}

// Fetch categories from API
const fetchCategories = async () => {
  categoriesLoading.value = true
  try {
    const response = await fetch('/api/categories')
    if (response.ok) {
      const data = await response.json()
      categories.value = data
      console.log('📚 Categories loaded:', categories.value)
    }
  } catch (error) {
    console.error('❌ Error fetching categories:', error)
  } finally {
    categoriesLoading.value = false
  }
}

// Category details page e navigate korar function
const openCategoryDetails = (category) => {
  router.visit(`/categories/${category.id}`)
}

// Listen for storage events
const handleStorageChange = (event) => {
  if (event.key === 'cart' || event.key === null) {
    console.log('📢 Storage event detected, reloading cart...')
    loadCart()
  }
}

// Listen for custom cart update events
const handleCartUpdate = () => {
  console.log('📢 Custom cart update event detected, reloading cart...')
  loadCart()
}

// Listen for custom cart change events
const handleCartChange = (event) => {
  console.log('📢 Cart change event detected:', event.detail)
  cartItems.value = event.detail || []
}

// Force UI update
const forceUpdate = () => {
  cartItems.value = [...cartItems.value]
}

// Load cart on component mount and set up event listeners
onMounted(() => {
  loadCart()
  fetchCategories()
  
  window.addEventListener('storage', handleStorageChange)
  window.addEventListener('cartUpdated', handleCartUpdate)
  window.addEventListener('cartChange', handleCartChange)
  
  const cartCheckInterval = setInterval(() => {
    const currentCart = localStorage.getItem('cart')
    if (currentCart) {
      const parsedCart = JSON.parse(currentCart)
      if (JSON.stringify(parsedCart) !== JSON.stringify(cartItems.value)) {
        console.log('🕒 Periodic check detected cart change')
        loadCart()
      }
    } else if (cartItems.value.length > 0) {
      console.log('🕒 Periodic check detected cart cleared')
      loadCart()
    }
  }, 1000)

  window.cartCheckInterval = cartCheckInterval
})

// Clean up event listeners when component unmounts
onUnmounted(() => {
  window.removeEventListener('storage', handleStorageChange)
  window.removeEventListener('cartUpdated', handleCartUpdate)
  window.removeEventListener('cartChange', handleCartChange)
  
  if (window.cartCheckInterval) {
    clearInterval(window.cartCheckInterval)
  }
})

const openDetails = (book) => {
  router.visit(`/books/${book.id}`)
}

// Add to cart function
const addToCart = (book) => {
  const existingItem = cartItems.value.find(item => item.id === book.id)
  
  if (existingItem) {
    if (existingItem.quantity < book.stock) {
      existingItem.quantity += 1
    }
  } else {
    cartItems.value.push({
      ...book,
      quantity: 1
    })
  }
  
  saveCart()
  forceUpdate()
}

// Check if book is in cart
const isInCart = (book) => {
  return cartItems.value.some(item => item.id === book.id)
}

// Get cart quantity for a book
const getCartQuantity = (book) => {
  const item = cartItems.value.find(item => item.id === book.id)
  return item ? item.quantity : 0
}
</script>

<template>
  <Head title="Book Haven" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="font-bold text-3xl md:text-4xl text-gray-900 leading-tight bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
            Book Haven
          </h2>
          <p class="text-gray-600 mt-1">Discover your next favorite read</p>
        </div>
        <div class="flex items-center space-x-2 text-sm">
          <span class="px-3 py-1 bg-gradient-to-r from-green-100 to-emerald-100 text-emerald-800 rounded-full font-medium">
            {{ books.length }} Books Available
          </span>
          <span class="px-3 py-1 bg-gradient-to-r from-blue-100 to-indigo-100 text-indigo-800 rounded-full font-medium">
            {{ cartItems.length }} In Cart
          </span>
        </div>
      </div>
    </template>

    <div class="py-8 max-w-7xl mx-auto">
      <!-- Books Grid -->
      <div v-if="books.length === 0" class="text-center py-20">
        <div class="max-w-md mx-auto">
          <svg class="w-24 h-24 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
          </svg>
          <h3 class="mt-6 text-xl font-semibold text-gray-700">No books available</h3>
          <p class="mt-2 text-gray-500">Check back later for new arrivals</p>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <div
          v-for="book in books"
          :key="book.id"
          class="group relative bg-white rounded-2xl overflow-hidden transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl"
          :class="book.stock === 0 ? 'opacity-80' : ''"
        >
          <!-- Book Cover with Gradient Overlay -->
          <div class="relative h-64 overflow-hidden">
            <img
              :src="book.photo ? `/storage/${book.photo}` : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'"
              :alt="book.title"
              class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700"
            />
            
            <!-- Stock Status Badge -->
            <div class="absolute top-4 right-4">
              <span 
                class="px-3 py-1 rounded-full text-xs font-bold shadow-lg"
                :class="book.stock > 10 ? 'bg-emerald-500 text-white' : book.stock > 0 ? 'bg-amber-500 text-white' : 'bg-rose-500 text-white'"
              >
                {{ book.stock > 0 ? `${book.stock} left` : 'Out of Stock' }}
              </span>
            </div>
            
            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            
            <!-- Quick Actions - Hidden by default, shown on hover -->
            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <div class="flex flex-col space-y-3 items-center">
                <button
                  @click.stop="addToCart(book)"
                  :disabled="book.stock === 0 || isInCart(book)"
                  class="flex items-center justify-center px-6 py-3 rounded-full font-semibold text-sm transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg w-48"
                  :class="isInCart(book) ? 'bg-gradient-to-r from-emerald-500 to-green-500 text-white' : 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white hover:from-indigo-600 hover:to-purple-600'"
                >
                  <svg v-if="!isInCart(book)" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                  </svg>
                  <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  {{ book.stock > 0 ? (isInCart(book) ? 'Added to Cart' : 'Add to Cart') : 'Out of Stock' }}
                </button>
                
                <button
                  @click.stop="openDetails(book)"
                  class="flex items-center justify-center px-6 py-3 bg-white/90 backdrop-blur-sm text-gray-800 rounded-full font-semibold text-sm transition-all duration-300 transform hover:scale-105 hover:bg-white shadow-lg w-48"
                >
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  View Details
                </button>
              </div>
            </div>
          </div>

          <!-- Book Info -->
          <div class="p-6">
            <!-- Title and Author -->
            <div class="mb-4">
              <h3 class="font-bold text-xl text-gray-900 mb-2 line-clamp-1 group-hover:text-indigo-600 transition-colors duration-300">
                {{ book.title }}
              </h3>
              <div class="flex items-center text-gray-600">
                <svg class="w-4 h-4 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm">{{ book.author }}</span>
              </div>
            </div>

            <!-- Publisher -->
            <div class="flex items-center mb-3" v-if="book.publisher">
              <svg class="w-4 h-4 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 a1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
              </svg>
              <span class="text-sm text-gray-600">{{ book.publisher }}</span>
            </div>

            <!-- Rating - UPDATED to use data from controller -->
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center">
                <!-- Star Rating Display -->
                <div class="flex items-center gap-1">
                  <template v-for="i in 5" :key="i">
                    <span
                      class="text-lg"
                      :style="{
                        color: i <= Math.round(book.average_rating) ? '#FACC15' : '#D1D5DB'
                      }"
                    >
                      ★
                    </span>
                  </template>
                  <span class="ml-2 text-gray-700 text-sm font-semibold">
                    {{ book.average_rating ? book.average_rating.toFixed(1) : '0.0' }} / 5
                  </span>
                </div>
              </div>
              
              <!-- Price -->
              <div class="text-right">
                <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                  ${{ book.price }}
                </span>
              </div>
            </div>

            <!-- Total Reviews Count -->
            <div v-if="book.feedbacks_count > 0" class="mb-3">
              <p class="text-sm text-gray-500">
                ({{ book.feedbacks_count }} {{ book.feedbacks_count === 1 ? 'review' : 'reviews' }})
              </p>
            </div>

            <!-- Cart Status -->
            <div v-if="isInCart(book)" class="mt-4 pt-4 border-t border-gray-100">
              <div class="flex items-center justify-center">
                <span class="flex items-center text-sm text-emerald-600 font-medium">
                  <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  Added to Cart: {{ getCartQuantity(book) }} items
                </span>
              </div>
            </div>

            <!-- Stock Progress Bar -->
            <div v-if="book.stock > 0" class="mt-4">
              <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Stock Level</span>
                <span>{{ book.stock }} left</span>
              </div>
              <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div 
                  class="h-full rounded-full transition-all duration-500"
                  :class="
                    book.stock > 20 ? 'bg-gradient-to-r from-emerald-400 to-green-400' :
                    book.stock > 10 ? 'bg-gradient-to-r from-amber-400 to-yellow-400' :
                    'bg-gradient-to-r from-rose-400 to-pink-400'
                  "
                  :style="{ width: `${Math.min(book.stock, 30) * 3.33}%` }"
                ></div>
              </div>
            </div>
          </div>
          
          <!-- Decorative Corner Accents -->
          <div class="absolute top-0 left-0 w-16 h-16">
            <div class="absolute top-0 left-0 w-16 h-16 border-t-2 border-l-2 border-indigo-200 rounded-tl-2xl"></div>
          </div>
          <div class="absolute bottom-0 right-0 w-16 h-16">
            <div class="absolute bottom-0 right-0 w-16 h-16 border-b-2 border-r-2 border-purple-200 rounded-br-2xl"></div>
          </div>
        </div>
      </div>

      <!-- Floating Cart Indicator -->
      <div v-if="cartItems.length > 0" class="fixed bottom-8 right-8 z-50">
        <div class="relative">
          <div class="absolute -top-2 -right-2 w-6 h-6 bg-gradient-to-r from-rose-500 to-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-lg">
            {{ cartItems.length }}
          </div>
          <div class="w-14 h-14 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center shadow-xl cursor-pointer hover:shadow-2xl hover:scale-110 transition-all duration-300">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Rokomari-style Footer -->
    <footer class="bg-gradient-to-b from-gray-900 to-black text-gray-300 mt-16">
      <!-- Main Footer -->
      <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          
          <!-- Company Info -->
          <div>
            <h3 class="text-2xl font-bold bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent mb-4">
              Book Haven
            </h3>
            <p class="text-gray-400 mb-4">
              Bangladesh's largest online bookstore. We offer millions of books at the best prices.
            </p>
            <div class="flex space-x-4">
              <a href="#" class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                </svg>
              </a>
              <a href="#" class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                </svg>
              </a>
              <a href="#" class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073z"/>
                  <path d="M12 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4z"/>
                  <circle cx="18.406" cy="5.594" r="1.44"/>
                </svg>
              </a>
              <a href="#" class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                </svg>
              </a>
            </div>
          </div>

          <!-- Quick Links -->
          <div>
            <h4 class="text-lg font-semibold text-white mb-4">Quick Links</h4>
            <ul class="space-y-2">
              <li><a href="#" class="hover:text-white transition-colors">Home</a></li>
              <li><a href="#" class="hover:text-white transition-colors">All Books</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Lorem, ipsum.</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Lorem, ipsum.</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Lorem, ipsum.</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Lorem, ipsum.</a></li>
            </ul>
          </div>

          <!-- Categories - Fetched from Database -->
          <div>
            <h4 class="text-lg font-semibold text-white mb-4">Categories</h4>
            <div v-if="categoriesLoading" class="text-center py-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-400 mx-auto"></div>
              <p class="text-sm text-gray-400 mt-2">Loading categories...</p>
            </div>
            <ul v-else-if="categories.length > 0" class="space-y-2">
              <li v-for="category in categories" :key="category.id">
                <a 
                  href="#" 
                  @click.prevent="openCategoryDetails(category)"
                  class="hover:text-white transition-colors flex items-center group"
                >
                  <svg class="w-4 h-4 mr-2 text-gray-400 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                  {{ category.name }}
                </a>
              </li>
            </ul>
            <div v-else class="text-center py-2">
              <p class="text-sm text-gray-400">No categories available</p>
            </div>
          </div>

          <!-- Contact & Support -->
          <div>
            <h4 class="text-lg font-semibold text-white mb-4">Contact & Support</h4>
            <ul class="space-y-2">
              <li class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                <span>Sylhet, Bangladesh</span>
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                </svg>
                <span>+880 1319513026</span>
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
                <span>support@bookhaven.com</span>
              </li>
            </ul>


          </div>
        </div>

        <!-- Payment Methods -->
        <div class="mt-10 pt-8 border-t border-gray-800">
          <h5 class="text-lg font-semibold text-white mb-4 text-center">We Accept</h5>
          <div class="flex flex-wrap justify-center items-center gap-4">
            <div class="bg-white p-2 rounded-lg">
              <svg class="w-10 h-10 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
            <div class="bg-white p-2 rounded-lg">
              <span class="text-lg font-bold text-gray-800">bKash</span>
            </div>
            <div class="bg-white p-2 rounded-lg">
              <span class="text-lg font-bold text-gray-800">Nagad</span>
            </div>
            <div class="bg-white p-2 rounded-lg">
              <svg class="w-10 h-10 text-blue-800" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM7.07 18.28c.43-.9 3.05-1.78 4.93-1.78s4.51.88 4.93 1.78C15.57 19.36 13.86 20 12 20s-3.57-.64-4.93-1.72zm11.29-1.45c-1.43-1.74-4.9-2.33-6.36-2.33s-4.93.59-6.36 2.33C4.62 15.49 4 13.82 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8c0 1.82-.62 3.49-1.64 4.83zM12 6c-1.94 0-3.5 1.56-3.5 3.5S10.06 13 12 13s3.5-1.56 3.5-3.5S13.94 6 12 6zm0 5c-.83 0-1.5-.67-1.5-1.5S11.17 8 12 8s1.5.67 1.5 1.5S12.83 11 12 11z"/>
              </svg>
            </div>
            <div class="bg-white p-2 rounded-lg">
              <span class="text-lg font-bold text-gray-800">VISA</span>
            </div>
            <div class="bg-white p-2 rounded-lg">
              <span class="text-lg font-bold text-gray-800">MasterCard</span>
            </div>
            <div class="bg-white p-2 rounded-lg">
              <span class="text-lg font-bold text-gray-800">Cash on Delivery</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Footer -->
      <div class="bg-black py-6">
        <div class="max-w-7xl mx-auto px-4">
          <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="text-center md:text-left mb-4 md:mb-0">
              <p class="text-gray-400 text-sm">
                &copy; 2024 Book Shop. All rights reserved.
              </p>
              <p class="text-gray-500 text-xs mt-1">
                A proud initiative to promote reading culture in Bangladesh
              </p>
            </div>
            <div class="flex flex-wrap justify-center gap-4">
              <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Privacy Policy</a>
              <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Terms & Conditions</a>
              <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Return Policy</a>
              <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">FAQ</a>
              <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Contact Us</a>
            </div>
          </div>
        </div>
      </div>
    </footer>
  </AuthenticatedLayout>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Smooth transitions */
* {
  transition-property: color, background-color, border-color, transform, opacity;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #6366f1, #8b5cf6);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #4f46e5, #7c3aed);
}
</style>
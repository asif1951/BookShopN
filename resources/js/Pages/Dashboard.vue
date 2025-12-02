<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'

const page = usePage()
const books = ref([])
const cartItems = ref([])

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
  // Dispatch multiple events to ensure all components get the update
  window.dispatchEvent(new Event('storage'))
  window.dispatchEvent(new Event('cartUpdated'))
  window.dispatchEvent(new CustomEvent('cartChange', { detail: cartItems.value }))
}

// Listen for storage events (when cart is updated from other components)
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
  // This forces Vue to re-render the component
  cartItems.value = [...cartItems.value]
}

// Load cart on component mount and set up event listeners
onMounted(() => {
  loadCart()
  
  // Listen for storage events (from other tabs/windows)
  window.addEventListener('storage', handleStorageChange)
  
  // Listen for custom cart update events (from same window)
  window.addEventListener('cartUpdated', handleCartUpdate)
  
  // Listen for custom cart change events
  window.addEventListener('cartChange', handleCartChange)
  
  // Set up a periodic check for cart changes (fallback)
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

  // Store interval ID for cleanup
  window.cartCheckInterval = cartCheckInterval
})

// Clean up event listeners when component unmounts
onUnmounted(() => {
  window.removeEventListener('storage', handleStorageChange)
  window.removeEventListener('cartUpdated', handleCartUpdate)
  window.removeEventListener('cartChange', handleCartChange)
  
  // Clear the interval
  if (window.cartCheckInterval) {
    clearInterval(window.cartCheckInterval)
  }
})

const openDetails = (book) => {
  router.visit(`/books/${book.id}`)
}

// Add to cart function - initially quantity 1
const addToCart = (book) => {
  const existingItem = cartItems.value.find(item => item.id === book.id)
  
  if (existingItem) {
    // If already in cart, increment quantity if stock available
    if (existingItem.quantity < book.stock) {
      existingItem.quantity += 1
    }
  } else {
    // If not in cart, add with quantity 1
    cartItems.value.push({
      ...book,
      quantity: 1
    })
  }
  
  saveCart()
  forceUpdate()
}

// Increment quantity
const incrementQuantity = (book) => {
  const cartItem = cartItems.value.find(item => item.id === book.id)
  if (cartItem && cartItem.quantity < book.stock) {
    cartItem.quantity += 1
    saveCart()
    forceUpdate()
  }
}

// Decrement quantity
const decrementQuantity = (book) => {
  const cartItem = cartItems.value.find(item => item.id === book.id)
  if (cartItem) {
    if (cartItem.quantity > 1) {
      cartItem.quantity -= 1
      saveCart()
      forceUpdate()
    } else {
      // Remove item if quantity becomes 0
      removeFromCart(book)
    }
  }
}

// Remove from cart
const removeFromCart = (book) => {
  cartItems.value = cartItems.value.filter(item => item.id !== book.id)
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
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </template>

    <div class="py-12 max-w-7xl mx-auto">
      <div v-if="books.length === 0" class="text-center text-gray-500">
        No books available.
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div
          v-for="book in books"
          :key="book.id"
          class="relative bg-white rounded-2xl shadow hover:shadow-lg overflow-hidden transition duration-300 group"
        >
          <img
            :src="book.photo ? `/storage/${book.photo}` : 'https://via.placeholder.com/200x250?text=No+Image'"
            :alt="book.title"
            class="w-full h-60 object-cover"
          />

          <div
            class="absolute inset-0 bg-black bg-opacity-50 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300"
          >
            <!-- Always show Add to Cart button (never show + - buttons here) -->
            <button
              @click.stop="addToCart(book)"
              :disabled="book.stock === 0 || isInCart(book)"
              class="bg-green-500 text-white px-4 py-2 rounded-md mb-2 hover:bg-green-600 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
            >
              {{ book.stock > 0 ? (isInCart(book) ? 'Added to Cart' : 'Add to Cart') : 'Out of Stock' }}
            </button>

            <button
              @click.stop="openDetails(book)"
              class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition"
            >
              Details
            </button>
          </div>

          <div class="p-4">
            <h3 class="font-semibold text-lg text-gray-800 truncate">{{ book.title }}</h3>
            <p class="text-gray-500 text-sm">{{ book.author }}</p>
            
            <!-- Publisher -->
            <p class="text-gray-500 text-sm mt-1" v-if="book.publisher">
              Publisher: {{ book.publisher }}
            </p>
            
            <!-- Average Rating -->
            <div class="flex items-center mt-1" v-if="book.average_rating">
              <div class="flex text-yellow-400">
                <svg v-for="star in 5" :key="star" 
                  class="w-3 h-3" 
                  :class="star <= Math.round(book.average_rating) ? 'fill-current' : 'text-gray-300'"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </div>
              <span class="text-xs text-gray-600 ml-1">
                {{ book.average_rating.toFixed(1) }}
              </span>
            </div>
            
            <p class="text-indigo-600 font-semibold mt-2">${{ book.price }}</p>
            <p
              :class="book.stock === 0 ? 'text-red-600' : 'text-gray-600'"
              class="text-sm mt-1"
            >
              Stock: {{ book.stock }}
            </p>
            
            <!-- Show cart badge if book is in cart -->
            <div v-if="isInCart(book)" class="mt-2">
              <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                In Cart: {{ getCartQuantity(book) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
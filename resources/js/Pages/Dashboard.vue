<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'

const page = usePage()
const books = ref([])
const cartItems = ref([])
const categories = ref([])
const categoriesLoading = ref(false)

// Search filters
const authorSearch = ref('')
const categorySearch = ref('')
const publisherSearch = ref('')
const searchTimeout = ref(null)

// Check if user is logged in
const isAuthenticated = ref(!!page.props.auth?.user)
const prevAuthState = ref(isAuthenticated.value)

// Initialize books from props
const initBooks = () => {
  books.value = page.props.books || []
  
  // Initialize search filters from URL parameters if they exist
  if (page.props.filters) {
    authorSearch.value = page.props.filters.author || ''
    categorySearch.value = page.props.filters.category || ''
    publisherSearch.value = page.props.filters.publisher || ''
  }
}
initBooks()
watch(() => page.props.books, initBooks)

// Watch for authentication state changes
watch(() => page.props.auth?.user, (newUser) => {
  const newAuthState = !!newUser
  
  // If authentication state changed (login/logout)
  if (newAuthState !== prevAuthState.value) {
    console.log('🔄 Authentication state changed:', newAuthState ? 'Logged in' : 'Logged out')
    
    // If user just logged in, reload the page
    if (newAuthState && !prevAuthState.value) {
      console.log('🎉 User logged in, reloading page...')
      
      // Small delay to ensure session is properly set
      setTimeout(() => {
        // Reload the current page to refresh data
        router.reload({
          only: ['books', 'filters', 'auth'],
          preserveScroll: true,
          preserveState: true
        })
      }, 500)
    }
    
    // Update the previous state
    prevAuthState.value = newAuthState
    isAuthenticated.value = newAuthState
    
    // Also reload cart if user logged in
    if (newAuthState) {
      loadCart()
    }
  }
}, { deep: true })

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

// Debounced search function (800ms delay for better UX)
const performSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  searchTimeout.value = setTimeout(() => {
    // Use '/' route which is handled by DashboardController
    router.get('/', {
      author: authorSearch.value,
      category: categorySearch.value,
      publisher: publisherSearch.value
    }, {
      preserveState: true,
      replace: true,
      only: ['books', 'filters']
    })
  }, 800) // 800ms delay - typing shesh korar pore filter hobe
}

// Manual search on Enter key press
const handleEnterSearch = (event) => {
  if (event.key === 'Enter') {
    if (searchTimeout.value) {
      clearTimeout(searchTimeout.value)
    }
    performSearch()
  }
}

// Watch search filter changes - debounced
watch([authorSearch, categorySearch, publisherSearch], () => {
  performSearch()
})

// Clear all search filters
const clearSearchFilters = () => {
  authorSearch.value = ''
  categorySearch.value = ''
  publisherSearch.value = ''
  
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  // Use '/' route which is handled by DashboardController
  router.get('/', {}, {
    preserveState: true,
    replace: true,
    only: ['books', 'filters']
  })
}

// Load cart on component mount and set up event listeners
onMounted(() => {
  loadCart()
  fetchCategories()
  
  // Check if user just logged in (for page refresh after login)
  const justLoggedIn = sessionStorage.getItem('justLoggedIn')
  if (justLoggedIn === 'true') {
    console.log('🔄 Just logged in, reloading page...')
    sessionStorage.removeItem('justLoggedIn')
    
    // Small delay to ensure everything is loaded
    setTimeout(() => {
      router.reload({
        only: ['books', 'filters', 'auth'],
        preserveScroll: true
      })
    }, 300)
  }
  
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

// Add to cart function - NOW REQUIRES LOGIN
const addToCart = (book) => {
  // Check if user is authenticated
  if (!isAuthenticated.value) {
    // Directly redirect to login page without alert
    sessionStorage.setItem('justLoggedIn', 'true')
    router.visit('/login')
    return
  }
  
  // Only proceed if user is logged in
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

// Function to handle login redirect
const handleLoginRedirect = () => {
  // Set flag to indicate user is going to login page
  sessionStorage.setItem('justLoggedIn', 'true')
  router.visit('/login')
}

// Function to handle checkout (redirects to login if not authenticated)
const handleCheckout = () => {
  if (!isAuthenticated.value) {
    // Store current cart and redirect to login
    localStorage.setItem('pendingCheckout', 'true')
    sessionStorage.setItem('justLoggedIn', 'true')
    router.visit('/login')
  } else {
    // Proceed to payment if logged in
    router.visit('/payment')
  }
}
</script>

<template>
  <Head title="Book Haven" />
  <AuthenticatedLayout :guest-mode="!isAuthenticated">
    <template #header>
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="font-bold text-3xl md:text-4xl text-gray-900 leading-tight bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
            Book Haven
          </h2>
          <p class="text-gray-600 mt-1">Discover your next favorite read</p>
          <!-- Guest indicator -->
          <div v-if="!isAuthenticated" class="mt-2">
            <span class="px-3 py-1 bg-gradient-to-r from-amber-100 to-orange-100 text-amber-800 rounded-full font-medium text-sm">
              Guest Mode - Login required to add to cart
            </span>
          </div>
          <!-- Welcome message for logged in users -->
          <div v-else class="mt-2">
            <span class="px-3 py-1 bg-gradient-to-r from-emerald-100 to-green-100 text-emerald-800 rounded-full font-medium text-sm">
              Welcome, {{ page.props.auth?.user?.name || 'User' }}!
            </span>
          </div>
        </div>
        <div class="flex items-center space-x-2 text-sm">
          <span class="px-3 py-1 bg-gradient-to-r from-green-100 to-emerald-100 text-emerald-800 rounded-full font-medium">
            {{ books.length }} Books Available
          </span>
          <span v-if="isAuthenticated" class="px-3 py-1 bg-gradient-to-r from-blue-100 to-indigo-100 text-indigo-800 rounded-full font-medium">
            {{ cartItems.length }} In Cart
          </span>
          <span v-else class="px-3 py-1 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-600 rounded-full font-medium">
            Login to use cart
          </span>
        </div>
      </div>
    </template>

    <div class="py-8 max-w-7xl mx-auto">
      <!-- Search Box Section - Rokomari Style -->
      <div class="mb-10 px-4">
        <div class="bg-gradient-to-r from-indigo-50 via-white to-purple-50 border border-indigo-100 rounded-2xl shadow-xl p-6">
          <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-6">
            <div class="w-full md:w-auto">
              <h3 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                Find Your Perfect Book
              </h3>
              <p class="text-gray-600 mt-1">Search by author, category, or publisher</p>
            </div>
            
            <div class="flex items-center space-x-2">
              <span class="text-sm text-gray-500">Live Results:</span>
              <span class="px-3 py-1 bg-gradient-to-r from-emerald-100 to-green-100 text-emerald-700 rounded-full text-sm font-medium">
                {{ books.length }} found
              </span>
              <span v-if="!isAuthenticated" class="px-3 py-1 bg-gradient-to-r from-amber-100 to-orange-100 text-amber-700 rounded-full text-sm font-medium">
                Login Required for Cart
              </span>
              <span v-else class="px-3 py-1 bg-gradient-to-r from-indigo-100 to-purple-100 text-indigo-700 rounded-full text-sm font-medium">
                Ready to Shop!
              </span>
            </div>
          </div>

          <!-- Search Inputs - Rokomari Style -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Author Search -->
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <input
                v-model="authorSearch"
                @keyup="handleEnterSearch"
                type="text"
                class="w-full pl-12 pr-12 py-4 text-base border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-all duration-300 shadow-sm hover:border-indigo-300 group-hover:border-indigo-400"
                placeholder="Search by author..."
              />
              <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                <button
                  v-if="authorSearch"
                  @click="authorSearch = ''; performSearch()"
                  class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <div v-else class="w-5 h-5"></div>
              </div>
              <div class="absolute -bottom-6 left-0 text-xs text-gray-500 mt-1">
                Type and wait...
              </div>
            </div>

            <!-- Category Search -->
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </div>
              <input
                v-model="categorySearch"
                @keyup="handleEnterSearch"
                type="text"
                class="w-full pl-12 pr-12 py-4 text-base border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:outline-none transition-all duration-300 shadow-sm hover:border-emerald-300 group-hover:border-emerald-400"
                placeholder="Search by category..."
              />
              <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                <button
                  v-if="categorySearch"
                  @click="categorySearch = ''; performSearch()"
                  class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <div v-else class="w-5 h-5"></div>
              </div>
              <div class="absolute -bottom-6 left-0 text-xs text-gray-500 mt-1">
                Type and wait...
              </div>
            </div>

            <!-- Publisher Search -->
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
              </div>
              <input
                v-model="publisherSearch"
                @keyup="handleEnterSearch"
                type="text"
                class="w-full pl-12 pr-12 py-4 text-base border-2 border-gray-200 rounded-xl focus:border-amber-500 focus:ring-2 focus:ring-amber-200 focus:outline-none transition-all duration-300 shadow-sm hover:border-amber-300 group-hover:border-amber-400"
                placeholder="Search by publisher..."
              />
              <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                <button
                  v-if="publisherSearch"
                  @click="publisherSearch = ''; performSearch()"
                  class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <div v-else class="w-5 h-5"></div>
              </div>
              <div class="absolute -bottom-6 left-0 text-xs text-gray-500 mt-1">
                Type and wait...
              </div>
            </div>
          </div>

          <!-- Search Tips -->
          <div class="mt-8 pt-6 border-t border-gray-100">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center space-x-4">
                <span class="text-sm font-medium text-gray-700">Search Tips:</span>
                <div class="flex flex-wrap gap-2">
                  <span class="px-3 py-1.5 bg-gradient-to-r from-gray-50 to-gray-100 text-gray-600 rounded-lg text-xs">
                    Type slowly for better results
                  </span>
                  <span class="px-3 py-1.5 bg-gradient-to-r from-gray-50 to-gray-100 text-gray-600 rounded-lg text-xs">
                    Press Enter for instant search
                  </span>
                  <span class="px-3 py-1.5 bg-gradient-to-r from-gray-50 to-gray-100 text-gray-600 rounded-lg text-xs">
                    Clear filters to reset
                  </span>
                </div>
              </div>
              
              <button
                @click="clearSearchFilters"
                class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-xl hover:from-indigo-600 hover:to-purple-600 transition-all duration-300 shadow-md hover:shadow-lg flex items-center justify-center group"
              >
                <svg class="w-4 h-4 mr-2 group-hover:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset All Filters
              </button>
            </div>
          </div>

          <!-- Active Filters Display - Only show when filters are active -->
          <div v-if="authorSearch || categorySearch || publisherSearch" class="mt-6">
            <div class="bg-gradient-to-r from-indigo-50/50 to-purple-50/50 border border-indigo-100 rounded-xl p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                  </svg>
                  <span class="font-semibold text-indigo-700">Active Filters</span>
                </div>
                <span class="text-sm text-gray-500">Results will update automatically</span>
              </div>
              
              <div class="flex flex-wrap gap-3">
                <div v-if="authorSearch" class="relative group">
                  <span class="px-4 py-2.5 bg-gradient-to-r from-indigo-100 to-purple-100 text-indigo-700 rounded-lg text-sm font-medium flex items-center group-hover:shadow-md transition-all duration-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Author: {{ authorSearch }}
                  </span>
                  <button
                    @click="authorSearch = ''; performSearch()"
                    class="absolute -top-2 -right-2 w-5 h-5 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xs hover:bg-red-200 transition-colors"
                  >
                    ×
                  </button>
                </div>
                
                <div v-if="categorySearch" class="relative group">
                  <span class="px-4 py-2.5 bg-gradient-to-r from-emerald-100 to-green-100 text-emerald-700 rounded-lg text-sm font-medium flex items-center group-hover:shadow-md transition-all duration-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Category: {{ categorySearch }}
                  </span>
                  <button
                    @click="categorySearch = ''; performSearch()"
                    class="absolute -top-2 -right-2 w-5 h-5 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xs hover:bg-red-200 transition-colors"
                  >
                    ×
                  </button>
                </div>
                
                <div v-if="publisherSearch" class="relative group">
                  <span class="px-4 py-2.5 bg-gradient-to-r from-amber-100 to-orange-100 text-amber-700 rounded-lg text-sm font-medium flex items-center group-hover:shadow-md transition-all duration-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    Publisher: {{ publisherSearch }}
                  </span>
                  <button
                    @click="publisherSearch = ''; performSearch()"
                    class="absolute -top-2 -right-2 w-5 h-5 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xs hover:bg-red-200 transition-colors"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Books Grid -->
      <div v-if="books.length === 0" class="text-center py-20">
        <div class="max-w-md mx-auto">
          <svg class="w-24 h-24 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
          </svg>
          <h3 class="mt-6 text-xl font-semibold text-gray-700">
            {{ authorSearch || categorySearch || publisherSearch ? 'No books found' : 'No books available' }}
          </h3>
          <p class="mt-2 text-gray-500">
            {{ authorSearch || categorySearch || publisherSearch ? 'Try a different search term' : 'Check back later for new arrivals' }}
          </p>
          <button
            v-if="authorSearch || categorySearch || publisherSearch"
            @click="clearSearchFilters"
            class="mt-4 px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 transition-all duration-300 shadow-sm hover:shadow"
          >
            Clear Search Filters
          </button>
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
                  :disabled="book.stock === 0"
                  class="flex items-center justify-center px-6 py-3 rounded-full font-semibold text-sm transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg w-48"
                  :class="
                    !isAuthenticated 
                      ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:from-amber-600 hover:to-orange-600' 
                      : isInCart(book) 
                        ? 'bg-gradient-to-r from-emerald-500 to-green-500 text-white' 
                        : 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white hover:from-indigo-600 hover:to-purple-600'
                  "
                >
                  <svg v-if="!isAuthenticated" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                  </svg>
                  <svg v-else-if="!isInCart(book)" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                  </svg>
                  <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  {{ 
                    !isAuthenticated ? 'Login to Add' : 
                    book.stock > 0 ? (isInCart(book) ? 'Added to Cart' : 'Add to Cart') : 'Out of Stock' 
                  }}
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
                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 a1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 a1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
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

            <!-- Cart Status - Only show if user is logged in -->
            <div v-if="isAuthenticated && isInCart(book)" class="mt-4 pt-4 border-t border-gray-100">
              <div class="flex items-center justify-center">
                <span class="flex items-center text-sm text-emerald-600 font-medium">
                  <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  Added to Cart: {{ getCartQuantity(book) }} items
                </span>
              </div>
            </div>

            <!-- Login Prompt for guests -->
            <div v-if="!isAuthenticated" class="mt-4 pt-4 border-t border-gray-100">
              <div class="text-center">
                <p class="text-sm text-amber-600 font-medium">
                  <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Login to add to cart
                </p>
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

      <!-- Floating Cart Indicator - Only show if user is logged in -->
      <div v-if="isAuthenticated && cartItems.length > 0" class="fixed bottom-8 right-8 z-50">
        <div class="relative">
          <div class="absolute -top-2 -right-2 w-6 h-6 bg-gradient-to-r from-rose-500 to-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-lg">
            {{ cartItems.length }}
          </div>
          <div 
            @click="handleCheckout"
            class="w-14 h-14 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center shadow-xl cursor-pointer hover:shadow-2xl hover:scale-110 transition-all duration-300 group"
            title="View Cart & Checkout"
          >
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 a2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Floating Login Button for guests -->
      <div v-if="!isAuthenticated" class="fixed bottom-8 right-8 z-50">
        <div class="relative">
          <div 
            @click="handleLoginRedirect"
            class="w-14 h-14 bg-gradient-to-r from-amber-500 to-orange-500 rounded-full flex items-center justify-center shadow-xl cursor-pointer hover:shadow-2xl hover:scale-110 transition-all duration-300 group"
            title="Login to use cart"
          >
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            <div class="absolute bottom-full right-0 mb-2 hidden group-hover:block">
              <div class="bg-gray-900 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                Login to use cart
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

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

/* Search input focus effects */
input:focus {
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Rokomari-style animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

/* Loading shimmer effect for search */
.shimmer {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

/* Loading animation for page refresh */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
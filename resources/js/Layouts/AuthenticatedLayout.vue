<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { Link, router, usePage } from '@inertiajs/vue3'

// Sidebar toggle
const sidebarOpen = ref(false)

// Cart
const showCart = ref(false)
const cartItems = ref([])
const cartCount = ref(0)

// Real-time books data for stock validation
const booksData = ref([])

// Search filters for navbar - SHOW ONLY ON DASHBOARD
const authorSearch = ref('')
const categorySearch = ref('')
const publisherSearch = ref('')
const searchTimeout = ref(null)

// Dropdown data
const authors = ref([])
const categories = ref([])
const publishers = ref([])
const authorsLoading = ref(false)
const categoriesLoading = ref(false)
const publishersLoading = ref(false)

// Real-time stock update interval
const stockUpdateInterval = ref(null)

// Debug stock updates
const debugStockUpdates = ref(true)

// Manual refresh loading state
const manualRefreshLoading = ref(false)

// Check if current page is dashboard
const isDashboard = ref(false)

// Current route path for sidebar active state
const currentPath = ref('')

// Use Inertia page for current URL
const page = usePage()

// Update current path when route changes
const updateCurrentPath = () => {
  currentPath.value = window.location.pathname
}

// Debounced search function for navbar
const performSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  searchTimeout.value = setTimeout(() => {
    router.get(route('dashboard'), {
      author: authorSearch.value,
      category: categorySearch.value,
      publisher: publisherSearch.value
    }, {
      preserveState: true,
      replace: true,
      only: ['books']
    })
  }, 500)
}

// Watch search filter changes
watch([authorSearch, categorySearch, publisherSearch], () => {
  if (isDashboard.value) {
    performSearch()
  }
})

// Clear all search filters
const clearSearchFilters = () => {
  authorSearch.value = ''
  categorySearch.value = ''
  publisherSearch.value = ''
  
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  if (isDashboard.value) {
    router.get(route('dashboard'), {}, {
      preserveState: true,
      replace: true,
      only: ['books']
    })
  }
}

// Load books data for stock validation - FIXED VERSION
const loadBooksData = async () => {
  try {
    if (debugStockUpdates.value) console.log('🔄 Fetching stock data...')
    
    // Use the correct endpoint from your web.php
    const response = await fetch('/books/stock-data')
    
    if (debugStockUpdates.value) console.log('📡 Response status:', response.status)
    
    if (response.ok) {
      const newBooksData = await response.json()
      if (debugStockUpdates.value) console.log('📚 Books stock data loaded:', newBooksData)
      
      booksData.value = newBooksData
      
      // Update cart items with latest stock data
      updateCartItemsWithLatestStock(newBooksData)
      return true
    } else {
      console.error('❌ Failed to fetch stock data:', response.status)
      return false
    }
  } catch (error) {
    console.error('❌ Error fetching books data:', error)
    return false
  }
}

// FIXED: Completely update cart items with latest stock data
const updateCartItemsWithLatestStock = (latestBooksData) => {
  if (latestBooksData.length === 0) {
    if (debugStockUpdates.value) console.log('⚠️ No stock data received')
    return
  }
  
  let hasChanges = false
  
  if (debugStockUpdates.value) console.log('📦 Current cart items:', cartItems.value)
  if (debugStockUpdates.value) console.log('📊 Latest stock data:', latestBooksData)
  
  // Create a completely new cart array with updated stock
  const updatedCart = cartItems.value.map(cartItem => {
    const latestBook = latestBooksData.find(book => book.id === cartItem.id)
    
    if (latestBook) {
      if (debugStockUpdates.value) console.log(`🔍 Comparing ${cartItem.title}: cart stock ${cartItem.stock} vs latest stock ${latestBook.stock}`)
      
      if (cartItem.stock !== latestBook.stock) {
        if (debugStockUpdates.value) console.log(`🔄 Updating ${cartItem.title} stock: ${cartItem.stock} → ${latestBook.stock}`)
        hasChanges = true
        return {
          ...cartItem,
          stock: latestBook.stock
        }
      }
    } else {
      if (debugStockUpdates.value) console.log(`❌ No matching book found for cart item: ${cartItem.title} (ID: ${cartItem.id})`)
    }
    
    return cartItem
  })
  
  // Only update if there are actual changes
  if (hasChanges) {
    cartItems.value = updatedCart
    saveCart()
    if (debugStockUpdates.value) console.log('✅ Cart updated with latest stock')
  } else {
    if (debugStockUpdates.value) console.log('ℹ️ No stock changes detected')
  }
}

// Alternative method: Force update all cart items with latest stock
const forceUpdateCartStock = (latestBooksData) => {
  if (latestBooksData.length === 0) return
  
  const updatedCart = cartItems.value.map(cartItem => {
    const latestBook = latestBooksData.find(book => book.id === cartItem.id)
    if (latestBook) {
      return {
        ...cartItem,
        stock: latestBook.stock
      }
    }
    return cartItem
  })
  
  cartItems.value = updatedCart
  saveCart()
  if (debugStockUpdates.value) console.log('🔄 Cart stock forcefully updated')
}

// Start real-time stock updates when cart is open
const startRealTimeStockUpdates = () => {
  // Clear existing interval
  if (stockUpdateInterval.value) {
    clearInterval(stockUpdateInterval.value)
  }
  
  // Update stock every 3 seconds when cart is open
  stockUpdateInterval.value = setInterval(async () => {
    if (showCart.value) {
      if (debugStockUpdates.value) console.log('🔄 Real-time stock update triggered')
      await loadBooksData()
    }
  }, 3000)
}

// Stop real-time stock updates when cart is closed
const stopRealTimeStockUpdates = () => {
  if (stockUpdateInterval.value) {
    clearInterval(stockUpdateInterval.value)
    stockUpdateInterval.value = null
    if (debugStockUpdates.value) console.log('🛑 Real-time updates stopped')
  }
}

const loadCart = () => {
  try {
    const cart = JSON.parse(localStorage.getItem('cart')) || []
    cartItems.value = cart
    cartCount.value = cart.length
    if (debugStockUpdates.value) console.log('📥 Cart loaded:', cart)
  } catch (error) {
    console.error('❌ Error loading cart:', error)
    cartItems.value = []
    cartCount.value = 0
  }
}

const saveCart = () => {
  try {
    localStorage.setItem('cart', JSON.stringify(cartItems.value))
    cartCount.value = cartItems.value.length
    window.dispatchEvent(new Event('storage'))
    if (debugStockUpdates.value) console.log('💾 Cart saved:', cartItems.value)
  } catch (error) {
    console.error('❌ Error saving cart:', error)
  }
}

const toggleCart = async () => {
  if (debugStockUpdates.value) console.log('🛒 Opening cart...')
  
  // Load latest stock data before showing cart
  await loadBooksData()
  loadCart()
  showCart.value = true
  
  // Start real-time updates when cart opens
  startRealTimeStockUpdates()
}

// Close cart function with cleanup
const closeCart = () => {
  if (debugStockUpdates.value) console.log('❌ Closing cart...')
  showCart.value = false
  // Stop real-time updates when cart closes
  stopRealTimeStockUpdates()
}

// Manual stock refresh function
const manualRefreshStock = async () => {
  if (manualRefreshLoading.value) return // Prevent multiple clicks
  
  if (debugStockUpdates.value) console.log('🔃 Manual stock refresh requested')
  
  manualRefreshLoading.value = true
  
  try {
    const success = await loadBooksData()
    
    if (success) {
      // Show success feedback
      if (debugStockUpdates.value) console.log('✅ Manual refresh successful')
      
      // Force refresh the cart display
      loadCart()
    } else {
      console.error('❌ Manual refresh failed')
    }
  } catch (error) {
    console.error('❌ Manual refresh error:', error)
  } finally {
    manualRefreshLoading.value = false
  }
}

// Force refresh function that always updates stock
const forceRefreshStock = async () => {
  if (manualRefreshLoading.value) return
  
  manualRefreshLoading.value = true
  
  try {
    if (debugStockUpdates.value) console.log('🔃 Force refreshing stock...')
    
    const response = await fetch('/books/stock-data')
    
    if (response.ok) {
      const newBooksData = await response.json()
      if (debugStockUpdates.value) console.log('📚 Force refresh stock data:', newBooksData)
      
      // Use the force update method
      forceUpdateCartStock(newBooksData)
      loadCart() // Reload cart to ensure UI updates
    } else {
      console.error('❌ Force refresh failed')
    }
  } catch (error) {
    console.error('❌ Force refresh error:', error)
  } finally {
    manualRefreshLoading.value = false
  }
}

const increaseQty = (id) => {
  const item = cartItems.value.find(i => i.id === id)
  
  // Stock check
  if (item && item.quantity < item.stock) {
    item.quantity++
    saveCart()
  } else if (item) {
    alert('Cannot add more than available stock!')
  }
}

const decreaseQty = (id) => {
  const item = cartItems.value.find(i => i.id === id)
  if (item && item.quantity > 1) {
    item.quantity--
    saveCart()
  }
}

const removeItem = (id) => {
  cartItems.value = cartItems.value.filter(i => i.id !== id)
  saveCart()
}

// Fetch authors, categories, publishers
const fetchAuthors = async () => {
  authorsLoading.value = true
  try {
    const response = await fetch('/api/authors')
    if (response.ok) {
      authors.value = await response.json()
    }
  } catch (error) {
    console.error('Error fetching authors:', error)
  } finally {
    authorsLoading.value = false
  }
}

const fetchCategories = async () => {
  categoriesLoading.value = true
  try {
    const response = await fetch('/api/categories')
    if (response.ok) {
      categories.value = await response.json()
    }
  } catch (error) {
    console.error('Error fetching categories:', error)
  } finally {
    categoriesLoading.value = false
  }
}

const fetchPublishers = async () => {
  publishersLoading.value = true
  try {
    const response = await fetch('/api/publishers')
    if (response.ok) {
      publishers.value = await response.json()
    }
  } catch (error) {
    console.error('Error fetching publishers:', error)
  } finally {
    publishersLoading.value = false
  }
}

// Author details page e navigate korar function
const openAuthorDetails = (author) => {
  router.visit(`/authors/${author.id}`)
}

// Category details page e navigate korar function
const openCategoryDetails = (category) => {
  router.visit(`/categories/${category.id}`)
}

// Publisher details page e navigate korar function
const openPublisherDetails = (publisher) => {
  router.visit(`/publishers/${publisher.id}`)
}

const totalAmount = computed(() =>
  cartItems.value.reduce((sum, i) => sum + i.price * i.quantity, 0)
)

// Watch for cart modal state changes
watch(showCart, (newValue) => {
  if (newValue) {
    // Cart opened - start real-time updates
    startRealTimeStockUpdates()
  } else {
    // Cart closed - stop real-time updates
    stopRealTimeStockUpdates()
  }
})

// Check current route and update isDashboard
const checkCurrentRoute = () => {
  currentPath.value = window.location.pathname
  isDashboard.value = currentPath.value === '/dashboard' || currentPath.value === '/'
}

// Function to check if a link is active
const isLinkActive = (linkPath) => {
  return currentPath.value === linkPath
}

onMounted(() => {
  // Load books data on component mount
  loadBooksData()
  loadCart()
  window.addEventListener('storage', loadCart)
  
  // Fetch dropdown data
  fetchAuthors()
  fetchCategories()
  fetchPublishers()
  
  // Check current route on mount
  checkCurrentRoute()
  
  // Watch for route changes
  window.addEventListener('popstate', checkCurrentRoute)
  
  // Also listen to Inertia navigation events
  window.addEventListener('inertia:navigate', checkCurrentRoute)
})

// Cleanup on unmount
onUnmounted(() => {
  stopRealTimeStockUpdates()
  window.removeEventListener('storage', loadCart)
  window.removeEventListener('popstate', checkCurrentRoute)
  window.removeEventListener('inertia:navigate', checkCurrentRoute)
})
</script>

<template>
  <div>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col">
      <!-- Navbar -->
      <nav class="bg-white shadow-lg border-b border-gray-200 fixed top-0 left-0 right-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16 items-center">
            <!-- Logo Section - LEFT SIDE -->
            <div class="flex items-center space-x-3">
              <Link :href="route('dashboard')" class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                BookShop
              </Link>
              <button
                @click="sidebarOpen = !sidebarOpen"
                class="sm:hidden text-gray-600 hover:text-gray-900 focus:outline-none p-1 rounded-lg hover:bg-gray-100"
              >
                <svg
                  v-if="!sidebarOpen"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Search Boxes - SHOW ONLY ON DASHBOARD -->
            <div v-if="isDashboard" class="hidden md:flex items-center space-x-2 flex-1 max-w-xl mx-4">
              <!-- Author Search -->
              <div class="relative flex-1">
                <input
                  v-model="authorSearch"
                  type="text"
                  placeholder="Search by author"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm"
                />
              </div>

              <!-- Category Search -->
              <div class="relative flex-1">
                <input
                  v-model="categorySearch"
                  type="text"
                  placeholder="Search by category"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm"
                />
              </div>

              <!-- Publisher Search -->
              <div class="relative flex-1">
                <input
                  v-model="publisherSearch"
                  type="text"
                  placeholder="Search by publisher"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm"
                />
              </div>

              <!-- Clear Search Button -->
              <button
                @click="clearSearchFilters"
                class="px-3 py-2 text-sm bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 rounded-lg hover:from-gray-200 hover:to-gray-300 transition-all duration-300 border border-gray-300 whitespace-nowrap shadow-sm hover:shadow"
              >
                Clear
              </button>
            </div>

            <!-- Dropdowns + Cart Icon + User Dropdown - RIGHT SIDE -->
            <div class="hidden sm:flex sm:items-center sm:space-x-3">
              <!-- Authors Dropdown -->
              <div class="relative group">
                <button class="flex items-center text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-indigo-50">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Authors
                  <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </button>
                <div class="absolute top-full left-0 mt-1 w-64 bg-white rounded-xl shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 max-h-72 overflow-y-auto">
                  <div v-if="authorsLoading" class="px-4 py-3 text-gray-500 text-sm">
                    <div class="flex items-center justify-center py-2">
                      <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600 mr-2"></div>
                      Loading...
                    </div>
                  </div>
                  <div v-else-if="authors.length === 0" class="px-4 py-3 text-gray-500 text-sm">
                    No authors found
                  </div>
                  <div v-else>
                    <div 
                      v-for="author in authors" 
                      :key="author.id" 
                      class="px-4 py-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-all duration-200 group/item"
                      @click="openAuthorDetails(author)"
                    >
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-r from-indigo-100 to-purple-100 rounded-full flex items-center justify-center mr-3">
                          <span class="text-indigo-600 text-xs font-semibold">{{ author.name.charAt(0) }}</span>
                        </div>
                        <span class="text-gray-800 group-hover/item:text-indigo-600">{{ author.name }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Categories Dropdown - WIDTH INCREASED -->
              <div class="relative group">
                <button class="flex items-center text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-indigo-50">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                  </svg>
                  Categories
                  <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </button>
                <div class="absolute top-full left-0 mt-1 w-72 bg-white rounded-xl shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 max-h-72 overflow-y-auto">
                  <div v-if="categoriesLoading" class="px-4 py-3 text-gray-500 text-sm">
                    <div class="flex items-center justify-center py-2">
                      <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600 mr-2"></div>
                      Loading...
                    </div>
                  </div>
                  <div v-else-if="categories.length === 0" class="px-4 py-3 text-gray-500 text-sm">
                    No categories found
                  </div>
                  <div v-else>
                    <div 
                      v-for="category in categories" 
                      :key="category.id" 
                      class="px-4 py-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-all duration-200 group/item"
                      @click="openCategoryDetails(category)"
                    >
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-r from-green-100 to-emerald-100 rounded-full flex items-center justify-center mr-3">
                          <span class="text-green-600 text-xs font-semibold">{{ category.name.charAt(0) }}</span>
                        </div>
                        <span class="text-gray-800 group-hover/item:text-green-600">{{ category.name }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Publishers Dropdown -->
              <div class="relative group">
                <button class="flex items-center text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-indigo-50">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                  </svg>
                  Publishers
                  <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </button>
                <div class="absolute top-full left-0 mt-1 w-64 bg-white rounded-xl shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 max-h-72 overflow-y-auto">
                  <div v-if="publishersLoading" class="px-4 py-3 text-gray-500 text-sm">
                    <div class="flex items-center justify-center py-2">
                      <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600 mr-2"></div>
                      Loading...
                    </div>
                  </div>
                  <div v-else-if="publishers.length === 0" class="px-4 py-3 text-gray-500 text-sm">
                    No publishers found
                  </div>
                  <div v-else>
                    <div 
                      v-for="publisher in publishers" 
                      :key="publisher.id" 
                      class="px-4 py-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-all duration-200 group/item"
                      @click="openPublisherDetails(publisher)"
                    >
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-r from-amber-100 to-orange-100 rounded-full flex items-center justify-center mr-3">
                          <span class="text-amber-600 text-xs font-semibold">{{ publisher.name.charAt(0) }}</span>
                        </div>
                        <span class="text-gray-800 group-hover/item:text-amber-600">{{ publisher.name }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Cart Icon -->
              <button @click="toggleCart" class="text-gray-700 hover:text-indigo-600 relative focus:outline-none p-2 rounded-lg hover:bg-indigo-50 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.5 6H19m-12-6V6a1 1 0 011-1h4a1 1 0 011 1v7" />
                </svg>
                <span
                  class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center font-semibold shadow-sm">
                  {{ cartCount }}
                </span>
              </button>

              <!-- User Dropdown - RIGHT ALIGNED -->
              <div class="ml-2">
                <Dropdown align="right" width="48">
                  <template #trigger>
                    <span class="inline-flex rounded-lg hover:shadow-sm transition-all duration-300">
                      <button
                        type="button"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white hover:text-indigo-600 hover:bg-indigo-50 focus:outline-none transition-all duration-300 border border-gray-200"
                      >
                        <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center mr-2">
                          <span class="text-white text-sm font-semibold">{{ $page.props.auth.user.name.charAt(0) }}</span>
                        </div>
                        {{ $page.props.auth.user.name }}
                        <svg
                          class="ms-2 -me-0.5 h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                          />
                        </svg>
                      </button>
                    </span>
                  </template>

                  <template #content>
                    <div class="py-1 bg-white rounded-lg shadow-lg border border-gray-200">
                      <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm font-semibold text-gray-900">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $page.props.auth.user.email }}</p>
                        <span class="inline-flex items-center px-2 py-1 mt-2 text-xs font-medium rounded-full"
                          :class="$page.props.auth.user.role === 'admin' 
                            ? 'bg-gradient-to-r from-indigo-100 to-purple-100 text-indigo-800' 
                            : 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800'">
                          {{ $page.props.auth.user.role }}
                        </span>
                      </div>
                      <DropdownLink :href="route('profile.edit')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Profile
                      </DropdownLink>
                      <DropdownLink :href="route('logout')" method="post" as="button" 
                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 w-full text-left">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Log Out
                      </DropdownLink>
                    </div>
                  </template>
                </Dropdown>
              </div>
            </div>
          </div>

          <!-- Mobile Search Boxes - SHOW ONLY ON DASHBOARD -->
          <div v-if="isDashboard" class="md:hidden mt-3 space-y-2">
            <div class="flex space-x-2">
              <input
                v-model="authorSearch"
                type="text"
                placeholder="Search by author"
                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm"
              />
              <input
                v-model="categorySearch"
                type="text"
                placeholder="Search by category"
                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm"
              />
            </div>
            <div class="flex space-x-2">
              <input
                v-model="publisherSearch"
                type="text"
                placeholder="Search by publisher"
                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm"
              />
              <button
                @click="clearSearchFilters"
                class="px-3 py-2 text-sm bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 rounded-lg hover:from-gray-200 hover:to-gray-300 transition-all duration-300 border border-gray-300 shadow-sm"
              >
                Clear
              </button>
            </div>
          </div>
        </div>
      </nav>

      <!-- Enhanced Sidebar with DARK BACKGROUND -->
      <aside
        :class="[ 
          'bg-gradient-to-b from-gray-800 to-gray-900 shadow-xl fixed top-16 h-full transition-all duration-300 ease-in-out z-10',
          sidebarOpen ? 'left-0' : '-left-64',
          'w-64 sm:left-0 sm:w-64'
        ]"
      >
        <div class="p-6 border-b border-gray-700">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center mr-3 shadow-md">
              <span class="text-white text-lg font-bold">{{ $page.props.auth.user.name.charAt(0) }}</span>
            </div>
            <div>
              <h2 class="text-lg font-bold text-white">
                {{ $page.props.auth.user.name }}
              </h2>
              <span class="text-xs font-medium px-2 py-1 rounded-full"
                :class="$page.props.auth.user.role === 'admin' 
                  ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' 
                  : 'bg-gradient-to-r from-green-500 to-emerald-500 text-white'">
                {{ $page.props.auth.user.role }}
              </span>
            </div>
          </div>
        </div>
        
        <nav class="mt-4 px-4">
          <div class="mb-4">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-2">Navigation</h3>
            <ul class="space-y-1">
              <!-- Dashboard -->
              <li>
                <Link href="/dashboard" 
                  class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-300 hover:shadow-sm group"
                  :class="isLinkActive('/dashboard') || isLinkActive('/') 
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white shadow-md' 
                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                  </svg>
                  Dashboard
                </Link>
              </li>
            </ul>
          </div>

          <!-- Admin Only Links -->
          <div v-if="$page.props.auth.user.role === 'admin'" class="mb-4">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-2">Admin Tools</h3>
            <ul class="space-y-1">
              <!-- Create Book -->
              <li>
                <Link href="/create-book" 
                  class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-300 hover:shadow-sm group"
                  :class="isLinkActive('/create-book') 
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white shadow-md' 
                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                  </svg>
                  Create Book
                </Link>
              </li>
              
              <!-- Create Category -->
              <li>
                <Link href="/create-category" 
                  class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-300 hover:shadow-sm group"
                  :class="isLinkActive('/create-category') 
                    ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-md' 
                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  Create Category
                </Link>
              </li>
              
              <!-- Create Author -->
              <li>
                <Link href="/create-author" 
                  class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-300 hover:shadow-sm group"
                  :class="isLinkActive('/create-author') 
                    ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-md' 
                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Create Author
                </Link>
              </li>
              
              <!-- Create Publisher -->
              <li>
                <Link href="/create-publisher" 
                  class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-300 hover:shadow-sm group"
                  :class="isLinkActive('/create-publisher') 
                    ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md' 
                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                  </svg>
                  Create Publisher
                </Link>
              </li>
              
              <!-- Users -->
              <li>
                <Link href="/users" 
                  class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-300 hover:shadow-sm group"
                  :class="isLinkActive('/users') 
                    ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-md' 
                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                  </svg>
                  Users
                </Link>
              </li>
            </ul>
          </div>

          <!-- Common Links -->
          <div class="mb-4">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-2">Orders</h3>
            <ul class="space-y-1">
              <!-- Order List / My Orders -->
              <li>
                <Link href="/order-list" 
                  class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-300 hover:shadow-sm group"
                  :class="isLinkActive('/order-list') 
                    ? 'bg-gradient-to-r from-teal-500 to-emerald-500 text-white shadow-md' 
                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  {{ $page.props.auth.user.role === 'admin' ? 'Order List' : 'My Orders' }}
                </Link>
              </li>
            </ul>
          </div>
        </nav>
        
        <!-- Sidebar Footer -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700">
          <div class="flex items-center justify-center">
            <span class="text-xs text-gray-400">
              BookShop v1.0
            </span>
          </div>
        </div>
      </aside>

      <!-- Page Content -->
      <main class="pt-20 sm:ml-64 transition-all duration-300 min-h-screen">
        <slot />
      </main>

      <!-- 🛒 Cart Modal -->
      <div
        v-if="showCart"
        class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50"
        @click.self="closeCart"
      >
        <div class="bg-white w-[480px] max-h-[80vh] overflow-y-auto rounded-xl shadow-2xl p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
              🛒 Your Cart
            </h2>
            <button @click="closeCart" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div v-if="cartItems.length === 0" class="text-center py-8">
            <div class="w-24 h-24 mx-auto mb-4 bg-gradient-to-r from-gray-100 to-gray-200 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.5 6H19m-12-6V6a1 1 0 011-1h4a1 1 0 011 1v7" />
              </svg>
            </div>
            <p class="text-gray-500 text-lg mb-2">Your cart is empty</p>
            <p class="text-gray-400 text-sm">Add some books to get started!</p>
          </div>

          <div v-else>
            <div
              v-for="(item, index) in cartItems"
              :key="index"
              class="flex items-center justify-between border border-gray-200 rounded-lg p-4 mb-3 hover:shadow-sm transition-all duration-300"
            >
              <img
                :src="item.photo ? '/storage/' + item.photo : 'https://via.placeholder.com/60x80?text=No+Image'"
                class="w-16 h-20 object-cover rounded-lg shadow"
              />
              <div class="flex-1 px-4">
                <p class="font-semibold text-gray-800">{{ item.title }}</p>
                <!-- Stock information with real-time updates -->
                <p class="text-sm mt-1" :class="item.quantity > item.stock ? 'text-red-600 font-semibold' : 'text-gray-600'">
                  <span class="inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                    Stock: {{ item.stock }}
                  </span>
                  <span v-if="item.quantity > item.stock" class="text-red-500 text-xs ml-2 bg-red-50 px-2 py-1 rounded-full">Insufficient stock!</span>
                </p>
                <p class="text-indigo-600 font-semibold mt-2">${{ (item.price * item.quantity).toFixed(2) }}</p>
              </div>

              <div class="flex flex-col items-center space-y-2">
                <div class="flex items-center space-x-2">
                  <button 
                    @click="decreaseQty(item.id)" 
                    class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full hover:bg-gray-200 transition-all duration-300"
                    :disabled="item.quantity <= 1"
                    :class="{ 'opacity-50 cursor-not-allowed': item.quantity <= 1 }"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                  </button>
                  <span class="font-medium text-lg w-8 text-center">{{ item.quantity }}</span>
                  <button 
                    @click="increaseQty(item.id)" 
                    class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full hover:bg-gray-200 transition-all duration-300"
                    :disabled="item.quantity >= item.stock"
                    :class="{ 'opacity-50 cursor-not-allowed': item.quantity >= item.stock }"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                </div>
                <button 
                  @click="removeItem(item.id)" 
                  class="text-red-500 text-xs mt-2 hover:text-red-700 transition-all duration-300 flex items-center"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Remove
                </button>
              </div>
            </div>

            <!-- Stock status summary -->
            <div v-if="cartItems.some(item => item.quantity > item.stock)" 
              class="mt-4 p-4 bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-lg">
              <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <p class="text-red-700 text-sm font-semibold">Some items have insufficient stock!</p>
              </div>
              <p class="text-red-600 text-xs mt-1">Please adjust quantities before proceeding.</p>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="flex justify-between items-center mb-4">
                <span class="text-gray-600">Subtotal:</span>
                <span class="text-lg font-semibold">${{ totalAmount.toFixed(2) }}</span>
              </div>
              <button
                @click="router.visit('/payment')"
                :disabled="cartItems.some(item => item.quantity > item.stock)"
                class="w-full py-3 rounded-lg font-semibold transition-all duration-300 flex items-center justify-center"
                :class="cartItems.some(item => item.quantity > item.stock) 
                  ? 'bg-gradient-to-r from-gray-300 to-gray-400 text-gray-600 cursor-not-allowed' 
                  : 'bg-gradient-to-r from-green-500 to-emerald-500 text-white hover:from-green-600 hover:to-emerald-600 hover:shadow-lg'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" v-if="!cartItems.some(item => item.quantity > item.stock)">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                {{ cartItems.some(item => item.quantity > item.stock) ? 'Fix Stock Issues First' : 'Proceed to Payment' }}
              </button>
            </div>
          </div>

          <button
            @click="closeCart"
            class="w-full mt-4 py-3 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 transition-all duration-300 font-semibold hover:shadow-lg"
          >
            Continue Shopping
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { Link, router, usePage } from '@inertiajs/vue3'

// Define props to accept guest mode
const props = defineProps({
  guestMode: {
    type: Boolean,
    default: false
  }
})

// Sidebar toggle
const sidebarOpen = ref(false)

// Cart
const showCart = ref(false)
const cartItems = ref([])
const cartCount = ref(0)

// Real-time books data for stock validation
const booksData = ref([])

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

// Check if user is logged in
const isAuthenticated = computed(() => !!page.props.auth?.user)

// Check if user is admin (only when authenticated)
const isAdmin = computed(() => isAuthenticated.value && page.props.auth.user?.role === 'admin')

// Update current path when route changes
const updateCurrentPath = () => {
  currentPath.value = window.location.pathname
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

// Handle checkout based on authentication
const handleCheckout = () => {
  if (!isAuthenticated.value) {
    // Store current cart and redirect to login
    localStorage.setItem('pendingCheckout', 'true')
    router.visit('/login')
  } else {
    // Proceed to payment if logged in
    router.visit('/payment')
  }
}

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
      <!-- Sidebar ekdom top e - SHOW ONLY FOR ADMIN (and only when authenticated) -->
      <aside
        v-if="isAdmin"
        :class="[ 
          'bg-gradient-to-b from-gray-800 to-gray-900 shadow-xl fixed top-0 h-full transition-all duration-300 ease-in-out z-30',
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
              <span class="text-xs font-medium px-2 py-1 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 text-white">
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
          <div class="mb-4">
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
              <!-- Order List -->
              <li>
                <Link href="/order-list" 
                  class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-300 hover:shadow-sm group"
                  :class="isLinkActive('/order-list') 
                    ? 'bg-gradient-to-r from-teal-500 to-emerald-500 text-white shadow-md' 
                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  Order List
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

      <!-- Navbar - Sidebar er niche shuru hobe -->
      <nav class="bg-white shadow-lg border-b border-gray-200 fixed top-0 left-0 right-0 z-20" :class="isAdmin ? 'sm:left-64' : ''">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16 items-center">
            <!-- Logo Section - LEFT SIDE -->
            <div class="flex items-center space-x-3">
              <Link href="/" class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                Book Haven
              </Link>
              <button
                v-if="isAdmin"
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

            <!-- Mobile: Dropdowns & Cart Icon (Hidden on desktop) -->
            <div class="sm:hidden flex items-center space-x-3">
              <!-- Order List Button for Mobile (Visible only for authenticated non-admin users) -->
              <Link
                v-if="isAuthenticated && !isAdmin"
                :href="route('order.list')"
                class="flex items-center px-3 py-2 bg-gradient-to-r from-teal-500 to-emerald-500 text-white rounded-lg hover:from-teal-600 hover:to-emerald-600 transition-all duration-200 shadow-sm hover:shadow text-sm"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Orders
              </Link>

              <!-- Login/Register buttons for guests (Mobile) -->
              <template v-if="!isAuthenticated">
                <Link
                  :href="route('login', { redirect: currentPath })"
                  class="px-3 py-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium hover:bg-indigo-50 rounded-lg transition-all duration-200"
                >
                  Login
                </Link>
                <Link
                  :href="route('register', { redirect: currentPath })"
                  class="px-3 py-2 text-sm bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 transition-all duration-200 shadow-sm hover:shadow"
                >
                  Register
                </Link>
              </template>

              <!-- Cart Icon for Mobile -->
              <button @click="toggleCart" class="text-gray-700 hover:text-indigo-600 relative focus:outline-none p-1 rounded-lg hover:bg-indigo-50 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.5 6H19m-12-6V6a1 1 0 011-1h4a1 1 0 011 1v7" />
                </svg>
                <span
                  v-if="cartCount > 0"
                  class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-full text-xs w-4 h-4 flex items-center justify-center font-semibold shadow-sm text-[10px]">
                  {{ cartCount }}
                </span>
              </button>

              <!-- Mobile User Menu (only for authenticated users) -->
              <Dropdown v-if="isAuthenticated" align="right" width="48">
                <template #trigger>
                  <span class="inline-flex rounded-lg hover:shadow-sm transition-all duration-300">
                    <button
                      type="button"
                      class="inline-flex items-center px-2 py-1 text-sm font-medium rounded-lg text-gray-700 bg-white hover:text-indigo-600 hover:bg-indigo-50 focus:outline-none transition-all duration-300 border border-gray-200"
                    >
                      <div class="w-6 h-6 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-xs font-semibold">{{ $page.props.auth.user.name.charAt(0) }}</span>
                      </div>
                    </button>
                  </span>
                </template>

                <template #content>
                  <div class="py-1 bg-white rounded-lg shadow-lg border border-gray-200 min-w-48">
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

            <!-- Desktop: Dropdowns + Cart Icon + User Dropdown + Order List Button -->
            <div class="hidden sm:flex sm:items-center sm:space-x-4">
              <!-- Home Button -->
              <Link href="/" class="flex items-center text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-indigo-50 border border-transparent hover:border-indigo-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Home
              </Link>

              <!-- Order List Button for Desktop (Visible only for authenticated non-admin users) -->
              <Link
                v-if="isAuthenticated && !isAdmin"
                :href="route('order.list')"
                class="flex items-center px-4 py-2 bg-gradient-to-r from-teal-500 to-emerald-500 text-white rounded-lg hover:from-teal-600 hover:to-emerald-600 transition-all duration-200 shadow-sm hover:shadow text-sm"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Order List
              </Link>

              <!-- Authors Dropdown -->
              <div class="relative group">
                <button class="flex items-center text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-indigo-50 border border-transparent hover:border-indigo-100">
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
                <button class="flex items-center text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-indigo-50 border border-transparent hover:border-indigo-100">
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
                <button class="flex items-center text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-indigo-50 border border-transparent hover:border-indigo-100">
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

              <!-- Login/Register buttons for guests (Desktop) -->
              <template v-if="!isAuthenticated">
                <div class="flex items-center space-x-2 ml-2">
                  <Link
                    :href="route('login', { redirect: currentPath })"
                    class="px-4 py-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium hover:bg-indigo-50 rounded-lg transition-all duration-200 border border-indigo-200 hover:border-indigo-300"
                  >
                    Login
                  </Link>
                  <Link
                    :href="route('register', { redirect: currentPath })"
                    class="px-4 py-2 text-sm bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 transition-all duration-200 shadow-sm hover:shadow"
                  >
                    Register
                  </Link>
                </div>
              </template>

              <!-- Cart Icon -->
              <button @click="toggleCart" class="text-gray-700 hover:text-indigo-600 relative focus:outline-none p-2 rounded-lg hover:bg-indigo-50 transition-all duration-300 border border-transparent hover:border-indigo-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.5 6H19m-12-6V6a1 1 0 011-1h4a1 1 0 011 1v7" />
                </svg>
                <span
                  v-if="cartCount > 0"
                  class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center font-semibold shadow-sm">
                  {{ cartCount }}
                </span>
              </button>

              <!-- User Dropdown - RIGHT ALIGNED (only for authenticated users) -->
              <div v-if="isAuthenticated" class="ml-2">
                <Dropdown align="right" width="48">
                  <template #trigger>
                    <span class="inline-flex rounded-lg hover:shadow-sm transition-all duration-300">
                      <button
                        type="button"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white hover:text-indigo-600 hover:bg-indigo-50 focus:outline-none transition-all duration-300 border border-gray-200 hover:border-indigo-100"
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
        </div>
      </nav>

      <!-- Page Content -->
      <main class="pt-16 transition-all duration-300 min-h-screen" :class="isAdmin ? 'sm:ml-64' : ''">
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
                @click="handleCheckout"
                :disabled="cartItems.some(item => item.quantity > item.stock)"
                class="w-full py-3 rounded-lg font-semibold transition-all duration-300 flex items-center justify-center"
                :class="cartItems.some(item => item.quantity > item.stock) 
                  ? 'bg-gradient-to-r from-gray-300 to-gray-400 text-gray-600 cursor-not-allowed' 
                  : isAuthenticated 
                    ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white hover:from-green-600 hover:to-emerald-600 hover:shadow-lg'
                    : 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white hover:from-indigo-600 hover:to-purple-600 hover:shadow-lg'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                {{ cartItems.some(item => item.quantity > item.stock) 
                  ? 'Fix Stock Issues First' 
                  : isAuthenticated 
                    ? 'Proceed to Payment' 
                    : 'Login to Checkout' }}
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

    <!-- Rokomari-style Footer (Added here) -->
    <footer class="bg-gradient-to-b from-gray-900 to-black text-gray-300">
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
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
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
              <li><a href="/dashboard" class="hover:text-white transition-colors">Home</a></li>
              <li><a href="#" class="hover:text-white transition-colors">All Books</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Best Sellers</a></li>
              <li><a href="#" class="hover:text-white transition-colors">New Arrivals</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Discounts</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
            </ul>
          </div>

          <!-- Categories -->
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
  </div>
</template>
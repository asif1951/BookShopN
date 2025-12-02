<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

const page = usePage()
const book = ref(page.props.book || {})
const authUser = page.props.auth?.user || null

// Reviews from database
const reviews = ref(page.props.feedbacks || [])
const rating = ref(0)
const reviewText = ref('')
const errors = ref({})
const editingReviewId = ref(null)

// Cart functionality
const cartItems = ref([])

// Load cart from localStorage
const loadCart = () => {
  const savedCart = localStorage.getItem('cart')
  if (savedCart) {
    cartItems.value = JSON.parse(savedCart)
  } else {
    cartItems.value = []
  }
  console.log('📥 Cart loaded:', cartItems.value)
}

// Save cart to localStorage
const saveCart = () => {
  localStorage.setItem('cart', JSON.stringify(cartItems.value))
  console.log('💾 Cart saved:', cartItems.value)
  
  // Dispatch multiple events to ensure all components get the update
  window.dispatchEvent(new Event('storage'))
  window.dispatchEvent(new Event('cartUpdated'))
  window.dispatchEvent(new CustomEvent('cartChange', { detail: cartItems.value }))
  
  // Force immediate UI update
  forceUpdate()
}

// Listen for storage events
const handleStorageChange = (event) => {
  if (event.key === 'cart' || event.key === null) {
    console.log('📢 Storage event detected')
    loadCart()
    forceUpdate()
  }
}

// Listen for custom cart update events
const handleCartUpdate = () => {
  console.log('📢 Custom cart update event detected')
  loadCart()
  forceUpdate()
}

// Listen for custom cart change events
const handleCartChange = (event) => {
  console.log('📢 Cart change event detected:', event.detail)
  cartItems.value = event.detail || []
  forceUpdate()
}

// Force UI update - Improved version
const forceUpdate = () => {
  // This forces Vue to re-render the component by creating a new array reference
  cartItems.value = [...cartItems.value]
  
  // Use nextTick to ensure DOM updates
  nextTick(() => {
    console.log('🔄 UI force updated, cart items:', cartItems.value)
  })
}

// Load cart on component mount
onMounted(() => {
  loadCart()
  
  window.addEventListener('storage', handleStorageChange)
  window.addEventListener('cartUpdated', handleCartUpdate)
  window.addEventListener('cartChange', handleCartChange)
  
  // Set up periodic check for immediate updates
  const cartCheckInterval = setInterval(() => {
    const currentCart = localStorage.getItem('cart')
    if (currentCart) {
      const parsedCart = JSON.parse(currentCart)
      if (JSON.stringify(parsedCart) !== JSON.stringify(cartItems.value)) {
        console.log('🕒 Periodic check detected cart change')
        loadCart()
        forceUpdate()
      }
    } else if (cartItems.value.length > 0) {
      console.log('🕒 Periodic check detected cart cleared')
      loadCart()
      forceUpdate()
    }
  }, 500) // Check every 500ms for immediate updates

  window.cartCheckInterval = cartCheckInterval
})

// Clean up event listeners
onUnmounted(() => {
  window.removeEventListener('storage', handleStorageChange)
  window.removeEventListener('cartUpdated', handleCartUpdate)
  window.removeEventListener('cartChange', handleCartChange)
  
  if (window.cartCheckInterval) {
    clearInterval(window.cartCheckInterval)
  }
})

// Check if book is in cart - Reactive
const isInCart = (book) => {
  return cartItems.value.some(item => item.id === book.id)
}

// Get cart quantity for a book - Reactive
const getCartQuantity = (book) => {
  const item = cartItems.value.find(item => item.id === book.id)
  return item ? item.quantity : 0
}

// Add to cart function - initially quantity 1
const addToCart = (book) => {
  const existingItem = cartItems.value.find(item => item.id === book.id)
  
  if (existingItem) {
    // If already in cart, increment quantity if stock available
    if (existingItem.quantity < book.stock) {
      existingItem.quantity += 1
      console.log('➕ Quantity increased for:', book.title)
    }
  } else {
    // If not in cart, add with quantity 1
    cartItems.value.push({
      ...book,
      quantity: 1
    })
    console.log('🛒 Added to cart:', book.title)
  }
  
  saveCart()
}

// Increment quantity
const incrementQuantity = (book) => {
  const cartItem = cartItems.value.find(item => item.id === book.id)
  if (cartItem && cartItem.quantity < book.stock) {
    cartItem.quantity += 1
    saveCart()
  }
}

// Decrement quantity
const decrementQuantity = (book) => {
  const cartItem = cartItems.value.find(item => item.id === book.id)
  if (cartItem) {
    if (cartItem.quantity > 1) {
      cartItem.quantity -= 1
      saveCart()
    } else {
      // Remove item if quantity becomes 0
      removeFromCart(book)
    }
  }
}

// Remove from cart - Improved with immediate UI update
const removeFromCart = (book) => {
  console.log('🗑️ Removing from cart:', book.title)
  cartItems.value = cartItems.value.filter(item => item.id !== book.id)
  saveCart()
  
  // Immediate UI feedback
  forceUpdate()
}

// Watch for page props changes to update reviews
watch(() => page.props.feedbacks, (newFeedbacks) => {
  reviews.value = newFeedbacks || []
})

// Watch for book props changes
watch(() => page.props.book, (newBook) => {
  if (newBook) {
    book.value = newBook
  }
})

// Check if user already has a review
const userReview = computed(() => {
  if (!authUser) return null
  return reviews.value.find(r => r.user_id === authUser.id)
})

// Calculate average rating and total reviews from reviews data
const averageRating = computed(() => {
  if (reviews.value.length === 0) return 0
  const sum = reviews.value.reduce((total, review) => total + review.rating, 0)
  return (sum / reviews.value.length).toFixed(1)
})

const totalReviewsCount = computed(() => {
  return reviews.value.length
})

// Calculate rating distribution like Rokomari
const ratingDistribution = computed(() => {
  const distribution = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }
  
  reviews.value.forEach(review => {
    if (review.rating >= 1 && review.rating <= 5) {
      distribution[review.rating]++
    }
  })
  
  return distribution
})

// Calculate percentage for each rating
const ratingPercentages = computed(() => {
  const percentages = {}
  const total = totalReviewsCount.value
  
  for (let i = 5; i >= 1; i--) {
    percentages[i] = total > 0 ? ((ratingDistribution.value[i] / total) * 100).toFixed(0) : 0
  }
  
  return percentages
})

// Initialize form with user's existing review
if (userReview.value) {
  rating.value = userReview.value.rating
  reviewText.value = userReview.value.review
  editingReviewId.value = userReview.value.id
}

// Go back
const goBack = () => {
  router.visit('/dashboard')
}

const cancelFeedback = () => {
  rating.value = 0
  reviewText.value = ''
  errors.value = {}
  editingReviewId.value = null
}

// Submit or Update Feedback
const submitFeedback = () => {
  errors.value = {}

  if (!authUser) return

  if (rating.value < 1 || rating.value > 5) {
    errors.value.rating = 'দয়া করে ১-৫ এর মধ্যে রেটিং দিন।'
  }

  if (Object.keys(errors.value).length > 0) return

  // FIXED: Use proper route method for update
  if (editingReviewId.value) {
    // Update existing review
    router.put(`/feedback/${editingReviewId.value}`, {
      rating: rating.value,
      review: reviewText.value,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        cancelFeedback()
        router.reload({ only: ['book', 'feedbacks'] })
      },
      onError: (err) => {
        errors.value = err
      }
    })
  } else {
    // Create new review
    router.post('/feedback', {
      book_id: book.value.id,
      rating: rating.value,
      review: reviewText.value,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        cancelFeedback()
        router.reload({ only: ['book', 'feedbacks'] })
      },
      onError: (err) => {
        errors.value = err
      }
    })
  }
}

// Edit existing review
const editReview = () => {
  if (userReview.value) {
    rating.value = userReview.value.rating
    reviewText.value = userReview.value.review
    editingReviewId.value = userReview.value.id
  }
}

// Delete review
const deleteReview = () => {
  if (!userReview.value) return
  
  if (confirm('Are you sure you want to delete your review?')) {
    router.delete(`/feedback/${userReview.value.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        cancelFeedback()
        router.reload({ only: ['book', 'feedbacks'] })
      }
    })
  }
}
</script>

<template>
  <Head :title="book.title || 'Book Details'" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ book.title || 'Book Details' }}
      </h2>
    </template>

    <div class="py-12 max-w-5xl mx-auto" v-if="book.title">
      <!-- Book Info -->
      <div class="bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
        <div class="md:w-1/3 w-full">
          <img
            :src="book.photo ? `/storage/${book.photo}` : 'https://via.placeholder.com/400x500?text=No+Image'"
            class="w-full h-full object-cover"
          />
        </div>

        <div class="md:w-2/3 w-full p-8 flex flex-col justify-between">
          <div>
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">{{ book.title }}</h1>

            <p class="text-gray-600 text-lg mb-4">By {{ book.author }}</p>

            <!-- ⭐ Average Rating -->
            <div class="flex items-center gap-1 mb-3">
              <template v-for="i in 5" :key="i">
                <span
                  class="text-3xl"
                  :style="{
                    color: i <= Math.round(averageRating) ? '#FACC15' : '#D1D5DB'
                  }"
                >
                  ★
                </span>
              </template>

              <span class="ml-2 text-gray-700 text-lg font-semibold">
                {{ averageRating }} / 5
              </span>
            </div>

            <p class="text-gray-500 mb-6">
              ({{ totalReviewsCount }} reviews)
            </p>

            <p class="text-gray-700 leading-relaxed text-md">
              {{ book.description || 'No description available.' }}
            </p>
          </div>

          <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <p class="text-indigo-600 text-3xl font-bold">${{ book.price }}</p>

            <div class="flex gap-4">
              <button
                @click="addToCart(book)"
                :disabled="book.stock === 0 || isInCart(book)"
                class="bg-green-500 text-white px-6 py-2 rounded-full hover:bg-green-600 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
              >
                {{ book.stock > 0 ? (isInCart(book) ? 'Added to Cart' : 'Add to Cart') : 'Out of Stock' }}
              </button>

              <button
                @click="goBack"
                class="bg-gray-300 text-gray-800 px-6 py-2 rounded-full hover:bg-gray-400 transition"
              >
                Back
              </button>
            </div>
          </div>

          <!-- Show cart badge if book is in cart -->
          <div v-if="isInCart(book)" class="mt-4">
            <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
              In Cart: {{ getCartQuantity(book) }}
            </span>
          </div>
        </div>
      </div>

      <!-- ⭐ Rating & Review Section -->
      <div class="mt-10 bg-gray-50 p-6 rounded-xl shadow-inner">
        <h3 class="text-2xl font-bold mb-4">
          {{ editingReviewId ? 'Edit Your Review' : 'Rate & Review This Book' }}
        </h3>

        <!-- Rating Distribution like Rokomari - MOVED to Rating & Review section -->
        <div class="mb-6 bg-white p-4 rounded-lg border">
          <h4 class="font-semibold text-lg mb-3">Rating Distribution</h4>
          <div 
            v-for="i in 5" 
            :key="i" 
            class="flex items-center gap-2 mb-2"
          >
            <span class="text-sm text-gray-600 w-8">{{ 6-i }} ★</span>
            <div class="w-32 bg-gray-200 rounded-full h-2">
              <div 
                class="bg-yellow-400 h-2 rounded-full" 
                :style="{ width: `${ratingPercentages[6-i]}%` }"
              ></div>
            </div>
            <span class="text-sm text-gray-500 w-16">{{ ratingDistribution[6-i] }} ({{ ratingPercentages[6-i] }}%)</span>
          </div>
        </div>

        <!-- User's existing review info -->
        <div v-if="userReview && !editingReviewId" class="mb-4 p-4 bg-blue-50 rounded-lg">
          <p class="text-blue-700 font-semibold">You already reviewed this book</p>
          <div class="flex items-center gap-2 mt-2">
            <button
              @click="editReview"
              class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600"
            >
              Edit Review
            </button>
            <button
              @click="deleteReview"
              class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600"
            >
              Delete Review
            </button>
          </div>
        </div>

        <!-- Rating Stars - Disabled if user already has a review and not editing -->
        <div v-if="!userReview || editingReviewId" class="flex items-center gap-2 mb-2">
          <template v-for="i in 5" :key="i">
            <button
              type="button"
              @click="rating = i"
              class="text-3xl"
              :style="{
                color: i <= rating ? '#FACC15' : '#D1D5DB',
                cursor: !userReview || editingReviewId ? 'pointer' : 'not-allowed'
              }"
            >
              ★
            </button>
          </template>
        </div>

        <p class="text-xs text-red-600 mb-2" v-if="errors.rating">{{ errors.rating }}</p>

        <!-- Review Textarea - Disabled if user already has a review and not editing -->
        <textarea
          v-if="!userReview || editingReviewId"
          v-model="reviewText"
          rows="4"
          placeholder="Write your review..."
          class="w-full p-3 rounded-md border focus:ring"
        ></textarea>

        <!-- Message when user has already reviewed and not editing -->
        <div v-if="userReview && !editingReviewId" class="p-4 bg-gray-100 rounded-md">
          <p class="text-gray-600 text-center">You have already submitted a review for this book. Use the Edit button above to modify your review.</p>
        </div>

        <p class="text-xs text-red-600" v-if="errors.review">{{ errors.review }}</p>

        <!-- Buttons - Only show if user hasn't reviewed or is editing -->
        <div v-if="!userReview || editingReviewId" class="mt-4 flex gap-3">
          <button
            @click="submitFeedback"
            class="bg-indigo-600 text-white px-5 py-2 rounded-md hover:bg-indigo-700 transition"
          >
            {{ editingReviewId ? 'Update Review' : 'Submit Review' }}
          </button>

          <button
            @click="cancelFeedback"
            class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 transition"
          >
            Cancel
          </button>
        </div>
      </div>

      <!-- Display Reviews -->
      <div class="mt-10">
        <h3 class="text-2xl font-bold mb-6">Customer Reviews ({{ totalReviewsCount }})</h3>
        
        <div v-if="reviews.length === 0" class="text-center text-gray-500 py-8">
          No reviews yet. Be the first to review this book!
        </div>

        <div v-else class="space-y-6">
          <div 
            v-for="reviewItem in reviews" 
            :key="reviewItem.id"
            class="bg-white p-6 rounded-lg shadow"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                  <span class="text-indigo-600 font-semibold">
                    {{ reviewItem.user_name ? reviewItem.user_name.charAt(0).toUpperCase() : 'U' }}
                  </span>
                </div>
                <div>
                  <p class="font-semibold text-gray-800">
                    {{ reviewItem.user_name || 'Anonymous User' }}
                  </p>
                  <div class="flex items-center gap-1">
                    <template v-for="i in 5" :key="i">
                      <span
                        class="text-yellow-400"
                        :style="{
                          color: i <= reviewItem.rating ? '#FACC15' : '#D1D5DB'
                        }"
                      >
                        ★
                      </span>
                    </template>
                    <span class="text-gray-500 text-sm ml-2">{{ reviewItem.rating }}/5</span>
                  </div>
                </div>
              </div>
              <span class="text-gray-400 text-sm">{{ new Date(reviewItem.created_at).toLocaleDateString() }}</span>
            </div>
            
            <p class="text-gray-700 leading-relaxed">{{ reviewItem.review }}</p>
            
            <!-- Show edit indicator if it's current user's review -->
            <div v-if="reviewItem.user_id === authUser?.id" class="mt-2">
              <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                Your Review
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Show loading state if book data is not available -->
    <div v-else class="py-12 max-w-5xl mx-auto">
      <div class="text-center text-gray-500">
        Loading book details...
      </div>
    </div>
  </AuthenticatedLayout>
</template>
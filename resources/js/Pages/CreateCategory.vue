<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3'
import { ref, reactive, computed, watch, nextTick, onMounted } from 'vue'

// Define props
const props = defineProps({
    categories: {
        type: Object,
        default: () => ({ data: [] })
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    flash: Object,
    filters: Object
})

// Modal and state
const showModal = ref(false)
const showEditModal = ref(false)
const successMessage = ref('')
const editingCategory = ref(null)

// Search input ref for focus
const searchInput = ref(null)

// Page input ref
const pageInput = ref(null)

// Search form - BACKEND SEARCH
const searchForm = useForm({
    search: props.filters?.search || ''
})

// Page input form
const pageForm = useForm({
    page: props.categories.current_page || 1
})

// Edit form
const editForm = useForm({
    name: ''
})

// Watch search input and debounce
let searchTimeout = null
watch(() => searchForm.search, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout)
    
    searchTimeout = setTimeout(() => {
        searchForm.get(route('categories.index'), {
            preserveState: true,
            preserveScroll: true,
            replace: true
        })
    }, 500)
})

// Focus search input when component mounts
onMounted(() => {
    if (searchInput.value) {
        nextTick(() => {
            searchInput.value.focus()
        })
    }
})

// Clear search and focus input
const clearSearch = () => {
    searchForm.search = ''
    searchForm.get(route('categories.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true
    }).then(() => {
        nextTick(() => {
            if (searchInput.value) {
                searchInput.value.focus()
            }
        })
    })
}

// New category form
const form = useForm({
  name: '',
})

// Fetch categories from Inertia
const page = usePage()

// Flash message from initial page load
if (page.props.flash?.success) {
  successMessage.value = page.props.flash.success
  setTimeout(() => (successMessage.value = ''), 3000)
}

// Get categories array (handle both paginated and simple array)
const categoriesArray = computed(() => {
    return props.categories.data || props.categories
})

// Submit new category
const submit = () => {
  form.post(route('categories.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false
      form.reset()
      if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success
        setTimeout(() => (successMessage.value = ''), 3000)
      }
    },
  })
}

// Start editing
const startEditing = (category) => {
  editingCategory.value = category
  editForm.name = category.name
  showEditModal.value = true
}

// Cancel editing
const cancelEditing = () => {
  editingCategory.value = null
  editForm.reset()
  showEditModal.value = false
}

// Update category
const updateCategory = () => {
  editForm.put(route('categories.update', editingCategory.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false
      editingCategory.value = null
      editForm.reset()
      if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success
        setTimeout(() => (successMessage.value = ''), 3000)
      }
    },
  })
}

// Delete category
const deleteCategory = (category) => {
  if (!confirm('Are you sure you want to delete this category?')) return

  const deleteForm = useForm({})
  deleteForm.delete(route('categories.destroy', category.id), {
    preserveScroll: true,
    onSuccess: () => {
      if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success
        setTimeout(() => (successMessage.value = ''), 3000)
      }
    },
  })
}

// Format date
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Dynamic pagination function
const goToPage = (pageNumber) => {
    if (pageNumber < 1 || pageNumber > props.categories.last_page) return
    
    router.get(route('categories.index', { 
        page: pageNumber,
        search: searchForm.search 
    }), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['categories', 'stats', 'flash', 'filters']
    })
}

// Go to specific page from input
const goToSpecificPage = () => {
    let pageNumber = parseInt(pageForm.page)
    
    if (isNaN(pageNumber) || pageNumber < 1) {
        pageNumber = 1
    }
    
    if (pageNumber > props.categories.last_page) {
        pageNumber = props.categories.last_page
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
watch(() => props.categories.current_page, (newPage) => {
    pageForm.page = newPage
})

// Generate page numbers for pagination
const pageNumbers = computed(() => {
    if (!props.categories.links) return []
    
    const current = props.categories.current_page
    const last = props.categories.last_page
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
</script>

<template>
  <Head title="Categories Management" />
  
  <AuthenticatedLayout>
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Categories Management</h1>
          <p class="text-gray-600 mt-2">Manage all book categories in the system</p>
        </div>
        <button 
          @click="showModal = true"
          class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition flex items-center space-x-2 shadow-sm"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          <span>Add New Category</span>
        </button>
      </div>

      <!-- Flash Messages -->
      <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-green-800 font-medium">{{ successMessage }}</span>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
          <div class="flex items-center">
            <div class="p-3 bg-blue-50 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-600">Total Categories</h3>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
          <div class="flex items-center">
            <div class="p-3 bg-green-50 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-600">Active Categories</h3>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
          <div class="flex items-center">
            <div class="p-3 bg-purple-50 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-600">Recently Added</h3>
              <p class="text-2xl font-bold text-gray-900">{{ categoriesArray.length > 0 ? '1' : '0' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Search Bar - BACKEND SEARCH -->
      <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex-1">
            <div class="relative">
              <input
                ref="searchInput"
                type="text"
                v-model="searchForm.search"
                placeholder="Search categories by name..."
                class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :disabled="searchForm.processing"
                @click="(event) => event.target.select()"
              >
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
              
              <!-- Clear button -->
              <button 
                v-if="searchForm.search"
                @click="clearSearch"
                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
                :disabled="searchForm.processing"
                type="button"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="text-sm text-gray-600">
            Showing {{ categoriesArray.length }} of 
            {{ categories.total || categoriesArray.length }} categories
            <span v-if="searchForm.search" class="text-indigo-600">
              for "{{ searchForm.search }}"
            </span>
          </div>
        </div>
        
        <!-- Loading indicator -->
        <div v-if="searchForm.processing" class="mt-2">
          <div class="flex items-center text-sm text-indigo-600">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Searching...
          </div>
        </div>
      </div>

      <!-- Categories Table -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="category in categoriesArray" 
                :key="category.id" 
                class="hover:bg-gray-50 transition-colors"
              >
                <!-- Category Info -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                      <span class="text-indigo-600 font-semibold text-sm">
                        {{ category.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">ID: {{ category.id }}</div>
                    </div>
                  </div>
                </td>
                
                <!-- Name -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ category.name }}</div>
                </td>
                
                <!-- Created Date -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(category.created_at) }}
                </td>
                
                <!-- Actions -->
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <!-- Edit Button -->
                    <button 
                      @click="startEditing(category)"
                      class="bg-blue-100 text-blue-700 text-xs px-3 py-2 rounded hover:bg-blue-200 transition font-medium"
                    >
                      Edit
                    </button>

                    <!-- Delete Button -->
                    <button
                      @click="deleteCategory(category)"
                      class="bg-red-100 text-red-700 text-xs px-3 py-2 rounded hover:bg-red-200 transition font-medium"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="categoriesArray.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No categories found</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ searchForm.search ? 'No categories match your search criteria.' : 'Get started by creating your first category.' }}
          </p>
          <div class="mt-6">
            <button 
              @click="showModal = true"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Add New Category
            </button>
          </div>
        </div>

        <!-- DYNAMIC PAGINATION WITH PAGE INPUT -->
        <div v-if="categories.data && categories.links" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div class="flex-1 flex justify-between items-center sm:hidden">
            <button 
              @click="goToPage(categories.current_page - 1)"
              :disabled="categories.current_page === 1"
              :class="{'opacity-50 cursor-not-allowed': categories.current_page === 1}"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <div class="text-sm text-gray-700">
              Page <span class="font-medium">{{ categories.current_page }}</span> of <span class="font-medium">{{ categories.last_page }}</span>
            </div>
            <button 
              @click="goToPage(categories.current_page + 1)"
              :disabled="categories.current_page === categories.last_page"
              :class="{'opacity-50 cursor-not-allowed': categories.current_page === categories.last_page}"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <!-- Showing results text - Moved to right side -->
            <div class="text-sm text-gray-700">
              <p>
                Showing
                <span class="font-medium">{{ categories.from }}</span>
                to
                <span class="font-medium">{{ categories.to }}</span>
                of
                <span class="font-medium">{{ categories.total }}</span>
                results
              </p>
            </div>
            
            <!-- Pagination controls - Moved to right side -->
            <div class="flex items-center space-x-4">
              <!-- Page Input -->
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-700">Go to page:</span>
                <div class="relative">
                  <input
                    ref="pageInput"
                    v-model="pageForm.page"
                    type="number"
                    min="1"
                    :max="categories.last_page"
                    class="w-16 px-2 py-1 border border-gray-300 rounded-md text-sm text-center focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    @keypress="handlePageInputKeypress"
                    @blur="goToSpecificPage"
                  >
                  <span class="absolute right-2 top-1 text-xs text-gray-500">/{{ categories.last_page }}</span>
                </div>
                <button
                  @click="goToSpecificPage"
                  class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition"
                >
                  Go
                </button>
              </div>

              <!-- Pagination Navigation with Previous/Next text -->
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <!-- Previous Page Link -->
                <button 
                  @click="goToPage(categories.current_page - 1)"
                  :disabled="categories.current_page === 1"
                  :class="{'opacity-50 cursor-not-allowed pointer-events-none': categories.current_page === 1}"
                  class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg class="h-5 w-5 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                  Previous
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
                      'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': pageNumber === categories.current_page,
                      'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': pageNumber !== categories.current_page
                    }"
                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                  >
                    {{ pageNumber }}
                  </button>
                </template>

                <!-- Next Page Link -->
                <button 
                  @click="goToPage(categories.current_page + 1)"
                  :disabled="categories.current_page === categories.last_page"
                  :class="{'opacity-50 cursor-not-allowed pointer-events-none': categories.current_page === categories.last_page}"
                  class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Next
                  <svg class="h-5 w-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
        <!-- END DYNAMIC PAGINATION -->
      </div>
    </div>

    <!-- Create Category Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Create New Category</h3>
        </div>
        
        <!-- Form -->
        <form @submit.prevent="submit">
          <div class="px-6 py-4 space-y-4">
            <!-- Name -->
            <div>
              <label for="category-name" class="block text-sm font-medium text-gray-700 mb-1">
                Category Name
              </label>
              <input 
                id="category-name"
                v-model="form.name" 
                type="text" 
                placeholder="Enter category name"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                :class="{ 'border-red-500': form.errors.name }"
              />
              <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
            </div>
          </div>
          
          <!-- Footer -->
          <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
            <button 
              type="button"
              @click="showModal = false"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
            >
              Cancel
            </button>
            <button 
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition"
            >
              <span v-if="form.processing">Creating...</span>
              <span v-else>Create Category</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Category Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Edit Category</h3>
          <p class="text-sm text-gray-500 mt-1">ID: {{ editingCategory.id }}</p>
        </div>
        
        <!-- Form -->
        <form @submit.prevent="updateCategory">
          <div class="px-6 py-4 space-y-4">
            <!-- Name -->
            <div>
              <label for="edit-category-name" class="block text-sm font-medium text-gray-700 mb-1">
                Category Name
              </label>
              <input 
                id="edit-category-name"
                v-model="editForm.name" 
                type="text" 
                placeholder="Enter category name"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                :class="{ 'border-red-500': editForm.errors.name }"
              />
              <p v-if="editForm.errors.name" class="text-red-500 text-xs mt-1">{{ editForm.errors.name }}</p>
            </div>
          </div>
          
          <!-- Footer -->
          <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
            <button 
              type="button"
              @click="cancelEditing"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
            >
              Cancel
            </button>
            <button 
              type="submit"
              :disabled="editForm.processing"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition"
            >
              <span v-if="editForm.processing">Updating...</span>
              <span v-else>Update Category</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
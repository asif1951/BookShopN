<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const showModal = ref(false)
const successMessage = ref('')

// Form setup
const form = useForm({
  name: '',
})

// Page input form
const pageForm = useForm({
  page: usePage().props.publishers.current_page || 1
})

// Fetch publishers from Inertia page props
const page = usePage()
const publishers = reactive(
  page.props.publishers.data.map(p => ({ ...p, editing: false, tempName: p.name }))
)

// Flash message from initial page load
if (page.props.flash?.success) {
  successMessage.value = page.props.flash.success
  setTimeout(() => (successMessage.value = ''), 3000)
}

// Submit new publisher
const submit = () => {
  form.post(route('publishers.store'), {
    preserveScroll: true,
    onSuccess: (page) => {
      showModal.value = false
      form.reset()
      
      // Update publishers list with new data
      publishers.splice(0, publishers.length, ...page.props.publishers.data.map(p => ({ 
        ...p, 
        editing: false,
        tempName: p.name 
      })))
      
      if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success
        setTimeout(() => (successMessage.value = ''), 3000)
      }
    },
  })
}

// Start editing
const startEditing = (publisher) => {
  publisher.editing = true
  publisher.tempName = publisher.name
}

// Cancel editing
const cancelEditing = (publisher) => {
  publisher.editing = false
  publisher.name = publisher.tempName
}

// Update publisher
const updatePublisher = (publisher) => {
  const updateForm = useForm({
    name: publisher.name
  })

  updateForm.put(route('publishers.update', publisher.id), {
    preserveScroll: true,
    onSuccess: (page) => {
      publisher.editing = false
      publisher.tempName = publisher.name
      
      // Update the publishers list with fresh data from server
      const updatedPublishers = page.props.publishers.data.map(p => ({
        ...p,
        editing: false,
        tempName: p.name
      }))
      
      publishers.splice(0, publishers.length, ...updatedPublishers)
      
      if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success
        setTimeout(() => (successMessage.value = ''), 3000)
      }
    },
    onError: (errors) => {
      // Revert to original name if there's an error
      publisher.name = publisher.tempName
    }
  })
}

// Delete publisher
const deletePublisher = (id) => {
  if (!confirm('Are you sure you want to delete this publisher?')) return

  const deleteForm = useForm({})
  deleteForm.delete(route('publishers.destroy', id), {
    preserveScroll: true,
    onSuccess: (page) => {
      // Remove deleted publisher from the list
      const index = publishers.findIndex(p => p.id === id)
      if (index !== -1) {
        publishers.splice(index, 1)
      }
      
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
    if (pageNumber < 1 || pageNumber > page.props.publishers.last_page) return
    
    router.get(route('publishers.index', { 
        page: pageNumber
    }), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['publishers', 'flash']
    })
}

// Go to specific page from input
const goToSpecificPage = () => {
    let pageNumber = parseInt(pageForm.page)
    
    if (isNaN(pageNumber) || pageNumber < 1) {
        pageNumber = 1
    }
    
    if (pageNumber > page.props.publishers.last_page) {
        pageNumber = page.props.publishers.last_page
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
watch(() => page.props.publishers.current_page, (newPage) => {
    pageForm.page = newPage
})

// Generate page numbers for pagination
const pageNumbers = computed(() => {
    if (!page.props.publishers.links) return []
    
    const current = page.props.publishers.current_page
    const last = page.props.publishers.last_page
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
  <Head title="Publishers Management" />
  
  <AuthenticatedLayout>
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Publishers Management</h1>
          <p class="text-gray-600 mt-2">Manage all book publishers in the system</p>
        </div>
        <button 
          @click="showModal = true"
          class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition flex items-center space-x-2 shadow-sm"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          <span>Add New Publisher</span>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-600">Total Publishers</h3>
              <p class="text-2xl font-bold text-gray-900">{{ page.props.publishers.total }}</p>
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
              <h3 class="text-sm font-medium text-gray-600">Active Publishers</h3>
              <p class="text-2xl font-bold text-gray-900">{{ page.props.publishers.total }}</p>
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
              <p class="text-2xl font-bold text-gray-900">{{ publishers.length > 0 ? '1' : '0' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Publishers Table -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Publisher</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="publisher in publishers" 
                :key="publisher.id" 
                class="hover:bg-gray-50 transition-colors"
              >
                <!-- Publisher Info -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                      <span class="text-indigo-600 font-semibold text-sm">
                        {{ publisher.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">ID: {{ publisher.id }}</div>
                    </div>
                  </div>
                </td>
                
                <!-- Name -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="!publisher.editing" class="text-sm text-gray-900">{{ publisher.name }}</div>
                  <input 
                    v-else 
                    v-model="publisher.name" 
                    type="text" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    @keyup.enter="updatePublisher(publisher)"
                    @keyup.escape="cancelEditing(publisher)"
                  />
                </td>
                
                <!-- Created Date -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(publisher.created_at) }}
                </td>
                
                <!-- Actions -->
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <!-- Edit Button -->
                    <button 
                      v-if="!publisher.editing"
                      @click="startEditing(publisher)"
                      class="bg-blue-100 text-blue-700 text-xs px-3 py-2 rounded hover:bg-blue-200 transition font-medium"
                    >
                      Edit
                    </button>
                    
                    <!-- Save/Cancel Buttons -->
                    <div v-else class="flex space-x-2">
                      <button
                        @click="updatePublisher(publisher)"
                        class="bg-green-100 text-green-700 text-xs px-3 py-2 rounded hover:bg-green-200 transition font-medium"
                      >
                        Save
                      </button>
                      <button
                        @click="cancelEditing(publisher)"
                        class="bg-gray-100 text-gray-700 text-xs px-3 py-2 rounded hover:bg-gray-200 transition font-medium"
                      >
                        Cancel
                      </button>
                    </div>

                    <!-- Delete Button -->
                    <button
                      @click="deletePublisher(publisher.id)"
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
        <div v-if="publishers.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No publishers found</h3>
          <p class="mt-1 text-sm text-gray-500">
            Get started by creating your first publisher.
          </p>
          <div class="mt-6">
            <button 
              @click="showModal = true"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Add New Publisher
            </button>
          </div>
        </div>

        <!-- DYNAMIC PAGINATION WITH PAGE INPUT -->
        <div v-if="page.props.publishers.data && page.props.publishers.links" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div class="flex-1 flex justify-between items-center sm:hidden">
            <Link 
              :href="page.props.publishers.links.prev" 
              :class="{'opacity-50 cursor-not-allowed': !page.props.publishers.links.prev}"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              preserve-scroll
            >
              Previous
            </Link>
            <div class="text-sm text-gray-700">
              Page <span class="font-medium">{{ page.props.publishers.current_page }}</span> of <span class="font-medium">{{ page.props.publishers.last_page }}</span>
            </div>
            <Link 
              :href="page.props.publishers.links.next" 
              :class="{'opacity-50 cursor-not-allowed': !page.props.publishers.links.next}"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              preserve-scroll
            >
              Next
            </Link>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium">{{ page.props.publishers.from }}</span>
                to
                <span class="font-medium">{{ page.props.publishers.to }}</span>
                of
                <span class="font-medium">{{ page.props.publishers.total }}</span>
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
                    :max="page.props.publishers.last_page"
                    class="w-16 px-2 py-1 border border-gray-300 rounded-md text-sm text-center focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    @keypress="handlePageInputKeypress"
                    @blur="goToSpecificPage"
                  >
                  <span class="absolute right-2 top-1 text-xs text-gray-500">/{{ page.props.publishers.last_page }}</span>
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
                <Link 
                  :href="page.props.publishers.links.prev" 
                  :class="{'opacity-50 cursor-not-allowed pointer-events-none': !page.props.publishers.links.prev}"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                  preserve-scroll
                >
                  <span class="sr-only">Previous</span>
                  <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </Link>

                <!-- Dynamic Page Numbers -->
                <template v-for="pageNumber in pageNumbers" :key="pageNumber">
                  <span 
                    v-if="pageNumber === '...'"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                  >
                    ...
                  </span>
                  <Link
                    v-else
                    :href="route('publishers.index', { page: pageNumber })"
                    :class="{
                      'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': pageNumber === page.props.publishers.current_page,
                      'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': pageNumber !== page.props.publishers.current_page
                    }"
                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                    preserve-scroll
                  >
                    {{ pageNumber }}
                  </Link>
                </template>

                <!-- Next Page Link -->
                <Link 
                  :href="page.props.publishers.links.next" 
                  :class="{'opacity-50 cursor-not-allowed pointer-events-none': !page.props.publishers.links.next}"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                  preserve-scroll
                >
                  <span class="sr-only">Next</span>
                  <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </Link>
              </nav>
            </div>
          </div>
        </div>
        <!-- END DYNAMIC PAGINATION -->
      </div>
    </div>

    <!-- Create Publisher Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Create New Publisher</h3>
        </div>
        
        <!-- Form -->
        <form @submit.prevent="submit">
          <div class="px-6 py-4 space-y-4">
            <!-- Name -->
            <div>
              <label for="publisher-name" class="block text-sm font-medium text-gray-700 mb-1">
                Publisher Name
              </label>
              <input 
                id="publisher-name"
                v-model="form.name" 
                type="text" 
                placeholder="Enter publisher name"
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
              <span v-else>Create Publisher</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
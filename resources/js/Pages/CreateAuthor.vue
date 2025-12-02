<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

const showModal = ref(false)
const successMessage = ref('')
const processingAuthorId = ref(null)

// Form setup
const form = useForm({
  name: '',
  bio: '',
  photo: null,
})

// Page input form
const pageForm = useForm({
  page: usePage().props.authors.current_page || 1
})

// Fetch authors from Inertia page props
const page = usePage()
const authors = ref(
  page.props.authors.data.map(a => ({ 
    ...a, 
    editing: false, 
    tempName: a.name, 
    tempBio: a.bio,
    newPhoto: null,
    originalPhoto: a.photo
  }))
)

// Flash message
if (page.props.flash?.success) {
  successMessage.value = page.props.flash.success
  setTimeout(() => (successMessage.value = ''), 3000)
}

// Submit new author
const submit = () => {
  form.post(route('authors.store'), {
    preserveScroll: true,
    onSuccess: (page) => {
      showModal.value = false
      form.reset()
      authors.value = page.props.authors.data.map(a => ({ 
        ...a, 
        editing: false, 
        tempName: a.name, 
        tempBio: a.bio,
        newPhoto: null,
        originalPhoto: a.photo
      }))
      if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success
        setTimeout(() => (successMessage.value = ''), 3000)
      }
    },
  })
}

// Start editing
const startEditing = (author) => {
  author.editing = true
  author.tempName = author.name
  author.tempBio = author.bio
  author.newPhoto = null
  author.photo = author.originalPhoto
}

// Cancel editing
const cancelEditing = (author) => {
  author.editing = false
  author.name = author.tempName
  author.bio = author.tempBio
  author.newPhoto = null
  author.photo = author.originalPhoto
}

// Handle photo change
const handlePhotoChange = (author, event) => {
  const file = event.target.files[0]
  if (file) {
    author.newPhoto = file
    
    // Preview the new photo
    const reader = new FileReader()
    reader.onload = (e) => {
      author.photo = e.target.result
    }
    reader.readAsDataURL(file)
  } else {
    author.newPhoto = null
  }
}

// Update author
const updateAuthor = (author) => {
  processingAuthorId.value = author.id
  
  const updateForm = useForm({
    name: author.name,
    bio: author.bio,
    photo: author.newPhoto
  })

  if (!author.newPhoto) {
    delete updateForm.photo
  }

  updateForm.put(route('authors.update', author.id), {
    preserveScroll: true,
    onSuccess: (page) => {
      author.editing = false
      author.newPhoto = null
      author.originalPhoto = author.photo
      processingAuthorId.value = null
      
      authors.value = page.props.authors.data.map(a => ({ 
        ...a, 
        editing: false, 
        tempName: a.name, 
        tempBio: a.bio,
        newPhoto: null,
        originalPhoto: a.photo
      }))
      
      if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success
        setTimeout(() => (successMessage.value = ''), 3000)
      }
    },
    onError: (errors) => {
      console.log('Update error:', errors)
      author.name = author.tempName
      author.bio = author.tempBio
      author.newPhoto = null
      author.photo = author.originalPhoto
      processingAuthorId.value = null
    }
  })
}

// Delete author
const deleteAuthor = (id) => {
  if (!confirm('Are you sure you want to delete this author?')) return

  const deleteForm = useForm({})
  deleteForm.delete(route('authors.destroy', id), {
    preserveScroll: true,
    onSuccess: (page) => {
      authors.value = page.props.authors.data.map(a => ({ 
        ...a, 
        editing: false, 
        tempName: a.name, 
        tempBio: a.bio,
        newPhoto: null,
        originalPhoto: a.photo
      }))
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
    if (pageNumber < 1 || pageNumber > page.props.authors.last_page) return
    
    router.get(route('authors.index', { 
        page: pageNumber
    }), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['authors', 'flash']
    })
}

// Go to specific page from input
const goToSpecificPage = () => {
    let pageNumber = parseInt(pageForm.page)
    
    if (isNaN(pageNumber) || pageNumber < 1) {
        pageNumber = 1
    }
    
    if (pageNumber > page.props.authors.last_page) {
        pageNumber = page.props.authors.last_page
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
watch(() => page.props.authors.current_page, (newPage) => {
    pageForm.page = newPage
})

// Generate page numbers for pagination
const pageNumbers = computed(() => {
    if (!page.props.authors.links) return []
    
    const current = page.props.authors.current_page
    const last = page.props.authors.last_page
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
  <Head title="Authors Management" />
  
  <AuthenticatedLayout>
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Authors Management</h1>
          <p class="text-gray-600 mt-2">Manage all book authors in the system</p>
        </div>
        <button 
          @click="showModal = true"
          class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition flex items-center space-x-2 shadow-sm"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          <span>Add New Author</span>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-600">Total Authors</h3>
              <p class="text-2xl font-bold text-gray-900">{{ page.props.authors.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
          <div class="flex items-center">
            <div class="p-3 bg-green-50 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-600">Active Authors</h3>
              <p class="text-2xl font-bold text-gray-900">{{ page.props.authors.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
          <div class="flex items-center">
            <div class="p-3 bg-purple-50 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-600">With Photos</h3>
              <p class="text-2xl font-bold text-gray-900">{{ authors.filter(a => a.photo).length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Authors Table -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bio</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="author in authors" 
                :key="author.id" 
                class="hover:bg-gray-50 transition-colors"
              >
                <!-- Author Info -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                      <span class="text-indigo-600 font-semibold text-sm">
                        {{ author.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">ID: {{ author.id }}</div>
                    </div>
                  </div>
                </td>
                
                <!-- Name -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="!author.editing" class="text-sm text-gray-900">{{ author.name }}</div>
                  <input 
                    v-else 
                    v-model="author.name" 
                    type="text" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    @keyup.enter="updateAuthor(author)"
                    @keyup.escape="cancelEditing(author)"
                  />
                </td>
                
                <!-- Bio -->
                <td class="px-6 py-4">
                  <div v-if="!author.editing" class="max-w-xs">
                    <div class="text-sm text-gray-700 whitespace-pre-wrap break-words max-h-32 overflow-y-auto">
                      {{ author.bio }}
                    </div>
                  </div>
                  <textarea 
                    v-else 
                    v-model="author.bio" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    rows="3"
                    @keyup.enter="updateAuthor(author)"
                    @keyup.escape="cancelEditing(author)"
                  ></textarea>
                </td>
                
                <!-- Photo -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="!author.editing" class="flex justify-center">
                    <img v-if="author.photo" :src="author.photo" class="w-16 h-16 object-cover rounded-lg border border-gray-200" />
                    <div v-else class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center border border-gray-200">
                      <span class="text-gray-400 text-xs">No Photo</span>
                    </div>
                  </div>
                  <div v-else class="space-y-2">
                    <img v-if="author.photo" :src="author.photo" class="w-16 h-16 object-cover rounded-lg border border-gray-200 mx-auto" />
                    <input 
                      type="file" 
                      @change="handlePhotoChange(author, $event)" 
                      class="w-full text-sm p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                      accept="image/*"
                    />
                    <p v-if="author.newPhoto" class="text-xs text-green-600 text-center">New photo: {{ author.newPhoto.name }}</p>
                    <p v-else class="text-xs text-gray-500 text-center">Choose new photo (optional)</p>
                  </div>
                </td>
                
                <!-- Actions -->
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <!-- Edit Button -->
                    <button 
                      v-if="!author.editing"
                      @click="startEditing(author)"
                      class="bg-blue-100 text-blue-700 text-xs px-3 py-2 rounded hover:bg-blue-200 transition font-medium"
                    >
                      Edit
                    </button>
                    
                    <!-- Save/Cancel Buttons -->
                    <div v-else class="flex space-x-2">
                      <button
                        @click="updateAuthor(author)"
                        :disabled="processingAuthorId === author.id"
                        class="bg-green-100 text-green-700 text-xs px-3 py-2 rounded hover:bg-green-200 disabled:opacity-50 transition font-medium"
                      >
                        {{ processingAuthorId === author.id ? 'Saving...' : 'Save' }}
                      </button>
                      <button
                        @click="cancelEditing(author)"
                        :disabled="processingAuthorId === author.id"
                        class="bg-gray-100 text-gray-700 text-xs px-3 py-2 rounded hover:bg-gray-200 disabled:opacity-50 transition font-medium"
                      >
                        Cancel
                      </button>
                    </div>

                    <!-- Delete Button -->
                    <button
                      @click="deleteAuthor(author.id)"
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
        <div v-if="authors.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No authors found</h3>
          <p class="mt-1 text-sm text-gray-500">
            Get started by creating your first author.
          </p>
          <div class="mt-6">
            <button 
              @click="showModal = true"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Add New Author
            </button>
          </div>
        </div>

        <!-- DYNAMIC PAGINATION WITH PAGE INPUT -->
        <div v-if="page.props.authors.data && page.props.authors.links" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div class="flex-1 flex justify-between items-center sm:hidden">
            <Link 
              :href="page.props.authors.links.prev" 
              :class="{'opacity-50 cursor-not-allowed': !page.props.authors.links.prev}"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              preserve-scroll
            >
              Previous
            </Link>
            <div class="text-sm text-gray-700">
              Page <span class="font-medium">{{ page.props.authors.current_page }}</span> of <span class="font-medium">{{ page.props.authors.last_page }}</span>
            </div>
            <Link 
              :href="page.props.authors.links.next" 
              :class="{'opacity-50 cursor-not-allowed': !page.props.authors.links.next}"
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
                <span class="font-medium">{{ page.props.authors.from }}</span>
                to
                <span class="font-medium">{{ page.props.authors.to }}</span>
                of
                <span class="font-medium">{{ page.props.authors.total }}</span>
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
                    :max="page.props.authors.last_page"
                    class="w-16 px-2 py-1 border border-gray-300 rounded-md text-sm text-center focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    @keypress="handlePageInputKeypress"
                    @blur="goToSpecificPage"
                  >
                  <span class="absolute right-2 top-1 text-xs text-gray-500">/{{ page.props.authors.last_page }}</span>
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
                  :href="page.props.authors.links.prev" 
                  :class="{'opacity-50 cursor-not-allowed pointer-events-none': !page.props.authors.links.prev}"
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
                    :href="route('authors.index', { page: pageNumber })"
                    :class="{
                      'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': pageNumber === page.props.authors.current_page,
                      'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': pageNumber !== page.props.authors.current_page
                    }"
                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                    preserve-scroll
                  >
                    {{ pageNumber }}
                  </Link>
                </template>

                <!-- Next Page Link -->
                <Link 
                  :href="page.props.authors.links.next" 
                  :class="{'opacity-50 cursor-not-allowed pointer-events-none': !page.props.authors.links.next}"
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

    <!-- Create Author Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Create New Author</h3>
        </div>
        
        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-4" enctype="multipart/form-data">
          <div class="px-6 py-4 space-y-4">
            <!-- Name -->
            <div>
              <label for="author-name" class="block text-sm font-medium text-gray-700 mb-1">
                Author Name
              </label>
              <input 
                id="author-name"
                v-model="form.name" 
                type="text" 
                placeholder="Enter author name"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                :class="{ 'border-red-500': form.errors.name }"
              />
              <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
            </div>

            <!-- Bio -->
            <div>
              <label for="author-bio" class="block text-sm font-medium text-gray-700 mb-1">
                Author Bio
              </label>
              <textarea 
                id="author-bio"
                v-model="form.bio" 
                placeholder="Enter author bio"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                :class="{ 'border-red-500': form.errors.bio }"
              ></textarea>
              <p v-if="form.errors.bio" class="text-red-500 text-xs mt-1">{{ form.errors.bio }}</p>
            </div>

            <!-- Photo -->
            <div>
              <label for="author-photo" class="block text-sm font-medium text-gray-700 mb-1">
                Author Photo
              </label>
              <input 
                id="author-photo"
                type="file" 
                @change="e => form.photo = e.target.files[0]" 
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                accept="image/*"
              />
              <p v-if="form.errors.photo" class="text-red-500 text-xs mt-1">{{ form.errors.photo }}</p>
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
              <span v-else>Create Author</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
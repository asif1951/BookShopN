<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted } from 'vue'

// Modal controls
const showModal = ref(false)
const showDetailsModal = ref(false)
const editingBook = ref(null)
const selectedBook = ref(null)
const processing = ref(false)
const showSuccessMessage = ref(false)
const successMessage = ref('')

// Filter controls
const filters = ref({
    search: '',
    sort_by: 'created_at',
    sort_order: 'desc'
})

// Book form
const form = useForm({
    title: '', 
    author: '', 
    category: '', 
    publisher: '', 
    description: '', 
    price: '', 
    stock: '', 
    photo: null,
})

// Page input form
const pageForm = useForm({
    page: 1
})

// Get Inertia props
const page = usePage()

// Get books from page props
const books = computed(() => {
    const booksData = page.props.books
    if (!booksData) return []
    
    // Handle paginated data
    if (booksData.data) {
        return booksData.data || []
    }
    
    return Array.isArray(booksData) ? booksData : []
})

// Get dropdown data from page props
const authors = computed(() => {
    return page.props.dropdowns?.authors || []
})

const categories = computed(() => {
    return page.props.dropdowns?.categories || []
})

const publishers = computed(() => {
    return page.props.dropdowns?.publishers || []
})

// Get pagination data safely
const paginationData = computed(() => {
    const booksData = page.props.books || {}
    return {
        current_page: booksData.current_page || 1,
        last_page: booksData.last_page || 1,
        from: booksData.from || 0,
        to: booksData.to || 0,
        total: booksData.total || books.value.length,
        links: booksData.links || [],
        prev_page_url: booksData.prev_page_url || null,
        next_page_url: booksData.next_page_url || null,
        per_page: booksData.per_page || 10
    }
})

// Statistics
const statistics = computed(() => {
    const booksData = books.value
    const total = paginationData.value.total
    
    // Calculate average price safely
    let averagePrice = 0
    if (booksData.length > 0) {
        const totalPrice = booksData.reduce((sum, book) => {
            return sum + parseFloat(book.price || 0)
        }, 0)
        averagePrice = totalPrice / booksData.length
    }
    
    return {
        total: total,
        inStock: booksData.filter(b => b.stock > 0).length,
        averagePrice: averagePrice.toFixed(2),
        withPhotos: booksData.filter(b => b.photo).length
    }
})

// Initialize
onMounted(() => {
    pageForm.page = paginationData.value.current_page
    
    if (page.props.filters) {
        filters.value = {
            search: page.props.filters.search || '',
            sort_by: page.props.filters.sort_by || 'created_at',
            sort_order: page.props.filters.sort_order || 'desc'
        }
    }
    
    // Flash message
    if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success
        showSuccessMessage.value = true
        setTimeout(() => {
            showSuccessMessage.value = false
            successMessage.value = ''
        }, 3000)
    }
})

// Apply filters function
const applyFilters = () => {
    const filterParams = {
        ...filters.value,
        page: 1 // Reset to first page when filtering
    }
    
    router.get(route('books.index'), filterParams, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['books', 'flash', 'filters']
    })
}

// Reset filters
const resetFilters = () => {
    filters.value = {
        search: '',
        sort_by: 'created_at',
        sort_order: 'desc'
    }
    applyFilters()
}

// Watch for filter changes with debounce
let filterTimeout = null
watch(filters, () => {
    if (filterTimeout) clearTimeout(filterTimeout)
    filterTimeout = setTimeout(() => {
        applyFilters()
    }, 500)
}, { deep: true })

// Sort table header
const sortTable = (column) => {
    if (filters.value.sort_by === column) {
        // Toggle sort order
        filters.value.sort_order = filters.value.sort_order === 'asc' ? 'desc' : 'asc'
    } else {
        // New column, default to ascending
        filters.value.sort_by = column
        filters.value.sort_order = 'asc'
    }
    // Filter will auto-apply via watcher
}

const getPhotoUrl = (photoPath) => {
    if (!photoPath) return null
    if (photoPath.startsWith('http')) return photoPath
    if (photoPath.startsWith('storage/')) return `/${photoPath}`
    return `/storage/${photoPath}`
}

const handlePhoto = (e) => { 
    form.photo = e.target.files[0] 
}

const showSuccess = (msg) => { 
    successMessage.value = msg; 
    showSuccessMessage.value = true; 
    setTimeout(() => showSuccessMessage.value = false, 3000) 
}

// Submit new book
const saveBook = () => {
    processing.value = true
    const data = new FormData()
    data.append('title', form.title)
    data.append('author', form.author)
    data.append('category', form.category)
    data.append('publisher', form.publisher)
    data.append('description', form.description)
    data.append('price', form.price)
    data.append('stock', form.stock)
    if (form.photo) {
        data.append('photo', form.photo)
    }

    form.post(route('books.store'), {
        data: data,
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            resetForm()
            showSuccess('Book created successfully!')
        },
        onError: (errors) => {
            console.error('Error creating book:', errors)
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

// Update book
const updateBook = () => {
    processing.value = true
    const data = new FormData()
    data.append('title', form.title)
    data.append('author', form.author)
    data.append('category', form.category)
    data.append('publisher', form.publisher)
    data.append('description', form.description)
    data.append('price', form.price)
    data.append('stock', form.stock)
    if (form.photo) {
        data.append('photo', form.photo)
    }
    data.append('_method', 'PUT')

    router.post(route('books.update', editingBook.value.id), data, {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            resetForm()
            editingBook.value = null
            showSuccess('Book updated successfully!')
        },
        onError: (errors) => {
            console.error('Error updating book:', errors)
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

const deleteBook = (book) => {
    if (!confirm('Are you sure you want to delete this book?')) return
    
    router.delete(route('books.destroy', book.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('Book deleted successfully!')
        },
        onError: (errors) => {
            console.error('Error deleting book:', errors)
        }
    })
}

const viewDetails = (book) => { 
    selectedBook.value = book; 
    showDetailsModal.value = true 
}

const editBook = (book) => {
    editingBook.value = book
    form.title = book.title
    form.author = book.author
    form.category = book.category
    form.publisher = book.publisher
    form.description = book.description
    form.price = book.price
    form.stock = book.stock
    form.photo = null
    showModal.value = true
}

const closeModal = () => { 
    showModal.value = false; 
    editingBook.value = null; 
    resetForm(); 
    processing.value = false 
}

const resetForm = () => { 
    form.reset()
}

// Dynamic pagination function
const goToPage = (pageNumber) => {
    if (pageNumber < 1 || pageNumber > paginationData.value.last_page) return
    
    const params = {
        page: pageNumber,
        ...filters.value
    }
    
    router.get(route('books.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['books', 'flash']
    })
}

// Go to specific page from input
const goToSpecificPage = () => {
    let pageNumber = parseInt(pageForm.page)
    
    if (isNaN(pageNumber) || pageNumber < 1) {
        pageNumber = 1
    }
    
    if (pageNumber > paginationData.value.last_page) {
        pageNumber = paginationData.value.last_page
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
watch(() => paginationData.value.current_page, (newPage) => {
    pageForm.page = newPage
})

// Generate page numbers for pagination
const pageNumbers = computed(() => {
    const current = paginationData.value.current_page
    const last = paginationData.value.last_page
    
    if (last <= 1) return []
    
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
    <Head title="Books Management" />
    
    <AuthenticatedLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Books Management</h1>
                    <p class="text-gray-600 mt-2">Manage all books in the system</p>
                </div>
                <button 
                    @click="showModal = true"
                    class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition flex items-center space-x-2 shadow-sm"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add New Book</span>
                </button>
            </div>

            <!-- Flash Messages -->
            <div v-if="showSuccessMessage" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-green-800 font-medium">{{ successMessage }}</span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-600">Total Books</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ statistics.total }}</p>
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
                            <h3 class="text-sm font-medium text-gray-600">In Stock</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ statistics.inStock }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-50 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-600">Average Price</h3>
                            <p class="text-2xl font-bold text-gray-900">${{ statistics.averagePrice }}</p>
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
                            <p class="text-2xl font-bold text-gray-900">{{ statistics.withPhotos }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="w-full">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search books by title, author, or description..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                        />
                    </div>
                    
                    <div class="flex gap-2">
                        <button
                            v-if="filters.search"
                            @click="resetFilters" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                        >
                            Clear Search
                        </button>
                    </div>
                </div>
                
                <!-- Results Count -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600">
                            Showing {{ paginationData.from || 1 }} to {{ paginationData.to || books.length }} of {{ paginationData.total }} books
                            <span v-if="filters.search" class="text-blue-600 font-medium">
                                for "{{ filters.search }}"
                            </span>
                        </p>
                        
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500">
                                Click headers to sort
                            </span>
                            <span class="text-xs text-gray-400">|</span>
                            <span class="text-xs text-gray-500">
                                {{ paginationData.per_page }} per page
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Books Table - WITHOUT horizontal scrolling -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <!-- Responsive table wrapper -->
                <div class="w-full">
                    <table class="w-full divide-y divide-gray-200 table-fixed">
                        <thead class="bg-gray-50">
                            <tr>
                                <!-- Photo - Fixed width -->
                                <th class="w-24 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                
                                <!-- Title - Flexible width -->
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    @click="sortTable('title')">
                                    Title
                                    <span v-if="filters.sort_by === 'title'" class="ml-1">
                                        {{ filters.sort_order === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                
                                <!-- Author - Fixed width -->
                                <th class="w-32 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    @click="sortTable('author')">
                                    Author
                                    <span v-if="filters.sort_by === 'author'" class="ml-1">
                                        {{ filters.sort_order === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                
                                <!-- Category - Fixed width -->
                                <th class="w-32 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    @click="sortTable('category')">
                                    Category
                                    <span v-if="filters.sort_by === 'category'" class="ml-1">
                                        {{ filters.sort_order === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                
                                <!-- Description - Flexible width -->
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <span>Description</span>
                                        <span class="ml-1 text-xs text-gray-400">(Truncated)</span>
                                    </div>
                                </th>
                                
                                <!-- Price - Fixed width -->
                                <th class="w-24 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    @click="sortTable('price')">
                                    Price
                                    <span v-if="filters.sort_by === 'price'" class="ml-1">
                                        {{ filters.sort_order === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                
                                <!-- Stock - Fixed width -->
                                <th class="w-20 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    @click="sortTable('stock')">
                                    Stock
                                    <span v-if="filters.sort_by === 'stock'" class="ml-1">
                                        {{ filters.sort_order === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                
                                <!-- Actions - Fixed width -->
                                <th class="w-40 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="book in books" :key="book.id" class="hover:bg-gray-50 transition-colors">
                                <!-- Photo -->
                                <td class="px-4 py-3">
                                    <div class="flex justify-center">
                                        <img
                                            v-if="book.photo"
                                            :src="getPhotoUrl(book.photo)"
                                            alt="Book Photo"
                                            class="w-16 h-16 object-cover rounded-lg border border-gray-200"
                                        />
                                        <div v-else class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center border border-gray-200">
                                            <span class="text-gray-400 text-xs">No Image</span>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Title -->
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 truncate" :title="book.title">
                                        {{ book.title }}
                                    </div>
                                </td>
                                
                                <!-- Author -->
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900 truncate" :title="book.author">
                                        {{ book.author }}
                                    </div>
                                </td>
                                
                                <!-- Category -->
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900 truncate" :title="book.category">
                                        {{ book.category }}
                                    </div>
                                </td>
                                
                                <!-- Description -->
                                <td class="px-4 py-3">
                                    <div class="max-w-xs">
                                        <div class="text-sm text-gray-700 truncate" :title="book.description || 'No description available'">
                                            {{ book.description ? (book.description.length > 50 ? book.description.substring(0, 50) + '...' : book.description) : 'No description' }}
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Price -->
                                <td class="px-4 py-3">
                                    <span class="text-sm font-semibold text-gray-900">${{ book.price }}</span>
                                </td>
                                
                                <!-- Stock -->
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="book.stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ book.stock }}
                                    </span>
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <button
                                            @click="viewDetails(book)"
                                            class="bg-blue-100 text-blue-700 text-xs px-2 py-1.5 rounded hover:bg-blue-200 transition font-medium flex items-center"
                                            title="View Details"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Details
                                        </button>
                                        <button
                                            @click="editBook(book)"
                                            class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1.5 rounded hover:bg-yellow-200 transition font-medium flex items-center"
                                            title="Edit Book"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteBook(book)"
                                            class="bg-red-100 text-red-700 text-xs px-2 py-1.5 rounded hover:bg-red-200 transition font-medium flex items-center"
                                            title="Delete Book"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="books.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No books found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        <span v-if="filters.search">
                            for "{{ filters.search }}"
                        </span>
                        <span v-else>
                            Create your first book!
                        </span>
                    </p>
                    <div class="mt-6">
                        <button 
                            @click="showModal = true"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add New Book
                        </button>
                        <button 
                            v-if="filters.search"
                            @click="resetFilters" 
                            class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Clear Search
                        </button>
                    </div>
                </div>

                <!-- PAGINATION -->
                <div v-if="paginationData.total > paginationData.per_page" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <!-- Mobile View -->
                    <div class="flex-1 flex justify-between items-center sm:hidden">
                        <button 
                            v-if="paginationData.prev_page_url"
                            @click="goToPage(paginationData.current_page - 1)"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md bg-white hover:bg-gray-50 transition text-gray-700"
                        >
                            Previous
                        </button>
                        <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md bg-gray-100 text-gray-400 cursor-not-allowed">
                            Previous
                        </span>
                        
                        <div class="text-sm text-gray-700">
                            Page <span class="font-medium">{{ paginationData.current_page }}</span> of <span class="font-medium">{{ paginationData.last_page }}</span>
                        </div>
                        
                        <button 
                            v-if="paginationData.next_page_url"
                            @click="goToPage(paginationData.current_page + 1)"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md bg-white hover:bg-gray-50 transition text-gray-700"
                        >
                            Next
                        </button>
                        <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md bg-gray-100 text-gray-400 cursor-not-allowed">
                            Next
                        </span>
                    </div>
                    
                    <!-- Desktop View -->
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing 
                                <span class="font-medium">{{ paginationData.from || 1 }}</span> 
                                to 
                                <span class="font-medium">{{ paginationData.to || books.length }}</span> 
                                of 
                                <span class="font-medium">{{ paginationData.total }}</span> 
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
                                        :max="paginationData.last_page"
                                        class="w-16 px-2 py-1 border border-gray-300 rounded-md text-sm text-center focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                        @keypress="handlePageInputKeypress"
                                        @blur="goToSpecificPage"
                                    >
                                    <span class="absolute right-2 top-1 text-xs text-gray-500">/{{ paginationData.last_page }}</span>
                                </div>
                                <button
                                    @click="goToSpecificPage"
                                    class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition"
                                >
                                    Go
                                </button>
                            </div>
                            
                            <!-- Pagination Numbers -->
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <!-- Previous Button -->
                                <button 
                                    v-if="paginationData.prev_page_url"
                                    @click="goToPage(paginationData.current_page - 1)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition"
                                >
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                
                                <!-- Page Numbers -->
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
                                            'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': pageNumber === paginationData.current_page,
                                            'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': pageNumber !== paginationData.current_page
                                        }"
                                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium transition"
                                    >
                                        {{ pageNumber }}
                                    </button>
                                </template>
                                
                                <!-- Next Button -->
                                <button 
                                    v-if="paginationData.next_page_url"
                                    @click="goToPage(paginationData.current_page + 1)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition"
                                >
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </nav>
                        </div>
                    </div>
                </div>
                <!-- END PAGINATION -->
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">{{ editingBook ? 'Edit Book' : 'Create New Book' }}</h3>
                </div>
                
                <!-- Form -->
                <form @submit.prevent="editingBook ? updateBook() : saveBook()" class="space-y-4" enctype="multipart/form-data">
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                                <input v-model="form.title" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
                            </div>
                            
                            <!-- Authors Dropdown -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Author *</label>
                                <select v-model="form.author" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" required>
                                    <option value="">Select an author</option>
                                    <option v-for="author in authors" :key="author.id" :value="author.name">
                                        {{ author.name }}
                                    </option>
                                </select>
                                <p v-if="authors.length === 0" class="text-xs text-red-500 mt-1">No authors found</p>
                            </div>

                            <!-- Categories Dropdown -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select v-model="form.category" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                    <option value="">Select a category</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.name">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Publishers Dropdown -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                                <select v-model="form.publisher" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                    <option value="">Select a publisher</option>
                                    <option v-for="publisher in publishers" :key="publisher.id" :value="publisher.name">
                                        {{ publisher.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea v-model="form.description" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" rows="3"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                                <input v-model="form.price" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                                <input v-model="form.stock" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                                <input @change="handlePhoto" type="file" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" accept="image/*">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
                        <button 
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            :disabled="processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition"
                        >
                            <span v-if="processing">Processing...</span>
                            <span v-else>{{ editingBook ? 'Update' : 'Save' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Details Modal -->
        <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Book Details</h3>
                </div>
                
                <div class="px-6 py-4" v-if="selectedBook">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Photo -->
                        <div class="md:col-span-1">
                            <div class="bg-gray-100 rounded-lg p-4 flex items-center justify-center h-64">
                                <img v-if="selectedBook.photo" :src="getPhotoUrl(selectedBook.photo)" :alt="selectedBook.title"
                                     class="max-h-56 max-w-full object-contain">
                                <div v-else class="text-gray-400 text-center">
                                    <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-sm">No Photo</p>
                                </div>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="md:col-span-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Title:</label>
                                    <p class="mt-1 text-gray-900 font-medium">{{ selectedBook.title }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Author:</label>
                                    <p class="mt-1 text-gray-900">{{ selectedBook.author }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Category:</label>
                                    <p class="mt-1 text-gray-900">{{ selectedBook.category }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Publisher:</label>
                                    <p class="mt-1 text-gray-900">{{ selectedBook.publisher }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Price:</label>
                                    <p class="mt-1 text-gray-900 font-semibold">${{ selectedBook.price }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Stock:</label>
                                    <p class="mt-1">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="selectedBook.stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ selectedBook.stock }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Description:</label>
                                <div class="mt-1 bg-gray-50 p-3 rounded-lg max-h-40 overflow-y-auto">
                                    <p class="text-gray-900 whitespace-pre-wrap break-words">{{ selectedBook.description || 'No description available' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end">
                        <button 
                            @click="showDetailsModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
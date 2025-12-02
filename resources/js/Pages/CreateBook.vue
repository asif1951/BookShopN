<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import { ref, onMounted, watch } from 'vue'

// Modal controls
const showModal = ref(false)
const showDetailsModal = ref(false)
const editingBook = ref(null)
const selectedBook = ref(null)
const books = ref([])
const authors = ref([])
const categories = ref([])
const publishers = ref([])
const loading = ref(true)
const authorsLoading = ref(false)
const categoriesLoading = ref(false)
const publishersLoading = ref(false)
const processing = ref(false)
const showSuccessMessage = ref(false)
const successMessage = ref('')

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

// Get Inertia props
const page = usePage()

// Initialize books from backend
const initBooks = () => {
    books.value = (page.props.books || []).map(b => ({
        ...b,
        editing: false,
    }))
}

// Watch for prop changes (after create/delete/update)
watch(() => page.props.books, initBooks)

// ✅ Authors fetch করার function - FIXED
const fetchAuthors = async () => {
    authorsLoading.value = true
    try {
        console.log('Fetching authors from API...')
        
        // ✅ /api/authors use করবে
        const response = await fetch('/api/authors')
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        console.log('Authors API response:', data)
        
        authors.value = data
        
    } catch (error) {
        console.error('Error fetching authors from API:', error)
        // Fallback data
        authors.value = [
            { id: 1, name: 'Humayun Ahmed' },
            { id: 2, name: 'Jhumpa Lahiri' },
            { id: 3, name: 'Rabindranath Tagore' }
        ]
    } finally {
        authorsLoading.value = false
    }
}

// ✅ Categories fetch করার function - FIXED
const fetchCategories = async () => {
    categoriesLoading.value = true
    try {
        console.log('Fetching categories from API...')
        
        const response = await fetch('/api/categories')
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        console.log('Categories API response:', data)
        
        categories.value = data
        
    } catch (error) {
        console.error('Error fetching categories from API:', error)
        // Fallback data
        categories.value = [
            { id: 1, name: 'Fiction' },
            { id: 2, name: 'Science Fiction' },
            { id: 3, name: 'Mystery' }
        ]
    } finally {
        categoriesLoading.value = false
    }
}

// ✅ Publishers fetch করার function - FIXED
const fetchPublishers = async () => {
    publishersLoading.value = true
    try {
        console.log('Fetching publishers from API...')
        
        const response = await fetch('/api/publishers')
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        console.log('Publishers API response:', data)
        
        publishers.value = data
        
    } catch (error) {
        console.error('Error fetching publishers from API:', error)
        // Fallback data
        publishers.value = [
            { id: 1, name: 'Bangla Academy' },
            { id: 2, name: 'Mowla Brothers' },
            { id: 3, name: 'Somoy Prokashan' }
        ]
    } finally {
        publishersLoading.value = false
    }
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

    router.post(`/books/${editingBook.value.id}`, data, {
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

// Initialize on component mount
onMounted(() => {
    initBooks()
    fetchAuthors()
    fetchCategories()
    fetchPublishers()
    loading.value = false
})
</script>

<template>
    <Head title="Books Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Books Management</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto space-y-4">
            <!-- Success Message -->
            <div
                v-if="showSuccessMessage"
                class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded text-center"
            >
                {{ successMessage }}
            </div>

            <!-- Create Button -->
            <div class="text-center">
                <button
                    @click="showModal = true"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700"
                >
                    + Create Book
                </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="text-center py-8">
                <p class="text-gray-600">Loading books...</p>
            </div>

            <!-- Books Table -->
            <div v-else class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Books List</h3>
                <table class="min-w-full border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 border">Photo</th>
                            <th class="px-4 py-2 border">Title</th>
                            <th class="px-4 py-2 border">Author</th>
                            <th class="px-4 py-2 border">Category</th>
                            <th class="px-4 py-2 border">Publisher</th>
                            <th class="px-4 py-2 border">Description</th>
                            <th class="px-4 py-2 border">Price</th>
                            <th class="px-4 py-2 border">Stock</th>
                            <th class="px-4 py-2 border text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="book in books" :key="book.id" class="hover:bg-gray-50">
                            <td class="border px-4 py-2 text-center">
                                <img
                                    v-if="book.photo"
                                    :src="getPhotoUrl(book.photo)"
                                    alt="Book Photo"
                                    class="w-16 h-16 object-cover mx-auto rounded"
                                />
                                <span v-else class="text-gray-400">No Image</span>
                            </td>
                            <td class="border px-4 py-2">
                                <div class="font-medium text-gray-900">{{ book.title }}</div>
                            </td>
                            <td class="border px-4 py-2">{{ book.author }}</td>
                            <td class="border px-4 py-2">{{ book.category }}</td>
                            <td class="border px-4 py-2">{{ book.publisher }}</td>
                            <td class="border px-4 py-2 max-w-xs">
                                <div class="text-sm text-gray-700 whitespace-pre-wrap break-words max-h-32 overflow-y-auto">
                                    {{ book.description || 'No description available' }}
                                </div>
                            </td>
                            <td class="border px-4 py-2">${{ book.price }}</td>
                            <td class="border px-4 py-2">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="book.stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                    {{ book.stock }}
                                </span>
                            </td>
                            <td class="border px-4 py-2 text-center">
                                <div class="flex justify-center space-x-2">
                                    <button
                                        @click="viewDetails(book)"
                                        class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                                    >
                                        Details
                                    </button>
                                    <button
                                        @click="editBook(book)"
                                        class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteBook(book)"
                                        class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="books.length === 0" class="text-center py-12">
                    <p class="text-gray-500">No books found. Create your first book!</p>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <div v-if="showModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50 px-4">
                <div class="bg-white w-full max-w-2xl rounded-xl p-6 relative shadow-xl">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ editingBook ? 'Edit Book' : 'Create New Book' }}</h2>
                    <form @submit.prevent="editingBook ? updateBook() : saveBook()" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-1">Title *</label>
                                <input v-model="form.title" type="text" class="w-full border rounded-lg px-3 py-2" required>
                            </div>
                            
                            <!-- Authors Dropdown -->
                            <div>
                                <label class="block text-gray-700 font-semibold mb-1">Author *</label>
                                <select v-model="form.author" class="w-full border rounded-lg px-3 py-2" required>
                                    <option value="">Select an author</option>
                                    <option v-for="author in authors" :key="author.id" :value="author.name">
                                        {{ author.name }}
                                    </option>
                                </select>
                                <p v-if="authorsLoading" class="text-xs text-gray-500 mt-1">Loading authors...</p>
                                <p v-if="authors.length === 0 && !authorsLoading" class="text-xs text-red-500 mt-1">No authors found</p>
                            </div>

                            <!-- Categories Dropdown -->
                            <div>
                                <label class="block text-gray-700 font-semibold mb-1">Category</label>
                                <select v-model="form.category" class="w-full border rounded-lg px-3 py-2">
                                    <option value="">Select a category</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.name">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <p v-if="categoriesLoading" class="text-xs text-gray-500 mt-1">Loading categories...</p>
                                <p v-if="categories.length === 0 && !categoriesLoading" class="text-xs text-red-500 mt-1">No categories found</p>
                            </div>

                            <!-- Publishers Dropdown -->
                            <div>
                                <label class="block text-gray-700 font-semibold mb-1">Publisher</label>
                                <select v-model="form.publisher" class="w-full border rounded-lg px-3 py-2">
                                    <option value="">Select a publisher</option>
                                    <option v-for="publisher in publishers" :key="publisher.id" :value="publisher.name">
                                        {{ publisher.name }}
                                    </option>
                                </select>
                                <p v-if="publishersLoading" class="text-xs text-gray-500 mt-1">Loading publishers...</p>
                                <p v-if="publishers.length === 0 && !publishersLoading" class="text-xs text-red-500 mt-1">No publishers found</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-1">Description</label>
                                <textarea v-model="form.description" class="w-full border rounded-lg px-3 py-2" rows="3"></textarea>
                            </div>

                            <div>
                                <label class="block text-gray-700 font-semibold mb-1">Price</label>
                                <input v-model="form.price" type="number" class="w-full border rounded-lg px-3 py-2">
                            </div>

                            <div>
                                <label class="block text-gray-700 font-semibold mb-1">Stock</label>
                                <input v-model="form.stock" type="number" class="w-full border rounded-lg px-3 py-2">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-1">Photo</label>
                                <input @change="handlePhoto" type="file" class="w-full border rounded-lg px-3 py-2">
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="closeModal" class="px-6 py-2 border rounded-lg font-semibold hover:bg-gray-100">Cancel</button>
                            <button type="submit" :disabled="processing"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span v-if="processing">Processing...</span>
                                <span v-else>{{ editingBook ? 'Update' : 'Save' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Details Modal -->
            <!-- Details Modal -->
<div v-if="showDetailsModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50 px-4">
    <div class="bg-white w-full max-w-4xl rounded-xl p-6 relative shadow-xl overflow-y-auto max-h-[90vh]">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Book Details</h2>
        <div class="space-y-4" v-if="selectedBook">
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
                            <label class="block text-gray-700 font-semibold">Title:</label>
                            <p class="text-gray-900 font-medium">{{ selectedBook.title }}</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Author:</label>
                            <p class="text-gray-900">{{ selectedBook.author }}</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Category:</label>
                            <p class="text-gray-900">{{ selectedBook.category }}</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Publisher:</label>
                            <p class="text-gray-900">{{ selectedBook.publisher }}</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Price:</label>
                            <p class="text-gray-900 font-semibold">${{ selectedBook.price }}</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Stock:</label>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                  :class="selectedBook.stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                {{ selectedBook.stock }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-gray-700 font-semibold">Description:</label>
                        <div class="mt-1 bg-gray-50 p-3 rounded-lg max-h-40 overflow-y-auto">
                            <p class="text-gray-900 whitespace-pre-wrap break-words">{{ selectedBook.description || 'No description available' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button @click="showDetailsModal = false" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">Close</button>
            </div>
        </div>
    </div>
</div>



        </div>
    </AuthenticatedLayout>
</template>
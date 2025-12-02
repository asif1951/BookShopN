<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted } from 'vue'
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Define props
const props = defineProps({
    users: {
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

// Search input ref for focus
const searchInput = ref(null)

// Page input form
const pageForm = useForm({
    page: props.users.current_page || 1
})

// Reactive state
const state = reactive({
    showCreateModal: false,
    showEditModal: false,
    selectedUser: null,
    loading: false
})

// Search form - BACKEND SEARCH
const searchForm = useForm({
    search: props.filters?.search || ''
})

// Watch search input and debounce
let searchTimeout = null
watch(() => searchForm.search, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout)
    
    searchTimeout = setTimeout(() => {
        searchForm.get(route('users.index'), {
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
    searchForm.get(route('users.index'), {
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

// Forms
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user'
})

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user'
})

// Computed
const currentUser = computed(() => usePage().props.auth.user)

// Get users array (handle both paginated and simple array)
const usersArray = computed(() => {
    return props.users.data || props.users
})

// Dynamic pagination function
const goToPage = (pageNumber) => {
    if (pageNumber < 1 || pageNumber > props.users.last_page) return
    
    router.get(route('users.index', { 
        page: pageNumber,
        search: searchForm.search 
    }), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['users', 'stats', 'flash', 'filters']
    })
}

// Go to specific page from input
const goToSpecificPage = () => {
    let pageNumber = parseInt(pageForm.page)
    
    if (isNaN(pageNumber) || pageNumber < 1) {
        pageNumber = 1
    }
    
    if (pageNumber > props.users.last_page) {
        pageNumber = props.users.last_page
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
watch(() => props.users.current_page, (newPage) => {
    pageForm.page = newPage
})

// Generate page numbers for pagination
const pageNumbers = computed(() => {
    if (!props.users.links) return []
    
    const current = props.users.current_page
    const last = props.users.last_page
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

// Methods
const openCreateModal = () => {
    state.showCreateModal = true
    resetCreateForm()
}

const closeCreateModal = () => {
    state.showCreateModal = false
    resetCreateForm()
}

const openEditModal = (user) => {
    state.selectedUser = user
    editForm.name = user.name
    editForm.email = user.email
    editForm.role = user.role
    editForm.password = ''
    editForm.password_confirmation = ''
    state.showEditModal = true
}

const closeEditModal = () => {
    state.showEditModal = false
    state.selectedUser = null
    resetEditForm()
}

const resetCreateForm = () => {
    createForm.name = ''
    createForm.email = ''
    createForm.password = ''
    createForm.password_confirmation = ''
    createForm.role = 'user'
}

const resetEditForm = () => {
    editForm.name = ''
    editForm.email = ''
    editForm.password = ''
    editForm.password_confirmation = ''
    editForm.role = 'user'
}

const submitCreate = () => {
    createForm.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal()
            createForm.reset()
        }
    })
}

const submitEdit = () => {
    if (!state.selectedUser) return
    
    editForm.put(route('users.update', state.selectedUser.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal()
            editForm.reset()
        }
    })
}

const deleteUser = (user) => {
    if (confirm(`Are you sure you want to delete ${user.name}? This action cannot be undone.`)) {
        router.delete(route('users.destroy', user.id), {
            preserveScroll: true
        })
    }
}

const toggleUserRole = (user) => {
    if (user.id === currentUser.value.id) {
        alert('You cannot change your own role.')
        return
    }
    
    router.post(route('users.toggle-role', user.id), {}, {
        preserveScroll: true
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
</script>

<template>
    <Head title="Users Management" />
    
    <AuthenticatedLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Users Management</h1>
                    <p class="text-gray-600 mt-2">Manage all system users and their permissions</p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition flex items-center space-x-2 shadow-sm"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add New User</span>
                </button>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash && flash.success" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-green-800 font-medium">{{ flash.success }}</span>
                </div>
            </div>
            
            <div v-if="flash && flash.error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-red-800 font-medium">{{ flash.error }}</span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-600">Total Users</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.total || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-50 rounded-lg">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-600">Admin Users</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.admins || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-50 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-600">Regular Users</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.regular_users || 0 }}</p>
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
                                placeholder="Search users by name, email, or role..."
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
                        Showing {{ usersArray.length }} of 
                        {{ users.total || usersArray.length }} users
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

            <!-- Users Table -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr 
                                v-for="user in usersArray" 
                                :key="user.id" 
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <!-- User Info -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-indigo-600 font-semibold text-sm">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                            <div 
                                                v-if="user.is_current_user" 
                                                class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded-full inline-block mt-1"
                                            >
                                                Current User
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Email -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ user.email }}</div>
                                </td>
                                
                                <!-- Role -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-3 py-1 text-xs font-medium rounded-full capitalize"
                                        :class="{
                                            'bg-red-100 text-red-800': user.role === 'admin',
                                            'bg-blue-100 text-blue-800': user.role === 'user'
                                        }"
                                    >
                                        {{ user.role }}
                                    </span>
                                </td>
                                
                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-3 py-1 text-xs font-medium rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': user.email_verified_at,
                                            'bg-yellow-100 text-yellow-800': !user.email_verified_at
                                        }"
                                    >
                                        {{ user.email_verified_at ? 'Verified' : 'Pending' }}
                                    </span>
                                </td>
                                
                                <!-- Created Date -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <!-- Toggle Role Button -->
                                        <button 
                                            v-if="!user.is_current_user"
                                            @click="toggleUserRole(user)"
                                            class="text-xs px-3 py-2 rounded transition font-medium"
                                            :class="{
                                                'bg-yellow-100 text-yellow-700 hover:bg-yellow-200': user.role === 'admin',
                                                'bg-purple-100 text-purple-700 hover:bg-purple-200': user.role === 'user'
                                            }"
                                        >
                                            Make {{ user.role === 'admin' ? 'User' : 'Admin' }}
                                        </button>

                                        <!-- Edit Button -->
                                        <button 
                                            @click="openEditModal(user)"
                                            class="bg-blue-100 text-blue-700 text-xs px-3 py-2 rounded hover:bg-blue-200 transition font-medium"
                                        >
                                            Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <button 
                                            v-if="!user.is_current_user"
                                            @click="deleteUser(user)"
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
                <div v-if="usersArray.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ searchForm.search ? 'No users match your search criteria.' : 'Get started by creating a new user.' }}
                    </p>
                    <div class="mt-6">
                        <button 
                            @click="openCreateModal"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add New User
                        </button>
                    </div>
                </div>

                <!-- DYNAMIC PAGINATION WITH PAGE INPUT -->
                <div v-if="users.data && users.links" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between items-center sm:hidden">
                        <Link 
                            :href="users.links.prev" 
                            :class="{'opacity-50 cursor-not-allowed': !users.links.prev}"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            preserve-scroll
                        >
                            Previous
                        </Link>
                        <div class="text-sm text-gray-700">
                            Page <span class="font-medium">{{ users.current_page }}</span> of <span class="font-medium">{{ users.last_page }}</span>
                        </div>
                        <Link 
                            :href="users.links.next" 
                            :class="{'opacity-50 cursor-not-allowed': !users.links.next}"
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
                                <span class="font-medium">{{ users.from }}</span>
                                to
                                <span class="font-medium">{{ users.to }}</span>
                                of
                                <span class="font-medium">{{ users.total }}</span>
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
                                        :max="users.last_page"
                                        class="w-16 px-2 py-1 border border-gray-300 rounded-md text-sm text-center focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                        @keypress="handlePageInputKeypress"
                                        @blur="goToSpecificPage"
                                    >
                                    <span class="absolute right-2 top-1 text-xs text-gray-500">/{{ users.last_page }}</span>
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
                                    :href="users.links.prev" 
                                    :class="{'opacity-50 cursor-not-allowed pointer-events-none': !users.links.prev}"
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
                                        :href="route('users.index', { page: pageNumber, search: searchForm.search })"
                                        :class="{
                                            'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': pageNumber === users.current_page,
                                            'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': pageNumber !== users.current_page
                                        }"
                                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                        preserve-scroll
                                    >
                                        {{ pageNumber }}
                                    </Link>
                                </template>

                                <!-- Next Page Link -->
                                <Link 
                                    :href="users.links.next" 
                                    :class="{'opacity-50 cursor-not-allowed pointer-events-none': !users.links.next}"
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

        <!-- Create User Modal -->
        <div v-if="state.showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Create New User</h3>
                </div>
                
                <!-- Form -->
                <form @submit.prevent="submitCreate">
                    <div class="px-6 py-4 space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="create-name" class="block text-sm font-medium text-gray-700 mb-1">
                                Full Name
                            </label>
                            <input 
                                type="text" 
                                id="create-name"
                                v-model="createForm.name"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                placeholder="Enter full name"
                                :class="{ 'border-red-500': createForm.errors.name }"
                            >
                            <p v-if="createForm.errors.name" class="text-red-500 text-xs mt-1">{{ createForm.errors.name }}</p>
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="create-email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address
                            </label>
                            <input 
                                type="email" 
                                id="create-email"
                                v-model="createForm.email"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                placeholder="Enter email address"
                                :class="{ 'border-red-500': createForm.errors.email }"
                            >
                            <p v-if="createForm.errors.email" class="text-red-500 text-xs mt-1">{{ createForm.errors.email }}</p>
                        </div>
                        
                        <!-- Password -->
                        <div>
                            <label for="create-password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password
                            </label>
                            <input 
                                type="password" 
                                id="create-password"
                                v-model="createForm.password"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                placeholder="Enter password"
                                :class="{ 'border-red-500': createForm.errors.password }"
                            >
                            <p v-if="createForm.errors.password" class="text-red-500 text-xs mt-1">{{ createForm.errors.password }}</p>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div>
                            <label for="create-password-confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm Password
                            </label>
                            <input 
                                type="password" 
                                id="create-password-confirmation"
                                v-model="createForm.password_confirmation"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                placeholder="Confirm password"
                            >
                        </div>
                        
                        <!-- Role -->
                        <div>
                            <label for="create-role" class="block text-sm font-medium text-gray-700 mb-1">
                                Role
                            </label>
                            <select 
                                id="create-role"
                                v-model="createForm.role"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                :class="{ 'border-red-500': createForm.errors.role }"
                            >
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                            <p v-if="createForm.errors.role" class="text-red-500 text-xs mt-1">{{ createForm.errors.role }}</p>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
                        <button 
                            type="button"
                            @click="closeCreateModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition"
                        >
                            <span v-if="createForm.processing">Creating...</span>
                            <span v-else>Create User</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div v-if="state.showEditModal && state.selectedUser" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Edit User: {{ state.selectedUser.name }}
                    </h3>
                </div>
                
                <!-- Form -->
                <form @submit.prevent="submitEdit">
                    <div class="px-6 py-4 space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-1">
                                Full Name
                            </label>
                            <input 
                                type="text" 
                                id="edit-name"
                                v-model="editForm.name"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                :class="{ 'border-red-500': editForm.errors.name }"
                            >
                            <p v-if="editForm.errors.name" class="text-red-500 text-xs mt-1">{{ editForm.errors.name }}</p>
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="edit-email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address
                            </label>
                            <input 
                                type="email" 
                                id="edit-email"
                                v-model="editForm.email"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                :class="{ 'border-red-500': editForm.errors.email }"
                            >
                            <p v-if="editForm.errors.email" class="text-red-500 text-xs mt-1">{{ editForm.errors.email }}</p>
                        </div>
                        
                        <!-- Password -->
                        <div>
                            <label for="edit-password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password (leave blank to keep current)
                            </label>
                            <input 
                                type="password" 
                                id="edit-password"
                                v-model="editForm.password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                placeholder="Leave blank to keep current password"
                                :class="{ 'border-red-500': editForm.errors.password }"
                            >
                            <p v-if="editForm.errors.password" class="text-red-500 text-xs mt-1">{{ editForm.errors.password }}</p>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div>
                            <label for="edit-password-confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm Password
                            </label>
                            <input 
                                type="password" 
                                id="edit-password-confirmation"
                                v-model="editForm.password_confirmation"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                placeholder="Confirm new password"
                            >
                        </div>
                        
                        <!-- Role -->
                        <div>
                            <label for="edit-role" class="block text-sm font-medium text-gray-700 mb-1">
                                Role
                            </label>
                            <select 
                                id="edit-role"
                                v-model="editForm.role"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                :class="{ 'border-red-500': editForm.errors.role }"
                            >
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                            <p v-if="editForm.errors.role" class="text-red-500 text-xs mt-1">{{ editForm.errors.role }}</p>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
                        <button 
                            type="button"
                            @click="closeEditModal"
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
                            <span v-else>Update User</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
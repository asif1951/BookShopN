<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Get the redirect URL from query parameter
const getRedirectUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const redirectParam = urlParams.get('redirect_to');
    
    if (redirectParam) {
        return redirectParam;
    }
    
    // Check if there's a referrer (previous page)
    if (document.referrer && document.referrer !== window.location.href) {
        const referrer = new URL(document.referrer);
        // Only redirect back if it's from the same domain and not auth pages
        if (referrer.origin === window.location.origin && 
            !referrer.pathname.includes('/login') && 
            !referrer.pathname.includes('/register')) {
            return document.referrer;
        }
    }
    
    return '/dashboard'; // Default redirect URL
};

const redirectUrl = ref(getRedirectUrl());

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    redirect_to: redirectUrl.value,
});

const submit = () => {
    router.post(route('register'), {
        ...form.data(),
        redirect_to: redirectUrl.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// Function to go back to previous page
const goBack = () => {
    if (redirectUrl.value && redirectUrl.value !== '/dashboard') {
        window.location.href = redirectUrl.value;
    } else {
        window.history.back();
    }
};

// Animation state
const isVisible = ref(false);

onMounted(() => {
    // Trigger animation after component mounts
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});
</script>

<template>
    <AuthenticatedLayout>
    <Head title="Register" />

    <!-- Main Container -->
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 to-blue-100 py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-6">
            <!-- Back Button -->
            <!-- <div class="mb-4">
                <button 
                    @click="goBack"
                    class="text-sm text-green-600 hover:text-green-500 font-medium transition duration-200 transform hover:scale-105"
                >
                    ← Back to Previous Page
                </button>
            </div> -->

            <!-- Header Section with Animation -->
            <div class="text-center transform transition-all duration-700 ease-out"
                 :class="isVisible ? 'translate-y-0 opacity-100' : '-translate-y-10 opacity-0'">
                <div class="mx-auto h-16 w-16 bg-gradient-to-r from-green-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg mb-4 transform transition-all duration-1000 ease-out"
                     :class="isVisible ? 'scale-100 rotate-0' : 'scale-50 rotate-180'">
                    <svg class="h-8 w-8 text-white transform transition-all duration-1000"
                         :class="isVisible ? 'scale-100' : 'scale-0'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 transform transition-all duration-800 delay-200"
                    :class="isVisible ? 'translate-y-0 opacity-100' : '-translate-y-5 opacity-0'">
                    Create Account
                </h2>
                <p class="mt-1 text-sm text-gray-600 transform transition-all duration-800 delay-300"
                   :class="isVisible ? 'translate-y-0 opacity-100' : '-translate-y-5 opacity-0'">
                    Join us and start your journey today
                </p>
                <!-- Show where you'll be redirected -->
                <p v-if="redirectUrl && redirectUrl !== '/dashboard'" 
                   class="mt-2 text-xs text-green-500 bg-green-50 px-3 py-1 rounded-full inline-block">
                    🔄 You'll be redirected back after registration
                </p>
            </div>

            <!-- Registration Form with Animation -->
            <div 
                class="bg-white rounded-xl shadow-lg p-6 space-y-5 border border-gray-100 transform transition-all duration-700 ease-out"
                :class="isVisible ? 'translate-y-0 scale-100 opacity-100' : 'translate-y-10 scale-95 opacity-0'"
            >
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Hidden redirect field -->
                    <input type="hidden" name="redirect_to" :value="redirectUrl" />
                    
                    <!-- Name Field with Animation -->
                    <div class="space-y-2 transform transition-all duration-500 delay-100"
                         :class="isVisible ? 'translate-x-0 opacity-100' : '-translate-x-5 opacity-0'">
                        <InputLabel for="name" value="Full Name" class="text-sm font-medium text-gray-700" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full pl-9 pr-3 py-2.5 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200 transform hover:scale-[1.01]"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Enter your full name"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>

                    <!-- Email Field with Animation -->
                    <div class="space-y-2 transform transition-all duration-500 delay-200"
                         :class="isVisible ? 'translate-x-0 opacity-100' : '-translate-x-5 opacity-0'">
                        <InputLabel for="email" value="Email Address" class="text-sm font-medium text-gray-700" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full pl-9 pr-3 py-2.5 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200 transform hover:scale-[1.01]"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="Enter your email address"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <!-- Password Field with Animation -->
                    <div class="space-y-2 transform transition-all duration-500 delay-300"
                         :class="isVisible ? 'translate-x-0 opacity-100' : '-translate-x-5 opacity-0'">
                        <InputLabel for="password" value="Password" class="text-sm font-medium text-gray-700" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full pl-9 pr-3 py-2.5 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200 transform hover:scale-[1.01]"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="Create a strong password"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password Field with Animation -->
                    <div class="space-y-2 transform transition-all duration-500 delay-400"
                         :class="isVisible ? 'translate-x-0 opacity-100' : '-translate-x-5 opacity-0'">
                        <InputLabel for="password_confirmation" value="Confirm Password" class="text-sm font-medium text-gray-700" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                class="mt-1 block w-full pl-9 pr-3 py-2.5 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200 transform hover:scale-[1.01]"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Confirm your password"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.password_confirmation" />
                    </div>

                    <!-- Submit Button with Animation -->
                    <div class="transform transition-all duration-500 delay-500"
                         :class="isVisible ? 'translate-y-0 opacity-100' : 'translate-y-5 opacity-0'">
                        <PrimaryButton 
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
                            :class="{ 'opacity-50': form.processing, 'from-gray-400 to-gray-500': form.processing }"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Creating Account...
                            </span>
                            <span v-else class="flex items-center">
                                <svg class="w-4 h-4 mr-2 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Register & Return
                            </span>
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Additional Links with Animation -->
                <div class="text-center pt-3 border-t border-gray-100 transform transition-all duration-500 delay-600"
                     :class="isVisible ? 'translate-y-0 opacity-100' : 'translate-y-5 opacity-0'">
                    <p class="text-sm text-gray-600">
                        Already have an account?
                        <Link :href="route('login') + (redirectUrl ? '?redirect_to=' + encodeURIComponent(redirectUrl) : '')" 
                              class="font-medium text-green-600 hover:text-green-500 ml-1 transition duration-200 transform hover:scale-105 inline-block">
                            Sign in here
                        </Link>
                    </p>
                </div>
            </div>

            <!-- Security & Benefits Note with Animation -->
            <div class="text-center space-y-2 transform transition-all duration-500 delay-700"
                 :class="isVisible ? 'translate-y-0 opacity-100' : 'translate-y-5 opacity-0'">
                <p class="text-xs text-gray-500">
                    🔒 Your data is securely encrypted and protected
                </p>
                <div class="flex justify-center space-x-4 text-xs text-gray-500">
                    <span class="flex items-center">
                        <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Secure
                    </span>
                    <span class="flex items-center">
                        <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Free
                    </span>
                    <span class="flex items-center">
                        <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Instant
                    </span>
                </div>
            </div>
        </div>
    </div>
    </AuthenticatedLayout>
</template>
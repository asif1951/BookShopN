<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    previousUrl: {
        type: String,
        default: '/'
    },
});

// Get redirect URL
const getRedirectUrl = () => {
    // First priority: query parameter
    const urlParams = new URLSearchParams(window.location.search);
    const redirectParam = urlParams.get('redirect_to');
    
    if (redirectParam) {
        return redirectParam;
    }
    
    // Second priority: prop from controller
    if (props.previousUrl && props.previousUrl !== window.location.href) {
        const previous = new URL(props.previousUrl);
        if (previous.origin === window.location.origin && 
            !previous.pathname.includes('/login') && 
            !previous.pathname.includes('/register')) {
            return props.previousUrl;
        }
    }
    
    // Third priority: document referrer
    if (document.referrer && document.referrer !== window.location.href) {
        const referrer = new URL(document.referrer);
        if (referrer.origin === window.location.origin && 
            !referrer.pathname.includes('/login') && 
            !referrer.pathname.includes('/register')) {
            return document.referrer;
        }
    }
    
    return '/dashboard';
};

const redirectUrl = ref(getRedirectUrl());

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    // Use router.post to include redirect URL
    router.post(route('login'), {
        email: form.email,
        password: form.password,
        remember: form.remember,
        redirect_to: redirectUrl.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => form.reset('password'),
    });
};

// Animation state
const isVisible = ref(false);

onMounted(() => {
    // Trigger animation after component mounts
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
    
    // Show where user will be redirected
    console.log('Redirecting to:', redirectUrl.value);
});

// Function to go back to previous page
const goBack = () => {
    if (redirectUrl.value && !redirectUrl.value.includes('/dashboard')) {
        window.location.href = redirectUrl.value;
    } else {
        window.history.back();
    }
};
</script>

<template>
    <AuthenticatedLayout>
    <Head title="Log in" />

    <!-- Main Container -->
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-6">
            <!-- Back Button -->
            <!-- <div class="mb-4">
                <button 
                    @click="goBack"
                    class="text-sm text-indigo-600 hover:text-indigo-500 font-medium transition duration-200 transform hover:scale-105"
                >
                    ← Back to Previous Page
                </button>
            </div> -->

            <!-- Header Section with Slide Animation -->
            <div 
                class="text-center transform transition-all duration-700 ease-out"
                :class="isVisible ? 'translate-y-0 opacity-100' : '-translate-y-10 opacity-0'"
            >
                <div class="mx-auto h-16 w-16 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg mb-4 transform transition-all duration-1000 ease-out"
                     :class="isVisible ? 'scale-100 rotate-0' : 'scale-50 rotate-180'">
                    <svg class="h-8 w-8 text-white transform transition-all duration-1000"
                         :class="isVisible ? 'scale-100' : 'scale-0'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 transform transition-all duration-800 delay-200"
                    :class="isVisible ? 'translate-y-0 opacity-100' : '-translate-y-5 opacity-0'">
                    Welcome Back
                </h2>
                <p class="mt-1 text-sm text-gray-600 transform transition-all duration-800 delay-300"
                   :class="isVisible ? 'translate-y-0 opacity-100' : '-translate-y-5 opacity-0'">
                    Sign in to your account to continue
                </p>
                <!-- Show where you'll be redirected -->
                <p v-if="redirectUrl && !redirectUrl.includes('/dashboard')" 
                   class="mt-2 text-xs text-indigo-500 bg-indigo-50 px-3 py-1 rounded-full inline-block">
                    🔄 You'll return to previous page after login
                </p>
            </div>

            <!-- Status Message with Fade Animation -->
            <div v-if="status" 
                 class="bg-green-50 border border-green-200 rounded-lg p-3 text-center transform transition-all duration-500 ease-out"
                 :class="isVisible ? 'scale-100 opacity-100' : 'scale-95 opacity-0'">
                <span class="text-green-700 text-sm font-medium">{{ status }}</span>
            </div>

            <!-- Login Form with Popup Animation -->
            <div 
                class="bg-white rounded-xl shadow-2xl p-6 space-y-5 border border-gray-100 transform transition-all duration-700 ease-out"
                :class="isVisible ? 'translate-y-0 scale-100 opacity-100' : 'translate-y-10 scale-95 opacity-0'"
            >
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field with Stagger Animation -->
                    <div class="space-y-2 transform transition-all duration-500 delay-100"
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
                                class="mt-1 block w-full pl-9 pr-3 py-2.5 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 transform hover:scale-[1.01]"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Enter your email"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <!-- Password Field with Stagger Animation -->
                    <div class="space-y-2 transform transition-all duration-500 delay-200"
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
                                class="mt-1 block w-full pl-9 pr-3 py-2.5 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 transform hover:scale-[1.01]"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <!-- Remember Me & Forgot Password with Stagger Animation -->
                    <div class="flex items-center justify-between transform transition-all duration-500 delay-300"
                         :class="isVisible ? 'translate-x-0 opacity-100' : '-translate-x-5 opacity-0'">
                        <label class="flex items-center space-x-2">
                            <Checkbox 
                                name="remember" 
                                v-model:checked="form.remember" 
                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition duration-200 transform hover:scale-110"
                            />
                            <span class="text-sm text-gray-600">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition duration-200 transform hover:scale-105"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <!-- Submit Button with Stagger Animation -->
                    <div class="transform transition-all duration-500 delay-400"
                         :class="isVisible ? 'translate-y-0 opacity-100' : 'translate-y-5 opacity-0'">
                        <PrimaryButton 
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-300 transform hover:scale-[1.03] active:scale-[0.98]"
                            :class="{ 'opacity-50': form.processing, 'from-gray-400 to-gray-500': form.processing }"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Signing in...
                            </span>
                            <span v-else class="flex items-center">
                                <svg class="w-4 h-4 mr-2 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Sign in & Return
                            </span>
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Additional Links with Fade Animation -->
                <div class="text-center pt-3 border-t border-gray-100 transform transition-all duration-500 delay-500"
                     :class="isVisible ? 'translate-y-0 opacity-100' : 'translate-y-5 opacity-0'">
                    <p class="text-sm text-gray-600">
                        Don't have an account?
                        <Link :href="route('register') + (redirectUrl ? '?redirect_to=' + encodeURIComponent(redirectUrl) : '')" 
                              class="font-medium text-indigo-600 hover:text-indigo-500 ml-1 transition duration-200 transform hover:scale-105 inline-block">
                            Create one now
                        </Link>
                    </p>
                </div>
            </div>

            <!-- Security Note with Fade Animation -->
            <div class="text-center transform transition-all duration-500 delay-600"
                 :class="isVisible ? 'translate-y-0 opacity-100' : 'translate-y-5 opacity-0'">
                <p class="text-xs text-gray-500">
                    🔒 Your data is securely encrypted and protected
                </p>
            </div>
        </div>
    </div>
    </AuthenticatedLayout>
</template>
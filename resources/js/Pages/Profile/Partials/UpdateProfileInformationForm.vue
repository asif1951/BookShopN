<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section class="max-w-3xl mx-auto">
        <!-- Header Card -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 mb-8 text-white">
            <div class="flex items-center space-x-4">
                <div class="bg-white/20 p-3 rounded-full backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold">Profile Information</h2>
                    <p class="mt-1 text-indigo-100 opacity-90">
                        Update your account's profile information and email address
                    </p>
                </div>
            </div>
        </div>

        <form 
            @submit.prevent="form.patch(route('profile.update'))" 
            class="space-y-6"
        >
            <!-- Name Field -->
            <div class="group relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-400 to-purple-500 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-indigo-100 p-2 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <InputLabel for="name" value="Full Name" class="font-semibold text-gray-700 text-lg" />
                    </div>
                    
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-2 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 transition-all duration-300 py-3 px-4 bg-gray-50 hover:bg-white border-2"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Enter your full name"
                    />

                    <InputError class="mt-3 text-sm" :message="form.errors.name" />
                </div>
            </div>

            <!-- Email Field -->
            <div class="group relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <InputLabel for="email" value="Email Address" class="font-semibold text-gray-700 text-lg" />
                    </div>
                    
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-2 block w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-all duration-300 py-3 px-4 bg-gray-50 hover:bg-white border-2"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="Enter your email address"
                    />

                    <InputError class="mt-3 text-sm" :message="form.errors.email" />
                </div>
            </div>

            <!-- Email Verification -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null" 
                 class="group relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-amber-400 to-orange-500 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-amber-50 p-6 rounded-xl border border-amber-200 hover:shadow-md transition-all duration-300">
                    <div class="flex items-start space-x-3">
                        <div class="bg-amber-100 p-2 rounded-lg mt-1">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-amber-800 font-medium mb-2">
                                Email Verification Required
                            </p>
                            <p class="text-amber-700 text-sm mb-3">
                                Your email address is unverified. Please verify your email to access all features.
                            </p>
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium transition-all duration-300 shadow-sm hover:shadow"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Resend Verification Email
                            </Link>
                        </div>
                    </div>

                    <div
                        v-show="status === 'verification-link-sent'"
                        class="mt-4 p-3 bg-green-100 border border-green-200 rounded-lg text-green-700 text-sm font-medium flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>A new verification link has been sent to your email address.</span>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="group relative pt-6">
                <div class="absolute -inset-1 bg-gradient-to-r from-green-400 to-emerald-500 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-white p-6 rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <PrimaryButton 
                                :disabled="form.processing" 
                                class="px-8 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300 flex items-center space-x-2"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ form.processing ? 'Saving...' : 'Save Changes' }}</span>
                            </PrimaryButton>

                            <Transition
                                enter-active-class="transition-all duration-300 ease-out"
                                enter-from-class="transform opacity-0 -translate-y-2"
                                enter-to-class="transform opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-300 ease-in"
                                leave-from-class="transform opacity-100 translate-y-0"
                                leave-to-class="transform opacity-0 -translate-y-2"
                            >
                                <div v-if="form.recentlySuccessful" 
                                     class="flex items-center space-x-2 px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Profile updated successfully!</span>
                                </div>
                            </Transition>
                        </div>

                        <div class="text-sm text-gray-500 hidden sm:block">
                            Last updated: {{ new Date().toLocaleDateString() }}
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>
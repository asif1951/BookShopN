<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="max-w-3xl mx-auto">
        <!-- Header Card -->
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-2xl shadow-xl p-6 mb-8 text-white">
            <div class="flex items-center space-x-4">
                <div class="bg-white/20 p-3 rounded-full backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold">Update Password</h2>
                    <p class="mt-1 text-blue-100 opacity-90">
                        Ensure your account is using a long, random password to stay secure.
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="updatePassword" class="space-y-6">
            <!-- Current Password -->
            <div class="group relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <InputLabel for="current_password" value="Current Password" class="font-semibold text-gray-700 text-lg" />
                    </div>
                    
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="mt-2 block w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-all duration-300 py-3 px-4 bg-gray-50 hover:bg-white border-2"
                        autocomplete="current-password"
                        placeholder="Enter your current password"
                    />

                    <InputError :message="form.errors.current_password" class="mt-3 text-sm" />
                </div>
            </div>

            <!-- New Password -->
            <div class="group relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-green-400 to-emerald-500 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-green-100 p-2 rounded-lg">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <InputLabel for="password" value="New Password" class="font-semibold text-gray-700 text-lg" />
                    </div>
                    
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-2 block w-full rounded-xl border-gray-300 focus:border-green-500 focus:ring-green-500 transition-all duration-300 py-3 px-4 bg-gray-50 hover:bg-white border-2"
                        autocomplete="new-password"
                        placeholder="Enter your new password"
                    />

                    <InputError :message="form.errors.password" class="mt-3 text-sm" />
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="group relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-purple-400 to-pink-500 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-purple-100 p-2 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <InputLabel for="password_confirmation" value="Confirm Password" class="font-semibold text-gray-700 text-lg" />
                    </div>
                    
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-2 block w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500 transition-all duration-300 py-3 px-4 bg-gray-50 hover:bg-white border-2"
                        autocomplete="new-password"
                        placeholder="Confirm your new password"
                    />

                    <InputError :message="form.errors.password_confirmation" class="mt-3 text-sm" />
                </div>
            </div>

            <!-- Save Button -->
            <div class="group relative pt-6">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-400 to-purple-500 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-white p-6 rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <PrimaryButton 
                                :disabled="form.processing" 
                                class="px-8 py-3 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300 flex items-center space-x-2"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>{{ form.processing ? 'Updating...' : 'Update Password' }}</span>
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
                                    <span>Password updated successfully!</span>
                                </div>
                            </Transition>
                        </div>

                        <div class="text-sm text-gray-500 hidden sm:block">
                            Security level: 🔒 Strong
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>
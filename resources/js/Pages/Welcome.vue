<script setup>
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const canLogin = page.props.canLogin
const canRegister = page.props.canRegister
</script>

<template>
    <nav class="w-full bg-white dark:bg-gray-900 shadow px-6 py-4 flex items-center justify-between">

        <!-- Left side Logo -->
        <div class="text-xl font-bold text-green-600">
            Bookshop
        </div>

        <!-- Right side menu -->
        <div class="flex items-center gap-4">

            <!-- Log In Button -->
            <Link
                v-if="canLogin"
                :href="route('login')"
                class="px-4 py-2 rounded-lg bg-green-500 text-white font-medium hover:bg-green-600 transition"
            >
                Log In
            </Link>

            <!-- Register Button -->
            <Link
                v-if="canRegister"
                :href="route('register')"
                class="px-4 py-2 rounded-lg border border-green-500 text-green-600 font-medium hover:bg-green-100 transition"
            >
                Register
            </Link>

        </div>
    </nav>
</template>
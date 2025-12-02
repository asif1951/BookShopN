<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Payment Successful" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Payment Successful</h2>
    </template>

    <div class="py-12">
      <div class="max-w-2xl mx-auto text-center">
        <div class="bg-white rounded-lg shadow-md p-8">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="text-green-600 text-2xl">✓</span>
          </div>
          <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful!</h1>
          <p class="text-lg text-gray-600 mb-8">
            Thank you for your order. Your payment has been processed successfully.
          </p>
          <div class="space-y-4">
            <Link 
              href="/dashboard"
              class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-md hover:bg-indigo-700 transition font-semibold"
            >
              Continue Shopping
            </Link>
            <br>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
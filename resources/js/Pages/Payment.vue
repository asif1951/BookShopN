<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, onMounted, watch } from 'vue'
import { loadStripe } from '@stripe/stripe-js'
import axios from "axios"

// Payment method
const paymentMethod = ref('cod')
const processingPayment = ref(false)
const paymentError = ref('')

// Stripe Elements
const stripe = ref(null)
const elements = ref(null)
const cardElement = ref(null)

// Card details
const cardName = ref('Asif Khan')
const cardComplete = ref(false)

// bKash dummy payment state
const bkashProcessing = ref(false)
const bkashOtp = ref('')
const showBkashOtpModal = ref(false)
const bkashPaymentSteps = ref(1)

// Mobile wallet details
const mobileNumber = ref('')
const transactionId = ref('')

// Cart items from localStorage
const cartItems = ref([])
const totalAmount = ref(0)

// Load cart data
const loadCartData = () => {
  const cart = JSON.parse(localStorage.getItem('cart')) || []
  cartItems.value = cart
  totalAmount.value = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
}

// ✅ FIXED: Initialize Stripe with package
const initializeStripe = async () => {
  try {
    const stripeInstance = await loadStripe('pk_test_51SUMC8AaZ2LiqIxYFaasHhBTZKzH1tX2Annglp0JmnoHlFFRSCSwcVl38oADW0siyChgKz7uMFchrBQBiz1xVG7t004KsD6rzz');
    
    if (!stripeInstance) {
      throw new Error('Failed to load Stripe');
    }

    const elementsInstance = stripeInstance.elements();
    return { stripe: stripeInstance, elements: elementsInstance };
  } catch (error) {
    console.error('Stripe initialization error:', error);
    throw error;
  }
};

// ✅ FIXED: Setup Stripe Card Element
const setupCardElement = async () => {
  try {
    // Clear existing element first
    if (cardElement.value) {
      cardElement.value.unmount();
      cardElement.value = null;
    }

    const { stripe: stripeInstance, elements: elementsInstance } = await initializeStripe();
    stripe.value = stripeInstance;
    elements.value = elementsInstance;

    // Create card element
    const card = elementsInstance.create('card', {
      style: {
        base: {
          fontSize: '16px',
          color: '#424770',
          '::placeholder': {
            color: '#aab7c4',
          },
          padding: '10px 12px',
        },
      },
    });

    // Mount card element
    card.mount('#card-element');
    cardElement.value = card;

    // Handle card validation
    card.on('change', (event) => {
      cardComplete.value = event.complete;
      if (event.error) {
        paymentError.value = event.error.message;
      } else {
        paymentError.value = '';
      }
    });

  } catch (error) {
    console.error('Error setting up Stripe:', error);
    paymentError.value = 'Failed to initialize payment system';
  }
};

// ✅ bKash Dummy Payment Process
const processBkashPayment = async () => {
  bkashProcessing.value = true
  paymentError.value = ''

  try {
    console.log("📱 Starting bKash dummy payment...")

    // Step 1: Validate mobile number
    if (!mobileNumber.value) {
      throw new Error("Please enter your bKash mobile number")
    }

    if (!/^01[3-9]\d{8}$/.test(mobileNumber.value)) {
      throw new Error("Please enter a valid Bangladeshi mobile number")
    }

    // Step 2: Check stock availability
    console.log("🔍 Checking stock availability...")
    const stockCheckResponse = await axios.get('/check-stock', {
      params: {
        items: JSON.stringify(cartItems.value)
      }
    })

    if (!stockCheckResponse.data.available) {
      throw new Error(stockCheckResponse.data.message || 'Stock not available')
    }

    console.log("✅ Stock available, proceeding with bKash payment...")

    // Step 3: Show OTP modal (simulating bKash OTP)
    bkashPaymentSteps.value = 2
    showBkashOtpModal.value = true

  } catch (error) {
    console.error("💥 bKash payment error:", error)
    paymentError.value = error.response?.data?.error || error.message
    bkashProcessing.value = false
  }
}

// ✅ Verify bKash OTP and Complete Payment
const verifyBkashOtp = async () => {
  try {
    if (!bkashOtp.value) {
      throw new Error("Please enter OTP")
    }

    if (bkashOtp.value !== '123456') {
      throw new Error("Invalid OTP. Please enter 123456")
    }

    bkashPaymentSteps.value = 3

    // Simulate payment processing delay
    await new Promise(resolve => setTimeout(resolve, 2000))

    // Generate transaction ID
    const transactionIdValue = `bkash_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`

    // 👉 CSRF cookie load
    await axios.get("/sanctum/csrf-cookie")

    // 👉 Save order to backend
    const response = await axios.post(
      "/save-order",
      {
        items: cartItems.value,
        total_amount: totalAmount.value,
        payment_method: 'bkash',
        payment_status: "paid",
        transaction_id: transactionIdValue,
        mobile_number: mobileNumber.value
      },
      {
        withCredentials: true,
        headers: {
          "Accept": "application/json",
        },
      }
    )

    console.log("✅ bKash payment successful:", response.data)
    console.log("📦 Stock updated for ordered items")

    // Step 4: Payment successful
    bkashPaymentSteps.value = 4

    // Clear cart and redirect after delay
    setTimeout(() => {
      localStorage.removeItem("cart")
      router.visit("/payment/success")
    }, 2000)

  } catch (error) {
    console.error("💥 bKash OTP verification error:", error)
    paymentError.value = error.response?.data?.error || error.message
    bkashProcessing.value = false
    showBkashOtpModal.value = false
  }
}

// ✅ Reset bKash Payment
const resetBkashPayment = () => {
  bkashProcessing.value = false
  bkashOtp.value = ''
  showBkashOtpModal.value = false
  bkashPaymentSteps.value = 1
  paymentError.value = ''
}

const processPayment = async () => {
  processingPayment.value = true;
  paymentError.value = "";

  try {
    console.log("💰 Processing payment with method:", paymentMethod.value);

    // Handle bKash payment separately
    if (paymentMethod.value === "bkash") {
      await processBkashPayment();
      processingPayment.value = false;
      return;
    }

    // First, check stock availability for other methods
    console.log("🔍 Checking stock availability...");
    const stockCheckResponse = await axios.get('/check-stock', {
      params: {
        items: JSON.stringify(cartItems.value)
      }
    });

    if (!stockCheckResponse.data.available) {
      throw new Error(stockCheckResponse.data.message || 'Stock not available');
    }

    console.log("✅ Stock available, proceeding with payment...");

    // Handle different payment methods
    if (paymentMethod.value === "stripe") {
      await processStripePayment();
    } else {
      await processOtherPayment();
    }

  } catch (error) {
    console.error("💥 Payment error:", error);
    
    // Handle stock-related errors specifically
    if (error.response?.data?.error?.includes('stock') || 
        error.response?.data?.error?.includes('Stock') ||
        error.message.includes('stock')) {
      paymentError.value = "❌ " + (error.response?.data?.error || error.message);
      
      // Reload cart to reflect current stock
      loadCartData();
    } else {
      paymentError.value = error.response?.data?.error || error.message;
    }
  } finally {
    processingPayment.value = false;
  }
};

// ✅ FIXED: Process Stripe Payment with Elements
const processStripePayment = async () => {
  try {
    console.log("💳 Processing Stripe payment...");

    if (!cardComplete.value) {
      throw new Error('Please complete card details');
    }

    if (!cardName.value) {
      throw new Error('Please enter cardholder name');
    }

    // Create Stripe Payment Intent
    const paymentIntentResponse = await axios.post('/create-stripe-payment-intent', {
      amount: totalAmount.value,
      items: cartItems.value
    });

    const { clientSecret, paymentIntentId } = paymentIntentResponse.data;

    // Confirm payment with Stripe Elements
    const { error, paymentIntent } = await stripe.value.confirmCardPayment(clientSecret, {
      payment_method: {
        card: cardElement.value,
        billing_details: {
          name: cardName.value,
        },
      },
      return_url: `${window.location.origin}/payment/success`,
    });

    if (error) {
      throw new Error(error.message);
    }

    if (paymentIntent.status === 'succeeded') {
      // Confirm payment with backend
      const confirmResponse = await axios.post('/confirm-stripe-payment', {
        payment_intent_id: paymentIntentId,
        items: cartItems.value,
        total_amount: totalAmount.value
      });

      console.log("✅ Stripe payment successful:", confirmResponse.data);
      
      // Clear cart
      localStorage.removeItem("cart");
      
      // Redirect to success page
      router.visit("/payment/success");
    } else {
      throw new Error('Payment failed. Please try again.');
    }

  } catch (error) {
    console.error("💥 Stripe payment error:", error);
    throw error;
  }
};

// Process other payment methods (COD, Nagad, Rocket)
const processOtherPayment = async () => {
  // Mobile wallet validation
  if (['nagad', 'rocket'].includes(paymentMethod.value)) {
    if (!mobileNumber.value) {
      throw new Error("Please enter your mobile number");
    }
    if (!transactionId.value) {
      alert(`Send $${totalAmount.value.toFixed(2)} to ${getWalletNumber(paymentMethod.value)} and then enter transaction ID`);
      processingPayment.value = false;
      return;
    }
  }

  // Generate transaction ID
  const transactionIdValue = generateTransactionId();

  // 👉 FIXED: CSRF cookie load
  await axios.get("/sanctum/csrf-cookie");

  // 👉 FIXED: Proper POST with axios - status automatically 'pending' হবে
  const response = await axios.post(
    "/save-order",
    {
      items: cartItems.value,
      total_amount: totalAmount.value,
      payment_method: paymentMethod.value,
      payment_status: "paid",
      transaction_id: transactionIdValue,
      mobile_number: mobileNumber.value
      // status field পাঠানোর দরকার নেই, backend এ automatically 'pending' set হবে
    },
    {
      withCredentials: true,
      headers: {
        "Accept": "application/json",
      },
    }
  );

  console.log("✅ Payment successful:", response.data);
  console.log("📦 Stock should be updated for ordered items");

  // Clear cart
  localStorage.removeItem("cart");

  // Redirect to success page
  router.visit("/payment/success");
};







// Generate transaction ID
const generateTransactionId = () => {
  const prefixes = {
    'stripe': 'stripe',
    'cod': 'cod', 
    'bkash': 'bkash',
    'nagad': 'nagad',
    'rocket': 'rocket'
  }
  
  const prefix = prefixes[paymentMethod.value] || 'txn'
  return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
}

// Get wallet number for display
const getWalletNumber = (method) => {
  const numbers = {
    'bkash': '01512345678',
    'nagad': '01712345678', 
    'rocket': '01812345678'
  }
  return numbers[method] || '01XXXXXXXXX'
}

onMounted(() => {
  loadCartData()
  
  // Redirect if cart is empty
  if (cartItems.value.length === 0) {
    router.visit('/dashboard')
  }
})

// ✅ FIXED: Watch payment method changes
watch(paymentMethod, (newMethod) => {
  if (newMethod === 'stripe') {
    // Small delay to ensure DOM is updated
    setTimeout(() => {
      setupCardElement();
    }, 100);
  } else {
    // Clear Stripe elements when switching to other methods
    if (cardElement.value) {
      cardElement.value.unmount();
      cardElement.value = null;
    }
    paymentError.value = '';
    
    // Reset bKash payment state when switching away from bKash
    if (newMethod !== 'bkash') {
      resetBkashPayment();
    }
  }
});
</script>

<template>
  <Head title="Payment" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Payment</h2>
    </template>

    <div class="py-8 max-w-4xl mx-auto">
      <!-- Order Summary -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Order Summary</h3>
        <div class="space-y-3">
          <div v-for="item in cartItems" :key="item.id" class="flex justify-between items-center">
            <div>
              <p class="font-medium">{{ item.title }}</p>
              <p class="text-sm text-gray-500">Qty: {{ item.quantity }} × ${{ item.price }}</p>
            </div>
            <p class="font-semibold">${{ (item.price * item.quantity).toFixed(2) }}</p>
          </div>
        </div>
        <div class="border-t mt-4 pt-4">
          <div class="flex justify-between items-center text-lg font-bold">
            <span>Total Amount:</span>
            <span>${{ totalAmount.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Payment Methods -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-6">Select Payment Method</h3>

        <!-- Payment Error -->
        <div v-if="paymentError" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
          {{ paymentError }}
        </div>

        <!-- Payment Method Selection -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <!-- Cash on Delivery -->
          <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors">
            <input 
              type="radio" 
              v-model="paymentMethod" 
              value="cod" 
              class="mr-3 text-indigo-600 focus:ring-indigo-500"
            >
            <div class="flex items-center">
              <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                <span class="text-green-600 font-bold">💰</span>
              </div>
              <div>
                <p class="font-medium">Cash on Delivery</p>
                <p class="text-sm text-gray-500">Pay when you receive</p>
              </div>
            </div>
          </label>

          <!-- Credit/Debit Card -->
          <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors">
            <input 
              type="radio" 
              v-model="paymentMethod" 
              value="stripe" 
              class="mr-3 text-indigo-600 focus:ring-indigo-500"
            >
            <div class="flex items-center">
              <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                <span class="text-blue-600 font-bold">💳</span>
              </div>
              <div>
                <p class="font-medium">Credit/Debit Card</p>
                <p class="text-sm text-gray-500">Visa, Mastercard, Amex</p>
              </div>
            </div>
          </label>

          <!-- bKash -->
          <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors">
            <input 
              type="radio" 
              v-model="paymentMethod" 
              value="bkash" 
              class="mr-3 text-indigo-600 focus:ring-indigo-500"
            >
            <div class="flex items-center">
              <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center mr-3">
                <span class="text-pink-600 font-bold">📱</span>
              </div>
              <div>
                <p class="font-medium">bKash</p>
                <p class="text-sm text-gray-500">Mobile Banking</p>
              </div>
            </div>
          </label>

          <!-- Nagad -->
          <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors">
            <input 
              type="radio" 
              v-model="paymentMethod" 
              value="nagad" 
              class="mr-3 text-indigo-600 focus:ring-indigo-500"
            >
            <div class="flex items-center">
              <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                <span class="text-green-600 font-bold">📱</span>
              </div>
              <div>
                <p class="font-medium">Nagad</p>
                <p class="text-sm text-gray-500">Mobile Banking</p>
              </div>
            </div>
          </label>

          <!-- Rocket -->
          <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors">
            <input 
              type="radio" 
              v-model="paymentMethod" 
              value="rocket" 
              class="mr-3 text-indigo-600 focus:ring-indigo-500"
            >
            <div class="flex items-center">
              <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                <span class="text-purple-600 font-bold">📱</span>
              </div>
              <div>
                <p class="font-medium">Rocket</p>
                <p class="text-sm text-gray-500">Mobile Banking</p>
              </div>
            </div>
          </label>
        </div>

        <!-- Payment Form Based on Selection -->
        <div class="border-t pt-6">
          <!-- Credit Card Form with Stripe Elements -->
          <div v-if="paymentMethod === 'stripe'" class="space-y-4">
            <h4 class="font-semibold mb-4">Card Details</h4>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name</label>
              <input
                v-model="cardName"
                type="text"
                placeholder="Asif Khan"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Card Details</label>
              <div id="card-element" class="p-3 border border-gray-300 rounded-md bg-white">
                <!-- Stripe Card Element will be inserted here -->
              </div>
              <p class="text-xs text-gray-500 mt-1">Card details are securely processed by Stripe</p>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-md p-4">
              <p class="text-sm text-green-800">
                <strong>Test Card Details:</strong><br>
                Card: 4242 4242 4242 4242<br>
                Expiry: 12/28, CVC: 123<br>
                This is a real Stripe test payment.
              </p>
            </div>
          </div>

          <!-- Mobile Wallet Form -->
          <div v-if="['bkash', 'nagad', 'rocket'].includes(paymentMethod)" class="space-y-4">
            <h4 class="font-semibold mb-4">{{ paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1) }} Payment</h4>
            
            <!-- bKash Specific Steps -->
            <div v-if="paymentMethod === 'bkash'" class="space-y-4">
              <!-- Step 1: Mobile Number -->
              <div v-if="bkashPaymentSteps === 1">
                <label class="block text-sm font-medium text-gray-700 mb-1">bKash Mobile Number</label>
                <input
                  v-model="mobileNumber"
                  type="text"
                  placeholder="01XXXXXXXXX"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                  :disabled="bkashProcessing"
                />
                <p class="text-xs text-gray-500 mt-1">Enter your bKash registered mobile number</p>
              </div>

              <!-- Step 2: OTP Verification (Modal) -->
              <div v-if="bkashPaymentSteps >= 2" class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex items-center mb-2">
                  <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm mr-2">2</div>
                  <p class="font-medium text-blue-800">OTP Sent to {{ mobileNumber }}</p>
                </div>
                <p class="text-sm text-blue-700">We've sent a 6-digit OTP to your bKash app. Please check and enter below.</p>
              </div>

              <!-- Step 3: Processing -->
              <div v-if="bkashPaymentSteps === 3" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                <div class="flex items-center">
                  <div class="w-6 h-6 bg-yellow-500 rounded-full flex items-center justify-center text-white text-sm mr-2">3</div>
                  <p class="font-medium text-yellow-800">Processing Payment...</p>
                </div>
                <p class="text-sm text-yellow-700 mt-1">Please wait while we process your payment.</p>
              </div>

              <!-- Step 4: Success -->
              <div v-if="bkashPaymentSteps === 4" class="bg-green-50 border border-green-200 rounded-md p-4">
                <div class="flex items-center">
                  <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-white text-sm mr-2">4</div>
                  <p class="font-medium text-green-800">Payment Successful! 🎉</p>
                </div>
                <p class="text-sm text-green-700 mt-1">Redirecting to success page...</p>
              </div>
            </div>

            <!-- Other Mobile Wallets (Original) -->
            <div v-if="paymentMethod !== 'bkash'">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                <input
                  v-model="mobileNumber"
                  type="text"
                  placeholder="01XXXXXXXXX"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID</label>
                <input
                  v-model="transactionId"
                  type="text"
                  placeholder="Enter transaction ID after payment"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
              <p class="text-sm text-yellow-800">
                <strong v-if="paymentMethod === 'bkash'">bKash Dummy Payment Instructions:</strong>
                <strong v-else>Instructions:</strong>
                <br v-if="paymentMethod === 'bkash'">
                <span v-if="paymentMethod === 'bkash'">
                  1. Enter your bKash mobile number<br>
                  2. Click "Pay with bKash" button<br>
                  3. Enter OTP: <strong>123456</strong><br>
                  4. Payment will be processed automatically
                </span>
                <span v-else>
                  1. Go to your {{ paymentMethod }} app<br>
                  2. Send ${{ totalAmount.toFixed(2) }} to {{ getWalletNumber(paymentMethod) }}<br>
                  3. Enter the transaction ID above<br>
                  4. Click "Pay" button to complete order
                </span>
              </p>
            </div>
          </div>

          <!-- Cash on Delivery Info -->
          <div v-if="paymentMethod === 'cod'" class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <p class="text-sm text-blue-800">
              <strong>Cash on Delivery:</strong><br>
              You will pay ${{ totalAmount.toFixed(2) }} when you receive your order.
            </p>
          </div>
        </div>

        <!-- Payment Button -->
        <div class="mt-8">
          <button
            @click="processPayment"
            :disabled="processingPayment || bkashProcessing || cartItems.length === 0 || 
                       (paymentMethod === 'stripe' && !cardComplete) ||
                       (paymentMethod === 'bkash' && bkashPaymentSteps > 1)"
            class="w-full bg-indigo-600 text-white py-3 px-4 rounded-md hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition font-semibold text-lg"
          >
            <span v-if="paymentMethod === 'bkash' && bkashPaymentSteps === 1">
              Pay with bKash - ${{ totalAmount.toFixed(2) }}
            </span>
            <span v-else-if="paymentMethod === 'bkash' && bkashPaymentSteps > 1">
              bKash Payment in Progress...
            </span>
            <span v-else>
              {{ processingPayment ? 'Processing...' : `Pay $${totalAmount.toFixed(2)}` }}
            </span>
          </button>
          
          <button
            @click="router.visit('/dashboard')"
            class="w-full mt-3 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 transition"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- bKash OTP Modal -->
    <div v-if="showBkashOtpModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">bKash OTP Verification</h3>
          <button 
            @click="resetBkashPayment" 
            class="text-gray-400 hover:text-gray-600"
            :disabled="bkashPaymentSteps >= 3"
          >
            ✕
          </button>
        </div>

        <div class="space-y-4">
          <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
            <p class="text-sm text-blue-700">
              <strong>OTP Sent to:</strong> {{ mobileNumber }}<br>
              <strong>Test OTP:</strong> <span class="font-mono bg-blue-100 px-2 py-1 rounded">123456</span>
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Enter 6-digit OTP</label>
            <input
              v-model="bkashOtp"
              type="text"
              maxlength="6"
              placeholder="123456"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-center text-lg font-mono"
              :disabled="bkashPaymentSteps >= 3"
            />
          </div>

          <div class="flex space-x-3">
            <button
              @click="verifyBkashOtp"
              :disabled="!bkashOtp || bkashOtp.length !== 6 || bkashPaymentSteps >= 3"
              class="flex-1 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition font-semibold"
            >
              {{ bkashPaymentSteps >= 3 ? 'Processing...' : 'Verify OTP' }}
            </button>
            
            <button
              @click="resetBkashPayment"
              :disabled="bkashPaymentSteps >= 3"
              class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 disabled:bg-gray-200 disabled:cursor-not-allowed transition"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Optional: Add some custom styles for Stripe Elements */
#card-element {
  min-height: 40px;
  padding: 10px 12px;
}
</style>
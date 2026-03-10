<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-400 to-brand-600 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-2xl">
        <div>
            <div class="flex justify-center mb-4">
                <img src="{{ asset('logo/logo.png') }}" alt="Logo" class="h-16 w-auto">
            </div>
            <h2 class="text-center text-3xl font-extrabold text-gray-900">
                Sales Management System
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Sign in to your account
            </p>
        </div>
        
        <form wire:submit.prevent="login" class="mt-8 space-y-6">
            <div class="rounded-md shadow-sm space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                    <input 
                        wire:model="email" 
                        id="email" 
                        type="email" 
                        autocomplete="email" 
                        required 
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent sm:text-sm"
                        placeholder="Enter your email"
                    >
                    @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input 
                        wire:model="password" 
                        id="password" 
                        type="password" 
                        autocomplete="current-password" 
                        required 
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent sm:text-sm"
                        placeholder="Enter your password"
                    >
                    @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input 
                        wire:model="remember" 
                        id="remember" 
                        type="checkbox" 
                        class="h-4 w-4 text-brand-500 focus:ring-brand-500 border-gray-300 rounded"
                    >
                    <label for="remember" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>
            </div>

            <div>
                <button 
                    type="submit" 
                    class="group relative w-full flex justify-center py-2.5 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-brand-500 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors duration-200"
                >
                    Sign in
                </button>
            </div>
        </form>
    </div>
</div>

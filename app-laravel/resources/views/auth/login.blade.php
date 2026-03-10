<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'Link Tracker') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-neutral-50 text-neutral-700">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-neutral-900">Link Tracker</h1>
                <p class="text-sm text-neutral-500 mt-1">Sign in with your @north-star.network email</p>
            </div>

            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                @if(session('success'))
                    <div class="mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-800 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-red-800 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('magic-link.send') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            placeholder="you@north-star.network"
                            class="w-full px-3 py-2 border border-neutral-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    <button
                        type="submit"
                        class="w-full bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded-md hover:bg-blue-700 transition-colors"
                    >
                        Send login link
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

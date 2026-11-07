<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - SIPB BigBang Eatery</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-[#4A2311] rounded-full flex items-center justify-center text-white font-black text-3xl border-4 border-stone-900 shadow-[6px_6px_0_0_rgba(0,0,0,0.3)] mx-auto">
                B
            </div>
            <h1 class="text-2xl font-black mt-4">SIPB - BigBang Eatery</h1>
            <p class="text-stone-500 text-sm">Sistem Informasi Pemesanan BigBang</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-xl border-4 border-stone-900 shadow-[8px_8px_0_0_rgba(0,0,0,0.3)] p-6">
            <h2 class="font-black text-lg mb-6 text-center">Login</h2>

            @if($errors->any())
                <div class="bg-red-100 border-2 border-red-500 rounded-lg p-3 mb-4 text-sm text-red-700 font-bold">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-100 border-2 border-green-500 rounded-lg p-3 mb-4 text-sm text-green-700 font-bold">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('login.authenticate') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block font-bold mb-1">Username</label>
                    <input type="text" name="username" required value="{{ old('username') }}"
                           class="w-full border-2 border-stone-300 rounded-lg px-4 py-3 focus:border-[#4A2311] focus:outline-none"
                           placeholder="Masukkan username">
                </div>

                <div>
                    <label class="block font-bold mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full border-2 border-stone-300 rounded-lg px-4 py-3 focus:border-[#4A2311] focus:outline-none"
                           placeholder="Masukkan password">
                </div>

                <button type="submit"
                        class="w-full py-4 bg-[#4A2311] text-white font-black text-lg rounded-lg border-2 border-stone-900 shadow-[5px_5px_0_0_rgba(0,0,0,0.3)] hover:bg-[#8B5A2B] active:shadow-none active:translate-x-[5px] active:translate-y-[5px] transition-all">
                    Login
                </button>
            </form>
        </div>

        <p class="text-center text-stone-500 text-xs mt-6">SIPB &copy; {{ date('Y') }} BigBang Eatery</p>
    </div>
</body>
</html>

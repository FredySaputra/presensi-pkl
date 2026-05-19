<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Presensi PKL Lab ICT</title>

    <link rel="icon" href="{{ asset('logo/lab.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col sm:flex-row">

        <div class="hidden sm:flex sm:w-1/2 bg-indigo-900 text-white flex-col justify-center items-center p-12 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[radial-gradient(circle_at_top_left,_var(--tw-gradient-stops))] from-white via-transparent to-transparent"></div>

            <div class="relative z-10 flex flex-col items-center text-center">
                <img src="{{ asset('logo/lab.png') }}" alt="Logo Lab ICT" class="w-36 h-36 mb-8 bg-white p-3 rounded-2xl shadow-xl">

                <h1 class="text-4xl font-bold mb-3 tracking-tight">Sistem Presensi PKL</h1>
                <h2 class="text-xl font-medium text-indigo-200 mb-8">Laboratorium ICT Universitas Budi Luhur</h2>

                <div class="w-16 h-1 bg-indigo-500 mb-8 rounded-full"></div>

                <p class="text-indigo-100 max-w-md text-base leading-relaxed">
                    Portal manajemen admin untuk mengelola master data sekolah, data siswa pkl, serta memonitor dan mencetak laporan kehadiran secara real-time.
                </p>
            </div>
        </div>

        <div class="w-full sm:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 bg-white shadow-[0_0_40px_rgba(0,0,0,0.05)] z-20">
            <div class="w-full max-w-md">

                <div class="sm:hidden flex flex-col items-center mb-8">
                    <img src="{{ asset('logo/logo.png') }}" alt="Logo Lab ICT" class="w-24 h-24 mb-4 bg-gray-50 p-2 rounded-xl shadow-sm border border-gray-100">
                    <h1 class="text-2xl font-bold text-gray-800 text-center">Presensi PKL Lab ICT</h1>
                </div>

                <div class="mb-10">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Selamat Datang</h2>
                    <p class="text-gray-500">Silakan masuk menggunakan kredensial admin Anda.</p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username Admin</label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                            class="block w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition duration-200 shadow-sm"
                            placeholder="Masukkan username Anda">
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            class="block w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition duration-200 shadow-sm"
                            placeholder="••••••••">
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="inline-flex items-center cursor-pointer">
                            <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5 transition duration-150">
                            <span class="ms-3 text-sm font-medium text-gray-600">Ingat Saya</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-indigo-200 transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0">
                        MASUK KE DASHBOARD
                    </button>
                </form>
            </div>
        </div>

    </div>
</body>
</html>

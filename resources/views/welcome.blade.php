<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backend API - BTA SISTEM | Universitas Nurul Huda</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        unuha: '#0F7646'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans min-h-screen flex flex-col justify-between antialiased">

    <!-- Top bar -->
    <header class="w-full border-b border-gray-200 bg-white">
        <div class="max-w-4xl mx-auto px-6 h-14 flex items-center justify-between text-sm">
            <span class="font-medium text-gray-600">Universitas Nurul Huda</span>
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                <span class="text-xs font-medium text-gray-600">API Service Operational</span>
            </div>
        </div>
    </header>

    <!-- Main Section -->
    <main class="max-w-2xl mx-auto px-6 py-16 flex flex-col items-center text-center">
        <!-- Logo -->
        <div class="mb-8">
            <img 
                src="https://unuha.ac.id/wp-content/uploads/2022/08/LOGO-UNIV-UNUHA-HIJAU.png" 
                alt="Logo Universitas Nurul Huda" 
                class="h-24 sm:h-28 w-auto object-contain mx-auto"
            />
        </div>

        <!-- Heading -->
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 mb-2">
            BTA SISTEM
        </h1>
        <p class="text-base sm:text-lg font-semibold text-[#0F7646] mb-1">
            BACA TULIS AL-QUR'AN DAN MQ
        </p>
        <p class="text-sm text-gray-500 mb-10">
            Universitas Nurul Huda
        </p>

        <!-- Information Card -->
        <div class="w-full max-w-lg bg-white border border-gray-200 rounded-xl shadow-sm p-6 text-left">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Backend API Gateway</h2>
                    <p class="text-xs text-gray-500">Layanan data & autentikasi sistem BTA</p>
                </div>
                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-xs font-medium rounded-full border border-emerald-200/60">
                    REST API
                </span>
            </div>

            <dl class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <dt class="text-gray-400 font-medium">Framework</dt>
                    <dd class="text-gray-700 font-semibold mt-0.5">Laravel {{ app()->version() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 font-medium">Lingkungan</dt>
                    <dd class="text-gray-700 font-semibold mt-0.5">{{ strtoupper(app()->environment()) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 font-medium">Autentikasi</dt>
                    <dd class="text-gray-700 font-semibold mt-0.5">Sanctum Token</dd>
                </div>
                <div>
                    <dt class="text-gray-400 font-medium">Status Layanan</dt>
                    <dd class="text-emerald-700 font-semibold mt-0.5">Online</dd>
                </div>
            </dl>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full border-t border-gray-200 bg-white">
        <div class="max-w-4xl mx-auto px-6 py-4 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-400">
            <p>&copy; {{ date('Y') }} Universitas Nurul Huda. All rights reserved.</p>
            <p>Sistem Baca Tulis Al-Qur'an dan MQ</p>
        </div>
    </footer>

</body>
</html>

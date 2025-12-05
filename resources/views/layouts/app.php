<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', sidebarOpen: true }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($title ?? 'TCT-YMS') ?> - <?= e(config('app.name')) ?></title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#EBF5FF',
                            100: '#E1EFFE',
                            200: '#C3DDFD',
                            300: '#A4CAFE',
                            400: '#76A9FA',
                            500: '#1C4E80',
                            600: '#1C4E80',
                            700: '#1A4570',
                            800: '#183C60',
                            900: '#163350',
                        },
                        accent: '#247BA0',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <style>
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: width 0.3s ease, transform 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php partial('components.sidebar') ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden" :class="{ 'ml-64': sidebarOpen, 'ml-16': !sidebarOpen }">
            <!-- Top Navigation -->
            <?php partial('components.topnav', ['title' => $title ?? '']) ?>

            <!-- Flash Messages -->
            <?php partial('components.flash') ?>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Global Scripts -->
    <script>
        // CSRF token for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Helper for fetch with CSRF
        async function fetchWithCsrf(url, options = {}) {
            const headers = {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            };

            if (options.body && !(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(options.body);
            }

            const response = await fetch(url, { ...options, headers });
            return response.json();
        }

        // Toast notifications
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            } text-white`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>

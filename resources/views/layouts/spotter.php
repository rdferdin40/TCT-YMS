<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($title ?? 'Spotter') ?> - TCT-YMS</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            500: '#1C4E80',
                            600: '#1C4E80',
                            700: '#1A4570',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <style>
        [x-cloak] { display: none !important; }
        body { -webkit-tap-highlight-color: transparent; }
        .safe-area-top { padding-top: env(safe-area-inset-top); }
        .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-primary-600 text-white px-4 py-3 safe-area-top sticky top-0 z-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="ti ti-forklift text-2xl mr-2"></i>
                <span class="font-bold">TCT-YMS</span>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm opacity-80"><?= e($user['full_name'] ?? 'Spotter') ?></span>
                <form action="<?= url('/logout') ?>" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="p-2 hover:bg-primary-500 rounded-lg">
                        <i class="ti ti-logout"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (hasFlash('success')): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4 rounded">
        <i class="ti ti-check-circle mr-2"></i><?= e(getFlash('success')) ?>
    </div>
    <?php endif; ?>

    <?php if (hasFlash('error')): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-4 rounded">
        <i class="ti ti-alert-circle mr-2"></i><?= e(getFlash('error')) ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto pb-20">
        <?= $content ?>
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 safe-area-bottom z-50">
        <div class="flex justify-around py-2">
            <a href="<?= url('/spotter') ?>" class="flex flex-col items-center py-2 px-4 <?= currentPath() === '/spotter' ? 'text-primary-600' : 'text-gray-500' ?>">
                <i class="ti ti-home text-2xl"></i>
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="<?= url('/spotter/tasks') ?>" class="flex flex-col items-center py-2 px-4 <?= pathStartsWith('/spotter/task') ? 'text-primary-600' : 'text-gray-500' ?>">
                <i class="ti ti-list-check text-2xl"></i>
                <span class="text-xs mt-1">Tasks</span>
            </a>
            <a href="<?= url('/spotter/yard-view') ?>" class="flex flex-col items-center py-2 px-4 <?= currentPath() === '/spotter/yard-view' ? 'text-primary-600' : 'text-gray-500' ?>">
                <i class="ti ti-map text-2xl"></i>
                <span class="text-xs mt-1">Yard</span>
            </a>
        </div>
    </nav>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

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
    </script>
</body>
</html>

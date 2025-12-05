<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Login') ?> - <?= e(config('app.name')) ?></title>

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

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-xl mb-4">
                <i class="ti ti-forklift text-3xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800"><?= e(config('app.name')) ?></h1>
            <p class="text-gray-500 mt-1">Yard Management System</p>
        </div>

        <!-- Flash Messages -->
        <?php if (hasFlash('success')): ?>
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <i class="ti ti-check-circle mr-2"></i><?= e(getFlash('success')) ?>
        </div>
        <?php endif; ?>

        <?php if (hasFlash('error')): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <i class="ti ti-alert-circle mr-2"></i><?= e(getFlash('error')) ?>
        </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            <?= $content ?>
        </div>

        <!-- Footer -->
        <p class="text-center text-gray-400 text-sm mt-8">
            &copy; <?= date('Y') ?> TCT Yard Management System
        </p>
    </div>
</body>
</html>

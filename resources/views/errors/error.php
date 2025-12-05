<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $code ?> - TCT-YMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="text-8xl font-bold text-gray-200 mb-4"><?= $code ?></div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2"><?= e($message) ?></h1>
        <p class="text-gray-500 mb-8">
            <?php
            echo match ($code) {
                401 => 'You need to log in to access this page.',
                403 => 'You don\'t have permission to access this resource.',
                404 => 'The page you\'re looking for doesn\'t exist.',
                500 => 'Something went wrong on our end. Please try again later.',
                default => 'An unexpected error occurred.',
            };
            ?>
        </p>
        <div class="space-x-4">
            <a href="javascript:history.back()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="ti ti-arrow-left mr-2"></i>Go Back
            </a>
            <a href="<?= url('/') ?>" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="ti ti-home mr-2"></i>Go Home
            </a>
        </div>
    </div>
</body>
</html>

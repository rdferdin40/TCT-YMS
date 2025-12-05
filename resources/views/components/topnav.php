<header class="bg-white dark:bg-gray-800 shadow-sm h-16 flex items-center justify-between px-6">
    <!-- Left: Page Title & Breadcrumb -->
    <div class="flex items-center">
        <h1 class="text-xl font-semibold text-gray-800 dark:text-white">
            <?= e($title ?? 'Dashboard') ?>
        </h1>
    </div>

    <!-- Right: Actions -->
    <div class="flex items-center space-x-4">
        <!-- Search -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="ti ti-search text-xl"></i>
            </button>
            <div x-show="open" @click.away="open = false" x-cloak
                 class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-700 rounded-lg shadow-lg p-4 z-50">
                <input type="text" placeholder="Search trailers..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500"
                       x-data
                       @keyup.enter="window.location.href = '<?= url('/trailers') ?>?q=' + $el.value">
            </div>
        </div>

        <!-- Dark Mode Toggle -->
        <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="ti text-xl" :class="darkMode ? 'ti-sun' : 'ti-moon'"></i>
        </button>

        <!-- Refresh -->
        <button onclick="location.reload()"
                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="ti ti-refresh text-xl"></i>
        </button>

        <!-- User Menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                    class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white">
                    <span class="text-sm font-bold"><?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?></span>
                </div>
                <span class="text-gray-700 dark:text-gray-200 hidden md:inline"><?= e($user['full_name'] ?? 'User') ?></span>
                <i class="ti ti-chevron-down text-gray-500"></i>
            </button>

            <div x-show="open" @click.away="open = false" x-cloak
                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg py-2 z-50">
                <a href="<?= url('/profile') ?>"
                   class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                    <i class="ti ti-user mr-2"></i> My Profile
                </a>
                <a href="<?= url('/profile') ?>#preferences"
                   class="flex items-center px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                    <i class="ti ti-settings mr-2"></i> Preferences
                </a>
                <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                <form action="<?= url('/logout') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="w-full flex items-center px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                        <i class="ti ti-logout mr-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

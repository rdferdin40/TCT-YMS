<aside class="fixed inset-y-0 left-0 z-30 bg-primary-600 dark:bg-gray-800 text-white sidebar-transition"
       :class="sidebarOpen ? 'w-64' : 'w-16'">
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center justify-between h-16 px-4 bg-primary-700 dark:bg-gray-900">
            <a href="<?= url('/dashboard') ?>" class="flex items-center space-x-2" x-show="sidebarOpen">
                <i class="ti ti-forklift text-2xl"></i>
                <span class="text-lg font-bold">TCT-YMS</span>
            </a>
            <button @click="sidebarOpen = !sidebarOpen" class="p-1 rounded hover:bg-primary-500">
                <i class="ti" :class="sidebarOpen ? 'ti-chevron-left' : 'ti-chevron-right'"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-2">
                <!-- Dashboard -->
                <li>
                    <a href="<?= url('/dashboard') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/dashboard') || currentPath() === '/' ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-dashboard text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Dashboard</span>
                    </a>
                </li>

                <!-- Yard Map -->
                <li>
                    <a href="<?= url('/yard-map') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/yard-map') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-map text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Yard Map</span>
                    </a>
                </li>

                <!-- Gate -->
                <?php if (can('gate.view')): ?>
                <li>
                    <a href="<?= url('/gate') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/gate') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-barrier-block text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Gate</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Trailers -->
                <li>
                    <a href="<?= url('/trailers') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/trailers') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-truck text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Trailers</span>
                    </a>
                </li>

                <!-- Dock Doors -->
                <li>
                    <a href="<?= url('/dock-doors') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/dock-doors') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-door text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Dock Doors</span>
                    </a>
                </li>

                <!-- Move Tasks -->
                <li>
                    <a href="<?= url('/moves') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/moves') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-arrows-move text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Move Tasks</span>
                    </a>
                </li>

                <!-- Reports -->
                <?php if (can('reports.view')): ?>
                <li>
                    <a href="<?= url('/reports') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/reports') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-report-analytics text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Reports</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Divider -->
                <li class="border-t border-primary-500 dark:border-gray-700 my-4"></li>

                <!-- Admin Section -->
                <?php if (hasAnyRole(['admin', 'supervisor'])): ?>
                <li x-show="sidebarOpen" class="px-3 py-2 text-xs uppercase tracking-wider text-primary-300">
                    Administration
                </li>

                <?php if (can('users.view')): ?>
                <li>
                    <a href="<?= url('/admin/users') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/admin/users') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-users text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Users</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can('admin.carriers')): ?>
                <li>
                    <a href="<?= url('/admin/carriers') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/admin/carriers') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-building text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Carriers</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasRole('admin')): ?>
                <li>
                    <a href="<?= url('/admin/yard-layout') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/admin/yard-layout') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-layout-grid text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Yard Layout</span>
                    </a>
                </li>

                <li>
                    <a href="<?= url('/admin/statuses') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/admin/statuses') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-tags text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Statuses</span>
                    </a>
                </li>

                <li>
                    <a href="<?= url('/admin/settings') ?>"
                       class="flex items-center px-3 py-2 rounded-lg transition-colors <?= pathStartsWith('/admin/settings') ? 'bg-primary-500 dark:bg-gray-700' : 'hover:bg-primary-500 dark:hover:bg-gray-700' ?>">
                        <i class="ti ti-settings text-xl"></i>
                        <span class="ml-3" x-show="sidebarOpen">Settings</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- User Info -->
        <div class="border-t border-primary-500 dark:border-gray-700 p-4" x-show="sidebarOpen">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-primary-400 flex items-center justify-center">
                    <span class="text-sm font-bold"><?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?></span>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?= e($user['full_name'] ?? 'User') ?></p>
                    <p class="text-xs text-primary-300"><?= e($user['role_display'] ?? 'Role') ?></p>
                </div>
            </div>
        </div>
    </div>
</aside>

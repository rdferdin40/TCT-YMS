<h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">Sign In</h2>

<form action="<?= url('/login') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="mb-4">
        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
            Username or Email
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="ti ti-user text-gray-400"></i>
            </div>
            <input type="text"
                   id="username"
                   name="username"
                   value="<?= e(old('username')) ?>"
                   class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                   placeholder="Enter your username or email"
                   required
                   autofocus>
        </div>
    </div>

    <div class="mb-6">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            Password
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="ti ti-lock text-gray-400"></i>
            </div>
            <input type="password"
                   id="password"
                   name="password"
                   class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                   placeholder="Enter your password"
                   required>
        </div>
    </div>

    <div class="flex items-center justify-between mb-6">
        <label class="flex items-center">
            <input type="checkbox" name="remember" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
            <span class="ml-2 text-sm text-gray-600">Remember me</span>
        </label>
        <a href="<?= url('/forgot-password') ?>" class="text-sm text-primary-600 hover:text-primary-700">
            Forgot password?
        </a>
    </div>

    <button type="submit"
            class="w-full bg-primary-600 text-white py-2 px-4 rounded-lg hover:bg-primary-700 transition-colors font-medium">
        <i class="ti ti-login mr-2"></i>Sign In
    </button>
</form>

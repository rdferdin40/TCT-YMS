<?php if (hasFlash('success')): ?>
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     class="fixed top-20 right-4 z-50 max-w-md bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg flex items-center">
    <i class="ti ti-check-circle text-xl mr-2"></i>
    <span><?= e(getFlash('success')) ?></span>
    <button @click="show = false" class="ml-4 text-green-700 hover:text-green-900">
        <i class="ti ti-x"></i>
    </button>
</div>
<?php endif; ?>

<?php if (hasFlash('error')): ?>
<div x-data="{ show: true }" x-show="show"
     class="fixed top-20 right-4 z-50 max-w-md bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg flex items-center">
    <i class="ti ti-alert-circle text-xl mr-2"></i>
    <span><?= e(getFlash('error')) ?></span>
    <button @click="show = false" class="ml-4 text-red-700 hover:text-red-900">
        <i class="ti ti-x"></i>
    </button>
</div>
<?php endif; ?>

<?php if (hasFlash('warning')): ?>
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     class="fixed top-20 right-4 z-50 max-w-md bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg shadow-lg flex items-center">
    <i class="ti ti-alert-triangle text-xl mr-2"></i>
    <span><?= e(getFlash('warning')) ?></span>
    <button @click="show = false" class="ml-4 text-yellow-700 hover:text-yellow-900">
        <i class="ti ti-x"></i>
    </button>
</div>
<?php endif; ?>

<?php if (hasFlash('info')): ?>
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     class="fixed top-20 right-4 z-50 max-w-md bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg shadow-lg flex items-center">
    <i class="ti ti-info-circle text-xl mr-2"></i>
    <span><?= e(getFlash('info')) ?></span>
    <button @click="show = false" class="ml-4 text-blue-700 hover:text-blue-900">
        <i class="ti ti-x"></i>
    </button>
</div>
<?php endif; ?>

<?php
$errors = getFlash('errors', []);
if (!empty($errors)):
?>
<div x-data="{ show: true }" x-show="show"
     class="fixed top-20 right-4 z-50 max-w-md bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg">
    <div class="flex items-start">
        <i class="ti ti-alert-circle text-xl mr-2 mt-0.5"></i>
        <div class="flex-1">
            <p class="font-medium">Please fix the following errors:</p>
            <ul class="mt-1 text-sm list-disc list-inside">
                <?php foreach ($errors as $field => $error): ?>
                <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button @click="show = false" class="ml-4 text-red-700 hover:text-red-900">
            <i class="ti ti-x"></i>
        </button>
    </div>
</div>
<?php endif; ?>

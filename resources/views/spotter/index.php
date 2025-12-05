<div class="p-4">
    <!-- Welcome Card -->
    <div class="bg-gradient-to-r from-primary-600 to-primary-500 text-white rounded-xl p-6 mb-6">
        <h1 class="text-2xl font-bold mb-2">Welcome, <?= e($user['first_name'] ?? 'Spotter') ?>!</h1>
        <p class="opacity-80">Ready to move some trailers?</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="text-3xl font-bold text-primary-600"><?= count($myTasks) ?></div>
            <div class="text-sm text-gray-500">My Tasks</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="text-3xl font-bold text-amber-500"><?= count($pendingTasks) ?></div>
            <div class="text-sm text-gray-500">Available</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="text-3xl font-bold text-green-500"><?= $stats['completed_today'] ?></div>
            <div class="text-sm text-gray-500">Completed Today</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="text-3xl font-bold text-purple-500"><?= $stats['in_progress'] ?></div>
            <div class="text-sm text-gray-500">In Progress</div>
        </div>
    </div>

    <!-- My Current Tasks -->
    <?php if (!empty($myTasks)): ?>
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">My Active Tasks</h2>
        <div class="space-y-3">
            <?php foreach ($myTasks as $task): ?>
            <?php $trailer = $task->getTrailer(); ?>
            <a href="<?= url("/spotter/task/{$task->id}") ?>"
               class="block bg-white rounded-xl p-4 shadow-sm border-l-4 <?= $task->status === 'in_progress' ? 'border-purple-500' : 'border-blue-500' ?>">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-lg"><?= e($trailer ? $trailer->trailer_number : 'N/A') ?></span>
                    <span class="px-2 py-1 rounded-full text-xs <?= $task->status === 'in_progress' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                        <?= ucfirst($task->status) ?>
                    </span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <span><?= e($task->getFromLocationString()) ?></span>
                    <i class="ti ti-arrow-right mx-2"></i>
                    <span><?= e($task->getToLocationString()) ?></span>
                </div>
                <div class="mt-2 flex items-center text-xs text-gray-400">
                    <span class="px-2 py-0.5 rounded" style="background-color: <?= $task->getPriorityColor() ?>20; color: <?= $task->getPriorityColor() ?>">
                        <?= ucfirst($task->priority) ?> Priority
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Available Tasks -->
    <?php if (!empty($pendingTasks)): ?>
    <div>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Available Tasks</h2>
        <div class="space-y-3">
            <?php foreach (array_slice($pendingTasks, 0, 5) as $task): ?>
            <?php $trailer = $task->getTrailer(); ?>
            <div class="bg-white rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold"><?= e($trailer ? $trailer->trailer_number : 'N/A') ?></span>
                    <form action="<?= url("/moves/{$task->id}/claim") ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm font-medium">
                            Claim
                        </button>
                    </form>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <span><?= e($task->getFromLocationString()) ?></span>
                    <i class="ti ti-arrow-right mx-2"></i>
                    <span><?= e($task->getToLocationString()) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($pendingTasks) > 5): ?>
        <a href="<?= url('/spotter/tasks') ?>" class="block text-center text-primary-600 font-medium mt-4">
            View All <?= count($pendingTasks) ?> Tasks
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-8">
        <i class="ti ti-check-circle text-5xl text-green-500 mb-3"></i>
        <p class="text-gray-500">No tasks available. Great job!</p>
    </div>
    <?php endif; ?>
</div>

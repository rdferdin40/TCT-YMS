<div class="p-4" x-data="{ showCompleteModal: false }">
    <!-- Back Button -->
    <a href="<?= url('/spotter/tasks') ?>" class="inline-flex items-center text-gray-600 mb-4">
        <i class="ti ti-arrow-left mr-1"></i> Back to Tasks
    </a>

    <!-- Task Card -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary-600 to-primary-500 text-white p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm opacity-80">Task #<?= $task->id ?></span>
                <span class="px-3 py-1 rounded-full text-xs font-medium"
                      style="background: rgba(255,255,255,0.2)">
                    <?= ucfirst($task->status) ?>
                </span>
            </div>
            <div class="text-2xl font-bold mt-2"><?= e($trailer ? $trailer->trailer_number : 'N/A') ?></div>
            <?php $carrier = $trailer ? $trailer->getCarrier() : null; ?>
            <div class="text-sm opacity-80"><?= e($carrier ? $carrier->name : '') ?></div>
        </div>

        <!-- Move Details -->
        <div class="p-4">
            <div class="flex items-center justify-between py-4 border-b">
                <div class="text-center flex-1">
                    <div class="text-xs text-gray-500 uppercase mb-1">From</div>
                    <div class="font-bold text-lg"><?= e($task->getFromLocationString()) ?></div>
                </div>
                <div class="px-4">
                    <i class="ti ti-arrow-right text-2xl text-gray-400"></i>
                </div>
                <div class="text-center flex-1">
                    <div class="text-xs text-gray-500 uppercase mb-1">To</div>
                    <div class="font-bold text-lg"><?= e($task->getToLocationString()) ?></div>
                </div>
            </div>

            <!-- Priority -->
            <div class="flex items-center justify-between py-4 border-b">
                <span class="text-gray-600">Priority</span>
                <span class="px-3 py-1 rounded-full text-sm font-medium"
                      style="background-color: <?= $task->getPriorityColor() ?>20; color: <?= $task->getPriorityColor() ?>">
                    <?= ucfirst($task->priority) ?>
                </span>
            </div>

            <!-- Instructions -->
            <?php if ($task->instructions): ?>
            <div class="py-4 border-b">
                <div class="text-xs text-gray-500 uppercase mb-2">Instructions</div>
                <div class="text-gray-800"><?= e($task->instructions) ?></div>
            </div>
            <?php endif; ?>

            <!-- Timestamps -->
            <div class="py-4 text-sm">
                <div class="flex justify-between text-gray-500 mb-2">
                    <span>Created</span>
                    <span><?= formatDateTime($task->created_at) ?></span>
                </div>
                <?php if ($task->started_at): ?>
                <div class="flex justify-between text-gray-500 mb-2">
                    <span>Started</span>
                    <span><?= formatDateTime($task->started_at) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="space-y-3">
        <?php if ($task->status === 'assigned' || ($task->status === 'pending' && !$task->assigned_to)): ?>
        <form action="<?= url("/spotter/task/{$task->id}/start") ?>" method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="w-full py-4 bg-green-500 text-white rounded-xl font-bold text-lg shadow-lg active:bg-green-600">
                <i class="ti ti-player-play mr-2"></i>Start Task
            </button>
        </form>
        <?php endif; ?>

        <?php if ($task->status === 'in_progress'): ?>
        <button @click="showCompleteModal = true"
                class="w-full py-4 bg-green-500 text-white rounded-xl font-bold text-lg shadow-lg active:bg-green-600">
            <i class="ti ti-check mr-2"></i>Complete Task
        </button>
        <?php endif; ?>
    </div>

    <!-- Complete Modal -->
    <div x-show="showCompleteModal" x-transition
         class="fixed inset-0 bg-black bg-opacity-50 flex items-end z-50">
        <div class="bg-white w-full rounded-t-2xl p-6 safe-area-bottom" @click.away="showCompleteModal = false">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Complete Task</h3>
            <form action="<?= url("/spotter/task/{$task->id}/complete") ?>" method="POST">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optional)</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500"
                              placeholder="Any issues or comments..."></textarea>
                </div>
                <div class="flex space-x-3">
                    <button type="button" @click="showCompleteModal = false"
                            class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-medium">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-3 bg-green-500 text-white rounded-xl font-bold">
                        Confirm Complete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

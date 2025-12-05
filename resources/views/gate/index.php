<div>
    <!-- Action Buttons -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <a href="<?= url('/gate/check-in') ?>"
           class="bg-green-500 hover:bg-green-600 text-white rounded-xl p-6 flex items-center transition-colors">
            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center mr-4">
                <i class="ti ti-truck-delivery text-3xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold">Check-In</h3>
                <p class="text-green-100">Incoming trailer arrival</p>
            </div>
        </a>

        <a href="<?= url('/gate/check-out') ?>"
           class="bg-red-500 hover:bg-red-600 text-white rounded-xl p-6 flex items-center transition-colors">
            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center mr-4">
                <i class="ti ti-truck-return text-3xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold">Check-Out</h3>
                <p class="text-red-100">Outgoing trailer departure</p>
            </div>
        </a>
    </div>

    <!-- Today's Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Check-Ins Today</p>
                    <p class="text-3xl font-bold text-green-600"><?= $stats['check_ins_today'] ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <i class="ti ti-arrow-down-right text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Check-Outs Today</p>
                    <p class="text-3xl font-bold text-red-600"><?= $stats['check_outs_today'] ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                    <i class="ti ti-arrow-up-right text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Events</p>
                    <p class="text-3xl font-bold text-blue-600"><?= $stats['total_today'] ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <i class="ti ti-activity text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
        <div class="p-4 border-b dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Today's Gate Activity</h2>
            <a href="<?= url('/gate/history') ?>" class="text-primary-600 hover:text-primary-700 text-sm">
                View History
            </a>
        </div>

        <?php if (empty($todayEvents)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="ti ti-barrier-block text-4xl mb-2"></i>
            <p>No gate activity today</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700">
                        <th class="px-4 py-3 font-medium">Time</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Trailer</th>
                        <th class="px-4 py-3 font-medium">Carrier</th>
                        <th class="px-4 py-3 font-medium">Driver</th>
                        <th class="px-4 py-3 font-medium">Processed By</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php foreach ($todayEvents as $event): ?>
                    <?php
                    $trailer = $event->getTrailer();
                    $carrier = $event->getCarrier();
                    $processor = $event->getProcessedBy();
                    ?>
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            <?= formatTime($event->created_at) ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($event->isCheckIn()): ?>
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                                <i class="ti ti-arrow-down-right mr-1"></i>Check-In
                            </span>
                            <?php else: ?>
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">
                                <i class="ti ti-arrow-up-right mr-1"></i>Check-Out
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <a href="<?= url("/trailers/{$event->trailer_id}") ?>" class="font-medium text-primary-600 hover:text-primary-700">
                                <?= e($trailer ? $trailer->trailer_number : 'N/A') ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            <?= e($carrier ? $carrier->name : '-') ?>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            <?= e($event->driver_name ?: '-') ?>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            <?= e($processor ? $processor->getFullName() : '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

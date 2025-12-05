<div x-data="dashboardData()" x-init="init()">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total in Yard -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Trailers in Yard</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white" x-text="stats.yard.total_in_yard">
                        <?= $yardStats['total_in_yard'] ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <i class="ti ti-truck text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-500 flex items-center">
                    <i class="ti ti-arrow-up mr-1"></i>
                    <span x-text="stats.yard.arrivals_today"><?= $yardStats['arrivals_today'] ?></span>
                </span>
                <span class="text-gray-400 mx-2">arrivals</span>
                <span class="text-red-500 flex items-center">
                    <i class="ti ti-arrow-down mr-1"></i>
                    <span x-text="stats.yard.departures_today"><?= $yardStats['departures_today'] ?></span>
                </span>
                <span class="text-gray-400 ml-2">departures today</span>
            </div>
        </div>

        <!-- At Doors -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">At Dock Doors</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white" x-text="stats.yard.at_doors">
                        <?= $yardStats['at_doors'] ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900 rounded-lg flex items-center justify-center">
                    <i class="ti ti-door text-2xl text-amber-600 dark:text-amber-400"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                <span x-text="stats.doors.available"><?= $doorStats['available'] ?></span> of
                <span x-text="stats.doors.total"><?= $doorStats['total'] ?></span> doors available
            </div>
        </div>

        <!-- Dwell Warnings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Dwell Warnings</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white" x-text="stats.yard.dwell_warnings">
                        <?= $yardStats['dwell_warnings'] ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                    <i class="ti ti-alert-triangle text-2xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-4 text-sm">
                <span class="text-red-500 font-medium" x-text="stats.yard.dwell_critical"><?= $yardStats['dwell_critical'] ?></span>
                <span class="text-gray-400">critical alerts</span>
            </div>
        </div>

        <!-- Move Tasks -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pending Tasks</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white" x-text="stats.tasks.pending">
                        <?= $taskStats['pending'] ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <i class="ti ti-list-check text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                <span x-text="stats.tasks.completed_today"><?= $taskStats['completed_today'] ?></span> completed today
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Status Breakdown Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Trailers by Status</h3>
            <div id="statusChart" class="h-64"></div>
        </div>

        <!-- Door Utilization -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Dock Door Utilization</h3>
            <div id="doorChart" class="h-64"></div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Activity -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Recent Arrivals</h3>
                <a href="<?= url('/trailers') ?>" class="text-primary-600 hover:text-primary-700 text-sm">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                            <th class="pb-3 font-medium">Trailer</th>
                            <th class="pb-3 font-medium">Carrier</th>
                            <th class="pb-3 font-medium">Location</th>
                            <th class="pb-3 font-medium">Status</th>
                            <th class="pb-3 font-medium">Dwell</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php foreach (array_slice($recentTrailers, 0, 8) as $trailer): ?>
                        <?php $status = $trailer->getStatus(); $carrier = $trailer->getCarrier(); ?>
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="py-3">
                                <a href="<?= url("/trailers/{$trailer->id}") ?>" class="font-medium text-primary-600 hover:text-primary-700">
                                    <?= e($trailer->trailer_number) ?>
                                </a>
                            </td>
                            <td class="py-3 text-gray-600 dark:text-gray-300">
                                <?= e($carrier ? $carrier->name : '-') ?>
                            </td>
                            <td class="py-3 text-gray-600 dark:text-gray-300">
                                <?= e($trailer->getLocationString()) ?>
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded-full text-xs"
                                      style="background-color: <?= $status ? $status->bg_color : '#F3F4F6' ?>; color: <?= $status ? $status->color : '#6B7280' ?>">
                                    <?= e($status ? $status->display_name : 'Unknown') ?>
                                </span>
                            </td>
                            <td class="py-3">
                                <?php $dwellStatus = $trailer->getDwellStatus(); ?>
                                <span class="<?= $dwellStatus === 'critical' ? 'text-red-500' : ($dwellStatus === 'warning' ? 'text-yellow-500' : 'text-gray-600 dark:text-gray-300') ?>">
                                    <?= formatDwellTime($trailer->getDwellTime()) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dwell Warnings Panel -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Dwell Alerts</h3>
                <span class="text-xs text-gray-500"><?= count($dwellWarnings) ?> trailers</span>
            </div>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                <?php if (empty($dwellWarnings)): ?>
                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">
                    <i class="ti ti-check-circle text-green-500 text-2xl block mb-2"></i>
                    No dwell warnings
                </p>
                <?php else: ?>
                <?php foreach (array_slice($dwellWarnings, 0, 10) as $trailer): ?>
                <?php $dwellStatus = $trailer->getDwellStatus(); ?>
                <a href="<?= url("/trailers/{$trailer->id}") ?>"
                   class="block p-3 rounded-lg border <?= $dwellStatus === 'critical' ? 'border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800' : 'border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-800' ?>">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-gray-800 dark:text-white"><?= e($trailer->trailer_number) ?></span>
                        <span class="text-sm <?= $dwellStatus === 'critical' ? 'text-red-600' : 'text-yellow-600' ?>">
                            <?= formatDwellTime($trailer->getDwellTime()) ?>
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <?= e($trailer->getLocationString()) ?>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Auto-refresh indicator -->
    <div class="fixed bottom-4 left-4 text-xs text-gray-400 dark:text-gray-600" x-show="autoRefresh">
        <span x-text="'Auto-refresh in ' + countdown + 's'"></span>
    </div>
</div>

<script>
function dashboardData() {
    return {
        stats: {
            yard: <?= json_encode($yardStats) ?>,
            doors: <?= json_encode($doorStats) ?>,
            slots: <?= json_encode($slotStats) ?>,
            tasks: <?= json_encode($taskStats) ?>,
        },
        statusBreakdown: <?= json_encode($statusBreakdown) ?>,
        autoRefresh: true,
        countdown: <?= $refreshInterval ?>,
        refreshInterval: <?= $refreshInterval ?>,

        init() {
            this.renderCharts();
            this.startAutoRefresh();
        },

        renderCharts() {
            // Status breakdown chart
            const statusLabels = this.statusBreakdown.map(s => s.display_name);
            const statusData = this.statusBreakdown.map(s => s.count);
            const statusColors = this.statusBreakdown.map(s => s.color);

            new ApexCharts(document.querySelector('#statusChart'), {
                chart: { type: 'donut', height: 250 },
                series: statusData,
                labels: statusLabels,
                colors: statusColors,
                legend: { position: 'bottom' },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '16px',
                                    fontWeight: 600
                                }
                            }
                        }
                    }
                }
            }).render();

            // Door utilization chart
            new ApexCharts(document.querySelector('#doorChart'), {
                chart: { type: 'radialBar', height: 250 },
                series: [this.stats.doors.utilization],
                labels: ['Door Utilization'],
                colors: ['#F59E0B'],
                plotOptions: {
                    radialBar: {
                        hollow: { size: '70%' },
                        dataLabels: {
                            name: { offsetY: -10, fontSize: '14px' },
                            value: { fontSize: '24px', fontWeight: 'bold' }
                        }
                    }
                }
            }).render();
        },

        startAutoRefresh() {
            setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    this.countdown = this.refreshInterval;
                    this.refreshData();
                }
            }, 1000);
        },

        async refreshData() {
            try {
                const response = await fetch('<?= url('/dashboard/stats') ?>');
                const data = await response.json();
                if (data.success) {
                    this.stats = data.data;
                }
            } catch (e) {
                console.error('Failed to refresh data:', e);
            }
        }
    };
}
</script>

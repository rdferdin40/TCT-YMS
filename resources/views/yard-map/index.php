<div x-data="yardMapApp()" x-init="init()">
    <!-- Toolbar -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    <i class="ti ti-map mr-2"></i>Yard Map
                </h2>
                <div class="text-sm text-gray-500">
                    <span x-text="stats.yard.total_in_yard">0</span> trailers |
                    <span x-text="stats.slots.available">0</span> slots available
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button @click="refreshData()" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="ti ti-refresh"></i>
                </button>
                <button @click="showLegend = !showLegend" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="ti ti-info-circle"></i> Legend
                </button>
                <?php if ($canEdit): ?>
                <label class="flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer">
                    <input type="checkbox" x-model="editMode" class="mr-2">
                    <span class="text-sm">Edit Mode</span>
                </label>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status Legend -->
    <div x-show="showLegend" x-transition class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Status Legend</h3>
        <div class="flex flex-wrap gap-3">
            <?php foreach ($statuses as $status): ?>
            <div class="flex items-center">
                <span class="w-4 h-4 rounded mr-2" style="background-color: <?= $status->color ?>"></span>
                <span class="text-sm text-gray-600 dark:text-gray-400"><?= e($status->display_name) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="flex items-center ml-4">
                <span class="w-4 h-4 rounded mr-2 border-2 border-yellow-400"></span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Dwell Warning</span>
            </div>
            <div class="flex items-center">
                <span class="w-4 h-4 rounded mr-2 border-2 border-red-500"></span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Dwell Critical</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- Yard Grid -->
        <div class="xl:col-span-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 overflow-x-auto">
            <!-- Dock Doors Section -->
            <div class="mb-8">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    <i class="ti ti-door mr-1"></i>Dock Doors
                </h3>
                <div class="flex flex-wrap gap-2">
                    <template x-for="door in doors" :key="door.id">
                        <div class="relative w-24 h-20 rounded-lg border-2 cursor-pointer transition-all hover:shadow-lg"
                             :class="{
                                 'border-green-400 bg-green-50 dark:bg-green-900/20': door.status === 'available',
                                 'border-amber-400 bg-amber-50 dark:bg-amber-900/20': door.status === 'occupied',
                                 'border-red-400 bg-red-50 dark:bg-red-900/20': door.status === 'maintenance',
                                 'border-gray-300 bg-gray-100 dark:bg-gray-700': door.status === 'disabled',
                                 'ring-2 ring-blue-500': selectedDestination?.type === 'door' && selectedDestination?.id === door.id
                             }"
                             @click="handleDoorClick(door)"
                             @dragover.prevent="editMode && door.status === 'available'"
                             @drop="handleDrop($event, 'door', door.id)">

                            <!-- Door number -->
                            <div class="text-xs font-bold text-gray-600 dark:text-gray-300 p-1" x-text="door.door_number"></div>

                            <!-- Trailer info if occupied -->
                            <template x-if="door.trailer">
                                <div class="absolute inset-0 flex flex-col items-center justify-center p-1">
                                    <div class="text-xs font-medium truncate w-full text-center"
                                         :style="{ color: door.trailer.status_color }"
                                         x-text="door.trailer.trailer_number"></div>
                                    <div class="text-xs text-gray-500 truncate w-full text-center"
                                         x-text="door.trailer.carrier_code || door.trailer.carrier?.substring(0, 8)"></div>
                                    <div class="text-xs mt-1"
                                         :class="{
                                             'text-red-500': door.trailer.dwell_status === 'critical',
                                             'text-yellow-500': door.trailer.dwell_status === 'warning',
                                             'text-gray-400': door.trailer.dwell_status === 'normal'
                                         }"
                                         x-text="formatDwell(door.trailer.dwell_hours)"></div>
                                </div>
                            </template>

                            <!-- Available indicator -->
                            <template x-if="!door.trailer && door.status === 'available'">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xs text-green-500">Available</span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Yard Slots Grid -->
            <template x-for="zone in zones" :key="zone.id">
                <div class="mb-6">
                    <h3 class="text-sm font-semibold mb-3" :style="{ color: zone.color }">
                        <i class="ti ti-layout-grid mr-1"></i><span x-text="zone.name"></span>
                    </h3>

                    <template x-for="row in zone.rows" :key="row.id">
                        <div class="mb-2">
                            <div class="flex items-center gap-1">
                                <span class="w-8 text-xs font-medium text-gray-500" x-text="row.code"></span>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="slot in row.slots" :key="slot.id">
                                        <div class="w-16 h-14 rounded border cursor-pointer transition-all hover:shadow-md"
                                             :class="{
                                                 'border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-600': slot.is_available,
                                                 'border-2': !slot.is_available && slot.trailer,
                                                 'border-yellow-400': slot.trailer?.dwell_status === 'warning',
                                                 'border-red-500': slot.trailer?.dwell_status === 'critical',
                                                 'ring-2 ring-blue-500': selectedDestination?.type === 'slot' && selectedDestination?.id === slot.id
                                             }"
                                             :style="slot.trailer ? { backgroundColor: slot.trailer.status_bg_color, borderColor: slot.trailer.dwell_status === 'normal' ? slot.trailer.status_color : '' } : {}"
                                             :draggable="editMode && slot.trailer"
                                             @dragstart="handleDragStart($event, slot.trailer)"
                                             @dragover.prevent="editMode && slot.is_available"
                                             @drop="handleDrop($event, 'slot', slot.id)"
                                             @click="handleSlotClick(slot)">

                                            <template x-if="slot.trailer">
                                                <div class="h-full flex flex-col items-center justify-center p-1">
                                                    <div class="text-xs font-medium truncate w-full text-center"
                                                         :style="{ color: slot.trailer.status_color }"
                                                         x-text="slot.trailer.trailer_number"></div>
                                                    <div class="text-[10px] text-gray-500 truncate w-full text-center"
                                                         x-text="slot.trailer.carrier_code || ''"></div>
                                                </div>
                                            </template>

                                            <template x-if="!slot.trailer">
                                                <div class="h-full flex items-center justify-center">
                                                    <span class="text-[10px] text-gray-400" x-text="slot.label"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Side Panel -->
        <div class="space-y-6">
            <!-- Selected Trailer Info -->
            <div x-show="selectedTrailer" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Selected Trailer</h3>
                <template x-if="selectedTrailer">
                    <div>
                        <div class="text-lg font-bold text-gray-800 dark:text-white" x-text="selectedTrailer.trailer_number"></div>
                        <div class="text-sm text-gray-500" x-text="selectedTrailer.carrier"></div>
                        <div class="mt-2 px-2 py-1 rounded text-xs inline-block"
                             :style="{ backgroundColor: selectedTrailer.status_bg_color, color: selectedTrailer.status_color }"
                             x-text="selectedTrailer.status_display"></div>
                        <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            Dwell: <span x-text="formatDwell(selectedTrailer.dwell_hours)"></span>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a :href="'<?= url('/trailers/') ?>' + selectedTrailer.id"
                               class="flex-1 px-3 py-2 bg-primary-600 text-white rounded-lg text-sm text-center hover:bg-primary-700">
                                View Details
                            </a>
                            <?php if ($canEdit): ?>
                            <button @click="startMove(selectedTrailer)"
                                    class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600">
                                <i class="ti ti-arrows-move"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Yard Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Trailers</span>
                        <span class="font-medium" x-text="stats.yard.total_in_yard">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">At Doors</span>
                        <span class="font-medium" x-text="stats.yard.at_doors">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">In Yard</span>
                        <span class="font-medium" x-text="stats.yard.in_slots">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Available Slots</span>
                        <span class="font-medium text-green-500" x-text="stats.slots.available">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Available Doors</span>
                        <span class="font-medium text-green-500" x-text="stats.doors.available">0</span>
                    </div>
                    <div class="border-t dark:border-gray-700 pt-3 mt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Slot Utilization</span>
                            <span class="font-medium" x-text="stats.slots.utilization + '%'">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-primary-600 h-2 rounded-full" :style="{ width: stats.slots.utilization + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="<?= url('/gate/check-in') ?>" class="block w-full px-4 py-2 bg-green-500 text-white rounded-lg text-sm text-center hover:bg-green-600">
                        <i class="ti ti-truck-delivery mr-1"></i>Gate Check-In
                    </a>
                    <a href="<?= url('/moves/create') ?>" class="block w-full px-4 py-2 bg-purple-500 text-white rounded-lg text-sm text-center hover:bg-purple-600">
                        <i class="ti ti-arrows-move mr-1"></i>Create Move Task
                    </a>
                    <a href="<?= url('/trailers') ?>" class="block w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm text-center hover:bg-gray-200 dark:hover:bg-gray-600">
                        <i class="ti ti-list mr-1"></i>View All Trailers
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Move Modal -->
    <div x-show="showMoveModal" x-transition class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md" @click.away="showMoveModal = false">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Create Move Task</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trailer</label>
                    <div class="text-gray-800 dark:text-white font-medium" x-text="movingTrailer?.trailer_number"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                    <div class="text-gray-600 dark:text-gray-400" x-text="movingTrailer?.from_location || 'Current Location'"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                    <div class="text-gray-600 dark:text-gray-400">Click a destination on the map</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                    <select x-model="movePriority" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button @click="cancelMove()" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function yardMapApp() {
    return {
        zones: [],
        doors: [],
        stats: {
            yard: { total_in_yard: 0, at_doors: 0, in_slots: 0 },
            slots: { available: 0, utilization: 0 },
            doors: { available: 0 }
        },
        editMode: false,
        showLegend: false,
        selectedTrailer: null,
        selectedDestination: null,
        showMoveModal: false,
        movingTrailer: null,
        movePriority: 'normal',
        draggedTrailer: null,
        refreshInterval: <?= $refreshInterval ?>,

        async init() {
            await this.loadData();
            this.startAutoRefresh();
        },

        async loadData() {
            try {
                const response = await fetch('<?= url('/yard-map/data') ?>');
                const data = await response.json();
                if (data.success) {
                    this.zones = data.data.zones;
                    this.doors = data.data.doors;
                    this.stats = data.data.stats;
                }
            } catch (e) {
                console.error('Failed to load yard data:', e);
            }
        },

        async refreshData() {
            await this.loadData();
        },

        startAutoRefresh() {
            setInterval(() => this.refreshData(), this.refreshInterval * 1000);
        },

        formatDwell(hours) {
            if (hours < 1) return Math.round(hours * 60) + 'm';
            if (hours < 24) return Math.round(hours * 10) / 10 + 'h';
            const days = Math.floor(hours / 24);
            const h = Math.round(hours % 24);
            return days + 'd ' + h + 'h';
        },

        handleSlotClick(slot) {
            if (this.movingTrailer && slot.is_available) {
                this.selectedDestination = { type: 'slot', id: slot.id };
                this.executeMove('slot', slot.id);
            } else if (slot.trailer) {
                this.selectedTrailer = slot.trailer;
            }
        },

        handleDoorClick(door) {
            if (this.movingTrailer && door.status === 'available') {
                this.selectedDestination = { type: 'door', id: door.id };
                this.executeMove('door', door.id);
            } else if (door.trailer) {
                this.selectedTrailer = door.trailer;
            }
        },

        handleDragStart(event, trailer) {
            if (!this.editMode || !trailer) return;
            this.draggedTrailer = trailer;
            event.dataTransfer.effectAllowed = 'move';
        },

        async handleDrop(event, destType, destId) {
            if (!this.editMode || !this.draggedTrailer) return;
            event.preventDefault();

            await this.executeMove(destType, destId, this.draggedTrailer.id);
            this.draggedTrailer = null;
        },

        startMove(trailer) {
            this.movingTrailer = trailer;
            this.showMoveModal = true;
        },

        cancelMove() {
            this.movingTrailer = null;
            this.showMoveModal = false;
            this.selectedDestination = null;
        },

        async executeMove(destType, destId, trailerId = null) {
            const id = trailerId || this.movingTrailer?.id;
            if (!id) return;

            try {
                const response = await fetchWithCsrf('<?= url('/yard-map/move') ?>', {
                    method: 'POST',
                    body: {
                        trailer_id: id,
                        destination_type: destType,
                        destination_id: destId,
                        create_task: this.showMoveModal,
                        priority: this.movePriority
                    }
                });

                if (response.success) {
                    showToast(response.message);
                    this.cancelMove();
                    await this.refreshData();
                } else {
                    showToast(response.message || 'Move failed', 'error');
                }
            } catch (e) {
                console.error('Move failed:', e);
                showToast('Move failed', 'error');
            }
        }
    };
}
</script>

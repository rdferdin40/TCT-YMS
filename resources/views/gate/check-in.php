<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
        <div class="p-6 border-b dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center">
                <i class="ti ti-truck-delivery text-green-500 mr-2"></i>
                Gate Check-In
            </h2>
            <p class="text-sm text-gray-500 mt-1">Register an incoming trailer arrival</p>
        </div>

        <form action="<?= url('/gate/check-in') ?>" method="POST" enctype="multipart/form-data" class="p-6">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Trailer Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-white border-b dark:border-gray-700 pb-2">
                        Trailer Information
                    </h3>

                    <div>
                        <label for="trailer_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Trailer Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="trailer_number"
                               name="trailer_number"
                               value="<?= e(old('trailer_number')) ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500"
                               placeholder="e.g., ABCD123456"
                               required
                               autofocus>
                    </div>

                    <div>
                        <label for="carrier_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Carrier <span class="text-red-500">*</span>
                        </label>
                        <select id="carrier_id"
                                name="carrier_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500"
                                required>
                            <option value="">Select Carrier</option>
                            <?php foreach ($carriers as $carrier): ?>
                            <option value="<?= $carrier->id ?>" <?= old('carrier_id') == $carrier->id ? 'selected' : '' ?>>
                                <?= e($carrier->name) ?><?= $carrier->code ? " ({$carrier->code})" : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="trailer_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Trailer Type
                        </label>
                        <select id="trailer_type"
                                name="trailer_type"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500">
                            <option value="dry_van">Dry Van</option>
                            <option value="reefer">Reefer</option>
                            <option value="flatbed">Flatbed</option>
                            <option value="tanker">Tanker</option>
                            <option value="intermodal">Intermodal</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="seal_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Seal Number <?= $requireSealNumber ? '<span class="text-red-500">*</span>' : '' ?>
                            </label>
                            <input type="text"
                                   id="seal_number"
                                   name="seal_number"
                                   value="<?= e(old('seal_number')) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500"
                                   <?= $requireSealNumber ? 'required' : '' ?>>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Load Status
                            </label>
                            <div class="flex items-center space-x-4 mt-2">
                                <label class="flex items-center">
                                    <input type="radio" name="is_loaded" value="1" class="mr-2">
                                    <span>Loaded</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="is_loaded" value="0" checked class="mr-2">
                                    <span>Empty</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="yard_slot_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Assign to Yard Slot
                        </label>
                        <select id="yard_slot_id"
                                name="yard_slot_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500">
                            <option value="">Leave at Gate (assign later)</option>
                            <?php foreach ($availableSlots as $slot): ?>
                            <option value="<?= $slot->id ?>" <?= $defaultSlot && $defaultSlot->id == $slot->id ? 'selected' : '' ?>>
                                <?= e($slot->getFullLocation()) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Driver Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-white border-b dark:border-gray-700 pb-2">
                        Driver Information
                    </h3>

                    <div>
                        <label for="driver_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Driver Name <?= $requireDriverName ? '<span class="text-red-500">*</span>' : '' ?>
                        </label>
                        <input type="text"
                               id="driver_name"
                               name="driver_name"
                               value="<?= e(old('driver_name')) ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500"
                               <?= $requireDriverName ? 'required' : '' ?>>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="driver_license" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                License #
                            </label>
                            <input type="text"
                                   id="driver_license"
                                   name="driver_license"
                                   value="<?= e(old('driver_license')) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500">
                        </div>

                        <div>
                            <label for="driver_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Phone
                            </label>
                            <input type="tel"
                                   id="driver_phone"
                                   name="driver_phone"
                                   value="<?= e(old('driver_phone')) ?>"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>

                    <div>
                        <label for="tractor_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Tractor Number
                        </label>
                        <input type="text"
                               id="tractor_number"
                               name="tractor_number"
                               value="<?= e(old('tractor_number')) ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500">
                    </div>

                    <?php if ($allowPhotoUpload): ?>
                    <div>
                        <label for="photo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Photo (optional)
                        </label>
                        <input type="file"
                               id="photo"
                               name="photo"
                               accept="image/*"
                               capture="environment"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500">
                    </div>
                    <?php endif; ?>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes
                        </label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500"
                                  placeholder="Any additional notes..."><?= e(old('notes')) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="mt-8 flex items-center justify-end space-x-4 pt-6 border-t dark:border-gray-700">
                <a href="<?= url('/gate') ?>"
                   class="px-6 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                    <i class="ti ti-check mr-2"></i>Check In Trailer
                </button>
            </div>
        </form>
    </div>
</div>

<?php
/**
 * TCT-YMS Trailer Controller
 */

namespace App\Controllers;

use App\Models\Trailer;
use App\Models\TrailerStatus;
use App\Models\TrailerHistory;
use App\Models\Carrier;
use App\Models\Customer;
use App\Models\YardSlot;
use App\Models\DockDoor;

class TrailerController extends Controller
{
    /**
     * List trailers
     */
    public function index(): string
    {
        $query = input('q', '');
        $statusId = input('status_id');
        $carrierId = input('carrier_id');
        $inYardOnly = input('in_yard', 1);

        $filters = [
            'in_yard' => $inYardOnly,
        ];

        if ($statusId) {
            $filters['status_id'] = $statusId;
        }

        if ($carrierId) {
            $filters['carrier_id'] = $carrierId;
        }

        $trailers = Trailer::search($query, $filters);

        $statuses = TrailerStatus::getActive();
        $carriers = Carrier::getActive();

        return $this->view('trailers.index', [
            'title' => 'Trailers',
            'trailers' => $trailers,
            'statuses' => $statuses,
            'carriers' => $carriers,
            'query' => $query,
            'statusId' => $statusId,
            'carrierId' => $carrierId,
            'inYardOnly' => $inYardOnly,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): string
    {
        $this->authorize('trailers.create');

        $carriers = Carrier::getActive();
        $customers = Customer::getActive();
        $statuses = TrailerStatus::getActive();
        $slots = YardSlot::getAvailable();
        $doors = DockDoor::getAvailable();

        return $this->view('trailers.create', [
            'title' => 'Add Trailer',
            'carriers' => $carriers,
            'customers' => $customers,
            'statuses' => $statuses,
            'slots' => $slots,
            'doors' => $doors,
        ]);
    }

    /**
     * Store new trailer
     */
    public function store(): never
    {
        $this->authorize('trailers.create');

        $data = $this->validate([
            'trailer_number' => 'required|max:50',
            'carrier_id' => 'required|integer',
            'status_id' => 'required|integer',
        ]);

        $locationType = input('location_type', 'yard_slot');
        $slotId = input('yard_slot_id');
        $doorId = input('dock_door_id');

        $trailer = Trailer::create([
            'trailer_number' => $data['trailer_number'],
            'carrier_id' => $data['carrier_id'],
            'customer_id' => input('customer_id') ?: null,
            'trailer_type' => input('trailer_type', 'dry_van'),
            'trailer_length' => input('trailer_length', 53),
            'status_id' => $data['status_id'],
            'location_type' => $locationType,
            'yard_slot_id' => $locationType === 'yard_slot' ? $slotId : null,
            'dock_door_id' => $locationType === 'dock_door' ? $doorId : null,
            'seal_number' => input('seal_number'),
            'is_loaded' => input('is_loaded', 0),
            'load_type' => input('load_type'),
            'po_numbers' => input('po_numbers'),
            'reference_numbers' => input('reference_numbers'),
            'temperature_setting' => input('temperature_setting'),
            'arrival_time' => input('arrival_time') ?: date('Y-m-d H:i:s'),
            'current_status_since' => date('Y-m-d H:i:s'),
            'priority' => input('priority', 'normal'),
            'notes' => input('notes'),
            'created_by' => $this->user['id'],
        ]);

        // Update slot/door availability
        if ($locationType === 'yard_slot' && $slotId) {
            $slot = YardSlot::find($slotId);
            if ($slot) {
                $slot->is_available = 0;
                $slot->save();
            }
        } elseif ($locationType === 'dock_door' && $doorId) {
            $door = DockDoor::find($doorId);
            if ($door) {
                $door->status = 'occupied';
                $door->current_trailer_id = $trailer->id;
                $door->save();
            }
        }

        flash('success', "Trailer {$trailer->trailer_number} created successfully");
        redirect(url("/trailers/{$trailer->id}"));
    }

    /**
     * Show trailer details
     */
    public function show(int $id): string
    {
        $trailer = Trailer::findOrFail($id);
        $history = $trailer->getHistory();
        $pendingTasks = $trailer->getPendingTasks();
        $allowedTransitions = $trailer->getAllowedTransitions();

        $slots = YardSlot::getAvailable();
        $doors = DockDoor::getAvailable();

        return $this->view('trailers.show', [
            'title' => 'Trailer: ' . $trailer->trailer_number,
            'trailer' => $trailer,
            'history' => $history,
            'pendingTasks' => $pendingTasks,
            'allowedTransitions' => $allowedTransitions,
            'slots' => $slots,
            'doors' => $doors,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(int $id): string
    {
        $this->authorize('trailers.edit');

        $trailer = Trailer::findOrFail($id);
        $carriers = Carrier::getActive();
        $customers = Customer::getActive();
        $statuses = TrailerStatus::getActive();
        $slots = YardSlot::getAvailable();
        $doors = DockDoor::getAvailable();

        // Add current slot/door to lists if not available
        if ($trailer->yard_slot_id) {
            $currentSlot = $trailer->getYardSlot();
            if ($currentSlot && !$currentSlot->is_available) {
                array_unshift($slots, $currentSlot);
            }
        }
        if ($trailer->dock_door_id) {
            $currentDoor = $trailer->getDockDoor();
            if ($currentDoor && $currentDoor->status !== 'available') {
                array_unshift($doors, $currentDoor);
            }
        }

        return $this->view('trailers.edit', [
            'title' => 'Edit: ' . $trailer->trailer_number,
            'trailer' => $trailer,
            'carriers' => $carriers,
            'customers' => $customers,
            'statuses' => $statuses,
            'slots' => $slots,
            'doors' => $doors,
        ]);
    }

    /**
     * Update trailer
     */
    public function update(int $id): never
    {
        $this->authorize('trailers.edit');

        $trailer = Trailer::findOrFail($id);

        $data = $this->validate([
            'trailer_number' => 'required|max:50',
            'carrier_id' => 'required|integer',
        ]);

        $trailer->fill([
            'trailer_number' => $data['trailer_number'],
            'carrier_id' => $data['carrier_id'],
            'customer_id' => input('customer_id') ?: null,
            'trailer_type' => input('trailer_type'),
            'trailer_length' => input('trailer_length'),
            'seal_number' => input('seal_number'),
            'is_loaded' => input('is_loaded', 0),
            'load_type' => input('load_type'),
            'po_numbers' => input('po_numbers'),
            'reference_numbers' => input('reference_numbers'),
            'temperature_setting' => input('temperature_setting'),
            'priority' => input('priority'),
            'notes' => input('notes'),
            'updated_by' => $this->user['id'],
        ]);

        $trailer->save();

        // Log history
        TrailerHistory::create([
            'trailer_id' => $trailer->id,
            'event_type' => 'update',
            'notes' => 'Trailer details updated',
            'user_id' => $this->user['id'],
        ]);

        flash('success', 'Trailer updated successfully');
        redirect(url("/trailers/{$trailer->id}"));
    }

    /**
     * Update trailer status
     */
    public function updateStatus(int $id): never
    {
        $this->authorize('trailers.edit');

        $trailer = Trailer::findOrFail($id);

        $data = $this->validate([
            'status_id' => 'required|integer',
        ]);

        $notes = input('notes');

        if (!$trailer->changeStatus($data['status_id'], $this->user['id'], $notes)) {
            if (isAjax()) {
                $this->error('Invalid status transition');
            }
            $this->backWithError('Invalid status transition');
        }

        if (isAjax()) {
            $this->success(['status' => $trailer->getStatus()->display_name], 'Status updated');
        }

        flash('success', 'Trailer status updated');
        redirect(url("/trailers/{$trailer->id}"));
    }

    /**
     * Delete trailer (soft delete)
     */
    public function delete(int $id): never
    {
        $this->authorize('trailers.delete');

        $trailer = Trailer::findOrFail($id);

        // Release location
        if ($trailer->yard_slot_id) {
            $slot = $trailer->getYardSlot();
            if ($slot) {
                $slot->is_available = 1;
                $slot->save();
            }
        }
        if ($trailer->dock_door_id) {
            $door = $trailer->getDockDoor();
            if ($door) {
                $door->status = 'available';
                $door->current_trailer_id = null;
                $door->save();
            }
        }

        // Soft delete
        $trailer->softDelete();

        flash('success', 'Trailer deleted successfully');
        redirect(url('/trailers'));
    }

    /**
     * Get trailer history
     */
    public function history(int $id): string
    {
        $trailer = Trailer::findOrFail($id);
        $history = TrailerHistory::getForTrailer($id, 100);

        return $this->view('trailers.history', [
            'title' => 'History: ' . $trailer->trailer_number,
            'trailer' => $trailer,
            'history' => $history,
        ]);
    }

    /**
     * Search trailers (AJAX)
     */
    public function search(): never
    {
        $query = input('q', '');
        $trailers = Trailer::search($query, ['in_yard' => true]);

        $results = array_map(function ($trailer) {
            $status = $trailer->getStatus();
            $carrier = $trailer->getCarrier();
            return [
                'id' => $trailer->id,
                'trailer_number' => $trailer->trailer_number,
                'carrier' => $carrier ? $carrier->name : 'Unknown',
                'location' => $trailer->getLocationString(),
                'status' => $status ? $status->display_name : 'Unknown',
                'status_color' => $status ? $status->color : '#6B7280',
            ];
        }, $trailers);

        $this->json(['results' => $results]);
    }

    /**
     * Export trailers
     */
    public function export(): never
    {
        $this->authorize('reports.export');

        $trailers = Trailer::getInYard();

        $data = array_map(function ($trailer) {
            $carrier = $trailer->getCarrier();
            $status = $trailer->getStatus();
            return [
                'Trailer Number' => $trailer->trailer_number,
                'Carrier' => $carrier ? $carrier->name : '',
                'Status' => $status ? $status->display_name : '',
                'Location' => $trailer->getLocationString(),
                'Arrival Time' => formatDateTime($trailer->arrival_time),
                'Dwell Hours' => $trailer->getDwellTime(),
                'Loaded' => $trailer->is_loaded ? 'Yes' : 'No',
                'Priority' => ucfirst($trailer->priority),
                'Notes' => $trailer->notes,
            ];
        }, $trailers);

        $filename = 'yard_inventory_' . date('Y-m-d_His') . '.csv';
        $this->exportCsv($data, $filename);
    }
}

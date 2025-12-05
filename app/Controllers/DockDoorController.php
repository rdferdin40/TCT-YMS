<?php
/**
 * TCT-YMS Dock Door Controller
 */

namespace App\Controllers;

use App\Models\DockDoor;
use App\Models\Trailer;
use App\Models\TrailerStatus;

class DockDoorController extends Controller
{
    /**
     * List dock doors
     */
    public function index(): string
    {
        $doors = DockDoor::getActive();
        $stats = DockDoor::getStats();
        $doorStatuses = TrailerStatus::getDoorStatuses();

        return $this->view('dock-doors.index', [
            'title' => 'Dock Doors',
            'doors' => $doors,
            'stats' => $stats,
            'doorStatuses' => $doorStatuses,
        ]);
    }

    /**
     * Get dock door data (AJAX)
     */
    public function getData(): never
    {
        $doors = [];

        foreach (DockDoor::getActive() as $door) {
            $trailer = $door->getCurrentTrailer();
            $doorData = [
                'id' => $door->id,
                'door_number' => $door->door_number,
                'name' => $door->getDisplayName(),
                'door_type' => $door->door_type,
                'status' => $door->status,
                'status_color' => $door->getStatusColor(),
                'status_bg_color' => $door->getStatusBgColor(),
                'trailer' => null,
            ];

            if ($trailer) {
                $status = $trailer->getStatus();
                $carrier = $trailer->getCarrier();
                $doorData['trailer'] = [
                    'id' => $trailer->id,
                    'trailer_number' => $trailer->trailer_number,
                    'carrier' => $carrier ? $carrier->name : null,
                    'status' => $status ? $status->display_name : null,
                    'status_color' => $status ? $status->color : '#6B7280',
                    'dwell_hours' => $trailer->getStatusDwellTime(),
                    'dwell_status' => $trailer->getDwellStatus(),
                ];
            }

            $doors[] = $doorData;
        }

        $this->json([
            'success' => true,
            'doors' => $doors,
            'stats' => DockDoor::getStats(),
        ]);
    }

    /**
     * Assign trailer to door
     */
    public function assign(int $id): never
    {
        $this->authorize('dock_doors.assign');

        $door = DockDoor::findOrFail($id);

        $data = $this->validate([
            'trailer_id' => 'required|integer',
        ]);

        $trailer = Trailer::findOrFail($data['trailer_id']);

        if (!$door->isAvailable()) {
            if (isAjax()) {
                $this->error('Door is not available');
            }
            $this->backWithError('Door is not available');
        }

        if (!$trailer->moveToDoor($door->id, $this->user['id'])) {
            if (isAjax()) {
                $this->error('Failed to assign trailer to door');
            }
            $this->backWithError('Failed to assign trailer to door');
        }

        // Update trailer status to "at_door" if applicable
        $atDoorStatus = TrailerStatus::findByName('at_door');
        if ($atDoorStatus && $trailer->canTransitionTo($atDoorStatus->id)) {
            $trailer->changeStatus($atDoorStatus->id, $this->user['id'], 'Assigned to door');
        }

        if (isAjax()) {
            $this->success(null, 'Trailer assigned to door');
        }

        flash('success', "Trailer {$trailer->trailer_number} assigned to Door {$door->door_number}");
        redirect(url('/dock-doors'));
    }

    /**
     * Release trailer from door
     */
    public function release(int $id): never
    {
        $this->authorize('dock_doors.assign');

        $door = DockDoor::findOrFail($id);
        $trailer = $door->getCurrentTrailer();

        if (!$trailer) {
            if (isAjax()) {
                $this->error('No trailer at this door');
            }
            $this->backWithError('No trailer at this door');
        }

        // Get destination slot if provided
        $slotId = input('yard_slot_id');

        if (!$trailer->releaseFromDoor($this->user['id'])) {
            if (isAjax()) {
                $this->error('Failed to release trailer from door');
            }
            $this->backWithError('Failed to release trailer from door');
        }

        // Move to slot if provided
        if ($slotId) {
            $trailer->moveToSlot($slotId, $this->user['id'], 'Released from door');
        }

        if (isAjax()) {
            $this->success(null, 'Trailer released from door');
        }

        flash('success', "Trailer released from Door {$door->door_number}");
        redirect(url('/dock-doors'));
    }

    /**
     * Update door status (maintenance, etc)
     */
    public function updateStatus(int $id): never
    {
        $this->authorize('dock_doors.manage');

        $door = DockDoor::findOrFail($id);

        $data = $this->validate([
            'status' => 'required|in:available,maintenance,disabled',
        ]);

        // Cannot change status if occupied
        if ($door->isOccupied() && $data['status'] !== 'occupied') {
            if (isAjax()) {
                $this->error('Cannot change status while door is occupied');
            }
            $this->backWithError('Cannot change status while door is occupied');
        }

        $door->status = $data['status'];
        $door->notes = input('notes');
        $door->save();

        if (isAjax()) {
            $this->success(null, 'Door status updated');
        }

        flash('success', 'Door status updated');
        redirect(url('/dock-doors'));
    }
}

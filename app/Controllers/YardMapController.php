<?php
/**
 * TCT-YMS Yard Map Controller
 */

namespace App\Controllers;

use App\Models\Trailer;
use App\Models\TrailerStatus;
use App\Models\DockDoor;
use App\Models\YardZone;
use App\Models\YardRow;
use App\Models\YardSlot;
use App\Models\MoveTask;
use App\Models\Setting;

class YardMapController extends Controller
{
    /**
     * Show yard map
     */
    public function index(): string
    {
        $zones = YardZone::getActive();
        $rows = YardRow::getActive();
        $doors = DockDoor::getActive();
        $statuses = TrailerStatus::getActive();

        // Auto-refresh interval
        $refreshInterval = Setting::get('auto_refresh_interval', 60);

        return $this->view('yard-map.index', [
            'title' => 'Yard Map',
            'zones' => $zones,
            'rows' => $rows,
            'doors' => $doors,
            'statuses' => $statuses,
            'refreshInterval' => $refreshInterval,
            'canEdit' => can('yard_map.edit'),
        ]);
    }

    /**
     * Get yard map data (AJAX)
     */
    public function getData(): never
    {
        // Get all zones with rows and slots
        $zones = [];
        foreach (YardZone::getActive() as $zone) {
            $zoneData = [
                'id' => $zone->id,
                'name' => $zone->name,
                'code' => $zone->code,
                'color' => $zone->color,
                'rows' => [],
            ];

            foreach ($zone->getRows() as $row) {
                $rowData = [
                    'id' => $row->id,
                    'name' => $row->name,
                    'code' => $row->code,
                    'grid_row' => $row->grid_row,
                    'orientation' => $row->orientation,
                    'slots' => [],
                ];

                foreach ($row->getSlots() as $slot) {
                    $trailer = $slot->getTrailer();
                    $slotData = [
                        'id' => $slot->id,
                        'label' => $slot->label,
                        'slot_number' => $slot->slot_number,
                        'slot_type' => $slot->slot_type,
                        'grid_x' => $slot->grid_x,
                        'grid_y' => $slot->grid_y,
                        'is_available' => (bool) $slot->is_available,
                        'trailer' => null,
                    ];

                    if ($trailer) {
                        $status = $trailer->getStatus();
                        $carrier = $trailer->getCarrier();
                        $slotData['trailer'] = [
                            'id' => $trailer->id,
                            'trailer_number' => $trailer->trailer_number,
                            'carrier' => $carrier ? $carrier->name : null,
                            'carrier_code' => $carrier ? $carrier->code : null,
                            'status' => $status ? $status->name : null,
                            'status_display' => $status ? $status->display_name : null,
                            'status_color' => $status ? $status->color : '#6B7280',
                            'status_bg_color' => $status ? $status->bg_color : '#F3F4F6',
                            'dwell_hours' => $trailer->getDwellTime(),
                            'dwell_status' => $trailer->getDwellStatus(),
                            'is_loaded' => (bool) $trailer->is_loaded,
                            'priority' => $trailer->priority,
                        ];
                    }

                    $rowData['slots'][] = $slotData;
                }

                $zoneData['rows'][] = $rowData;
            }

            $zones[] = $zoneData;
        }

        // Get dock doors
        $doors = [];
        foreach (DockDoor::getActive() as $door) {
            $trailer = $door->getCurrentTrailer();
            $doorData = [
                'id' => $door->id,
                'door_number' => $door->door_number,
                'name' => $door->getDisplayName(),
                'door_type' => $door->door_type,
                'grid_x' => $door->grid_x,
                'grid_y' => $door->grid_y,
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
                    'carrier_code' => $carrier ? $carrier->code : null,
                    'status' => $status ? $status->name : null,
                    'status_display' => $status ? $status->display_name : null,
                    'status_color' => $status ? $status->color : '#6B7280',
                    'dwell_hours' => $trailer->getStatusDwellTime(),
                    'dwell_status' => $trailer->getDwellStatus(),
                    'is_loaded' => (bool) $trailer->is_loaded,
                ];
            }

            $doors[] = $doorData;
        }

        // Get available slots for moves
        $availableSlots = [];
        foreach (YardSlot::getAvailable() as $slot) {
            $availableSlots[] = [
                'id' => $slot->id,
                'label' => $slot->label,
                'row_id' => $slot->row_id,
            ];
        }

        // Get available doors for moves
        $availableDoors = [];
        foreach (DockDoor::getAvailable() as $door) {
            $availableDoors[] = [
                'id' => $door->id,
                'door_number' => $door->door_number,
                'name' => $door->getDisplayName(),
            ];
        }

        // Get status colors for legend
        $statusColors = [];
        foreach (TrailerStatus::getActive() as $status) {
            $statusColors[$status->name] = [
                'name' => $status->name,
                'display_name' => $status->display_name,
                'color' => $status->color,
                'bg_color' => $status->bg_color,
            ];
        }

        $this->json([
            'success' => true,
            'data' => [
                'zones' => $zones,
                'doors' => $doors,
                'available_slots' => $availableSlots,
                'available_doors' => $availableDoors,
                'status_colors' => $statusColors,
                'stats' => [
                    'yard' => Trailer::getYardStats(),
                    'doors' => DockDoor::getStats(),
                    'slots' => YardSlot::getCapacityStats(),
                ],
            ],
        ]);
    }

    /**
     * Move trailer (drag-and-drop)
     */
    public function moveTrailer(): never
    {
        // Check permission
        if (!can('yard_map.edit')) {
            $this->error('Permission denied', 403);
        }

        $data = $this->validate([
            'trailer_id' => 'required|integer',
            'destination_type' => 'required|in:slot,door',
            'destination_id' => 'required|integer',
        ]);

        $trailer = Trailer::find($data['trailer_id']);
        if (!$trailer) {
            $this->error('Trailer not found', 404);
        }

        $createTask = input('create_task', false);

        if ($createTask) {
            // Create a move task instead of immediate move
            $fromLocationType = $trailer->location_type;
            $fromSlotId = $trailer->yard_slot_id;
            $fromDoorId = $trailer->dock_door_id;

            MoveTask::create([
                'trailer_id' => $trailer->id,
                'task_type' => $data['destination_type'] === 'door' ? 'move_to_door' : 'move_to_slot',
                'priority' => input('priority', 'normal'),
                'status' => 'pending',
                'from_location_type' => $fromLocationType,
                'from_slot_id' => $fromSlotId,
                'from_door_id' => $fromDoorId,
                'from_location_text' => $trailer->getLocationString(),
                'to_location_type' => $data['destination_type'] === 'door' ? 'dock_door' : 'yard_slot',
                'to_slot_id' => $data['destination_type'] === 'slot' ? $data['destination_id'] : null,
                'to_door_id' => $data['destination_type'] === 'door' ? $data['destination_id'] : null,
                'instructions' => input('instructions'),
                'created_by' => $this->user['id'],
            ]);

            $this->success(null, 'Move task created');
        } else {
            // Immediate move
            if ($data['destination_type'] === 'slot') {
                if (!$trailer->moveToSlot($data['destination_id'], $this->user['id'])) {
                    $this->error('Failed to move trailer to slot');
                }
            } else {
                if (!$trailer->moveToDoor($data['destination_id'], $this->user['id'])) {
                    $this->error('Failed to move trailer to door');
                }
            }

            $this->success([
                'trailer_id' => $trailer->id,
                'new_location' => $trailer->getLocationString(),
            ], 'Trailer moved successfully');
        }
    }
}

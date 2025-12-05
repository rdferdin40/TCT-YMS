<?php
/**
 * TCT-YMS Move Task Controller
 */

namespace App\Controllers;

use App\Models\MoveTask;
use App\Models\Trailer;
use App\Models\YardSlot;
use App\Models\DockDoor;
use App\Models\User;

class MoveTaskController extends Controller
{
    /**
     * List move tasks
     */
    public function index(): string
    {
        $status = input('status', 'active');

        if ($status === 'active') {
            $tasks = MoveTask::getActive();
        } elseif ($status === 'completed') {
            $tasks = MoveTask::hydrate(
                MoveTask::query()
                    ->where('status', 'completed')
                    ->orderBy('completed_at', 'DESC')
                    ->limit(50)
                    ->get()
            );
        } else {
            $tasks = MoveTask::hydrate(
                MoveTask::query()
                    ->where('status', $status)
                    ->orderBy('created_at', 'DESC')
                    ->get()
            );
        }

        $stats = MoveTask::getStats();
        $spotters = User::getSpotters();

        return $this->view('moves.index', [
            'title' => 'Move Tasks',
            'tasks' => $tasks,
            'stats' => $stats,
            'spotters' => $spotters,
            'status' => $status,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): string
    {
        $this->authorize('moves.create');

        $trailers = Trailer::getInYard();
        $slots = YardSlot::getAvailable();
        $doors = DockDoor::getAvailable();
        $spotters = User::getSpotters();

        // Pre-select trailer if provided
        $trailerId = input('trailer_id');
        $selectedTrailer = $trailerId ? Trailer::find($trailerId) : null;

        return $this->view('moves.create', [
            'title' => 'Create Move Task',
            'trailers' => $trailers,
            'slots' => $slots,
            'doors' => $doors,
            'spotters' => $spotters,
            'selectedTrailer' => $selectedTrailer,
        ]);
    }

    /**
     * Store new move task
     */
    public function store(): never
    {
        $this->authorize('moves.create');

        $data = $this->validate([
            'trailer_id' => 'required|integer',
            'to_location_type' => 'required|in:yard_slot,dock_door',
        ]);

        $trailer = Trailer::findOrFail($data['trailer_id']);

        // Determine destination
        $toSlotId = null;
        $toDoorId = null;

        if ($data['to_location_type'] === 'yard_slot') {
            $toSlotId = input('to_slot_id');
            if (!$toSlotId) {
                $this->backWithError('Please select a destination slot');
            }
        } else {
            $toDoorId = input('to_door_id');
            if (!$toDoorId) {
                $this->backWithError('Please select a destination door');
            }
        }

        // Get destination text
        $toLocationText = '';
        if ($toSlotId) {
            $slot = YardSlot::find($toSlotId);
            $toLocationText = $slot ? $slot->label : '';
        } elseif ($toDoorId) {
            $door = DockDoor::find($toDoorId);
            $toLocationText = $door ? 'Door ' . $door->door_number : '';
        }

        $task = MoveTask::create([
            'trailer_id' => $trailer->id,
            'task_type' => $data['to_location_type'] === 'dock_door' ? 'move_to_door' : 'move_to_slot',
            'priority' => input('priority', 'normal'),
            'status' => 'pending',
            'from_location_type' => $trailer->location_type,
            'from_slot_id' => $trailer->yard_slot_id,
            'from_door_id' => $trailer->dock_door_id,
            'from_location_text' => $trailer->getLocationString(),
            'to_location_type' => $data['to_location_type'],
            'to_slot_id' => $toSlotId,
            'to_door_id' => $toDoorId,
            'to_location_text' => $toLocationText,
            'assigned_to' => input('assigned_to') ?: null,
            'assigned_at' => input('assigned_to') ? date('Y-m-d H:i:s') : null,
            'estimated_minutes' => input('estimated_minutes', 10),
            'instructions' => input('instructions'),
            'notes' => input('notes'),
            'created_by' => $this->user['id'],
        ]);

        if (input('assigned_to')) {
            $task->status = 'assigned';
            $task->save();
        }

        flash('success', 'Move task created successfully');
        redirect(url('/moves'));
    }

    /**
     * Show task details
     */
    public function show(int $id): string
    {
        $task = MoveTask::findOrFail($id);
        $spotters = User::getSpotters();

        return $this->view('moves.show', [
            'title' => 'Task #' . $task->id,
            'task' => $task,
            'spotters' => $spotters,
        ]);
    }

    /**
     * Assign task to spotter
     */
    public function assign(int $id): never
    {
        $this->authorize('moves.assign');

        $task = MoveTask::findOrFail($id);

        $data = $this->validate([
            'assigned_to' => 'required|integer',
        ]);

        if (!$task->assignTo($data['assigned_to'])) {
            if (isAjax()) {
                $this->error('Cannot assign this task');
            }
            $this->backWithError('Cannot assign this task');
        }

        if (isAjax()) {
            $this->success(null, 'Task assigned');
        }

        flash('success', 'Task assigned successfully');
        redirect(url('/moves'));
    }

    /**
     * Claim task (spotter claims from pool)
     */
    public function claim(int $id): never
    {
        $this->authorize('moves.claim');

        $task = MoveTask::findOrFail($id);

        if (!$task->claim($this->user['id'])) {
            if (isAjax()) {
                $this->error('Cannot claim this task');
            }
            $this->backWithError('Cannot claim this task');
        }

        if (isAjax()) {
            $this->success(null, 'Task claimed');
        }

        flash('success', 'Task claimed successfully');
        redirect(url('/spotter/tasks'));
    }

    /**
     * Start task
     */
    public function start(int $id): never
    {
        $this->authorize('moves.complete');

        $task = MoveTask::findOrFail($id);

        // Verify user is assigned or can claim
        if ($task->assigned_to && $task->assigned_to !== $this->user['id'] && !hasAnyRole(['admin', 'supervisor'])) {
            if (isAjax()) {
                $this->error('This task is assigned to another spotter');
            }
            $this->backWithError('This task is assigned to another spotter');
        }

        if (!$task->start($this->user['id'])) {
            if (isAjax()) {
                $this->error('Cannot start this task');
            }
            $this->backWithError('Cannot start this task');
        }

        if (isAjax()) {
            $this->success(null, 'Task started');
        }

        flash('success', 'Task started');
        redirect(url("/spotter/task/{$id}"));
    }

    /**
     * Complete task
     */
    public function complete(int $id): never
    {
        $this->authorize('moves.complete');

        $task = MoveTask::findOrFail($id);

        // Verify user is assigned
        if ($task->assigned_to !== $this->user['id'] && !hasAnyRole(['admin', 'supervisor'])) {
            if (isAjax()) {
                $this->error('You are not assigned to this task');
            }
            $this->backWithError('You are not assigned to this task');
        }

        $notes = input('notes');

        if (!$task->complete($notes)) {
            if (isAjax()) {
                $this->error('Cannot complete this task');
            }
            $this->backWithError('Cannot complete this task');
        }

        if (isAjax()) {
            $this->success(null, 'Task completed');
        }

        flash('success', 'Task completed successfully');

        // Redirect based on role
        if (hasRole('spotter')) {
            redirect(url('/spotter/tasks'));
        }
        redirect(url('/moves'));
    }

    /**
     * Cancel task
     */
    public function cancel(int $id): never
    {
        $this->authorize('moves.create'); // Same permission as create

        $task = MoveTask::findOrFail($id);

        $reason = input('reason', '');

        if (!$task->cancel($reason)) {
            if (isAjax()) {
                $this->error('Cannot cancel this task');
            }
            $this->backWithError('Cannot cancel this task');
        }

        if (isAjax()) {
            $this->success(null, 'Task cancelled');
        }

        flash('success', 'Task cancelled');
        redirect(url('/moves'));
    }

    /**
     * Get task queue (AJAX)
     */
    public function queue(): never
    {
        $tasks = MoveTask::getActive();

        $result = array_map(function ($task) {
            $trailer = $task->getTrailer();
            $assignee = $task->getAssignedUser();
            return [
                'id' => $task->id,
                'trailer_number' => $trailer ? $trailer->trailer_number : 'N/A',
                'from' => $task->getFromLocationString(),
                'to' => $task->getToLocationString(),
                'priority' => $task->priority,
                'priority_color' => $task->getPriorityColor(),
                'status' => $task->status,
                'status_color' => $task->getStatusColor(),
                'assigned_to' => $assignee ? $assignee->getFullName() : null,
                'created_at' => formatDateTime($task->created_at),
            ];
        }, $tasks);

        $this->json([
            'success' => true,
            'tasks' => $result,
            'stats' => MoveTask::getStats(),
        ]);
    }
}

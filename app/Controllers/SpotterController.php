<?php
/**
 * TCT-YMS Spotter Controller (Mobile Interface)
 */

namespace App\Controllers;

use App\Models\MoveTask;
use App\Models\Trailer;
use App\Models\YardSlot;
use App\Models\DockDoor;

class SpotterController extends Controller
{
    protected string $layout = 'layouts.spotter';

    /**
     * Spotter dashboard
     */
    public function index(): string
    {
        $myTasks = MoveTask::getForUser($this->user['id']);
        $pendingTasks = MoveTask::getPending();
        $stats = MoveTask::getStats();

        return $this->view('spotter.index', [
            'title' => 'Spotter Dashboard',
            'myTasks' => $myTasks,
            'pendingTasks' => $pendingTasks,
            'stats' => $stats,
        ]);
    }

    /**
     * Task list
     */
    public function tasks(): string
    {
        $myTasks = MoveTask::getForUser($this->user['id']);
        $poolTasks = MoveTask::getPending();

        return $this->view('spotter.tasks', [
            'title' => 'My Tasks',
            'myTasks' => $myTasks,
            'poolTasks' => $poolTasks,
        ]);
    }

    /**
     * Show single task
     */
    public function showTask(int $id): string
    {
        $task = MoveTask::findOrFail($id);
        $trailer = $task->getTrailer();

        return $this->view('spotter.task', [
            'title' => 'Task #' . $task->id,
            'task' => $task,
            'trailer' => $trailer,
        ]);
    }

    /**
     * Start task
     */
    public function startTask(int $id): never
    {
        $task = MoveTask::findOrFail($id);

        // Check if task is assigned to this spotter or unassigned
        if ($task->assigned_to && $task->assigned_to !== $this->user['id']) {
            if (isAjax()) {
                $this->error('This task is assigned to another spotter');
            }
            flash('error', 'This task is assigned to another spotter');
            redirect(url('/spotter/tasks'));
        }

        if (!$task->start($this->user['id'])) {
            if (isAjax()) {
                $this->error('Cannot start this task');
            }
            flash('error', 'Cannot start this task');
            redirect(url('/spotter/tasks'));
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
    public function completeTask(int $id): never
    {
        $task = MoveTask::findOrFail($id);

        // Verify this spotter is assigned
        if ($task->assigned_to !== $this->user['id']) {
            if (isAjax()) {
                $this->error('You are not assigned to this task');
            }
            flash('error', 'You are not assigned to this task');
            redirect(url('/spotter/tasks'));
        }

        $notes = input('notes');

        if (!$task->complete($notes)) {
            if (isAjax()) {
                $this->error('Cannot complete this task');
            }
            flash('error', 'Cannot complete this task');
            redirect(url("/spotter/task/{$id}"));
        }

        if (isAjax()) {
            $this->success(null, 'Task completed');
        }

        flash('success', 'Great job! Task completed.');
        redirect(url('/spotter/tasks'));
    }

    /**
     * Simple yard view for spotters
     */
    public function yardView(): string
    {
        $slots = YardSlot::hydrate(YardSlot::query()->orderBy('row_id')->orderBy('slot_number')->get());
        $doors = DockDoor::getActive();

        // Group slots by row
        $slotsByRow = [];
        foreach ($slots as $slot) {
            $row = $slot->getRow();
            $rowCode = $row ? $row->code : 'Unknown';
            if (!isset($slotsByRow[$rowCode])) {
                $slotsByRow[$rowCode] = [];
            }
            $slotsByRow[$rowCode][] = $slot;
        }

        return $this->view('spotter.yard-view', [
            'title' => 'Yard View',
            'slotsByRow' => $slotsByRow,
            'doors' => $doors,
        ]);
    }
}

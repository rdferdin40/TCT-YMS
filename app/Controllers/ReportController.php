<?php
/**
 * TCT-YMS Report Controller
 */

namespace App\Controllers;

use App\Models\Trailer;
use App\Models\TrailerStatus;
use App\Models\TrailerHistory;
use App\Models\GateEvent;
use App\Models\MoveTask;
use App\Models\DockDoor;
use App\Database;

class ReportController extends Controller
{
    /**
     * Reports index
     */
    public function index(): string
    {
        return $this->view('reports.index', [
            'title' => 'Reports',
        ]);
    }

    /**
     * Yard inventory report
     */
    public function yardInventory(): string
    {
        $trailers = Trailer::getInYard();
        $statusBreakdown = TrailerStatus::getBreakdown();
        $slotStats = \App\Models\YardSlot::getCapacityStats();
        $doorStats = DockDoor::getStats();

        return $this->view('reports.yard-inventory', [
            'title' => 'Yard Inventory Report',
            'trailers' => $trailers,
            'statusBreakdown' => $statusBreakdown,
            'slotStats' => $slotStats,
            'doorStats' => $doorStats,
        ]);
    }

    /**
     * Gate activity report
     */
    public function gateActivity(): string
    {
        $startDate = input('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = input('end_date', date('Y-m-d'));

        $events = GateEvent::getForDateRange($startDate, $endDate);

        // Group by day
        $dailyStats = Database::fetchAll(
            "SELECT DATE(created_at) as date,
                    SUM(CASE WHEN event_type = 'check_in' THEN 1 ELSE 0 END) as check_ins,
                    SUM(CASE WHEN event_type = 'check_out' THEN 1 ELSE 0 END) as check_outs
             FROM gate_events
             WHERE DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY date",
            [$startDate, $endDate]
        );

        return $this->view('reports.gate-activity', [
            'title' => 'Gate Activity Report',
            'events' => $events,
            'dailyStats' => $dailyStats,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Move history report
     */
    public function moveHistory(): string
    {
        $startDate = input('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = input('end_date', date('Y-m-d'));
        $spotterId = input('spotter_id');

        $query = MoveTask::query()
            ->whereRaw('DATE(created_at) BETWEEN ? AND ?', [$startDate, $endDate]);

        if ($spotterId) {
            $query->where('assigned_to', $spotterId);
        }

        $tasks = MoveTask::hydrate($query->orderBy('created_at', 'DESC')->get());

        // Spotter productivity
        $spotterStats = MoveTask::getSpotterProductivity();

        // Daily move stats
        $dailyStats = Database::fetchAll(
            "SELECT DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    AVG(actual_minutes) as avg_time
             FROM move_tasks
             WHERE DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY date",
            [$startDate, $endDate]
        );

        $spotters = \App\Models\User::getSpotters();

        return $this->view('reports.move-history', [
            'title' => 'Move History Report',
            'tasks' => $tasks,
            'spotterStats' => $spotterStats,
            'dailyStats' => $dailyStats,
            'spotters' => $spotters,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'spotterId' => $spotterId,
        ]);
    }

    /**
     * Dwell time report
     */
    public function dwellTime(): string
    {
        $trailers = Trailer::getInYard();

        // Sort by dwell time descending
        usort($trailers, fn($a, $b) => $b->getDwellTime() <=> $a->getDwellTime());

        // Calculate stats
        $dwellTimes = array_map(fn($t) => $t->getDwellTime(), $trailers);
        $avgDwell = count($dwellTimes) > 0 ? array_sum($dwellTimes) / count($dwellTimes) : 0;
        $maxDwell = count($dwellTimes) > 0 ? max($dwellTimes) : 0;
        $minDwell = count($dwellTimes) > 0 ? min($dwellTimes) : 0;

        // Count by dwell ranges
        $ranges = [
            '0-4h' => count(array_filter($trailers, fn($t) => $t->getDwellTime() < 4)),
            '4-8h' => count(array_filter($trailers, fn($t) => $t->getDwellTime() >= 4 && $t->getDwellTime() < 8)),
            '8-24h' => count(array_filter($trailers, fn($t) => $t->getDwellTime() >= 8 && $t->getDwellTime() < 24)),
            '24h+' => count(array_filter($trailers, fn($t) => $t->getDwellTime() >= 24)),
        ];

        // Get status-specific dwell
        $statusDwell = [];
        foreach (TrailerStatus::getActive() as $status) {
            if (!$status->is_final_status) {
                $statusTrailers = array_filter($trailers, fn($t) => $t->status_id === $status->id);
                if (count($statusTrailers) > 0) {
                    $times = array_map(fn($t) => $t->getStatusDwellTime(), $statusTrailers);
                    $statusDwell[] = [
                        'status' => $status->display_name,
                        'color' => $status->color,
                        'count' => count($statusTrailers),
                        'avg_dwell' => round(array_sum($times) / count($times), 1),
                    ];
                }
            }
        }

        return $this->view('reports.dwell-time', [
            'title' => 'Dwell Time Report',
            'trailers' => $trailers,
            'avgDwell' => round($avgDwell, 1),
            'maxDwell' => round($maxDwell, 1),
            'minDwell' => round($minDwell, 1),
            'ranges' => $ranges,
            'statusDwell' => $statusDwell,
        ]);
    }

    /**
     * Export report
     */
    public function export(string $type): never
    {
        $this->authorize('reports.export');

        $startDate = input('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = input('end_date', date('Y-m-d'));

        switch ($type) {
            case 'yard-inventory':
                $data = $this->exportYardInventory();
                $filename = 'yard_inventory_' . date('Y-m-d_His') . '.csv';
                break;

            case 'gate-activity':
                $data = $this->exportGateActivity($startDate, $endDate);
                $filename = 'gate_activity_' . date('Y-m-d_His') . '.csv';
                break;

            case 'move-history':
                $data = $this->exportMoveHistory($startDate, $endDate);
                $filename = 'move_history_' . date('Y-m-d_His') . '.csv';
                break;

            case 'dwell-time':
                $data = $this->exportDwellTime();
                $filename = 'dwell_time_' . date('Y-m-d_His') . '.csv';
                break;

            default:
                abort(404, 'Invalid export type');
        }

        $this->exportCsv($data, $filename);
    }

    private function exportYardInventory(): array
    {
        $trailers = Trailer::getInYard();

        return array_map(function ($trailer) {
            $carrier = $trailer->getCarrier();
            $status = $trailer->getStatus();
            return [
                'Trailer Number' => $trailer->trailer_number,
                'Carrier' => $carrier ? $carrier->name : '',
                'Status' => $status ? $status->display_name : '',
                'Location' => $trailer->getLocationString(),
                'Arrival Time' => formatDateTime($trailer->arrival_time),
                'Dwell Hours' => round($trailer->getDwellTime(), 1),
                'Dwell Status' => ucfirst($trailer->getDwellStatus()),
                'Loaded' => $trailer->is_loaded ? 'Yes' : 'No',
                'Priority' => ucfirst($trailer->priority),
                'Seal Number' => $trailer->seal_number ?? '',
            ];
        }, $trailers);
    }

    private function exportGateActivity(string $startDate, string $endDate): array
    {
        $events = GateEvent::getForDateRange($startDate, $endDate);

        return array_map(function ($event) {
            $trailer = $event->getTrailer();
            $carrier = $event->getCarrier();
            return [
                'Date/Time' => formatDateTime($event->created_at),
                'Event Type' => $event->event_type === 'check_in' ? 'Check In' : 'Check Out',
                'Trailer Number' => $trailer ? $trailer->trailer_number : '',
                'Carrier' => $carrier ? $carrier->name : '',
                'Driver Name' => $event->driver_name ?? '',
                'Tractor Number' => $event->tractor_number ?? '',
                'Seal Number' => $event->seal_number ?? '',
                'Loaded' => $event->is_loaded ? 'Yes' : 'No',
            ];
        }, $events);
    }

    private function exportMoveHistory(string $startDate, string $endDate): array
    {
        $tasks = MoveTask::hydrate(
            MoveTask::query()
                ->whereRaw('DATE(created_at) BETWEEN ? AND ?', [$startDate, $endDate])
                ->orderBy('created_at', 'DESC')
                ->get()
        );

        return array_map(function ($task) {
            $trailer = $task->getTrailer();
            $assignee = $task->getAssignedUser();
            return [
                'Task ID' => $task->id,
                'Trailer Number' => $trailer ? $trailer->trailer_number : '',
                'From' => $task->getFromLocationString(),
                'To' => $task->getToLocationString(),
                'Priority' => ucfirst($task->priority),
                'Status' => ucfirst($task->status),
                'Assigned To' => $assignee ? $assignee->getFullName() : '',
                'Created At' => formatDateTime($task->created_at),
                'Started At' => $task->started_at ? formatDateTime($task->started_at) : '',
                'Completed At' => $task->completed_at ? formatDateTime($task->completed_at) : '',
                'Actual Minutes' => $task->actual_minutes ?? '',
            ];
        }, $tasks);
    }

    private function exportDwellTime(): array
    {
        $trailers = Trailer::getInYard();

        usort($trailers, fn($a, $b) => $b->getDwellTime() <=> $a->getDwellTime());

        return array_map(function ($trailer) {
            $carrier = $trailer->getCarrier();
            $status = $trailer->getStatus();
            return [
                'Trailer Number' => $trailer->trailer_number,
                'Carrier' => $carrier ? $carrier->name : '',
                'Status' => $status ? $status->display_name : '',
                'Location' => $trailer->getLocationString(),
                'Arrival Time' => formatDateTime($trailer->arrival_time),
                'Total Dwell Hours' => round($trailer->getDwellTime(), 1),
                'Status Dwell Hours' => round($trailer->getStatusDwellTime(), 1),
                'Dwell Status' => ucfirst($trailer->getDwellStatus()),
                'Warning Threshold' => $status ? $status->dwell_warning_hours : '',
                'Critical Threshold' => $status ? $status->dwell_critical_hours : '',
            ];
        }, $trailers);
    }
}

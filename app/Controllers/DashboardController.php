<?php
/**
 * TCT-YMS Dashboard Controller
 */

namespace App\Controllers;

use App\Models\Trailer;
use App\Models\TrailerStatus;
use App\Models\DockDoor;
use App\Models\YardSlot;
use App\Models\MoveTask;
use App\Models\GateEvent;
use App\Models\Setting;

class DashboardController extends Controller
{
    /**
     * Show dashboard
     */
    public function index(): string
    {
        // Get yard statistics
        $yardStats = Trailer::getYardStats();
        $doorStats = DockDoor::getStats();
        $slotStats = YardSlot::getCapacityStats();
        $taskStats = MoveTask::getStats();
        $gateStats = GateEvent::getStats();

        // Get status breakdown for chart
        $statusBreakdown = TrailerStatus::getBreakdown();

        // Get recent activity
        $recentTrailers = Trailer::hydrate(
            Trailer::query()
                ->whereNull('deleted_at')
                ->orderBy('arrival_time', 'DESC')
                ->limit(10)
                ->get()
        );

        // Get dwell warnings
        $dwellWarnings = Trailer::getWithDwellWarnings();

        // Auto-refresh interval
        $refreshInterval = Setting::get('auto_refresh_interval', 60);

        return $this->view('dashboard.index', [
            'title' => 'Dashboard',
            'yardStats' => $yardStats,
            'doorStats' => $doorStats,
            'slotStats' => $slotStats,
            'taskStats' => $taskStats,
            'gateStats' => $gateStats,
            'statusBreakdown' => $statusBreakdown,
            'recentTrailers' => $recentTrailers,
            'dwellWarnings' => $dwellWarnings,
            'refreshInterval' => $refreshInterval,
        ]);
    }

    /**
     * Get dashboard stats (AJAX)
     */
    public function stats(): never
    {
        $this->json([
            'success' => true,
            'data' => [
                'yard' => Trailer::getYardStats(),
                'doors' => DockDoor::getStats(),
                'slots' => YardSlot::getCapacityStats(),
                'tasks' => MoveTask::getStats(),
                'gate' => GateEvent::getStats(),
                'status_breakdown' => TrailerStatus::getBreakdown(),
            ],
        ]);
    }
}

<?php
/**
 * TCT-YMS Gate Controller
 */

namespace App\Controllers;

use App\Models\Trailer;
use App\Models\TrailerStatus;
use App\Models\TrailerHistory;
use App\Models\GateEvent;
use App\Models\Carrier;
use App\Models\YardSlot;
use App\Models\Setting;

class GateController extends Controller
{
    /**
     * Gate module index
     */
    public function index(): string
    {
        $todayEvents = GateEvent::getToday();
        $stats = GateEvent::getStats();

        return $this->view('gate.index', [
            'title' => 'Gate',
            'todayEvents' => $todayEvents,
            'stats' => $stats,
        ]);
    }

    /**
     * Show check-in form
     */
    public function showCheckIn(): string
    {
        $carriers = Carrier::getActive();
        $statuses = TrailerStatus::getActive();
        $availableSlots = YardSlot::getAvailable();

        // Get first available slot for default
        $defaultSlot = !empty($availableSlots) ? $availableSlots[0] : null;

        // Get arrived status
        $arrivedStatus = TrailerStatus::findByName('arrived');

        return $this->view('gate.check-in', [
            'title' => 'Gate Check-In',
            'carriers' => $carriers,
            'statuses' => $statuses,
            'availableSlots' => $availableSlots,
            'defaultSlot' => $defaultSlot,
            'arrivedStatus' => $arrivedStatus,
            'requireDriverName' => Setting::get('require_driver_name', true),
            'requireSealNumber' => Setting::get('require_seal_number', false),
            'allowPhotoUpload' => Setting::get('allow_photo_upload', true),
        ]);
    }

    /**
     * Process check-in
     */
    public function checkIn(): never
    {
        $rules = [
            'trailer_number' => 'required|max:50',
            'carrier_id' => 'required|integer',
        ];

        if (Setting::get('require_driver_name', true)) {
            $rules['driver_name'] = 'required|max:100';
        }

        if (Setting::get('require_seal_number', false)) {
            $rules['seal_number'] = 'required|max:50';
        }

        $data = $this->validate($rules);

        // Get arrived status
        $arrivedStatus = TrailerStatus::findByName('arrived');
        if (!$arrivedStatus) {
            $this->backWithError('System error: Arrived status not configured');
        }

        // Get default slot if provided
        $slotId = input('yard_slot_id');
        $slot = $slotId ? YardSlot::find($slotId) : null;

        // Create trailer
        $trailer = Trailer::create([
            'trailer_number' => $data['trailer_number'],
            'carrier_id' => $data['carrier_id'],
            'customer_id' => input('customer_id') ?: null,
            'trailer_type' => input('trailer_type', 'dry_van'),
            'trailer_length' => input('trailer_length', 53),
            'status_id' => $arrivedStatus->id,
            'location_type' => $slot ? 'yard_slot' : 'gate',
            'yard_slot_id' => $slot ? $slot->id : null,
            'seal_number' => input('seal_number'),
            'is_loaded' => input('is_loaded', 0),
            'load_type' => input('load_type'),
            'po_numbers' => input('po_numbers'),
            'reference_numbers' => input('reference_numbers'),
            'temperature_setting' => input('temperature_setting'),
            'arrival_time' => date('Y-m-d H:i:s'),
            'current_status_since' => date('Y-m-d H:i:s'),
            'priority' => input('priority', 'normal'),
            'notes' => input('notes'),
            'created_by' => $this->user['id'],
        ]);

        // Update slot availability
        if ($slot) {
            $slot->is_available = 0;
            $slot->save();
        }

        // Handle photo upload
        $photoPath = null;
        if (Setting::get('allow_photo_upload', true)) {
            $photoPath = $this->storeUpload('photo', 'uploads/gate');
        }

        // Create gate event
        GateEvent::create([
            'trailer_id' => $trailer->id,
            'event_type' => 'check_in',
            'driver_name' => input('driver_name'),
            'driver_license' => input('driver_license'),
            'driver_phone' => input('driver_phone'),
            'tractor_number' => input('tractor_number'),
            'carrier_id' => $data['carrier_id'],
            'seal_number' => input('seal_number'),
            'is_loaded' => input('is_loaded', 0),
            'load_description' => input('load_description'),
            'photo_path' => $photoPath,
            'destination' => input('destination'),
            'appointment_time' => input('appointment_time'),
            'gate_lane' => input('gate_lane'),
            'notes' => input('notes'),
            'processed_by' => $this->user['id'],
        ]);

        // Log history
        TrailerHistory::create([
            'trailer_id' => $trailer->id,
            'event_type' => 'gate_in',
            'to_status_id' => $arrivedStatus->id,
            'to_location' => $slot ? $slot->label : 'Gate',
            'notes' => 'Checked in at gate',
            'user_id' => $this->user['id'],
        ]);

        flash('success', "Trailer {$trailer->trailer_number} checked in successfully");
        redirect(url('/gate'));
    }

    /**
     * Show check-out form (search for trailer)
     */
    public function showCheckOut(): string
    {
        // Get trailers in yard
        $trailersInYard = Trailer::getInYard();

        return $this->view('gate.check-out', [
            'title' => 'Gate Check-Out',
            'trailersInYard' => $trailersInYard,
        ]);
    }

    /**
     * Show check-out form for specific trailer
     */
    public function showCheckOutTrailer(int $id): string
    {
        $trailer = Trailer::findOrFail($id);

        // Verify trailer is in yard
        if ($trailer->departure_time) {
            flash('error', 'This trailer has already departed');
            redirect(url('/gate/check-out'));
        }

        return $this->view('gate.check-out-trailer', [
            'title' => 'Check Out: ' . $trailer->trailer_number,
            'trailer' => $trailer,
        ]);
    }

    /**
     * Process check-out
     */
    public function checkOut(int $id): never
    {
        $trailer = Trailer::findOrFail($id);

        // Verify trailer is in yard
        if ($trailer->departure_time) {
            $this->backWithError('This trailer has already departed');
        }

        // Handle photo upload
        $photoPath = null;
        if (Setting::get('allow_photo_upload', true)) {
            $photoPath = $this->storeUpload('photo', 'uploads/gate');
        }

        // Create gate event
        GateEvent::create([
            'trailer_id' => $trailer->id,
            'event_type' => 'check_out',
            'driver_name' => input('driver_name'),
            'driver_license' => input('driver_license'),
            'driver_phone' => input('driver_phone'),
            'tractor_number' => input('tractor_number'),
            'carrier_id' => $trailer->carrier_id,
            'seal_number' => input('seal_number') ?: $trailer->seal_number,
            'is_loaded' => input('is_loaded', $trailer->is_loaded),
            'load_description' => input('load_description'),
            'photo_path' => $photoPath,
            'destination' => input('destination'),
            'gate_lane' => input('gate_lane'),
            'notes' => input('notes'),
            'processed_by' => $this->user['id'],
        ]);

        // Mark trailer as departed
        $trailer->depart($this->user['id'], 'Checked out at gate');

        flash('success', "Trailer {$trailer->trailer_number} checked out successfully");
        redirect(url('/gate'));
    }

    /**
     * Gate history
     */
    public function history(): string
    {
        $startDate = input('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = input('end_date', date('Y-m-d'));
        $eventType = input('event_type');

        $query = GateEvent::query()
            ->whereRaw('DATE(created_at) BETWEEN ? AND ?', [$startDate, $endDate]);

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        $events = GateEvent::hydrate(
            $query->orderBy('created_at', 'DESC')->get()
        );

        return $this->view('gate.history', [
            'title' => 'Gate History',
            'events' => $events,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'eventType' => $eventType,
        ]);
    }

    /**
     * Search for trailer (AJAX)
     */
    public function searchTrailer(): never
    {
        $query = input('q', '');

        if (strlen($query) < 2) {
            $this->json(['results' => []]);
        }

        $trailers = Trailer::search($query, ['in_yard' => true]);

        $results = array_map(function ($trailer) {
            $carrier = $trailer->getCarrier();
            return [
                'id' => $trailer->id,
                'trailer_number' => $trailer->trailer_number,
                'carrier' => $carrier ? $carrier->name : 'Unknown',
                'location' => $trailer->getLocationString(),
                'status' => $trailer->getStatus()?->display_name ?? 'Unknown',
            ];
        }, $trailers);

        $this->json(['results' => $results]);
    }
}

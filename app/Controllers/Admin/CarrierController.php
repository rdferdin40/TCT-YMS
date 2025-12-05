<?php
/**
 * TCT-YMS Admin Carrier Controller
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Carrier;

class CarrierController extends Controller
{
    /**
     * List carriers
     */
    public function index(): string
    {
        $carriers = Carrier::hydrate(
            Carrier::query()->orderBy('name')->get()
        );

        return $this->view('admin.carriers.index', [
            'title' => 'Manage Carriers',
            'carriers' => $carriers,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): string
    {
        return $this->view('admin.carriers.create', [
            'title' => 'Add Carrier',
        ]);
    }

    /**
     * Store new carrier
     */
    public function store(): never
    {
        $data = $this->validate([
            'name' => 'required|max:255',
        ]);

        $carrier = Carrier::create([
            'name' => $data['name'],
            'code' => input('code'),
            'mc_number' => input('mc_number'),
            'dot_number' => input('dot_number'),
            'scac_code' => input('scac_code'),
            'contact_name' => input('contact_name'),
            'contact_phone' => input('contact_phone'),
            'contact_email' => input('contact_email'),
            'address' => input('address'),
            'city' => input('city'),
            'state' => input('state'),
            'zip' => input('zip'),
            'country' => input('country', 'USA'),
            'notes' => input('notes'),
            'is_active' => input('is_active', 1),
        ]);

        flash('success', "Carrier {$carrier->name} created successfully");
        redirect(url('/admin/carriers'));
    }

    /**
     * Show edit form
     */
    public function edit(int $id): string
    {
        $carrier = Carrier::findOrFail($id);

        return $this->view('admin.carriers.edit', [
            'title' => 'Edit Carrier: ' . $carrier->name,
            'carrier' => $carrier,
        ]);
    }

    /**
     * Update carrier
     */
    public function update(int $id): never
    {
        $carrier = Carrier::findOrFail($id);

        $data = $this->validate([
            'name' => 'required|max:255',
        ]);

        $carrier->fill([
            'name' => $data['name'],
            'code' => input('code'),
            'mc_number' => input('mc_number'),
            'dot_number' => input('dot_number'),
            'scac_code' => input('scac_code'),
            'contact_name' => input('contact_name'),
            'contact_phone' => input('contact_phone'),
            'contact_email' => input('contact_email'),
            'address' => input('address'),
            'city' => input('city'),
            'state' => input('state'),
            'zip' => input('zip'),
            'country' => input('country'),
            'notes' => input('notes'),
            'is_active' => input('is_active', 1),
        ]);
        $carrier->save();

        flash('success', 'Carrier updated successfully');
        redirect(url('/admin/carriers'));
    }

    /**
     * Delete carrier
     */
    public function delete(int $id): never
    {
        $carrier = Carrier::findOrFail($id);

        // Check if carrier has trailers
        if ($carrier->getTrailerCountInYard() > 0) {
            if (isAjax()) {
                $this->error('Cannot delete carrier with trailers in yard');
            }
            $this->backWithError('Cannot delete carrier with trailers in yard');
        }

        $carrier->delete();

        if (isAjax()) {
            $this->success(null, 'Carrier deleted');
        }

        flash('success', 'Carrier deleted');
        redirect(url('/admin/carriers'));
    }

    /**
     * Show import form
     */
    public function showImport(): string
    {
        return $this->view('admin.carriers.import', [
            'title' => 'Import Carriers',
        ]);
    }

    /**
     * Import carriers from CSV
     */
    public function import(): never
    {
        $file = $this->getUploadedFile('file');

        if (!$file) {
            $this->backWithError('Please select a file to import');
        }

        if ($file['extension'] !== 'csv') {
            $this->backWithError('Please upload a CSV file');
        }

        $handle = fopen($file['tmp_name'], 'r');
        $headers = fgetcsv($handle);

        $imported = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            if (empty($data['name'])) {
                continue;
            }

            // Check if carrier exists by code or name
            $existing = null;
            if (!empty($data['code'])) {
                $existing = Carrier::findByCode($data['code']);
            }
            if (!$existing) {
                $existing = Carrier::first(['name' => $data['name']]);
            }

            if ($existing) {
                // Update existing
                $existing->fill($data);
                $existing->save();
            } else {
                // Create new
                Carrier::create($data);
            }

            $imported++;
        }

        fclose($handle);

        flash('success', "{$imported} carriers imported successfully");
        redirect(url('/admin/carriers'));
    }
}

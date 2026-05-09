<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use Illuminate\Support\Str;

class DeviceManager extends Component
{
    public $name;
    public $isAdding = false;

    public function render()
    {
        // Ambil semua device, yang paling baru di atas
        $devices = Device::orderBy('created_at', 'desc')->get();
        return view('livewire.device-manager', compact('devices'));
    }

    public function generateDevice()
    {
        $this->validate([
            'name' => 'required|string|max:100'
        ]);

        Device::create([
            'name' => $this->name,
            'api_key' => Str::random(32),
            'is_active' => true
        ]);

        $this->reset(['name', 'isAdding']);
        session()->flash('message', 'Device berhasil ditambahkan dan API Key otomatis digenerate!');
    }

    public function toggleStatus($id)
    {
        $device = Device::find($id);
        if ($device) {
            $device->is_active = !$device->is_active;
            $device->save();
        }
    }

    public function deleteDevice($id)
    {
        Device::find($id)?->delete();
    }
}
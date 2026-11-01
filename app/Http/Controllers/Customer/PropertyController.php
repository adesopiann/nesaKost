<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;

class PropertyController extends Controller
{
    public function show(int $id)
    {
        $property = BranchDorm::query()
            ->where('status', config('ekost.property_active_status', 'active'))
            ->with([
                'facilities',
                'rooms' => fn ($query) => $query
                    ->where('status', config('ekost.room_available_status', 'available')),
            ])
            ->findOrFail($id);

        return view('customer.properties.detail', compact('property'));
    }
}

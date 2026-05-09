<?php

namespace App\Http\Controllers\v1\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizationController extends Controller
{
    public function getCurrent()
    {
        $organization = Organization::where('user_id', Auth::id())->first();

        return response()->json([
            'success' => true,
            'data' => $organization
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_size' => 'nullable|string',
            'country' => 'nullable|string',
            'industries' => 'nullable|array',
            'use_cases' => 'nullable|array',
            'policy_interests' => 'nullable|array',
            'alert_preference' => 'nullable|string',
        ]);

        $organization = Organization::create(array_merge($validated, [
            'user_id' => Auth::id()
        ]));

        return response()->json([
            'success' => true,
            'data' => $organization
        ]);
    }

    public function update(Request $request)
    {
        $organization = Organization::where('user_id', Auth::id())->first();

        if (!$organization) {
            return $this->create($request);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company_size' => 'nullable|string',
            'country' => 'nullable|string',
            'industries' => 'nullable|array',
            'use_cases' => 'nullable|array',
            'policy_interests' => 'nullable|array',
            'alert_preference' => 'nullable|string',
        ]);

        $organization->update($validated);

        return response()->json([
            'success' => true,
            'data' => $organization
        ]);
    }
}

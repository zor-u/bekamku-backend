<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStaffRequest;
use App\Http\Resources\AdminResource;
use App\Models\Auth\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $staff = Admin::query()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => AdminResource::collection($staff),
        ]);
    }

    public function update(UpdateStaffRequest $request, Admin $admin): JsonResponse
    {
        $deactivating = $request->has('is_active') && ! $request->boolean('is_active');

        $admin->fill($request->validated());
        $admin->save();

        if ($deactivating) {
            $admin->tokens()->delete();
        }

        return response()->json([
            'data' => AdminResource::make($admin)->resolve($request),
        ]);
    }
}

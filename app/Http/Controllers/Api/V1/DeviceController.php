<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Device\RegisterDeviceRequest;
use App\Http\Resources\V1\DeviceResource;
use App\Http\Traits\ApiResponse;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use ApiResponse;

    /**
     * Register or update a device for push notifications.
     */
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $device = UserDevice::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'user_id' => $user->id,
                'fcm_token' => $data['fcm_token'],
                'platform' => $data['platform'],
                'device_name' => $data['device_name'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'os_version' => $data['os_version'] ?? null,
                'last_used_at' => now(),
            ],
        );

        return $this->success(
            new DeviceResource($device),
            'Device registered',
        );
    }

    /**
     * Unregister a device for the authenticated user.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $device = $request->user()->devices()->find($id);

        if (! $device) {
            return $this->error('الجهاز غير موجود', null, 404);
        }

        $device->delete();

        return $this->success(null, 'تم إلغاء تسجيل الجهاز');
    }
}

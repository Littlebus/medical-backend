<?php

namespace App\Http\Controllers;

use App\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{

    public function getList(Request $request) {
        $devices = Device::where('user_id', Auth::id())->get();
        return response()->json(['success' => true, 'data' => $devices]);
    }

    public function insert(Request $request) {
        $token = $request->input('token', '');
        $info = $request->input('device_info', '');
        if ($token == '') {
            return response()->json([
                'success' => false,
                'data' => '400'
            ]);
        }

        $device = Device::where('user_id', Auth::id())
            ->where('token', $token)
            ->first();

        if (!$device) {
            $device = Device::create([
                'user_id' => Auth::id(),
                'token' => $token,
                'device_info' => $info,
            ]);
        } else {
            $device->device_info = $info;
            $device->save();
        }

        return response()->json(['success' => true, 'data' => $device]);
    }

}

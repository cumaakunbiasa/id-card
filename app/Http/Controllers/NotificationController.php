<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class NotificationController extends Controller {
    public function index(Request $request) {
        return view('notifications.index', ['notifications' => $request->user()->notifications()->latest()->paginate(20)]);
    }
    public function read(Request $request, string $notification) {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();
        return back();
    }
}

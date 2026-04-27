<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Notification\BaileysService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformWhatsAppController extends Controller
{
    public function __construct(
        private BaileysService $baileys,
    ) {}

    public function show(): Response
    {
        return Inertia::render('Admin/WhatsApp/Platform', [
            'status' => $this->baileys->getSessionStatus(),
        ]);
    }

    public function initiate(): RedirectResponse
    {
        $result = $this->baileys->initiateSession();

        return redirect()->route('admin.whatsapp.platform.show')
            ->with('qr_code', $result['qr_code'] ?? null)
            ->with('whatsapp_status', $result['status'] ?? 'initializing');
    }

    public function disconnect(): RedirectResponse
    {
        $this->baileys->disconnectSession();

        return redirect()->route('admin.whatsapp.platform.show')
            ->with('flash_key', 'disconnectSuccess')
            ->with('flash_type', 'success');
    }

    public function testSend(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $sent = $this->baileys->send($data['phone_number'], $data['message']);

        return redirect()->route('admin.whatsapp.platform.show')
            ->with('flash_key', $sent ? 'testSendSuccess' : 'testSendFail')
            ->with('flash_type', $sent ? 'success' : 'error');
    }
}

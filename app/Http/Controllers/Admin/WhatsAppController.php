<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Services\Notification\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsapp,
    ) {}

    public function show(Club $club): Response
    {
        $status = $this->whatsapp->getSessionStatus($club->id);

        return Inertia::render('Admin/WhatsApp/Show', [
            'club' => ['id' => $club->id, 'name' => $club->getTranslation('name', 'ar')],
            'status' => $status,
        ]);
    }

    public function initiate(Club $club): RedirectResponse
    {
        $result = $this->whatsapp->initiateSession($club->id);

        return redirect()->route('admin.whatsapp.show', $club)
            ->with('qr_code', $result['qr_code'] ?? null)
            ->with('whatsapp_status', $result['status'] ?? 'initializing');
    }

    public function disconnect(Club $club): RedirectResponse
    {
        $this->whatsapp->disconnectSession($club->id);

        return redirect()->route('admin.whatsapp.show', $club)
            ->with('success', 'تم قطع الاتصال بنجاح');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $accounts = WhatsAppAccount::all();
        return view('settings.index', compact('accounts'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'account_name' => 'required|string|max:100',
            'phone_number' => 'required|string|max:20',
        ]);

        WhatsAppAccount::updateOrCreate(
            ['id' => $request->account_id],
            [
                'user_id' => auth()->id(),
                'account_name' => $request->account_name,
                'phone_number' => $request->phone_number,
                'provider' => 'manual',
                'status' => 'active',
            ]
        );

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}

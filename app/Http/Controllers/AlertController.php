<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\RedirectResponse;

class AlertController extends Controller
{
    public function acknowledge(Alert $alert): RedirectResponse
    {
        $this->authorize('update', $alert);

        $alert->update(['acknowledged_at' => now()]);

        return redirect()->route('budgets');
    }
}

<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Portal\BaseShipmentController;
use Illuminate\Support\Facades\Auth;

class ShipmentController extends BaseShipmentController
{
    protected function seller(): array
    {
        return ['supplier', Auth::user()->supplier_id];
    }

    protected function viewPrefix(): string
    {
        return 'portal.supplier';
    }

    protected function routePrefix(): string
    {
        return 'portal.supplier';
    }
}

<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Portal\BaseShipmentController;

class ShipmentController extends BaseShipmentController
{
    protected function seller(): array
    {
        return ['hq', null];
    }

    protected function viewPrefix(): string
    {
        return 'portal.hq';
    }

    protected function routePrefix(): string
    {
        return 'portal.hq';
    }
}

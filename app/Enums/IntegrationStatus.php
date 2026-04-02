<?php

namespace App\Enums;

enum IntegrationStatus: string
{
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Paused = 'paused';
}

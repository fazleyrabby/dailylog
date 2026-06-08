<?php

namespace App\Enums;

enum CapturedVia: string
{
    case Palette = 'palette';
    case Web = 'web';
    case Extension = 'extension';
    case Share = 'share';
    case Import = 'import';
    case Api = 'api';
    case Seed = 'seed';
}

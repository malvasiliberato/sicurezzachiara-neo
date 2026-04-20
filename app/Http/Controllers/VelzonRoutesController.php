<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class VelzonRoutesController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('dashboard/index');
    }

    public function sicurezzachiara_ui_reference(): Response
    {
        return Inertia::render('sicurezzachiara/ui-reference');
    }
}

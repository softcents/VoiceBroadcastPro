<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AsteriskController extends Controller
{
    public function __invoke(Request $request)
    {
        ray($request->all())->showApp();
        return response()->json(['status' => 'received'], 200);
    }
}

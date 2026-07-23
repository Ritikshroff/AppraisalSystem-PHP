<?php

namespace App\Http\Controllers;

use App\Services\AppraisalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Options from request parameters
        $options = [
            'query' => $request->query('query', ''),
            'visiblePage' => intval($request->query('visiblePage', 1)),
            'pendingPage' => intval($request->query('pendingPage', 1)),
            'teamStatusPage' => intval($request->query('teamStatusPage', 1)),
            'view' => $request->query('view', 'dashboard'),
        ];

        $data = AppraisalService::getDashboardData($user->id, $options);

        return view('dashboard.index', compact('data'));
    }
}

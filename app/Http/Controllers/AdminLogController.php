<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    //create function to send the paginated logs to frontend and also add filters for from and to date, actor, ip, change 
    public function index(Request $request){
        $logs = AdminLog::latest();
        if($request->has('change_by')){
            $logs->whereRelation('admin', "name" , $request->change_by);
        }
        if($request->has('ip')){
            $logs->where('ip', $request->ip);
        }
        if($request->has('change')){
            $logs->where('change', 'like', '%'.$request->change.'%');
        }
        if($request->has('from_date') && $request->has('to_date')){
            $logs->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }
        return response()->json($logs->paginate(10));
    }
}

<?php

namespace App\Http\Controllers;

use App\Constants\IPWhitelistConstants;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIPWhiteListRequest;
use App\Http\Requests\UpdateIPWhiteListRequest;
use App\Http\Requests\WhitelistIPListingRequest;
use App\Models\WhitelistIP;
use App\Services\LogService\AdminLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class WhitelistIPController extends Controller
{
    //store an IP to the whitelist
    public function store(StoreIPWhiteListRequest $request)
    {
        $whitelist_ip=WhitelistIP::create($request->getIpWhiteListData());
        AdminLogService::createLog('ADDED IP '.' '. $request->ip);
       if($whitelist_ip){
           return response()->json([
               'status' => true,
               'message' => 'IP_WHITELISTED_SUCCESSFULLY',
           ], 200);
       }else{
           return response()->json([
               'status' => false,
               'message' => 'IP_WHITELISTED_FAILED',
           ], 400);
       }

    }

    //delete an IP from the whitelist
    public function delete($id)
    {
            if(!auth()->user()->can('Delete IP Whitelist')){
                return response()->json([
                    'status' => false,
                    'message' => 'UNAUTHORIZED',
                ], 401);
            }
        $whitelistip = WhitelistIP::find($id);
        if ($whitelistip->delete()) {
            AdminLogService::createLog('DELETE IP '.' '. $whitelistip->ip);
            return response()->json([
                'status' => true,
                'message' => 'IP_DELETED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'IP_DELETE_FAILED',
        ], 400);

    }
    //update an IP from the whitelist
    public function update(UpdateIPWhiteListRequest $request, WhitelistIP $whitelist_ip)
    {
        if ($whitelist_ip->update($request->getIpWhiteListData())) {
            AdminLogService::createLog('updated ip to'.'  '.$whitelist_ip->ip);
            return response()->json([
                'status' => true,
                'message' => 'IP_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'IP_UPDATE_FAILED',
        ], 400);
    }
//get all IPs from the whitelist with pagination and search filters
    public function index(WhitelistIPListingRequest $request)
    {
        $WhitelistIP=WhitelistIP::scopeSearch($request->getSearchData())->orderByDesc('id')->paginate(10);
        $WhitelistIP->getCollection()->transform(function ($item) {
            $item->type_name = IPWhitelistConstants::getTypes()[$item->type];
            return $item;
        });
        return response()->json($WhitelistIP) ;
    }


    //get all types of IP
    public function getTypes()
    {
        return response()->json(IPWhitelistConstants::getTypes());
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAgentChangeHistoryRequest;
use App\Models\AgentChangeHistory;

class AgentChangeHistoryController extends Controller
{
    public function index(ListAgentChangeHistoryRequest $request)
    {
        return AgentChangeHistory::getAgentHistoriesWithRelations($request->validated())->orderByDesc('id')->paginate(10);
    }
}
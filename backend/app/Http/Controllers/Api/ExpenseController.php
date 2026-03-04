<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateExpenseAction;
use App\Actions\RetryExpenseConversionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $expenses = $request->user()->expenses()->latest()->paginate(15);

        return ExpenseResource::collection($expenses);
    }

    public function store(StoreExpenseRequest $request, CreateExpenseAction $action): JsonResponse
    {
        $expense = $action->execute($request->user(), $request->validated());

        return (new ExpenseResource($expense))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Expense $expense): ExpenseResource
    {
        $this->authorize('view', $expense);

        return new ExpenseResource($expense);
    }

    public function retry(Request $request, Expense $expense, RetryExpenseConversionAction $action): ExpenseResource
    {
        $this->authorize('update', $expense);

        return new ExpenseResource($action->execute($expense));
    }
}

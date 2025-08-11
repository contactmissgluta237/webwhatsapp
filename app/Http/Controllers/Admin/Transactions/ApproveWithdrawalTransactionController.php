<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\ExternalTransaction;
use App\Services\Transaction\ExternalTransactionService;
use Illuminate\Http\Request;

class ApproveWithdrawalTransactionController extends Controller
{
    public function __construct(private readonly ExternalTransactionService $externalTransactionService)
    {
        $this->middleware('can:'.PermissionEnum::TRANSACTIONS_APPROVE_WITHDRAWAL()->value.',externalTransaction');
    }

    /**
     * Approve a withdrawal transaction.
     *
     * Route: POST /admin/transactions/externals/{externalTransaction}/approve
     * Name: admin.transactions.externals.approve
     */
    public function __invoke(Request $request, ExternalTransaction $externalTransaction)
    {
        $this->externalTransactionService->approve($externalTransaction);

        return back()->with('success', 'Transaction approved successfully.');
    }
}

<?php

namespace App\Http\Controllers\Payment;

use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\Payment\PaymentService;

class PipraPayController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function callback(Deposit $deposit)
    {
        if ($deposit->status === DepositStatus::Completed) {
            return to_route('payments.cancel');
        }

        $verified = $this->paymentService->verify($deposit);

        if ($verified) {
            $this->paymentService->confirm($deposit);
            return to_route('payments.success');
        }

        $deposit->update(['status' => DepositStatus::Failed]);
        return to_route('payments.failed');
    }

    public function cancel(Deposit $deposit)
    {
        $deposit->update(['status' => DepositStatus::Cancelled]);
        return to_route('payments.cancel');
    }

    public function ipn(Deposit $deposit)
    {
        if ($deposit->status === DepositStatus::Completed) {
            return response()->json(['status' => 'success', 'message' => 'Already completed']);
        }

        $verified = $this->paymentService->verify($deposit);

        if ($verified) {
            $this->paymentService->confirm($deposit);
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'failed'], 400);
    }
}

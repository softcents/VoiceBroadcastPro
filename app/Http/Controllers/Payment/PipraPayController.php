<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

final class PipraPayController extends Controller
{
    public function __construct(Request $request, protected PaymentService $paymentService)
    {
        parent::__construct($request);
    }

    public function callback(Request $request, Deposit $deposit)
    {
        // If the payment is already completed, just redirect to success page
        if ($deposit->status === DepositStatus::Completed) {
            return to_route('payments.success');
        }

        // If the payment is canceled by gateway, just redirect to canceled page
        if ($request->get('pp_status') === 'canceled') {
            $deposit->update(['status' => DepositStatus::Cancelled]);

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

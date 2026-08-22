<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /** Últimos 10 pagos del alumno, con el/los adeudos que cubrieron. */
    public function index(Request $request)
    {
        $student = $request->student();

        $payments = $student->payments()
            ->with(['method', 'reference', 'orders.concept', 'orders.agreement'])
            ->latest('payment_date')
            ->limit(10)
            ->get();

        return response()->json([
            'pending_balance' => (float) $student->pending_balance,
            'payments' => $payments->map(fn (Payment $payment) => $this->formatPayment($payment)),
        ]);
    }

    public function show(Request $request, Payment $payment)
    {
        $student = $request->student();

        if ($payment->student_id !== $student->id) {
            abort(404);
        }

        $payment->load(['method', 'reference', 'orders.concept', 'orders.agreement.installments']);

        return response()->json($this->formatPayment($payment, detailed: true));
    }

    private function formatPayment(Payment $payment, bool $detailed = false): array
    {
        $data = [
            'id' => $payment->id,
            'folio' => $payment->folio,
            'amount_received' => (float) $payment->amount_received,
            'amount_applied' => (float) $payment->amount_applied,
            'change_amount' => (float) $payment->change_amount,
            'date' => $payment->payment_date?->format('Y-m-d H:i'),
            'status' => $payment->status,
            'coverage_label' => $payment->coverage_label,
            'method' => $payment->method?->name,
            'reference' => $payment->reference ? [
                'reference_number' => $payment->reference->reference_number,
                'bank' => $payment->reference->bank,
            ] : null,
            'orders' => $payment->orders->map(fn ($order) => [
                'id' => $order->id,
                'folio' => $order->folio,
                'concept' => $order->concept?->name,
                'is_surcharge' => (bool) $order->is_surcharge,
                'amount_applied' => (float) $order->pivot->amount_applied,
                'order_status' => $order->status,
                'agreement' => $order->agreement ? [
                    'folio' => $order->agreement->folio,
                    'type' => $order->agreement->type,
                ] : null,
            ])->values(),
        ];

        if ($detailed) {
            $data['notes'] = $payment->notes;
        }

        return $data;
    }
}

<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        return response()->json(Transaction::all());
    }

    public function show(int $id)
    {
        $transaction = Transaction::find($id);
        return $transaction
            ? response()->json($transaction)
            : response()->json(['error' => 'Transaction not found'], 404);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
        ]);

        return response()->json(Transaction::create($validated), 201);
    }

    public function update(Request $request, int $id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'email'  => 'sometimes|email',
            'amount' => 'sometimes|numeric',
            'status' => 'sometimes|string',
        ]);

        $transaction->update($validated);
        return response()->json($transaction);
    }

    public function destroy(int $id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }
        $transaction->delete();
        return response()->json(null, 204);
    }
}

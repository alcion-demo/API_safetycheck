<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SafetyCheck;
use App\Http\Requests\StoreSafetyCheckRequest;
use App\Http\Requests\UpdateSafetyCheckRequest;

class SafetyCheckController extends Controller
{
    /**
     * __construct
     */
    public function __construct(protected SafetyCheck $safetycheck)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $safetyChecks = $this->safetycheck->latest()->get();

        return response()->json($safetyChecks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSafetyCheckRequest $request)
    {
        $data = $request->validated();

        if ($data['type'] === 'training') {
            return $this->safetycheck->createTraining($data);
        }

        if ($data['type'] === 'production') {
            return $this->safetycheck->createProduction($data);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSafetyCheckRequest $request, string $id)
    {
        $safetyCheck = $this->safetycheck->findOrFail($id);

        if ($safetyCheck->type !== 'training') {
            abort(422, '訓練の安否確認のみ編集できます。');
        }

        if ($safetyCheck->status !== 'scheduled') {
            abort(422, '予約中の安否確認のみ編集できます。');
        }

        $safetyCheck->updateTraining(
            $request->validated()
        );

        return response()->json($safetyCheck);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * 訓練開始状態の設定
     *
     * @param string $id
     * @return void
     */
    public function activate(string $id)
    {
        $safetyCheck = $this->safetycheck->findOrFail($id);

        if ($safetyCheck->type !== 'training') {
            abort(422, '訓練の安否確認のみ開始できます。');
        }

        if ($safetyCheck->status !== 'scheduled') {
            abort(422, '予約中の安否確認のみ開始できます。');
        }

        $safetyCheck->activateTraining();

        return response()->json($safetyCheck);
    }

}

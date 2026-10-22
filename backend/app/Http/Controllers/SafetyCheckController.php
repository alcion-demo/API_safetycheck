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
     * 指定された安否確認を取得する
     */
    public function show(string $id)
    {
        $safetyCheck = $this->safetycheck->findOrFail($id);

        return response()->json($safetyCheck);
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
        $safetyCheck = $this->safetycheck->findOrFail($id);

        if ($safetyCheck->type !== 'training') {
            abort(422, '訓練の安否確認のみ削除できます。');
        }

        if ($safetyCheck->status !== 'closed') {
            abort(422, '終了済みの訓練のみ削除できます。');
        }

        $safetyCheck->delete();

        return response()->json([
            'message' => '訓練履歴を削除しました。',
        ]);
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

        return response()->json($safetyCheck->fresh());
    }

    /**
     * 訓練用安否確認を終了する
     */
    public function close(string $id)
    {
        $safetyCheck = $this->safetycheck->findOrFail($id);

        // 訓練以外は終了できない
        if ($safetyCheck->type !== 'training') {
            abort(422, '訓練の安否確認のみ終了できます。');
        }

        // 実施中の訓練のみ終了できる
        if ($safetyCheck->status !== 'active') {
            abort(422, '実施中の訓練のみ終了できます。');
        }

        $safetyCheck->closeTraining();

        return response()->json($safetyCheck);
    }


    /**
     * 本番の安否確認を終了する
     */
    public function closeProduction(string $id)
    {
        $safetyCheck = $this->safetycheck->findOrFail($id);

        // 本番以外は終了できない
        if ($safetyCheck->type !== 'production') {
            abort(422, '本番の安否確認のみ終了できます。');
        }

        // 対応中の本番のみ終了できる
        if ($safetyCheck->status !== 'active') {
            abort(422, '対応中の本番のみ終了できます。');
        }

        $safetyCheck->update([
            'status' => 'closed',
            'ended_at' => now(),
        ]);

        return response()->json($safetyCheck->fresh());
    }


}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreSafetyCheckResponseRequest;
use App\Models\SafetyCheck;
use App\Models\SafetyCheckResponse;
use App\Models\User;

class SafetyCheckResponseController extends Controller
{
    /**
     * __construct
     */
    public function __construct(protected SafetyCheck $safetycheck, protected SafetyCheckResponse $safetycheckresponse)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(int $id)
    {
        $safetyCheck = $this->safetycheck->findOrFail($id);

        // 登録ユーザー全員を取得
        $users = User::query()
            ->orderBy('id')
            ->get();

        // この安否確認への回答を取得
        $responses = $this->safetycheckresponse
            ->getResponsesBySafetyCheckId($safetyCheck->id)
            ->keyBy('user_id');

        // 全ユーザーに回答を紐づける
        $results = $users->map(function ($user) use ($responses, $safetyCheck) {
            $response = $responses->get($user->id);

            return [
                'id' => $response?->id,
                'safety_check_id' => $safetyCheck->id,
                'user_id' => $user->id,
                'status' => $response?->status,
                'memo' => $response?->memo,
                'created_at' => $response?->created_at,
                'updated_at' => $response?->updated_at,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ];
        });

        return response()->json([
            'responses' => $results,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSafetyCheckResponseRequest $request, int $id)
    {

        $data = $request->validated();

        $safetyCheck = $this->safetycheck->findOrFail($id);

        if ($safetyCheck->status !== 'active') {
            return response()->json([
                'message' => 'この安否確認は現在回答を受け付けていません。',
            ], 422);
        }

        $response = $this->safetycheckresponse->createResponse(
            $id, 
            $request->user()->id,
            $data
        );

        return response()->json([
            'message' => '回答を登録しました。',
            'response' => $response,
        ], 201);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

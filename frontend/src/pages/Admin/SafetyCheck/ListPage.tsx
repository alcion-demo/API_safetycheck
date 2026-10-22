import { useEffect, useState } from 'react'
import {
  getSafetyChecks,
  closeSafetyCheck,
  closeProductionSafetyCheck,
  deleteSafetyCheck,
} from '../../../safety-checks/api/safetyChecksApi'
import type { SafetyCheck } from '../../../safety-checks/api/types'
import { useNavigate } from 'react-router-dom'

const SafetyCheckListPage = () => {
  const [safetyChecks, setSafetyChecks] = useState<SafetyCheck[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [searchDate, setSearchDate] = useState('')
  const navigate = useNavigate()

  // 訓練を終了
  const handleClose = async (id: number) => {
    setError('')

    try {
      await closeSafetyCheck(id)
      const data = await getSafetyChecks()
      setSafetyChecks(data)
    } catch {
      setError('訓練の終了に失敗しました。')
    }
  }

  // 本番対応を終了
  const handleCloseProduction = async (id: number) => {
    if (
      !window.confirm(
        '本番対応を終了しますか？終了後は回答の新規登録・更新ができなくなります。',
      )
    ) {
      return
    }

    setError('')

    try {
      await closeProductionSafetyCheck(id)
      const data = await getSafetyChecks()
      setSafetyChecks(data)
    } catch {
      setError('本番対応の終了に失敗しました。')
    }
  }

  // 終了済み訓練の履歴を削除
  const handleDelete = async (id: number) => {
    if (
      !window.confirm(
        'この訓練履歴を削除しますか？この操作は取り消せません。',
      )
    ) {
      return
    }

    setError('')

    try {
      await deleteSafetyCheck(id)

      setSafetyChecks((prev) =>
        prev.filter((safetyCheck) => safetyCheck.id !== id),
      )
    } catch {
      setError('訓練履歴の削除に失敗しました。')
    }
  }

  useEffect(() => {
    const fetchSafetyChecks = async () => {
      try {
        const data = await getSafetyChecks()
        setSafetyChecks(data)
      } catch (err: any) {
        // ステータスコードが 401 (Unauthorized) などの場合
        if (err?.response?.status === 401) {
          navigate('/login') // ログイン画面へ誘導
          return
        }
        setError('安否確認一覧の取得に失敗しました。')
      } finally {
        setLoading(false)
      }
    }

    fetchSafetyChecks()

    const timer = setInterval(fetchSafetyChecks, 5_000)

    return () => clearInterval(timer)
  }, [])

  // ▼ 修正: タイムゾーンのズレを解消し、日本時間の年月日ベースで確実に比較するフィルター
  const filteredAndSortedSafetyChecks = [...safetyChecks]
    .filter((safetyCheck: any) => {
      if (!searchDate) return true // 日付が未選択の場合はすべて表示

      const targetString =
        safetyCheck.scheduled_at ||
        safetyCheck.started_at

      if (!targetString) return false

      // Dateオブジェクトに変換（ブラウザ側でローカル時間（JST）に自動変換されます）
      const dateObj = new Date(targetString)
      if (isNaN(dateObj.getTime())) return false

      // ローカル時間での年・月・日を "YYYY-MM-DD" 形式に組み立てる
      const year = dateObj.getFullYear()
      const month = String(dateObj.getMonth() + 1).padStart(2, '0')
      const day = String(dateObj.getDate()).padStart(2, '0')
      const targetDateOnly = `${year}-${month}-${day}`

      return targetDateOnly === searchDate
    })
    .sort((a: any, b: any) => {
      const timeA = a.scheduled_at
        ? new Date(a.scheduled_at).getTime()
        : a.started_at
          ? new Date(a.started_at).getTime()
          : 0

      const timeB = b.scheduled_at
        ? new Date(b.scheduled_at).getTime()
        : b.started_at
          ? new Date(b.started_at).getTime()
          : 0

      return timeB - timeA
    })

  return (
    <main className="safety-check-management-page">
      <section className="safety-check-management-card">
        <h1>安否確認管理</h1>

        {/* 検索フィルター用のUIエリア */}
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            margin: '16px 0',
            flexWrap: 'wrap',
            gap: '12px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <label htmlFor="search-date" style={{ fontSize: '14px', fontWeight: 'bold' }}>
              実施日検索：
            </label>
            <input
              id="search-date"
              type="date"
              value={searchDate}
              onChange={(e) => setSearchDate(e.target.value)}
              style={{ padding: '6px 8px', borderRadius: '4px', border: '1px solid #cbd5e1' }}
            />
            {searchDate && (
              <button
                type="button"
                onClick={() => setSearchDate('')}
                style={{ padding: '6px 10px', fontSize: '12px' }}
              >
                クリア
              </button>
            )}
          </div>

          <button
            type="button"
            onClick={() => navigate('/admin/safety-checks/create')}
          >
            訓練を予約
          </button>
        </div>

        {loading ? (
          <p>安否確認一覧を読み込んでいます...</p>
        ) : error ? (
          <p className="error-message" role="alert">
            {error}
          </p>
        ) : safetyChecks.length === 0 ? (
          <p>安否確認はまだ登録されていません。</p>
        ) : filteredAndSortedSafetyChecks.length === 0 ? (
          <p>指定した日付に一致する安否確認はありません。</p>
        ) : (
          <ul className="safety-check-list">
            {filteredAndSortedSafetyChecks.map((safetyCheck) => (
              <li
                className="safety-check-card"
                key={safetyCheck.id}
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '12px',
                  textAlign: 'left',
                }}
              >
                {/* 日時・種別・ステータス */}
                <div
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'flex-start',
                    gap: '12px',
                    flexWrap: 'wrap',
                  }}
                >
                  {safetyCheck.type === 'training' ? (
                    <div
                      className="safety-check-schedule-inline"
                      style={{ margin: 0 }}
                    >
                      <span className="label">実施予定日時：</span>
                      <span className="datetime">
                        {safetyCheck.scheduled_at
                          ? new Date(
                            safetyCheck.scheduled_at,
                          ).toLocaleString('ja-JP')
                          : '未設定'}
                      </span>
                    </div>
                  ) : (
                    <div
                      className="safety-check-schedule-inline"
                      style={{ margin: 0 }}
                    >
                      <span className="label">開始日時：</span>
                      <span className="datetime">
                        {safetyCheck.started_at
                          ? new Date(
                            safetyCheck.started_at,
                          ).toLocaleString('ja-JP')
                          : '未設定'}
                      </span>
                    </div>
                  )}

                  <span className="safety-check-type">
                    {safetyCheck.type === 'training' ? '訓練' : '本番'}
                  </span>

                  <span
                    className={`safety-check-status status-${safetyCheck.status}`}
                  >
                    {safetyCheck.status === 'scheduled'
                      ? '予約中'
                      : safetyCheck.status === 'active'
                        ? safetyCheck.type === 'training'
                          ? '実施中'
                          : '対応中'
                        : '終了'}
                  </span>
                </div>

                {/* タイトル・メッセージ */}
                <div
                  style={{
                    borderTop: '1px dashed #e2e8f0',
                    paddingTop: '10px',
                    textAlign: 'left',
                  }}
                >
                  <h2
                    style={{
                      margin: '0 0 6px 0',
                      fontSize: '16px',
                      color: '#1e3a5f',
                      textAlign: 'left',
                    }}
                  >
                    {safetyCheck.title}
                  </h2>

                  <p
                    className="safety-check-message"
                    style={{
                      margin: 0,
                      border: 'none',
                      padding: 0,
                      textAlign: 'left',
                    }}
                  >
                    {safetyCheck.message}
                  </p>
                </div>

                {/* 操作ボタン */}
                <div
                  className="safety-check-actions"
                  style={{ marginTop: '4px', textAlign: 'left' }}
                >
                  <button
                    type="button"
                    onClick={() =>
                      navigate(
                        `/admin/safety-checks/${safetyCheck.id}/responses`,
                      )
                    }
                  >
                    回答結果
                  </button>

                  {safetyCheck.type === 'training' &&
                    safetyCheck.status === 'scheduled' && (
                      <button
                        type="button"
                        onClick={() =>
                          navigate(
                            `/admin/safety-checks/${safetyCheck.id}/edit`,
                          )
                        }
                      >
                        予約を編集
                      </button>
                    )}

                  {safetyCheck.type === 'training' &&
                    safetyCheck.status === 'active' && (
                      <button
                        type="button"
                        onClick={() => handleClose(safetyCheck.id)}
                      >
                        訓練を終了
                      </button>
                    )}

                  {safetyCheck.type === 'training' &&
                    safetyCheck.status === 'closed' && (
                      <button
                        type="button"
                        onClick={() => handleDelete(safetyCheck.id)}
                      >
                        履歴を削除
                      </button>
                    )}

                  {safetyCheck.type === 'production' &&
                    safetyCheck.status === 'active' && (
                      <button
                        type="button"
                        onClick={() =>
                          handleCloseProduction(safetyCheck.id)
                        }
                      >
                        本番対応を終了
                      </button>
                    )}
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>
    </main>
  )
}

export default SafetyCheckListPage
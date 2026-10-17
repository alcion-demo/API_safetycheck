import { useEffect, useState } from 'react'
import { getSafetyChecks, closeSafetyCheck, deleteSafetyCheck } from '../../../safety-checks/api/safetyChecksApi'
import type { SafetyCheck } from '../../../safety-checks/api/types'
import { useNavigate } from 'react-router-dom'

const SafetyCheckListPage = () => {
  const [safetyChecks, setSafetyChecks] = useState<SafetyCheck[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const navigate = useNavigate()

  const handleClose = async (id: number) => {
    try {
      await closeSafetyCheck(id)
      const data = await getSafetyChecks()
      setSafetyChecks(data)
    } catch {
      setError('安否確認の終了に失敗しました。')
    }
  }

  const handleDelete = async (id: number) => {
    if (!window.confirm('この訓練履歴を削除しますか？この操作は取り消せません。')) {
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
      } catch {
        setError('安否確認一覧の取得に失敗しました。')
      } finally {
        setLoading(false)
      }
    }

    fetchSafetyChecks()

    const timer = setInterval(fetchSafetyChecks, 5_000)

    return () => clearInterval(timer)

  }, [])

  const sortedSafetyChecks = [...safetyChecks].sort(
    (a, b) =>
      new Date(b.created_at).getTime() -
      new Date(a.created_at).getTime(),
  )

  return (
    <main className="safety-check-management-page">
      <section className="safety-check-management-card">
        <h1>安否確認管理</h1>

        <div style={{ display: 'flex', justifyContent: 'flex-end', margin: '16px 0' }}>
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
        ) : (
          <ul className="safety-check-list">
            {sortedSafetyChecks.map((safetyCheck) => (
              <li className="safety-check-card" key={safetyCheck.id} style={{ display: 'flex', flexDirection: 'column', gap: '12px', textAlign: 'left' }}>
                
                {/* 1行目：日付、訓練バッチ、ステータスバッジ（すべて左寄せ） */}
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-start', gap: '12px', flexWrap: 'wrap' }}>
                  <div className="safety-check-schedule-inline" style={{ margin: 0 }}>
                    <span className="label">実施予定日時：</span>
                    <span className="datetime">
                      {safetyCheck.scheduled_at
                        ? new Date(safetyCheck.scheduled_at).toLocaleString('ja-JP')
                        : '未設定'}
                    </span>
                  </div>
                  <span className="safety-check-type">
                    {safetyCheck.type === 'training' ? '訓練' : '本番'}
                  </span>
                  <span className={`safety-check-status status-${safetyCheck.status}`}>
                    {safetyCheck.status === 'scheduled'
                      ? '予約中'
                      : safetyCheck.status === 'active'
                        ? '実施中'
                        : '終了'}
                  </span>
                </div>

                {/* 2行目：タイトルとメッセージ（左寄せ） */}
                <div style={{ borderTop: '1px dashed #e2e8f0', paddingTop: '10px', textAlign: 'left' }}>
                  <h2 style={{ margin: '0 0 6px 0', fontSize: '16px', color: '#1e3a5f', textAlign: 'left' }}>{safetyCheck.title}</h2>
                  <p className="safety-check-message" style={{ margin: 0, border: 'none', padding: 0, textAlign: 'left' }}>
                    {safetyCheck.message}
                  </p>
                </div>

                {/* 3行目：訓練開始 / 終了 / 履歴削除ボタン */}
                {safetyCheck.type === 'training' && (
                  <div
                    className="safety-check-actions"
                    style={{ marginTop: '4px', textAlign: 'left' }}
                  >
                    <button
                      type="button"
                      onClick={() =>
                        navigate(`/admin/safety-checks/${safetyCheck.id}/responses`)
                      }
                    >
                      回答結果
                    </button>
                    {safetyCheck.type === 'training' &&
                      safetyCheck.status === 'scheduled' && (
                        <button
                          type="button"
                          onClick={() =>
                            navigate(`/admin/safety-checks/${safetyCheck.id}/edit`)
                          }
                        >
                          予約を編集
                        </button>
                      )}

                    {safetyCheck.status === 'active' && (
                      <button
                        type="button"
                        onClick={() => handleClose(safetyCheck.id)}
                      >
                        訓練を終了
                      </button>
                    )}

                    {safetyCheck.status === 'closed' && (
                      <button
                        type="button"
                        onClick={() => handleDelete(safetyCheck.id)}
                      >
                        履歴を削除
                      </button>
                    )}
                  </div>
                )}

              </li>
            ))}
          </ul>
        )}
      </section>
    </main>
  )
}

export default SafetyCheckListPage
import { useEffect, useState } from 'react'
import {
  getSafetyChecks,
  getSafetyCheckResponses,
  getMyUnansweredSafetyChecks,
} from '../../safety-checks/api/safetyChecksApi'
import type {
  SafetyCheck,
  SafetyCheckResponseItem,
} from '../../safety-checks/api/types'

type User = {
  id: number
  name: string
  email: string
  roles: string[]
}

const AdminHomePage = ({ user }: { user: User }) => {
  const [checks, setChecks] = useState<SafetyCheck[]>([])
  const [responses, setResponses] = useState<SafetyCheckResponseItem[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false

    const load = async () => {
      try {
        setLoading(true)
        setError('')

        const items = await getSafetyChecks()
        const activeCheck = items.find((item) => item.status === 'active')

        const responseItems = activeCheck
          ? await getSafetyCheckResponses(activeCheck.id)
          : []

        if (!cancelled) {
          setChecks(items)
          setResponses(responseItems)
        }
      } catch {
        if (!cancelled) setError('安全確認データを取得できませんでした。')
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    void load()
    return () => {
      cancelled = true
    }
  }, [])

  const activeCount = checks.filter((c) => c.status === 'active').length
  const scheduledCount = checks.filter((c) => c.status === 'scheduled').length
  const closedCount = checks.filter((c) => c.status === 'closed').length
  const productionCount = checks.filter(
    (c) => c.type === 'production' && c.status === 'active',
  ).length

  const safeCount = responses.filter((r) => r.status === 'safe').length
  const seriousCount = responses.filter((r) => r.status === 'serious').length
  const trafficCount = responses.filter((r) => r.status === 'traffic').length
  const unknownCount = responses.filter((r) => r.status === 'unknown').length
  const unansweredCount = responses.filter((r) => r.status === null).length

  const recentChecks = [...checks]
    .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 5)

  const formatDate = (value: string | null) =>
    value
      ? new Date(value).toLocaleString('ja-JP', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit',
        })
      : '—'

  return (
    <section className="home-page dashboard-page">
      <header className="dashboard-heading">
        <div>
          <p className="dashboard-eyebrow">SAFETY MANAGEMENT</p>
          <h1>状況確認</h1>
          <p className="dashboard-welcome">お疲れさまです、{user.name} さん</p>
        </div>
        <span className="dashboard-role">ADMIN</span>
      </header>

      {error && <p className="error-message">{error}</p>}

      <section className="dashboard-metrics" aria-label="安全確認の概要">
        <article className="dashboard-metric metric-active">
          <span>発生中</span>
          <strong>{loading ? '—' : activeCount}</strong>
          <small>現在の安全確認</small>
        </article>
        <article className="dashboard-metric metric-scheduled">
          <span>実施予定</span>
          <strong>{loading ? '—' : scheduledCount}</strong>
          <small>開始前の安全確認</small>
        </article>
        <article className="dashboard-metric metric-production">
          <span>災害対応</span>
          <strong>{loading ? '—' : productionCount}</strong>
          <small>発生中の災害対応</small>
        </article>
        <article className="dashboard-metric metric-closed">
          <span>終了済み</span>
          <strong>{loading ? '—' : closedCount}</strong>
          <small>完了した安全確認</small>
        </article>
      </section>

      <section className="dashboard-panel">
        <div className="dashboard-panel-heading">
          <div>
            <p className="dashboard-eyebrow">LATEST ACTIVITY</p>
            <h2>最近の安全確認</h2>
          </div>
          <span className="dashboard-count">{checks.length} 件</span>
        </div>

        {loading ? (
          <p className="dashboard-empty">読み込み中...</p>
        ) : recentChecks.length === 0 ? (
          <p className="dashboard-empty">安全確認はまだありません。</p>
        ) : (
          <div className="dashboard-check-list">
            {recentChecks.map((check) => (
              <article className="dashboard-check-row" key={check.id}>
                <div className="dashboard-check-icon">
                  {check.type === 'production' ? '!' : '✓'}
                </div>
                <div className="dashboard-check-main">
                  <h3>{check.title}</h3>
                  <p>
                    {check.type === 'production' ? '災害対応' : '訓練'}
                    {' / '}
                    {formatDate(check.started_at ?? check.scheduled_at ?? check.created_at)}
                  </p>
                </div>
                <span className={`dashboard-status status-${check.status}`}>
                  {check.status === 'active'
                    ? '発生中'
                    : check.status === 'scheduled'
                      ? '予定'
                      : '終了'}
                </span>
              </article>
            ))}
          </div>
        )}
      </section>

      <section className="dashboard-panel">
        <div className="dashboard-panel-heading">
          <div>
            <p className="dashboard-eyebrow">RESPONSE STATUS</p>
            <h2>回答状況</h2>
          </div>
          <span className="dashboard-count">最新の発生中案件</span>
        </div>

        <div className="dashboard-response-grid">
          {[
            ['無事', safeCount, 'response-safe'],
            ['重傷', seriousCount, 'response-serious'],
            ['交通困難', trafficCount, 'response-traffic'],
            ['状況不明', unknownCount, 'response-unknown'],
            ['未回答', unansweredCount, 'response-unanswered'],
          ].map(([label, count, className]) => (
            <div className={`response-card ${className}`} key={label}>
              <span className="response-label">
                <span className="dot" />
                {label}
              </span>
              <strong>{loading ? '—' : count}</strong>
            </div>
          ))}
        </div>
      </section>
    </section>
  )
}

const UserHomePage = ({ user }: { user: User }) => {
  const [checks, setChecks] = useState<SafetyCheck[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false

    const load = async () => {
      try {
        setLoading(true)
        setError('')
        const items = await getMyUnansweredSafetyChecks()

        if (!cancelled) setChecks(items)
      } catch {
        if (!cancelled) setError('安否確認の状況を取得できませんでした。')
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    void load()
    return () => {
      cancelled = true
    }
  }, [])

  return (
    <section className="home-page dashboard-page">
      <header className="dashboard-heading">
        <div>
          <p className="dashboard-eyebrow">SAFETY CHECK</p>
          <h1>ホーム</h1>
          <p className="dashboard-welcome">こんにちは、{user.name} さん</p>
        </div>
      </header>

      {error ? (
        <p className="error-message">{error}</p>
      ) : loading ? (
        <section className="dashboard-panel">
          <p className="dashboard-empty">安否確認の状況を確認しています...</p>
        </section>
      ) : checks.length > 0 ? (
        <section className="dashboard-panel">
          <div className="dashboard-panel-heading">
            <div>
              <p className="dashboard-eyebrow">ACTION REQUIRED</p>
              <h2>未回答の安否確認があります</h2>
            </div>
            <span className="dashboard-count">{checks.length} 件</span>
          </div>
          <div className="dashboard-check-list">
            {checks.map((check) => (
              <article className="dashboard-check-row" key={check.id}>
                <div className="dashboard-check-icon">!</div>
                <div className="dashboard-check-main">
                  <h3>{check.title}</h3>
                  <p>{check.message}</p>
                </div>
                <span className="dashboard-status status-active">未回答</span>
              </article>
            ))}
          </div>
          <p className="dashboard-empty">
            回答依頼メールに記載されたリンクから回答してください。
          </p>
        </section>
      ) : (
        <section className="dashboard-panel">
          <div className="dashboard-panel-heading">
            <div>
              <p className="dashboard-eyebrow">ALL CAUGHT UP</p>
              <h2>現在、未回答の安否確認はありません</h2>
            </div>
          </div>
          <p className="dashboard-empty">
            新しい安否確認が届いた場合は、メールをご確認ください。
          </p>
        </section>
      )}
    </section>
  )
}

const HomePage = ({ user }: { user: User }) =>
  user.roles.includes('admin') ? (
    <AdminHomePage user={user} />
  ) : (
    <UserHomePage user={user} />
  )

export default HomePage
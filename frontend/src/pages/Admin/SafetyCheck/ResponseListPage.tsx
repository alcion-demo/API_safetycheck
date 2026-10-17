
import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { getSafetyCheckResponses } from '../../../safety-checks/api/safetyChecksApi'
import type { SafetyCheckResponseItem } from '../../../safety-checks/api/types'

type ResponseFilter =
  | 'all'
  | 'unanswered'
  | 'safe'
  | 'serious'
  | 'traffic'
  | 'unknown'

const ResponseListPage = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const [responses, setResponses] = useState<SafetyCheckResponseItem[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [filter, setFilter] = useState<ResponseFilter>('all')
  const [searchText, setSearchText] = useState('')

  useEffect(() => {
    const fetchResponses = async () => {
      if (!id) {
        setError('安否確認IDが取得できません。')
        setLoading(false)
        return
      }

      try {
        const data = await getSafetyCheckResponses(Number(id))
        setResponses(data)
      } catch {
        setError('回答結果の取得に失敗しました。')
      } finally {
        setLoading(false)
      }
    }

    fetchResponses()
  }, [id])

  const getStatusLabel = (status: SafetyCheckResponseItem['status']) => {
    switch (status) {
      case 'safe':
        return '無事'
      case 'serious':
        return '重症'
      case 'traffic':
        return '交通事情で移動困難'
      case 'unknown':
        return '状況不明'
      default:
        return '未回答'
    }
  }

  const filteredResponses = responses.filter((response) => {
    // 回答状況で絞り込む
    if (filter === 'unanswered' && response.status !== null) {
      return false
    }

    if (filter !== 'all' && filter !== 'unanswered' && response.status !== filter) {
      return false
    }

    // 名前・メールアドレスで検索する
    const keyword = searchText.trim().toLowerCase()
    if (!keyword) {
      return true
    }

    const name = response.user?.name?.toLowerCase() ?? ''
    const email = response.user?.email?.toLowerCase() ?? ''

    return name.includes(keyword) || email.includes(keyword)
  })

  return (
    <main className="safety-check-management-page">
      <section className="safety-check-management-card">
        <h1>回答結果一覧</h1>

        {loading ? (
          <p>回答結果を読み込んでいます...</p>
        ) : error ? (
          <p className="error-message" role="alert">{error}</p>
        ) : (
          <>
            <div
              style={{
                marginBottom: '16px',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                gap: '12px',
                flexWrap: 'wrap',
              }}
            >
              <div
                style={{
                  display: 'flex',
                  gap: '12px',
                  flexWrap: 'wrap',
                  alignItems: 'center',
                }}
              >
                <label htmlFor="response-search">個人検索：</label>
                <input
                  id="response-search"
                  type="search"
                  placeholder="名前・メールアドレス"
                  value={searchText}
                  onChange={(event) => setSearchText(event.target.value)}
                />

                <label htmlFor="response-filter">回答状況：</label>
                <select
                  id="response-filter"
                  value={filter}
                  onChange={(event) =>
                    setFilter(event.target.value as ResponseFilter)
                  }
                >
                  <option value="all">全員</option>
                  <option value="unanswered">未回答</option>
                  <option value="safe">無事</option>
                  <option value="serious">重症</option>
                  <option value="traffic">交通事情で移動困難</option>
                  <option value="unknown">状況不明</option>
                </select>
              </div>

              <button
                type="button"
                onClick={() => navigate('/admin/safety-checks')}
              >
                安否確認一覧へ戻る
              </button>
            </div>

            {responses.length === 0 ? (
              <p>登録ユーザーがいません。</p>
            ) : filteredResponses.length === 0 ? (
              <p>該当するユーザーはいません。</p>
            ) : (
              <div className="user-table-wrapper">
                <table className="user-table">
                  <thead>
                    <tr>
                      <th>回答者</th>
                      <th>メールアドレス</th>
                      <th>回答</th>
                      <th>メモ</th>
                      <th>回答日時</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredResponses.map((response) => (
                      <tr key={response.user_id}>
                        <td>{response.user?.name ?? '不明'}</td>
                        <td>{response.user?.email ?? '不明'}</td>
                        <td>{getStatusLabel(response.status)}</td>
                        <td>{response.memo || '—'}</td>
                        <td>
                          {response.created_at
                            ? new Date(response.created_at).toLocaleString('ja-JP')
                            : '—'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </>
        )}
      </section>
    </main>
  )
}

export default ResponseListPage

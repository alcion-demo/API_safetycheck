
import { useEffect, useState } from 'react'
import type { SubmitEvent } from 'react'
import { useParams } from 'react-router-dom'
import {
  getSafetyCheck,
  submitSafetyCheckResponse,
} from './api/safetyChecksApi'
import type { SafetyCheck } from './api/types'
import type { SafetyCheckResponseStatus } from './api/safetyChecksApi'


const ResponsePage = () => {
  const { id } = useParams<{ id: string }>()
  const [safetyCheck, setSafetyCheck] = useState<SafetyCheck | null>(null)
  const [status, setStatus] = useState<SafetyCheckResponseStatus | ''>('')
  const [memo, setMemo] = useState('')
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [submitted, setSubmitted] = useState(false)

  useEffect(() => {
    const fetchSafetyCheck = async () => {
      if (!id || !Number.isInteger(Number(id))) {
        setError('安否確認の指定が正しくありません。')
        setLoading(false)
        return
      }

      try {
        const data = await getSafetyCheck(Number(id))
        setSafetyCheck(data)

        if (data.status !== 'active') {
          setError('この安否確認は現在回答を受け付けていません。')
        }
      } catch {
        setError('安否確認の取得に失敗しました。')
      } finally {
        setLoading(false)
      }
    }

    fetchSafetyCheck()
  }, [id])

  const handleSubmit = async (event: SubmitEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError('')
    setMessage('')

    if (!id || !status) {
      setError('状態を選択してください。')
      return
    }

    setSubmitting(true)

    try {
      await submitSafetyCheckResponse(Number(id), {
        status,
        memo: memo.trim() || undefined,
      })
      setMessage('回答を登録しました。')
      setSubmitted(true)
    } catch {
      setError('回答の登録に失敗しました。もう一度お試しください。')
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) {
    return <main><p>安否確認を読み込んでいます...</p></main>
  }

  if (!safetyCheck) {
    return (
      <main>
        <p className="error-message" role="alert">
          {error || '安否確認が見つかりません。'}
        </p>
      </main>
    )
  }

  const canRespond = safetyCheck.status === 'active'

  return (
    <main className="safety-check-response-page">
      <section className="safety-check-management-card">
        <h1>安否確認への回答</h1>

        <h2>{safetyCheck.title}</h2>
        <p>{safetyCheck.message}</p>

        {!canRespond && (
          <p role="alert">
            この安否確認は現在回答を受け付けていません。
          </p>
        )}

        {error && (
          <p className="error-message" role="alert">{error}</p>
        )}

        {message && <p role="status">{message}</p>}

        {canRespond && !submitted && (
          <form onSubmit={handleSubmit}>
            <fieldset disabled={submitting}>
              <legend>現在の状況（必須）</legend>

              <label>
                <input
                  type="radio"
                  name="status"
                  value="safe"
                  checked={status === 'safe'}
                  onChange={() => setStatus('safe')}
                  required
                />
                無事
              </label>

              <label>
                <input
                  type="radio"
                  name="status"
                  value="serious"
                  checked={status === 'serious'}
                  onChange={() => setStatus('serious')}
                />
                重傷・重体
              </label>

              <label>
                <input
                  type="radio"
                  name="status"
                  value="traffic"
                  checked={status === 'traffic'}
                  onChange={() => setStatus('traffic')}
                />
                交通麻痺
              </label>

              <label>
                <input
                  type="radio"
                  name="status"
                  value="unknown"
                  checked={status === 'unknown'}
                  onChange={() => setStatus('unknown')}
                />
                不明
              </label>

              <label>
                <input
                  type="radio"
                  name="status"
                  value="not_applicable"
                  checked={status === 'not_applicable'}
                  onChange={() => setStatus('not_applicable')}
                />
                災害対象外地域
              </label>

            </fieldset>

            <div>
              <label htmlFor="memo">備考（任意）</label>
              <textarea
                id="memo"
                value={memo}
                onChange={(event) => setMemo(event.target.value)}
                maxLength={2000}
                rows={4}
              />
            </div>

            <button type="submit" disabled={submitting || !status}>
              {submitting ? '送信中...' : '回答を登録'}
            </button>
          </form>
        )}
      </section>
    </main>
  )
}

export default ResponsePage

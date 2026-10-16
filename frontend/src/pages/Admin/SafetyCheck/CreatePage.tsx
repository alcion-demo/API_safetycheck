
import { useState, type SubmitEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { createSafetyCheck } from '../../../safety-checks/api/safetyChecksApi'
import axios from 'axios'

const SafetyCheckCreatePage = () => {
  const navigate = useNavigate()
  const [scheduledDate, setScheduledDate] = useState('')
  const [scheduledTime, setScheduledTime] = useState('')
  const [errors, setErrors] = useState<{
    scheduled_at?: string
  }>({})
  const title = '防災訓練のお知らせ'
  const message = '現在、防災訓練で安否確認を実施しています。'
  const [submitting, setSubmitting] = useState(false)
  const todayString = new Date().toISOString().split('T')[0]

  const handleSubmit = async (event: SubmitEvent<HTMLFormElement>) => {
    event.preventDefault()
    setErrors({})
    setSubmitting(true)

    try {
      await createSafetyCheck({
        title,
        message,
        type: 'training',
        scheduled_at: `${scheduledDate} ${scheduledTime}:00`,
      })

      navigate('/admin/safety-checks')

      } catch (error: unknown) {
        if (axios.isAxiosError(error)) {
          const validationErrors = error.response?.data?.errors

          if (validationErrors && typeof validationErrors === 'object') {
            setErrors({
              scheduled_at: validationErrors.scheduled_at?.[0],
            })
          } else {
            setErrors({
              scheduled_at:
                error.response?.data?.message ??
                '予約に失敗しました。入力内容や通信状況を確認してください。',
            })
          }
        } else {
          setErrors({
            scheduled_at: '予期しないエラーが発生しました。',
          })
        }
      } finally {
        setSubmitting(false)
      }
  }

  return (
    <section
      className="safety-check-management-card safety-check-create-page"
      style={{
        width: '100%',
        maxWidth: '900px',
        margin: '24px 0',
        padding: '28px 32px',
        boxSizing: 'border-box',
      }}
    >
      <h1 style={{ margin: '0 0 8px' }}>訓練の予約</h1>

      <p style={{ margin: '0 0 28px', color: '#666' }}>
        安否確認訓練の開始予定日時を設定します。
      </p>

      <form className="safety-check-create-form" onSubmit={handleSubmit} noValidate>

        <div style={{ marginBottom: '28px' }}>
          <div
            style={{
              display: 'flex',
              gap: '16px',
              alignItems: 'flex-start',
              flexWrap: 'wrap',
            }}
          >
            <div style={{ flex: '1 1 220px' }}>
              <label
                htmlFor="scheduledDate"
                style={{
                  display: 'block',
                  marginBottom: '8px',
                  fontWeight: 600,
                }}
              >
                開始予定日
              </label>
              <input
                id="scheduledDate"
                type="date"
                value={scheduledDate}
                min={todayString}
                onChange={(event) => setScheduledDate(event.target.value)}
                style={{
                  display: 'block',
                  width: '100%',
                  padding: '10px 12px',
                  boxSizing: 'border-box',
                }}
              />
            </div>

            <div style={{ flex: '1 1 180px' }}>
              <label
                htmlFor="scheduledTime"
                style={{
                  display: 'block',
                  marginBottom: '8px',
                  fontWeight: 600,
                }}
              >
                開始予定時刻
              </label>
              <input
                id="scheduledTime"
                type="time"
                value={scheduledTime}
                onChange={(event) => setScheduledTime(event.target.value)}
                style={{
                  display: 'block',
                  width: '100%',
                  padding: '10px 12px',
                  boxSizing: 'border-box',
                }}
              />
            </div>
          </div>

          {errors.scheduled_at && (
            <p className="safety-check-form-error" role="alert">
              {errors.scheduled_at}
            </p>
          )}
        </div>

        <div
          style={{
            display: 'flex',
            justifyContent: 'flex-end',
            gap: '12px',
            borderTop: '1px solid #ddd',
            paddingTop: '20px',
          }}
        >
          <button type="button" onClick={() => navigate('/admin/safety-checks')}>
            キャンセル
          </button>
          <button type="submit" disabled={submitting}>
            {submitting ? '予約中...' : '予約する'}
          </button>
        </div>
      </form>
    </section>
  )
}

export default SafetyCheckCreatePage

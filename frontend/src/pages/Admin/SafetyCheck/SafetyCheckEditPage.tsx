import { useEffect, useState, type SubmitEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  getSafetyChecks,
  updateSafetyCheck,
} from '../../../safety-checks/api/safetyChecksApi'
import axios from 'axios'

const SafetyCheckEditPage = () => {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()

  const [scheduledDate, setScheduledDate] = useState('')
  const [scheduledTime, setScheduledTime] = useState('')
  const [errors, setErrors] = useState<{
    scheduled_at?: string
  }>({})
  const [submitting, setSubmitting] = useState(false)
  const [loading, setLoading] = useState(true)

  const title = '防災訓練のお知らせ'
  const message = '現在、防災訓練で安否確認を実施しています。'
  const todayString = new Date().toLocaleDateString('sv-SE')

  useEffect(() => {
    const loadSafetyCheck = async () => {
      try {
        if (!id || !Number.isInteger(Number(id)) || Number(id) <= 0) {
          setErrors({ scheduled_at: '予約IDが正しくありません。' })
          return
        }

        const safetyChecks = await getSafetyChecks()
        const safetyCheck = safetyChecks.find(
          (item) => item.id === Number(id),
        )

        if (!safetyCheck) {
          setErrors({ scheduled_at: '予約が見つかりません。' })
          return
        }

        if (
          safetyCheck.type !== 'training' ||
          safetyCheck.status !== 'scheduled'
        ) {
          setErrors({
            scheduled_at: 'この予約は編集できません。',
          })
          return
        }

        if (safetyCheck.scheduled_at) {
          const scheduledAt = new Date(
            safetyCheck.scheduled_at.replace(' ', 'T'),
          )

          const year = scheduledAt.getFullYear()
          const month = String(scheduledAt.getMonth() + 1).padStart(2, '0')
          const day = String(scheduledAt.getDate()).padStart(2, '0')
          const hours = String(scheduledAt.getHours()).padStart(2, '0')
          const minutes = String(scheduledAt.getMinutes()).padStart(2, '0')

          setScheduledDate(`${year}-${month}-${day}`)
          setScheduledTime(`${hours}:${minutes}`)
        }
      } catch {
        setErrors({
          scheduled_at: '予約情報の取得に失敗しました。',
        })
      } finally {
        setLoading(false)
      }
    }

    void loadSafetyCheck()
  }, [id])

  const handleSubmit = async (event: SubmitEvent<HTMLFormElement>) => {
    event.preventDefault()
    setErrors({})

    setSubmitting(true)

    try {
      if (!id) {
        setErrors({ scheduled_at: '予約IDが正しくありません。' })
        return
      }

      await updateSafetyCheck(Number(id), {
        title,
        message,
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
              '更新に失敗しました。入力内容や通信状況を確認してください。',
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

  if (loading) {
    return (
      <section className="safety-check-management-card safety-check-create-page">
        予約情報を読み込んでいます...
      </section>
    )
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
      <h1 style={{ margin: '0 0 8px' }}>訓練の予約を編集</h1>

      <p style={{ margin: '0 0 28px', color: '#666' }}>
        安否確認訓練の開始予定日時を変更します。
      </p>

      <form
        className="safety-check-create-form"
        onSubmit={handleSubmit}
        noValidate
      >
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
          <button
            type="button"
            onClick={() => navigate('/admin/safety-checks')}
          >
            キャンセル
          </button>
          <button type="submit" disabled={submitting}>
            {submitting ? '更新中...' : '変更を保存'}
          </button>
        </div>
      </form>
    </section>
  )
}

export default SafetyCheckEditPage

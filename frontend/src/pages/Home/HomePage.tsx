type User = {
  id: number
  name: string
  email: string
  roles: string[]
}

const HomePage = ({ user }: { user: User }) => (
  <section className="home-page">
    <h1>ホーム</h1>
    <p>ログインしました。</p>

    <dl className="user-info">
      <dt>ユーザー名</dt>
      <dd>{user.name}</dd>

      <dt>メールアドレス</dt>
      <dd>{user.email}</dd>
    </dl>
  </section>
)

export default HomePage
# Reactの安否確認  

<img alt="Static Badge" src="https://img.shields.io/badge/wsl2-w?style=plastic&logo=linux&logoColor=000000&labelColor=%23FCC624&color=%23FCC624"> <img alt="Static Badge" src="https://img.shields.io/badge/ubuntu-u?style=plastic&logo=ubuntu&logoColor=%23ffffff&labelColor=%23E95420&color=%23E95420"> <img alt="Static Badge" src="https://img.shields.io/badge/alpine-l?style=plastic&logo=alpinelinux&logoColor=%23ffffff&labelColor=%230D597F&color=%230D597F">
<img alt="Static Badge" src="https://img.shields.io/badge/Docker-d?style=plastic&logo=docker&logoColor=%23ffffff&labelColor=%232496ED&color=%232496ED">  
<img alt="Static Badge" src="https://img.shields.io/badge/NGINX-n?style=plastic&logo=nginx&logoColor=%23ffffff">
<img alt="Static Badge" src="https://img.shields.io/badge/MySQL-m?style=plastic&logo=mysql&logoColor=%23ffffff&labelColor=%234479A1&color=%234479A1">
<img alt="Static Badge" src="https://img.shields.io/badge/Laravel13-l?style=plastic&logo=laravel&logoColor=%23ffffff&labelColor=%23FF2D20&color=%23FF2D20">
<img alt="Static Badge" src="https://img.shields.io/badge/php-p?style=plastic&logo=php&logoColor=%23ffffff&labelColor=%23777BB4&color=%23777BB4">  
<img alt="Static Badge" src="https://img.shields.io/badge/React-%20?style=plastic&logo=react&logoColor=%23000000&labelColor=%2361DAFB&color=%2361DAFB">
<img alt="Static Badge" src="https://img.shields.io/badge/TypeScript-%20?style=plastic&logo=typescript&logoColor=%233178C6&labelColor=%23ffffff&color=%23ffffff">
<img alt="Static Badge" src="https://img.shields.io/badge/tailwind-%20?style=plastic&logo=tailwindcss&logoColor=ffffff&color=%2306B6D4">
<img alt="Static Badge" src="https://img.shields.io/badge/vite-v?style=plastic&logo=vite&logoColor=%23ffffff&labelColor=%23646CFF&color=%23646CFF">
<img alt="Static Badge" src="https://img.shields.io/badge/npm-n?style=plastic&logo=npm&logoColor=%23ffffff&labelColor=%23CB3837&color=%23CB3837">  

## プロジェクト概要
このリポジトリは、バックエンドに Laravel、フロントエンドに React + TypeScript + Vite を持つ SPA 型の安否確認アプリケーションです。

確認できた実装の軸は次の通りです。

- Backend: Laravel 13 を使用した API と認証基盤
- Frontend: React 19 + TypeScript + Vite によるシングルページアプリケーション
- 安否確認通知
- 管理者向けユーザー管理


## 学習・検証目的
- CRUD + API通信の理解
- SPAでの認証についての理解
- バックエンドを中心とした開発において、フロントエンドとの連携に必要な知識・実装の理解
- React / TypeScriptからLaravel APIを利用する一連の流れの検証

## 主な機能
- CRUD + API通信
  - 後で書き直す
  - `AdminController` にて 管理者向けユーザー管理 API（一覧・作成・更新・削除）
- 認証フロー
  - ユーザー認証（Fortify）
  - API 認証（Sanctum ミドルウェア適用）
- フロントエンド骨格（React + TypeScript + Vite + Tailwind）
- 管理者チェック用ミドルウェア
  - ユーザー一覧取得
  - ユーザー追加
  - ユーザー更新
  - ユーザー削除

## 使用技術
| カテゴリ | 使用技術 |
| :--- | :--- |
| **Backend** | Laravel 13 |
| **Frontend** | React `^19.2.7`, Tailwind CSS, Node.js |
| **Authentication / Authorization** | Fortify |
| **Infrastructure** | Docker Compose (App / Node / MySQL / Nginx) |
| **OS Environment** | WSL2 (Ubuntu / Alpine Linux) |
| **Database** | MySQL 8.x |

## セットアップ手順

### 1. インフラのビルドと起動
```
docker compose build
docker compose up -d
```

### 2. バックエンド初期化
```
docker compose exec app ash
composer create-project laravel/laravel .
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```
#### 3. 認証・認可packcageの導入
```
composer require laravel/sanctum
php artisan install:api
composer require laravel/fortify
php artisan fortify:install
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```
### 4. フロントエンド初期化
```
docker compose run --rm node sh
npm create vite@latest . -- --template react-ts
npm install
npm install tailwindcss @tailwindcss/vite
npm install @iconify/react
```
#### 5. .env修正・作成  
※ バックエンドはcompose.ymlに設定した内容に修正。
※ フロントエンドは作成する。
```
VITE_API_URL=http://localhost:8000/api
```
#### 6. マイグレーション
```
php artisan migrate
```
#### 7. その他(バックエンド)
```
composer require --dev "squizlabs/php_codesniffer=*"
composer require --dev barryvdh/laravel-debugbar
composer require laravel-lang/lang:~8.0
php artisan lang:publish
cp ./vendor/laravel-lang/lang/json/ja.json ./lang/
cp -r ./vendor/laravel-lang/lang/src/ja ./lang/
```

#### 8. その他(フロントエンド)
```
docker compose exec node sh
npm install axios
npm list axios
npm list react-router-dom
```

#### 9. 認証の設定(バックエンド)
※下記で、`config/core.php`
```
php artisan config:publish cors
```
```
    'allowed_origins' => ['http://localhost:5173'],
    'supports_credentials' => true,
```

#### 10. メール設定(gmailの場合)  
- セキュリティで2段階認証をonにする  
- .env下記内容を修正
```
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=受信先のメールアドレス
MAIL_PASSWORD=アプリパスワード
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="送信元のメールアドレス"
```

## ディレクトリ構成（主要部分）後で書き直す
- compose.yaml
- php
  - `Dockerfile`
- nginx
  - `default.conf`
- backend
  - `artisan`
  - composer.json
  - package.json
  - .env.example
  - `routes/`
    - web.php
    - api.php
  - `app/`
    - `Http/`
      - `Controllers/`
        - Controller.php
        - `Shopping/`
          - ShoppingController.php
          - RecipeController.php
        - `User/`
          - UserController.php
        - `Admin/`
          - AdminUserController.php
      - `Middleware/`
        - Admin.php
      - `Requests/`
        - StoreShoppingRequest.php
        - UpdateShoppingRequest.php
        - SuggestionRequest.php
        - UpdateProfileRequest.php
        - UpdatePasswordRequest.php
    - `Models/`
      - User.php
      - ShoppingItem.php
      - CurrentCart.php
      - PurchaseLog.php
    - `Services/`
      - ShoppingService.php
      - RecipeGenerator.php
      - RecipeShareService.php
      - UserService.php
    - `Jobs/`
      - GenerateRecipeJob.php
    - `Ai/`
      - `Agents/`
        - NoblemanAgent.php
    - `Providers/`
      - AppServiceProvider.php
      - FortifyServiceProvider.php
    - `Actions/`
      - `Fortify/`
        - CreateNewUser.php
        - UpdateUserPassword.php
        - ResetUserPassword.php
        - UpdateUserProfileInformation.php
        - PasswordValidationRules.php
    - `Enums/`
      - ShopType.php
  - `resources/`
    - `views/`
      - welcome.blade.php
  - `database/`
    - `migrations/`
- frontend
  - package.json
  - README.md
  - `src/`
    - `components/`
      - `Layout/`
        - AppLayout.tsx
      - `Header.tsx`
      - `BottomNav.tsx`
      - `ApplicationLogo.tsx`
    - `pages/`
      - `shopping/`
        - ShoppingPage.tsx
        - ListSection.tsx
        - EditForm.tsx
        - HistorySection.tsx
        - `recipe/`
          - RecipeSection.tsx
          - RecipeCard.tsx
          - RecipeSharePage.tsx
      - `Admin/`
        - `User/`
          - UserForm.tsx
          - UserItem.tsx
          - UserEditForm.tsx
        - AdminUserPage.tsx
      - `Settings/`
        - SettingsPage.tsx
    - `utils/`
      - axios.ts
    - App.tsx
    - `main.tsx`
    - `login.tsx`
    - `Register.tsx`
  - `public/`

## 設計・実装の特徴設計・実装の特徴
- バックエンドはサービス層とモデル層に分離
  - ShoppingService
  - UserService
  - RecipeGenerator
  - RecipeShareService
- モデルでスコープとキャストを定義
- 後で書き直す
- フロントエンドは状態管理を React の useState / useEffect で実施
- フロントエンドは React Hooks でシンプルに API 連携し、CRUD 操作を実装

---

## 処理の流れ後で書き直す
```mermaid
graph TD
    A[React Frontend] -->|ログイン/登録/API呼び出し| B[Axios]
    B --> C[Laravel API Routes]
    C --> D[ShoppingController]
    C --> E[AdminUserController]
    C --> F[UserController]
    C --> G[RecipeController]

    D --> H[ShoppingService]
    H --> I[CurrentCart]
    H --> J[PurchaseLog]
    H --> K[ShoppingItem]

    E --> L[UserService]
    L --> M[User]

    G --> N[GenerateRecipeJob]
    N --> O[RecipeGenerator]
    O --> P[NoblemanAgent]
    P --> Q[AI Provider]
    N --> R[Cache]

    C --> S[Fortify + Sanctum]
    S --> T[User Auth]

    I --> U[(MySQL / SQLite depending on env)]
    J --> U
    M --> U
```
## クラス構成図後で書き直す
```mermaid
classDiagram
    class User {
        +id
        +name
        +email
        +password
        +is_admin
        +casts()
    }

    class ShoppingItem {
        +id
        +user_id
        +name
        +is_active
        +recentPurchaseLogs()
        +scopeActive()
        +scopeForUser()
    }

    class CurrentCart {
        +id
        +shopping_item_id
        +price
        +quantity
        +shop_type
        +item()
        +scopeForUser()
    }

    class PurchaseLog {
        +id
        +shopping_item_id
        +price
        +quantity
        +shop_type
        +purchased_at
        +item()
        +scopeForUser()
        +getPurchasedDateStringAttribute()
        +getFrequentItems()
        +getRecentPurchaseIn3Days()
    }

    class ShoppingController {
        +index()
        +store()
        +update()
        +destroy()
        +purchase()
    }

    class AdminUserController {
        +index()
        +store()
        +update()
        +destroy()
    }

    class UserController {
        +update()
        +destroy()
        +updatePassword()
    }

    class RecipeController {
        +store()
        +show()
        +shared()
    }

    class ShoppingService {
        +savePurchase()
        +recordPurchase()
        +deleteCart()
        +isInvalid()
        +getRecentPurchasedAt()
        +updateCart()
    }

    class UserService {
        +createUser()
        +userList()
    }

    class RecipeGenerator {
        +generate()
    }

    class NoblemanAgent {
        +instructions()
        +messages()
        +tools()
        +schema()
    }

    class GenerateRecipeJob {
        +handle()
    }

    class RecipeShareService {
        +encode()
        +decode()
    }

    User --> ShoppingItem
    ShoppingItem --> CurrentCart
    ShoppingItem --> PurchaseLog
    ShoppingController --> ShoppingService
    AdminUserController --> UserService
    RecipeController --> ShoppingService
    RecipeController --> RecipeShareService
    GenerateRecipeJob --> RecipeGenerator
    RecipeGenerator --> NoblemanAgent
```
## 今後の改善予定
- 後で書き直す
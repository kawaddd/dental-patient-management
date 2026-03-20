# チケット進捗管理

顧客施術履歴管理システム（Laravel + Supabase PostgreSQL + Tailwind CSS）

---

## ステータス凡例

| 記号 | 意味 |
|------|------|
| ⬜ | 未着手 |
| 🔄 | 進行中 |
| ✅ | 完了 |

---

## チケット構成の考え方

**共通基盤（TICKET-00）を先に完了させ、その後は各画面チケットを独立して作業・確認できる構成にしています。**

- 各画面チケットは専用のSeederを持ち、他のチケットが完了していなくても単体で動作確認できます
- 認証ミドルウェアは各チケットの確認手順に「一時的に外す方法」を記載しています

---

## チケット一覧

| チケット | 概要 | 依存 | 進捗 |
|---------|------|------|------|
| [TICKET-00](./ticket-00-foundation.md) | 共通基盤（Migration・Model・レイアウト） | なし | ⬜ |
| [TICKET-01](./ticket-01-login.md) | ログイン画面 | TICKET-00 | ⬜ |
| [TICKET-02](./ticket-02-dashboard.md) | ダッシュボード画面 | TICKET-00 | ⬜ |
| [TICKET-03](./ticket-03-customer-list.md) | 顧客一覧画面 | TICKET-00 | ⬜ |
| [TICKET-04](./ticket-04-customer-detail.md) | 顧客詳細画面（施術履歴タイムライン） | TICKET-00 | ⬜ |
| [TICKET-05](./ticket-05-csv-import.md) | CSVインポート画面 | TICKET-00 | ⬜ |
| [TICKET-06](./ticket-06-import-history.md) | インポート履歴画面 | TICKET-00 | ⬜ |
| [TICKET-07](./ticket-07-ui-design.md) | UI/UXデザイン実装 | TICKET-00〜06 | ⬜ |

**合計: 0 / 8 完了**

---

## 作業順序

```
TICKET-00（必須・最初に完了させる）
    │
    ├── TICKET-01（ログイン）        ← 他と並行可
    ├── TICKET-02（ダッシュボード）  ← 他と並行可
    ├── TICKET-03（顧客一覧）        ← 他と並行可
    ├── TICKET-04（顧客詳細）        ← 他と並行可
    ├── TICKET-05（CSVインポート）   ← 他と並行可
    └── TICKET-06（インポート履歴）  ← 他と並行可
```

TICKET-00 完了後は、TICKET-01〜06 を任意の順序・並行で進められます。

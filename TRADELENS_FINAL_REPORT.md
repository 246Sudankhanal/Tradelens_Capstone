# NIT3003 / Applied Capstone Project
# FINAL REPORT
# TradeLens — AI-Enhanced Trading Journal

**Team Members:**  
Sudan Khanal  
Rajan Shrestha  
Jagdish Pyakurel  

**Supervisor:** *[Insert supervisor name]*  
**Campus:** Footscray Park  
**Semester:** Block 4, Semester 1 2026  

---

## Table of Contents

1. Introduction  
   1.1 Scope Review  
2. System Requirements Review  
   2.1 Functional Requirements Review  
   2.2 Non-Functional Requirements Review  
3. Database  
   3.1 Database Review  
   3.2 Database Layout and Functionality  
4. System Architecture  
   4.1 Technologies Used  
   4.2 User Interface Descriptions  
   4.3 Subsystems Explained  
5. Challenges and Problems  
6. Student Contributions  
7. References  

---

## 1 Introduction

### 1.1 Scope Review — Sudan Khanal

Retail traders now have easy access to markets through low-cost brokers, but most still struggle to stay consistent. Professional traders treat a journal as a core tool: they log entries and exits, record how they felt, and review results over time. Many retail traders still use spreadsheets, which are slow, easy to break, and give no visual or psychological feedback.

TradeLens is a web-based trading journal built for that gap. A registered user can log Buy and Sell trades, attach notes and emotion tags, import history from CSV or Excel, and review performance on a dashboard. An AI assistant reads the user’s own stats and recent trades and answers questions about win rate, P&L, and emotional patterns. It does not give buy/sell advice for specific assets.

The live system also includes extras that grew out of testing: Google sign-in (OAuth placeholders wired to a real callback), MetaTrader auto-sync through MetaApi, saved chat history, a full-screen chat mode, and advanced dashboard charts (30-day P&L calendar, weekday P&L, win/loss doughnut, P&L by asset, average P&L by emotion).

**In scope**

- Secure registration, login, logout, and profile/password updates  
- Full trade CRUD with live P&L preview  
- Search, filter, and sort on the journal  
- Dashboard metrics and charts  
- CSV/Excel import with a downloadable template  
- Context-aware AI chat with stored conversation history  
- MySQL persistence with per-user isolation  

**Out of scope / future**

- Live market prices and order execution  
- Native mobile apps  
- Multi-broker production sync beyond the MetaApi prototype  
- Institutional reporting and tax packs  

The product is a PHP + MySQL web app with a dark, desktop-first UI that also works on phones. The team used GitHub for version control and a shared XAMPP / PHP built-in server for local testing.

---

## 2 System Requirements Review

### 2.1 Functional Requirements Review

| ID | Requirement | Status |
|----|-------------|--------|
| FR-01 | Register, login, logout | Achieved |
| FR-02 | Update profile and password | Achieved |
| FR-03 | Add, edit, delete trades | Achieved |
| FR-04 | Automatic P&L (long and short) | Achieved |
| FR-05 | Dashboard KPIs (trades, win rate, net P&L, best/worst) | Achieved |
| FR-06 | Search, filter, sort journal | Achieved |
| FR-07 | Cumulative / monthly P&L charts | Achieved |
| FR-08 | Emotion tags on trades | Achieved |
| FR-09 | Bulk CSV / Excel import | Achieved |
| FR-10 | AI assistant using the user’s trade data | Achieved |
| FR-11 | Live P&L preview before save | Achieved |
| FR-12 | Technical documentation | Achieved |

**Additional features delivered**

- Sign-out confirmation modal  
- Google OAuth callback (`api/google_oauth.php`)  
- MetaTrader connect + sync (`api/broker_sync.php`)  
- Chat history and full-screen chat  
- 30-day calendar heatmap with daily P&L, trade count, and weekly totals  

We met the original functional list. Optional items (Google login, broker sync, richer charts) were added after the core journal was stable.

### 2.2 Non-Functional Requirements Review

| Area | Requirement | How we addressed it |
|------|-------------|---------------------|
| Security | Passwords hashed; no raw SQL concatenation | `password_hash` / `password_verify`; PDO prepared statements; session `auth_check` |
| Isolation | Users only see their own trades | Every trade/analytics/chat query filters on `user_id` |
| Performance | Pages and APIs feel instant on local hosting | Indexed `user_id` and `trade_date`; AJAX so tables do not full-reload |
| Usability | Dark theme, sidebar, toasts, empty states | Shared `header.php` / `style.css`; confirmation modals for delete and logout |
| Integrity | Money stored accurately | `DECIMAL(15,4)` prices; P&L rounded to 2 dp in SQL |
| Reliability | Validation on server, not only in the browser | `api/trades.php` and import parser reject bad rows and return messages |
| Maintainability | Clear folders | `api/`, `config/`, `includes/`, `css/`, `js/` |

We did not run a formal load test on production hosting. Locally, dashboard and journal stay responsive with imported sample histories. CSRF tokens were discussed in the plan; session-based APIs plus same-origin fetch were used for the submitted build.

---

## 3 Database

### 3.1 Database Review — Rajan Shrestha

The proposal specified a relational MySQL database rather than a NoSQL store. Trade records are numeric and relational (one user has many trades, conversations, and broker links). SQL aggregations (`SUM`, `COUNT`, `GROUP BY`, `WEEKDAY`) power the dashboard without extra services.

PHP connects through PDO with exceptions enabled and emulated prepares off. `setup.sql` creates the schema; some later columns (`google_id`, `source`, `broker_trade_id`, chat tables) are also created on first use so an existing test database can upgrade without a full rebuild.

Passwords are stored as bcrypt hashes. Google-only accounts receive a random unusable hash so the `password` column can stay NOT NULL.

### 3.2 Database Layout and Functionality

**users**

| Column | Purpose |
|--------|---------|
| id | Primary key |
| name, email | Profile; email unique |
| password | Bcrypt hash |
| google_id | Optional Google subject id |
| auth_provider | `local` or `google` |
| created_at, updated_at | Audit |

**trades**

| Column | Purpose |
|--------|---------|
| id | Primary key |
| user_id | Owner (FK, cascade delete) |
| asset_name | e.g. XAUUSD, AAPL |
| trade_type | ENUM `Buy`, `Sell` |
| entry_price, exit_price | DECIMAL(15,4) |
| quantity | Default 1 |
| trade_date | DATE |
| notes | Optional reflection |
| emotion | Optional psychology tag |
| source | `manual`, import, or broker key |
| broker_trade_id | Deduplicate MetaApi deals |

P&L is not stored as a column. It is calculated:

- Buy: `(exit_price - entry_price) * quantity`  
- Sell: `(entry_price - exit_price) * quantity`  

**broker_connections** — MetaApi account id, region, status, last sync, last error per user and broker.

**chat_conversations** / **chat_messages** — titled threads and `user` / `assistant` messages so history survives a refresh.

Indexes: `idx_trades_user_id`, `idx_trades_trade_date`, `idx_trades_asset_name`.

---

## 4 System Architecture

### 4.1 Technologies Used — Jagdish Pyakurel

| Layer | Choice | Reason |
|-------|--------|--------|
| Frontend | HTML5, CSS3, Vanilla JS | No build step; easy to mark and demonstrate |
| Backend | PHP 8 | Fits XAMPP teaching stack |
| Database | MySQL 8 via PDO | Relational stats and student familiarity |
| Charts | Chart.js 4 (CDN) | Line, bar, doughnut without a frontend framework |
| Icons / font | Font Awesome 6, Inter | Consistent dark UI |
| AI | OpenAI Chat Completions (`gpt-4o-mini`) | Context window large enough for stats + last 10 trades |
| Auth extra | Google OAuth 2.0 | Optional passwordless login |
| Broker extra | MetaApi REST | Cloud bridge to MT4/MT5 history |
| Version control | Git / GitHub | Daily commits per member (see team strategy) |
| Local run | `php -S localhost:8080` or Apache | Fast demo |

GitHub was used so members could work on auth, journal, dashboard, and AI in parallel and merge weekly.

### 4.2 User Interface Descriptions — Jagdish Pyakurel

**Login (`index.php`)**  
Email/password form, Google button, logged-out message. Failed login shows an inline error without leaving the page.

**Register (`register.php`)**  
Name, email, password (min. 6 characters), Google option.

**App chrome (`includes/header.php`)**  
Fixed sidebar: Dashboard, Trade Journal, Profile. Footer shows avatar, name, and Sign out. Sign out opens a confirmation dialog, then `logout.php`.

**Dashboard (`dashboard.php`)**  
Four KPI cards: total trades, win rate, net P&L, best trade (worst in the subtitle). Cumulative vs monthly P&L line chart. Recent five trades. Below that: 30-day calendar heatmap (7 days per row, daily P&L + “n trades”, weekly P&L on the right), weekday bar chart, win/loss doughnut, P&L by asset, average P&L by emotion.

**Trade journal (`trades.php`)**  
Quick stats bar, search, type and date filters, sort. Table with badges and colour-coded P&L. Add Trade modal: direction toggles, $ prefixes, emotion chips, live P&L. Delete confirm. Import modal: drag-and-drop CSV/XLSX, template download, skip/error report.

**Profile (`profile.php`)**  
Name/email, password change, account summary metrics, MetaTrader connect form and Sync now.

**AI widget (`includes/chat_widget.php`)**  
Floating robot button. Panel with history sidebar, new chat, expand-to-full-screen, and composer. History is loaded from `api/chat_history.php`.

### 4.3 Subsystems Explained

#### Authentication — Rajan Shrestha

`api/auth.php` handles `register`, `login`, `update_profile`, and `change_password`. Registration checks unique email. Login sets `user_id`, `user_name`, `user_email` in the session. Protected pages include `auth_check.php`. JSON APIs call `requireAuth()`.

Google flow: `config/oauth.php` holds client id, secret, and redirect URI. `api/google_oauth.php` sends the user to Google, verifies `state`, exchanges the code, reads userinfo, and creates or links a row by email / `google_id`. Redirect URI is taken from the current host so `localhost` and `127.0.0.1` do not drop the session.

Logout is `session_destroy()` then redirect with `msg=logged_out`.

#### Trade journal and P&L — Sudan Khanal

`api/trades.php` is REST-style: GET (filters), POST (create), PUT (update), DELETE. Ownership is always `user_id = session`.

Live preview in the modal:

```
Buy:  (exit - entry) * qty
Sell: (entry - exit) * qty
```

The same formula is used in SQL for the table, dashboard, and AI context so the UI and the database never disagree.

Import (`api/import_trades.php`) maps flexible headers (Asset, Symbol, Entry Price, …), parses dates including Excel serials, and skips bad rows. CSV uses an explicit `fgetcsv` escape argument for PHP 8.4+.

#### Analytics and visualisation — Jagdish Pyakurel

`api/analytics.php` returns KPIs, 12-month series, recent trades, last-30-day heatmap `{ date: { pnl, trades } }`, weekday totals, top assets, and emotion averages. `dashboard.php` renders Chart.js charts and a calendar heatmap in JavaScript. Heatmap rows are Monday–Sunday; padding days outside the 30-day window are dimmed.

#### AI assistant — Sudan Khanal / Jagdish Pyakurel

On each message, `api/chat.php` loads the user’s stats, last 10 trades, and emotion counts into a system prompt. Guardrails: trading-journal topics only; no asset-specific financial advice. Last 20 turns are sent as history. After a successful reply, the user and assistant messages are saved to `chat_conversations` / `chat_messages`. The widget supports new thread, load history, delete thread, and full-screen (Esc to exit).

#### Broker auto-sync — Sudan Khanal

`config/brokers.php` stores the MetaApi token. Connect provisions (or reuses) a cloud account, deploys it, and waits until MetaApi reports connected. Sync pulls history deals, pairs IN/OUT by `positionId` using `entryType`, writes closed trades with `broker_trade_id`, and surfaces API errors on Profile. Open positions are not imported. Lookback is 90 days.

---

## 5 Challenges and Problems

**PHP 8.4 / 8.5 deprecations**  
`fgetcsv()` now requires an explicit `$escape` argument. Imports flooded the server log until the fifth argument was passed.

**Google OAuth host mismatch**  
A fixed redirect URI of `localhost` failed when the browser used `127.0.0.1`, because the session cookie did not come back. The callback now builds the redirect URI from `HTTP_HOST`. Google Cloud must list both origins.

**MetaTrader sync looked “connected” but imported nothing**  
The code checked `entry` on deals; MetaApi uses `entryType`. History also lives on a **regional** client host (often `new-york`, not only London), and a new account can return HTTP 202 until it has synchronised. We now retry 202s, try multiple regions, pair IN/OUT prices, and show `last_error` on Profile.

**Dashboard charts blank after the first line chart**  
`renderHeatmap` used `weeks.length` before `const weeks` (temporal dead zone). That exception stopped weekday, doughnut, asset, and emotion charts. Reordering the variable fixed all of them.

**Heatmap readability**  
The first heatmap copied GitHub’s tiny 16-week grid. Testers could not read it, and a red cell could appear to belong to the wrong day. It was replaced with a 30-day calendar: seven days per row, daily P&L plus “n trades”, weekly P&L at the row end.

**AI context vs persistence**  
The first chatbot kept messages only in browser memory. Refresh lost the thread. We added MySQL conversations and a history sidebar. The model still only receives a slice of history plus live stats, which keeps token use bounded.

**Team Git discipline**  
Four-way (later three-way) work on `style.css` and `header.php` needed small branches and frequent pulls. The 8-week GitHub plan (auth / journal / dashboard / AI) reduced most collisions.

**Design skill**  
None of us are graphic designers. We stayed with a single dark token set (accent blue, green profit, red loss) and Font Awesome rather than custom illustration.

---

## 6 Student Contributions

Work was split using the team GitHub strategy. Features that started as a fourth-member AI/import track were finished inside the three-person team after that allocation was reduced.

| Student ID | Name | Role | Contribution |
|------------|------|------|----------------|
| *[sID]* | Sudan Khanal | Team lead | **40%** |
| *[sID]* | Rajan Shrestha | Team member | **30%** |
| *[sID]* | Jagdish Pyakurel | Team member | **30%** |

Percentages reflect design, implementation, testing, and documentation time, not only lines of code.

### Sudan Khanal — Team lead (40%)

Led integration, the trade engine, and late extras that had to work for the demo.

- Trade CRUD API (`api/trades.php`), validation, long vs short P&L, live preview, edit/delete confirmations  
- Journal UX (direction toggles, emotion chips, estimated P&L card)  
- CSV/Excel import parser, templates, skip/error reporting  
- MetaApi connect/deploy/sync, deal pairing, error display on Profile  
- AI backend context (stats + recent trades + emotions), saving chats to MySQL  
- Sign-out confirmation, Google OAuth host/session fixes, heatmap calendar behaviour  
- Coordinated merges, local `php -S` testing, and bug fixes found in the browser console  

### Rajan Shrestha — Team member (30%)

Owned accounts, data model, and written deliverables.

- `setup.sql`, PDO connection, `BASE_URL` helpers  
- Register/login UI and `api/auth.php` (register, login, profile, password)  
- Session guard (`auth_check.php`) and logout  
- Google OAuth config placeholders and user columns (`google_id`, `auth_provider`)  
- Profile forms and account summary metrics  
- `DOCUMENTATION.md`, proposal support, security notes (hashing, prepared statements, user isolation)  
- Import template endpoint (`api/template.php`)  

### Jagdish Pyakurel — Team member (30%)

Owned look-and-feel, dashboard, and the chat shell.

- Dark theme and layout (`css/style.css`, sidebar, topbar, cards, tables, modals)  
- Shared header/footer and mobile sidebar  
- Dashboard KPI cards, Chart.js cumulative/monthly P&L, recent trades  
- Search/filter/sort UI on the journal  
- Advanced charts: weekday P&L, win vs loss, P&L by asset, emotion averages  
- 30-day heatmap layout (7-day rows, weekly P&L column)  
- Chat widget UI: FAB, panel, full-screen mode, history list, suggestions  

**Shared (all three)**  
Cross-browser checks, empty/error states, demo data, and final report sections for each person’s subsystems.

---

## 7 References

PHP Group. (n.d.). *PHP manual*. https://www.php.net/docs.php  

Oracle. (n.d.). *MySQL 8.0 reference manual*. https://dev.mysql.com/doc/  

Chart.js. (n.d.). *Chart.js documentation*. https://www.chartjs.org/docs/latest/  

Font Awesome. (n.d.). *Font Awesome icons*. https://fontawesome.com/  

OpenAI. (n.d.). *Chat Completions API*. https://platform.openai.com/docs/api-reference/chat  

Google. (n.d.). *Using OAuth 2.0 to access Google APIs*. https://developers.google.com/identity/protocols/oauth2  

MetaApi. (n.d.). *Read deals by time range*. https://metaapi.cloud/docs/client/restApi/api/retrieveHistoricalData/readDealsByTimeRange/  

MetaApi. (n.d.). *MetatraderDeal model*. https://metaapi.cloud/docs/client/models/metatraderDeal/  

GitHub, Inc. (n.d.). *GitHub about*. https://github.com/about  

Mozilla. (n.d.). *Using the Fetch API*. https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API  

---

*Insert screenshots into Section 4.2 before PDF export (login, dashboard, journal modal, import results, profile broker card, chat full-screen, heatmap). Replace [sID] and supervisor name on the cover.*

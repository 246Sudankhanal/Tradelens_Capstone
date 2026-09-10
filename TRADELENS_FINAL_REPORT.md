# NIT3003 — IT Capstone Project
# FINAL REPORT
# TRADELENS
## A web-based personal trading journal and analytics platform

**Team Members:**

Sudan Khanal — S8171155  
Rajan Shrestha — S8171018  
Jagdish Pyakurel — S8095963  

**Supervisor:** *[Insert supervisor name]*  
**Campus:** Footscray Park  
**Semester:** Block 4, Semester 1 2026  

GitHub: https://github.com/246Sudankhanal/Tradelens_Capstone  

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

### List of figures (insert screenshots before PDF export)

Figure 3.2.1 Entity relationship overview (users, trades, trading_accounts, chat).  
Figure 4.2.1 Landing page.  
Figure 4.2.2 Login screen.  
Figure 4.2.3 Register screen.  
Figure 4.2.4 Dashboard with KPI cards and P&L chart.  
Figure 4.2.5 Daily P&L heatmap.  
Figure 4.2.6 Trade journal table.  
Figure 4.2.7 Add Trade modal with live P&L.  
Figure 4.2.8 CSV import modal.  
Figure 4.2.9 Profile — manual dashboards and MetaTrader connect.  
Figure 4.2.10 Account switcher in the top bar.  
Figure 4.2.11 Expanded AI chat.  
Figure 4.3.1 Session guard (`auth_check.php`).  
Figure 4.3.2 Trade P&L SQL helper.  
Figure 4.3.3 OpenRouter chat request.

---

## 1 Introduction

### 1.1 Scope Review — Sudan Khanal

Retail traders can open a broker account in minutes, but most still review performance in a spreadsheet or not at all. Professional desks treat a journal as a working tool: they record entry and exit, size, notes, and how they felt, then look at the numbers over weeks rather than one lucky day. Spreadsheets break formulas, hide psychology, and do not keep broker books separate from manual notes.

TradeLens is a web application that fills that gap for a single trader on a laptop or phone browser. A registered user can:

- create an account (email or Google);
- log Buy and Sell trades with notes and emotion tags;
- import a CSV or Excel file;
- connect MetaTrader through MetaApi and pull closed deals;
- keep **separate dashboards** for each broker login and each manual journal;
- review win rate, P&L, heatmaps and asset breakdowns;
- ask an AI copilot that only sees *that user’s* stats (and the currently selected account).

The assistant is instructed not to recommend buying or selling a named asset. TradeLens is a journal, not a broker and not financial advice.

**In scope for the submitted build**

- Registration, login, logout, profile and password change  
- Trade create, read, update, delete with live P&L preview  
- Search, filter and sort on the journal  
- Dashboard KPIs and Chart.js visualisations  
- CSV/XLSX import and a downloadable template  
- Multiple manual dashboards plus MetaTrader accounts  
- AI chat with saved history (OpenRouter)  
- Per-user MySQL isolation  

**Out of scope / later work**

- Live quotes and order placement  
- Native iOS/Android apps  
- Production-grade secret management (keys currently live in `config/` on the demo machine)  
- Tax packs and multi-user firm reporting  

The stack is PHP 8, MySQL, HTML/CSS and vanilla JavaScript. Local demos use `php -S localhost:3000` (or Apache/XAMPP). The team used GitHub for version control.

---

## 2 System Requirements Review

### 2.1 Functional Requirements Review

The original proposal listed twelve functional requirements. The table below is the review against the delivered system.

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

**Additional requirements delivered after the core journal was stable**

- Public landing page (`index.php`) with sign-in / register  
- Google OAuth (`api/google_oauth.php`)  
- MetaTrader 4/5 connect and sync (`api/broker_sync.php`)  
- Multiple manual journals and an account switcher  
- Saved chat threads and an expanded chat layout  
- Shared P&L multipliers (gold/silver lots) on dashboard, journal and chat  

We achieved the original functional list. Google login, broker sync and extra dashboards were treated as stretch items and are present in the demo, with known limits (local secrets, MetaApi closed-trades only, 90-day lookback).

### 2.2 Non-Functional Requirements Review

| Area | Requirement | How we addressed it |
|------|-------------|---------------------|
| Security | Passwords not stored in plain text | `password_hash` / `password_verify` (bcrypt) |
| Security | No string-concatenated SQL | PDO prepared statements |
| Isolation | One user cannot see another’s trades | `user_id` on every query; session `auth_check` |
| Integrity | Money stored accurately | `DECIMAL(15,4)` prices; display rounded to 2 dp |
| Usability | Dark theme, obvious navigation | Shared sidebar, toasts, confirmations for delete and sign-out |
| Performance | Journal and dashboard usable on a laptop | Indexes on `user_id`, `trade_date`, `account_id`; AJAX tables |
| Reliability | Bad import rows must not crash the batch | Row-level skip and error list |
| Maintainability | Clear folders | `api/`, `config/`, `includes/`, `css/`, `js/` |

We did not run a formal production load test. CSRF tokens were discussed in planning; the submitted build uses same-origin `fetch` and PHP sessions. API keys for Google, MetaApi and OpenRouter sit in PHP config files for the demo — that is acceptable for a lab machine and would be moved to environment variables before any public host.

---

## 3 Database

### 3.1 Database Review — Rajan Shrestha

The team planned a **relational MySQL** database from the proposal. A trade belongs to a user (and later to a trading account). Dashboard numbers are SQL aggregations (`SUM`, `COUNT`, `GROUP BY`, `WEEKDAY`), which fit SQL better than a document store.

PHP connects with PDO (`ERRMODE_EXCEPTION`, emulated prepares off). `setup.sql` creates the schema. Extra columns (`google_id`, `account_id`, `source`, `broker_trade_id`) are also applied on first run so an older test database can upgrade without a full wipe.

Passwords are bcrypt hashes. A Google-only account still gets a random hash so the `password` column can stay `NOT NULL`.

### 3.2 Database Layout and Functionality — Rajan Shrestha

**Figure 3.2.1** — Conceptual layout: `users` 1—N `trading_accounts`; `users` 1—N `trades`; `trades.account_id` points at a journal book; `chat_conversations` 1—N `chat_messages`.

**users**

| Column | Purpose |
|--------|---------|
| id | Primary key |
| name, email | Profile; email unique |
| password | Bcrypt hash |
| google_id | Optional Google `sub` |
| auth_provider | `local` or `google` |
| created_at, updated_at | Audit |

**trading_accounts**

Each row is one dashboard: `manual` (CSV / typed trades) or `metatrader5` / `metatrader4` (MetaApi). Fields include display name, broker login/server, `metaapi_id`, region, status, last sync and last error.

**trades**

| Column | Purpose |
|--------|---------|
| id | Primary key |
| user_id | Owner (FK, cascade delete) |
| account_id | Which dashboard the row belongs to |
| asset_name | e.g. XAUUSD |
| trade_type | ENUM `Buy`, `Sell` |
| entry_price, exit_price | DECIMAL(15,4) |
| quantity | Default 1 |
| trade_date | DATE |
| notes, emotion | Optional reflection |
| source | `manual` or broker key |
| broker_trade_id | Deduplicate MetaApi deals |

P&L is **not** a stored column. It is calculated in SQL (and in the add-trade preview) using a shared helper `tradePnlSql()`:

- Default: Buy `(exit − entry) × qty`; Sell `(entry − exit) × qty`  
- Gold (`XAU` / GOLD): × 100 (lot to ounces)  
- Silver (`XAG` / SILVER): × 5000  

Dashboard, journal and AI all use this helper so the screens do not disagree.

**chat_conversations / chat_messages** — titled threads and `user` / `assistant` rows so a refresh does not wipe the chat.

**broker_connections** — legacy one-row-per-broker table; new connects write `trading_accounts`. Old rows are migrated on login.

Indexes include `idx_trades_user_id`, `idx_trades_trade_date`, `idx_trades_asset_name`, `idx_trades_account`.

---

## 4 System Architecture

### 4.1 Technologies Used — Jagdish Pyakurel

The application is a classic three-layer web app: browser UI, PHP JSON APIs, MySQL.

| Layer | Choice | Reason |
|-------|--------|--------|
| Frontend | HTML5, CSS3, vanilla JavaScript | No build step; easy to mark and demonstrate |
| Backend | PHP 8 | Matches the XAMPP teaching stack |
| Database | MySQL via PDO | Relational stats; student familiarity |
| Charts | Chart.js 4 (CDN) | Line, bar, doughnut without React |
| Icons / type | Font Awesome 6, Inter; Fraunces/Outfit in expanded chat | Dark UI plus a reading layout for full-screen AI |
| AI | OpenRouter (`openai/gpt-4o-mini`) | OpenAI-compatible Chat Completions without calling OpenAI directly |
| Optional auth | Google OAuth 2.0 | Passwordless sign-in |
| Broker | MetaApi REST | Cloud bridge to MT4/MT5 history |
| Version control | Git / GitHub | Parallel work on auth, journal, dashboard |
| Local run | `php -S` or Apache | Fast demo |

GitHub let three people work on different files and merge. Conflicts were most common in `style.css` and `header.php`.

### 4.2 User Interface Descriptions — Jagdish Pyakurel

There are public pages (landing, login, register) and authenticated pages (dashboard, journal, profile) plus a floating AI panel.

**Landing (`index.php`)**  
Marketing home: product story, feature grid (journal, analytics, sync, CSV, extra dashboards, AI), how-it-works, and calls to action. Logged-in visitors are sent to the dashboard. Sign-out can show a short banner.

**Login (`login.php`)**  
Google button, then email and password. Errors stay on the page. Link back to home and to register.

**Register (`register.php`)**  
Name, email, password (minimum six characters), Google option. After success the user is sent to login.

**App chrome (`includes/header.php`)**  
Fixed sidebar: Dashboard, Trade Journal, Profile. Footer: avatar, name, Sign out (confirmation modal). Top bar: page title, **account switcher** (All accounts + each book), **New dashboard**, live date.

**Dashboard (`dashboard.php`)**  
Four KPI cards: total trades, win rate, net P&L, best trade. Cumulative vs monthly P&L chart. Recent trades. Heatmap (last 30 days, seven days per row). Weekday P&L, win/loss doughnut, P&L by asset, average P&L by emotion. Figures follow the selected account.

**Trade journal (`trades.php`)**  
Quick stats, search, type/date filters, sort. Table with Buy/Sell badges and coloured P&L. A banner states **where Add Trade and CSV will save** (selected book, or the default manual journal if “All accounts” is selected). Add Trade modal: direction toggles, emotion chips, live P&L. Import modal: drag-and-drop, template download, skip/error report.

**Profile (`profile.php`)**  
Personal details, password, **Manual dashboards** (create extra journals), MetaTrader connect (nickname, MT4/MT5, server, login, password), list of accounts with Open / Sync / Rename / Disconnect, and an account summary that follows the switcher.

**AI widget (`includes/chat_widget.php`)**  
Robot button. Compact panel or expanded reading view (serif AI replies, larger type, history sidebar). Chats are stored per user.

### 4.3 Subsystems Explained

#### Authentication — Rajan Shrestha

`api/auth.php` handles `register`, `login`, `update_profile`, and `change_password`. Duplicate emails are rejected. Login writes `user_id`, `user_name`, `user_email` into `$_SESSION`. Pages include `includes/auth_check.php` (redirect to `login.php`). JSON APIs call `requireAuth()`.

Google: `config/oauth.php` holds client id, secret and redirect URI. `api/google_oauth.php` starts OAuth, checks `state`, exchanges the code, reads userinfo, and **links by `google_id` or `LOWER(email)`** so an existing TradeLens email is not treated as a new insert (that duplicate used to fail OAuth). `SHOW COLUMNS` is listed in PHP rather than `LIKE ?`, because native PDO prepares on MySQL rejected the placeholder (`SQLSTATE 1064 near '?'`).

Logout destroys the session and returns to the landing page.

#### Trade journal, accounts and P&L — Sudan Khanal

`api/trades.php`: GET (filters + `account_id` when a book is selected), POST, PUT, DELETE. Ownership is always the session user.

New rows call `writeAccountId()`: if a specific dashboard is selected, the trade is stored there; if the switcher is on **All accounts**, it goes to the first manual journal. CSV import uses the same rule. Broker sync writes to the MetaTrader `trading_accounts` row and does **not** move those rows to Manual by itself.

`includes/trading_accounts.php` creates the schema, a default “Manual journal”, and migrates old `broker_connections`. `api/accounts.php` lists, selects, renames, creates extra manuals, and disconnects (broker trades move to the default manual; extra manuals can be deleted if at least one remains).

#### Analytics — Jagdish Pyakurel

`api/analytics.php` returns KPIs, 12-month series, recent trades, heatmap map, weekday totals, assets and emotions, all filtered by the active account (or unfiltered for All). `dashboard.php` draws Chart.js charts and the calendar heatmap. A temporal-dead-zone bug (`weeks` used before declaration) once blanked every chart after the first; that is fixed.

#### AI copilot — Sudan Khanal (API) and Jagdish Pyakurel (widget)

Each message, `api/chat.php` loads stats, last ten trades and emotion counts **for the active account**, plus a short label of which book is in view. Guardrails: journal topics only; no named-asset advice. The request goes to OpenRouter Chat Completions. Replies are saved to MySQL. The widget supports new thread, history, delete, and expand.

#### Broker auto-sync — Sudan Khanal

`config/brokers.php` holds the MetaApi token. Connect provisions a cloud account, deploys it, waits until connected, inserts a **new** `trading_accounts` row (multiple MT5 logins allowed), then syncs. Sync reads history deals, pairs IN/OUT using `entryType` and `positionId`, retries HTTP 202, and tries regional client hosts. Open positions are not imported. Lookback is 90 days. PHP 8.5 `curl_close()` warnings were removed so the browser could parse JSON (otherwise Profile showed “network error” after a successful import).

---

## 5 Challenges and Problems — Sudan Khanal, Jagdish Pyakurel, Rajan Shrestha

**PHP 8.4 / 8.5 warnings leaking into JSON and OAuth**  
`fgetcsv` needed an explicit escape argument. `curl_close()` is deprecated on PHP 8.5 and printed before JSON, so sync “failed” in the UI while the database updated. Deprecations are hidden on API responses; `curl_close` was dropped.

**Google OAuth**  
A fixed `localhost` redirect broke `127.0.0.1` sessions. `SHOW COLUMNS … LIKE ?` caused SQL 1064. Existing emails failed INSERT on unique email until we matched `LOWER(email)`. Users should stay on `http://localhost:3000` (or 8080) with that exact redirect URI in Google Cloud.

**MetaTrader “connected” but zero trades**  
Deals use `entryType`, not `entry`. History is on a regional client host. New accounts often return 202 until ready. We retry, rotate regions, pair IN/OUT, and show `last_error` on Profile.

**Dashboard vs journal P&L**  
The journal applied gold/silver lot multipliers; analytics did not, so XAUUSD totals disagreed. One SQL helper is now shared.

**Heatmap and charts**  
An early GitHub-style grid was unreadable. It was replaced with a 30-day calendar. The `weeks` TDZ bug blocked later charts until reordered.

**Chat lost on refresh**  
The first chatbot kept messages only in the browser. Conversations were moved to MySQL.

**Multiple books**  
The first MetaApi design allowed only one MT5 per user (`UNIQUE user_id, broker_key`). Traders needed several manuals and several logins. `trading_accounts` plus the top-bar switcher replaced that.

**Git**  
Three people editing `style.css` and `header.php` needed frequent pulls. The 8-week split (auth / journal / dashboard) reduced most collisions.

**Design**  
None of us are graphic designers. We used one dark token set (accent blue, green profit, red loss) and Font Awesome rather than custom illustration.

---

## 6 Student Contributions

Work followed the team GitHub plan. A fourth-member AI/import track in an early plan was absorbed by the three-person team.

| Student ID | Name | Role | Contribution |
|------------|------|------|----------------|
| S8171155 | Sudan Khanal | Team lead | **40%** |
| S8171018 | Rajan Shrestha | Team member | **30%** |
| S8095963 | Jagdish Pyakurel | Team member | **30%** |

Percentages cover design, implementation, testing and documentation — not only lines of code.

### Sudan Khanal — S8171155 — Team lead (40%)

Led integration, the trade engine, broker sync, multi-account behaviour, OAuth/sync bugfixes, and the AI backend.

- Trade CRUD (`api/trades.php`), validation, long vs short P&L, live preview  
- Journal UX (direction toggles, emotion chips, estimated P&L)  
- CSV/Excel import, templates, skip/error reporting  
- `trading_accounts`, switcher behaviour, “where does this CSV go?”  
- MetaApi connect/deploy/sync, deal pairing, JSON/deprecation fixes  
- Chat API context, OpenRouter call, saving threads  
- Google OAuth host, `SHOW COLUMNS`, email-link fixes  
- Landing page structure, merge coordination, demo `php -S` testing  

### Rajan Shrestha — S8171018 — Team member (30%)

Owned accounts, the data model, and written deliverables.

- `setup.sql`, PDO connection, `jsonResponse` / `requireAuth`  
- Register/login UI and `api/auth.php` (register, login, profile, password)  
- Session guard and logout  
- Google user columns and OAuth configuration  
- Profile forms and account summary metrics  
- `DOCUMENTATION.md`, proposal support, security notes  
- Import template endpoint (`api/template.php`)  
- Requirements tables and database sections of this report  

### Jagdish Pyakurel — S8095963 — Team member (30%)

Owned look-and-feel, dashboard visualisation, and the chat shell.

- Dark theme and layout (`css/style.css`, sidebar, topbar, cards, tables, modals)  
- Shared header/footer and mobile sidebar  
- Dashboard KPI cards, Chart.js P&L, recent trades  
- Search/filter/sort UI on the journal  
- Weekday, win/loss, asset and emotion charts; 30-day heatmap  
- Chat widget: FAB, panel, expanded reading layout, history list, suggestions  
- Landing-page visual system (nav, hero, feature cards)  
- UI descriptions in this report  

**Shared (all three)**  
Cross-browser checks, empty/error states, demo walkthroughs, and review of each other’s sections.

---

## 7 References

PHP Group. (n.d.). *PHP manual*. https://www.php.net/docs.php  

Oracle. (n.d.). *MySQL 8.0 reference manual*. https://dev.mysql.com/doc/  

Chart.js. (n.d.). *Chart.js documentation*. https://www.chartjs.org/docs/latest/  

Font Awesome. (n.d.). *Font Awesome icons*. https://fontawesome.com/  

OpenRouter. (n.d.). *API reference*. https://openrouter.ai/docs  

Google. (n.d.). *Using OAuth 2.0 to access Google APIs*. https://developers.google.com/identity/protocols/oauth2  

MetaApi. (n.d.). *Read deals by time range*. https://metaapi.cloud/docs/client/restApi/api/retrieveHistoricalData/readDealsByTimeRange/  

MetaApi. (n.d.). *MetatraderDeal model*. https://metaapi.cloud/docs/client/models/metatraderDeal/  

GitHub, Inc. (n.d.). *About GitHub*. https://github.com/about  

Mozilla. (n.d.). *Using the Fetch API*. https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API  

---

*Before you export to PDF/Word: paste screenshots into Section 4.2, confirm the supervisor name and unit/group line on the cover, and check student IDs. Convert this Markdown in Word (File → Open) or Pandoc if you need a .docx.*

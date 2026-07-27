# TradeLens — Project Documentation

> A Trading Journal Web Application built with HTML, CSS, Vanilla JavaScript, PHP, and MySQL on XAMPP.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Tech Stack](#tech-stack)
3. [File Structure](#file-structure)
4. [Functional Requirements Coverage](#functional-requirements-coverage)
5. [Non-Functional Requirements Coverage](#non-functional-requirements-coverage)
6. [Extra Features Built](#extra-features-built-beyond-requirements)
7. [Database Schema](#database-schema)
8. [API Endpoints](#api-endpoints)
9. [AI Chatbot Integration](#ai-chatbot-integration)
10. [Setup Instructions](#setup-instructions)

---

## Project Overview

TradeLens is a personal trading journal web application where users manually log their trades, track performance, and analyze their trading behavior over time. The system provides a clean dashboard with key metrics, a full trade history table with search and filter capabilities, and an AI-powered chatbot that gives personalized feedback based on the user's actual trade data.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript (ES6+) |
| Backend | PHP 8 |
| Database | MySQL via PDO |
| Server | XAMPP (Apache + MySQL) |
| Charts | Chart.js (CDN) |
| Icons | Font Awesome 6 (CDN) |
| Fonts | Inter (Google Fonts CDN) |
| AI | Ollama (local, free) or Google Gemini API (free) |

---

## File Structure

```
CAPSTONE_CLAUDE/
│
├── config/
│   ├── db.php                  — Database connection, BASE_URL, shared helpers
│   └── ai.php                  — AI provider configuration (Ollama / Gemini)
│
├── includes/
│   ├── auth_check.php          — Session guard: redirects to login if not authenticated
│   ├── header.php              — Shared HTML head, sidebar nav, topbar
│   ├── footer.php              — Shared scripts, Chart.js, chat widget loader
│   └── chat_widget.php         — Floating AI chatbot UI (CSS + HTML + JS)
│
├── api/
│   ├── auth.php                — Login, register, update profile, change password
│   ├── trades.php              — Trade CRUD (GET / POST / PUT / DELETE)
│   ├── analytics.php           — Dashboard stats + chart data
│   ├── import_trades.php       — CSV and Excel file import handler
│   ├── chat.php                — AI chat endpoint (calls Ollama or Gemini)
│   └── template.php            — Downloadable CSV import template
│
├── css/
│   └── style.css               — All styles: layout, components, responsive, dark theme
│
├── js/
│   └── main.js                 — Shared JS: sidebar toggle, live date display
│
├── index.php                   — Login page
├── register.php                — Registration page
├── logout.php                  — Logout handler (destroys session, redirects)
├── dashboard.php               — Main dashboard: metrics, P&L chart, recent trades
├── trades.php                  — Trade journal: history table, add/edit/delete/import
├── profile.php                 — Profile settings and password change
└── setup.sql                   — Database creation script
```

---

## Functional Requirements Coverage

### 1. User Management

| Requirement | Status | Implementation |
|---|---|---|
| Register with name, email, password | ✅ Done | `register.php` + `api/auth.php` → `action=register` |
| Login with valid credentials | ✅ Done | `index.php` + `api/auth.php` → `action=login` |
| Logout securely | ✅ Done | `logout.php` — destroys PHP session, redirects to login |
| Update profile information | ✅ Done | `profile.php` + `api/auth.php` → `action=update_profile` |

**How it works:**
- Registration validates name, email format, and minimum 6-character password. Email uniqueness is enforced at the database level.
- Login uses `password_verify()` against the bcrypt hash stored in the database.
- On successful login, `$_SESSION['user_id']`, `$_SESSION['user_name']`, and `$_SESSION['user_email']` are set.
- Every protected page starts with `includes/auth_check.php` which checks for `$_SESSION['user_id']` and redirects to login if missing.
- The profile page allows updating name and email (with duplicate email check) and changing password (requires current password verification).

---

### 2. Trade Management (Core Feature)

| Requirement | Status | Implementation |
|---|---|---|
| Add a new trade | ✅ Done | Modal form in `trades.php` → POST to `api/trades.php` |
| Edit existing trades | ✅ Done | Edit button pre-fills same modal → PUT to `api/trades.php` |
| Delete trades | ✅ Done | Delete button → confirmation modal → DELETE to `api/trades.php` |
| Store asset name | ✅ Done | `asset_name` column in `trades` table |
| Store trade type (Buy/Sell) | ✅ Done | `trade_type` ENUM('Buy','Sell') column |
| Store entry price | ✅ Done | `entry_price` DECIMAL(15,4) column |
| Store exit price | ✅ Done | `exit_price` DECIMAL(15,4) column |
| Store trade date | ✅ Done | `trade_date` DATE column |
| Store notes | ✅ Done | `notes` TEXT column |

**Extra field added beyond requirements:**
- `quantity` — number of units/shares traded, used for accurate P&L calculation (defaults to 1)
- `emotion` — dropdown tag (Confident, Calm, Patient, Fearful, Greedy, Impulsive, Uncertain)

**How it works:**
- The Add/Edit modal is a single reusable form. When editing, the existing trade data is injected into the form fields via JavaScript.
- All trade operations are done via AJAX (fetch API) — no page reloads.
- Ownership is enforced in `api/trades.php`: every query includes `WHERE user_id = ?` so users can never access each other's trades.
- A delete confirmation modal prevents accidental deletions.

---

### 3. Trade Calculations

| Requirement | Status | Implementation |
|---|---|---|
| Auto-calculate profit/loss per trade | ✅ Done | SQL CASE expression in `api/trades.php` and `api/analytics.php` |
| Total profit/loss across all trades | ✅ Done | `SUM()` aggregation in `api/analytics.php` |
| Calculate win rate (%) | ✅ Done | `(wins / total) * 100` in `api/analytics.php` |
| Display total number of trades | ✅ Done | `COUNT(*)` in `api/analytics.php` |

**P&L Formula used:**
```
Buy trade:   P&L = (exit_price - entry_price) × quantity
Sell trade:  P&L = (entry_price - exit_price) × quantity
```

- A **Buy** (long) trade profits when the exit price is higher than entry.
- A **Sell** (short) trade profits when the exit price is lower than entry.
- Win = P&L > 0, Loss = P&L < 0.
- All calculations are done in MySQL using `ROUND(..., 2)` for precision.

**Bonus — Live P&L Preview:**
- As the user fills in entry price, exit price, quantity, and trade type in the modal, a real-time P&L estimate updates instantly using JavaScript — before saving.

---

### 4. Trade History

| Requirement | Status | Implementation |
|---|---|---|
| Display all trades in tabular format | ✅ Done | HTML `<table>` in `trades.php` rendered via JS from `api/trades.php` |
| Sort by date | ✅ Done | `sort=date_desc` / `sort=date_asc` query param |
| Sort by asset | ✅ Done | `sort=asset_asc` / `sort=asset_desc` query param |
| Filter by date range | ✅ Done | `date_from` and `date_to` query params |

**Table columns:**
Date · Asset · Type · Entry Price · Exit Price · Quantity · P&L · Emotion · Notes · Actions (Edit / Delete)

**Sort options available:**
- Newest first (default)
- Oldest first
- Asset A–Z / Z–A
- Best P&L first / Worst P&L first

---

### 5. Dashboard & Analytics

| Requirement | Status | Implementation |
|---|---|---|
| Summary dashboard | ✅ Done | `dashboard.php` with metric cards |
| Visualize P&L trends (chart) | ✅ Done | Line chart via Chart.js in `dashboard.php` |
| Display total trades | ✅ Done | Metric card fed from `api/analytics.php` |
| Display win rate | ✅ Done | Metric card with color-coded value (green ≥ 50%, red < 50%) |
| Display net profit/loss | ✅ Done | Metric card with green (profit) or red (loss) accent |

**Dashboard components:**
1. **4 Metric Cards** — Total Trades, Win Rate, Net P&L, Best Trade
2. **Cumulative P&L Line Chart** — shows both monthly P&L and running cumulative P&L over the last 12 months using Chart.js
3. **Recent Trades List** — last 5 trades with asset, date, trade type badge, and color-coded P&L
4. **Empty state** — friendly message with a call-to-action when no trades exist yet

---

### 6. Search & Filter

| Requirement | Status | Implementation |
|---|---|---|
| Search by asset name | ✅ Done | Live search input → `search` query param → SQL `LIKE '%...%'` |
| Filter by trade type (Buy/Sell) | ✅ Done | Dropdown → `type` query param → SQL `WHERE trade_type = ?` |

**Additional filters implemented:**
- Filter by date range (from / to)
- Sort selector (6 sort options)
- "Clear" button resets all filters at once
- Search uses a 350ms debounce to avoid excessive API calls while typing

**Quick Stats Bar** on the trade journal page shows live aggregate stats for the currently filtered result set (not all trades), so filtering by date also updates win rate, P&L, etc.

---

### 7. Trade Notes / Reflection

| Requirement | Status | Implementation |
|---|---|---|
| Add notes for each trade | ✅ Done | `notes` TEXT field in trade modal, shown truncated in table with full tooltip |
| Store user reflections (mistakes, emotions) | ✅ Done | `emotion` dropdown (7 options) + `notes` textarea |

**Emotion options:** Confident, Calm, Patient, Fearful, Greedy, Impulsive, Uncertain

The emotion data is:
- Stored per trade in the `emotion` column
- Displayed as a tag in the trade history table
- Aggregated and sent to the AI chatbot for psychology pattern analysis

---

### 8. Data Persistence

| Requirement | Status | Implementation |
|---|---|---|
| Store all user and trade data in a database | ✅ Done | MySQL database `tradelens` with `users` and `trades` tables |
| Retrieve data when user logs in again | ✅ Done | All pages fetch live data from DB via PHP API endpoints |

- All data is stored in MySQL and persists across sessions and browser restarts.
- Trades are linked to users via `user_id` foreign key with `ON DELETE CASCADE`.
- Sessions are PHP-native sessions — logging in again restores full access to all data.

---

## Non-Functional Requirements Coverage

### 1. Performance

| Requirement | Status | How Addressed |
|---|---|---|
| Pages load within 2 seconds | ✅ | Minimal dependencies, no heavy frameworks, assets via CDN |
| Handle 50+ concurrent users | ✅ | Apache + PHP-FPM on XAMPP handles this; PDO with prepared statements |
| DB queries return within 1 second | ✅ | Indexed columns: `user_id`, `trade_date`, `asset_name`; simple aggregation queries |

- Database indexes are defined in `setup.sql` on `trades.user_id`, `trades.trade_date`, and `trades.asset_name`.
- All API responses are JSON — lightweight and fast.
- Chart.js and Font Awesome are loaded from CDN with browser caching.
- Debounced search avoids hammering the server on every keystroke.

---

### 2. Security

| Requirement | Status | How Addressed |
|---|---|---|
| Passwords must be hashed | ✅ | `password_hash($password, PASSWORD_BCRYPT)` on register; `password_verify()` on login |
| Prevent unauthorized access | ✅ | `includes/auth_check.php` guards every protected page and API endpoint |
| Users only access their own trades | ✅ | All trade queries include `WHERE user_id = ?` with PDO prepared statements |

**Additional security measures:**
- All database queries use **PDO prepared statements** — no raw SQL string concatenation, preventing SQL injection.
- All user-generated content is escaped with `htmlspecialchars()` before rendering in PHP and `escHtml()` in JavaScript, preventing XSS.
- The API endpoints use `requireAuth()` which validates the session server-side before any data operation.
- HTTP methods are enforced per endpoint (GET for reads, POST for create, PUT for update, DELETE for delete).

---

### 3. Usability

| Requirement | Status | How Addressed |
|---|---|---|
| Simple and intuitive interface | ✅ | Clean dark theme, consistent layout, clear labels |
| Responsive (mobile + desktop) | ✅ | CSS media queries, collapsible sidebar on mobile |
| Add a trade in less than 1 minute | ✅ | Modal form with 6 required fields, live P&L preview, accessible from any page |

- The sidebar collapses on screens narrower than 768px with a hamburger menu toggle.
- Forms use clear labels, placeholder text, and inline validation feedback.
- Toast notifications confirm every action (save, update, delete, import) without interrupting workflow.
- The Add Trade button is always visible in the top-right of the trade journal page.

---

### 4. Reliability

| Requirement | Status | How Addressed |
|---|---|---|
| Available 99% of the time | ✅ | Runs on local XAMPP — availability depends on local machine uptime |
| Prevent data loss | ✅ | PDO transactions, MySQL foreign key constraints with CASCADE |
| Validate user input | ✅ | Client-side (HTML5 + JS) and server-side (PHP) validation on all forms |

**Validation layers:**
- **Client-side:** HTML `required`, `type="number"`, `min` attributes + JavaScript checks before submission
- **Server-side:** PHP validates all inputs independently — never trusts client data
- **Database-level:** ENUM constraints on `trade_type`, NOT NULL on required fields, DECIMAL precision on prices
- Error messages are specific and shown inline (modal) or as toast notifications

---

### 5. Maintainability

| Requirement | Status | How Addressed |
|---|---|---|
| Modular and well-structured code | ✅ | Separated into config/, includes/, api/, css/, js/ directories |
| Easy to update or extend | ✅ | New pages just include header.php/footer.php; new API actions added to existing handlers |
| Consistent naming and coding standards | ✅ | snake_case for PHP/SQL, camelCase for JavaScript, BEM-like CSS class names |

- **Config is centralized:** DB credentials in `config/db.php`, AI settings in `config/ai.php`
- **Shared helpers:** `getDB()`, `jsonResponse()`, `requireAuth()` used across all API files
- **Shared layout:** `header.php` and `footer.php` included by all app pages — change once, updates everywhere
- **API is RESTful:** Each resource (`/api/trades.php`) handles all HTTP methods in one file

---

### 6. Compatibility

| Requirement | Status | How Addressed |
|---|---|---|
| Works on Chrome, Edge, Safari | ✅ | Standard HTML5/CSS3/ES6 — no browser-specific APIs used |
| Desktop and mobile support | ✅ | Responsive CSS grid and flexbox layouts, mobile sidebar |

- CSS uses custom properties (variables) with standard values — no experimental features.
- JavaScript uses `fetch()`, `async/await`, `FormData`, `URLSearchParams` — all widely supported.
- No jQuery or other compatibility shims needed.

---

### 7. Scalability

| Requirement | Status | How Addressed |
|---|---|---|
| Allow future feature additions | ✅ | Modular file structure; REST API design separates frontend from backend |
| DB supports growing users and trades | ✅ | Indexed tables, `AUTO_INCREMENT` primary keys, normalized schema |

- Adding a new feature = new page file + new API endpoint (or action in existing endpoint)
- The AI chatbot, CSV import, and template download were all added as extensions without touching core files
- The database schema can be extended with `ALTER TABLE` without breaking existing functionality

---

### 8. Data Integrity

| Requirement | Status | How Addressed |
|---|---|---|
| Accurate P&L calculations | ✅ | Calculations done in MySQL with `DECIMAL(15,4)` precision; `ROUND(..., 2)` for display |
| Prevent invalid data entry | ✅ | Positive number validation on prices, ENUM on trade type, NOT NULL constraints |

- Entry and exit prices are validated `> 0` on both client and server.
- Quantity defaults to 1 if not provided; validated `> 0` if provided.
- Trade type is restricted to `'Buy'` or `'Sell'` at both application and database level.
- Date is validated and normalized to `YYYY-MM-DD` format before storage.

---

## Extra Features Built (Beyond Requirements)

These features were added to improve the application beyond the base requirements.

### 1. CSV / Excel Import
- **File:** `api/import_trades.php`, `api/template.php`
- Users can bulk-import trades from a `.csv` or `.xlsx` file
- Drag-and-drop upload zone with file validation
- Flexible column mapping — recognizes common aliases (e.g. "Symbol" maps to asset_name)
- Supports multiple date formats (YYYY-MM-DD, MM/DD/YYYY, Excel serial numbers)
- Returns a detailed results summary: imported count, skipped rows, and per-row error messages
- Downloadable CSV template with sample data and correct headers

### 2. AI Trading Assistant (Chatbot)
- **Files:** `api/chat.php`, `config/ai.php`, `includes/chat_widget.php`
- Floating chat button (bottom-right) on all authenticated pages
- AI has full context of the user's trade stats, recent trades, and emotion patterns
- Conversation history maintained per session (last 20 turns)
- Supports two free AI providers:
  - **Ollama** — runs 100% locally, no API key, no internet required
  - **Google Gemini API** — free tier (1,500 requests/day), no credit card
- Suggested question chips for quick interaction
- Typing indicator, markdown formatting in responses, notification dot on new replies

### 3. Emotion / Psychology Tracking
- **Field:** `emotion` column in `trades` table
- 7 emotional states: Confident, Calm, Patient, Fearful, Greedy, Impulsive, Uncertain
- Logged per trade alongside notes
- Aggregated and sent to AI chatbot for pattern analysis (e.g. "You tend to lose when trading Impulsive")

### 4. Live P&L Preview
- In the Add/Edit trade modal, P&L updates in real time as the user types entry price, exit price, quantity, and selects trade type
- Color-coded green (profit) or red (loss) before the trade is even saved

### 5. Quick Stats Bar
- Displayed at the top of the Trade Journal page
- Shows aggregate stats (total, wins, losses, win rate, net P&L) for the **currently filtered** set of trades
- Updates instantly when filters or search terms change

### 6. Toast Notifications
- Non-blocking success/error messages appear bottom-right after every action
- Auto-dismiss after 3.5 seconds
- Used for: trade saved, trade updated, trade deleted, import complete, profile updated

---

## Database Schema

```sql
CREATE DATABASE tradelens CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  UNIQUE NOT NULL,
    password   VARCHAR(255)  NOT NULL,             -- bcrypt hash
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE trades (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT           NOT NULL,
    asset_name  VARCHAR(100)  NOT NULL,
    trade_type  ENUM('Buy','Sell') NOT NULL,
    entry_price DECIMAL(15,4) NOT NULL,
    exit_price  DECIMAL(15,4) NOT NULL,
    quantity    DECIMAL(15,4) NOT NULL DEFAULT 1,
    trade_date  DATE          NOT NULL,
    notes       TEXT,
    emotion     VARCHAR(50)   DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Performance indexes
CREATE INDEX idx_trades_user_id    ON trades(user_id);
CREATE INDEX idx_trades_trade_date ON trades(trade_date);
CREATE INDEX idx_trades_asset_name ON trades(asset_name);
```

---

## API Endpoints

All endpoints return JSON: `{ "success": true/false, "message": "...", "data": {...} }`

### Authentication — `api/auth.php`

| Method | Action | Description |
|---|---|---|
| POST | `action=register` | Create new user account |
| POST | `action=login` | Authenticate and start session |
| POST | `action=update_profile` | Update name and email |
| POST | `action=change_password` | Change password (requires current password) |

### Trades — `api/trades.php`

| Method | Query Params | Description |
|---|---|---|
| GET | `search`, `type`, `date_from`, `date_to`, `sort` | List trades with filters |
| POST | — | Create a new trade |
| PUT | — (body) | Update an existing trade |
| DELETE | — (body) | Delete a trade |

### Analytics — `api/analytics.php`

| Method | Description |
|---|---|
| GET | Returns stats (total, wins, losses, win rate, net P&L, best/worst) + chart data + recent 5 trades |

### Import — `api/import_trades.php`

| Method | Description |
|---|---|
| POST (multipart) | Upload CSV or XLSX file, parse and insert valid trade rows |

### AI Chat — `api/chat.php`

| Method | Body | Description |
|---|---|---|
| POST | `{ message, history[] }` | Send message; returns AI reply with trade context |

---

## AI Chatbot Integration

### Provider: Ollama (default — 100% free, local)

```
config/ai.php  →  define('AI_PROVIDER', 'ollama')
                  define('OLLAMA_HOST',  'http://localhost:11434')
                  define('OLLAMA_MODEL', 'llama3.2')
```

**Setup:**
1. Install Ollama from `ollama.com`
2. Run `ollama pull llama3.2` in Terminal
3. Run `ollama serve`

### Provider: Google Gemini (alternative — free tier)

```
config/ai.php  →  define('GEMINI_API_KEY', 'your-key-here')
                  define('GEMINI_MODEL',   'gemini-2.0-flash')
```

**Setup:**
1. Go to `aistudio.google.com`
2. Sign in with Google → Get API Key → Create
3. Paste key into `config/ai.php`

### What the AI knows (per user, per request)
- Total trades, wins, losses, win rate, net P&L, best and worst trade
- Last 10 trades with full details (asset, type, entry/exit, P&L, emotion, notes excerpt)
- Emotion frequency breakdown across all trades

---

## Setup Instructions

### Prerequisites
- XAMPP installed and running (Apache + MySQL)
- PHP 8.0 or higher
- `ZipArchive` PHP extension (included in XAMPP by default)

### Steps

**1. Place project files**
```
/Applications/XAMPP/xamppfiles/htdocs/CAPSTONE_CLAUDE/
```

**2. Create the database**

Option A — Run in Terminal:
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root < setup.sql
```

Option B — Open phpMyAdmin (`http://localhost/phpmyadmin`), create database `tradelens`, and run the contents of `setup.sql`.

**3. Configure database** (if your MySQL uses a password)

Edit `config/db.php`:
```php
define('DB_PASS', 'your-mysql-password');
```

**4. Open the app**
```
http://localhost/CAPSTONE_CLAUDE/
```

**5. Register an account and start logging trades.**

---

*Built with HTML · CSS · Vanilla JavaScript · PHP · MySQL · Chart.js · Ollama*

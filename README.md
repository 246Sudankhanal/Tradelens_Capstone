# TradeLens

University capstone (NIT3003, Victoria University) — a personal **trading journal** in PHP and MySQL.

Log Buy/Sell trades, import a CSV, look at P&L on a dashboard, and optionally sync closed MetaTrader deals or talk to an AI copilot that only sees *your* journal stats.

This is **not** a broker and it does **not** give buy/sell advice.

Team: Sudan Khanal, Rajan Shrestha, Jagdish Pyakurel  
Repo: https://github.com/246Sudankhanal/Tradelens_Capstone

If you are hiring: this is a coursework app. My main personal projects are elsewhere (AI code reviewer, real-time chat).

---

## What it does

- Register / login (email, optional Google)
- Journal: add, edit, delete trades (asset, side, entry, exit, size, date, notes, emotion)
- CSV / Excel import
- Dashboard: win rate, net P&L, charts (Chart.js)
- Multiple journals (manual + MetaTrader accounts) with a switcher
- Optional MetaTrader sync via MetaApi (closed deals only)
- Optional AI chat (OpenRouter) scoped to the signed-in user’s data

![Architecture](docs/journal-flow.svg)

## Stack

PHP 8, MySQL (PDO), HTML/CSS/vanilla JS. Built to run on XAMPP or `php -S`. Charts via Chart.js CDN.

## Run locally

1. Import `setup.sql` in phpMyAdmin (database `tradelens`).
2. In `config/`, copy the example files and drop the `.example` from the name:

   - `db.example.php` → `db.php`
   - `ai.example.php` → `ai.php`
   - `oauth.example.php` → `oauth.php`
   - `brokers.example.php` → `brokers.php`

3. Default XAMPP: MySQL user `root`, empty password. Change `config/db.php` if yours is different.
4. Start it:

```bash
php -S localhost:8080
```

Open http://localhost:8080 — landing page, then register.

Google login, MetaTrader sync, and the AI chat stay off until you put real keys in those config files. **Do not commit** `config/db.php`, `ai.php`, `oauth.php`, or `brokers.php` (they are gitignored).

More setup notes: `TUTOR_SETUP.md`. Longer write-up: `DOCUMENTATION.md` and `TRADELENS_FINAL_REPORT.md`.

## Layout

```
api/          JSON endpoints (auth, trades, analytics, import, chat, broker sync)
config/       example configs only in git
includes/     header, auth check, P&L helper, chat widget
css/ js/      styles and shared JS
dashboard.php trades.php profile.php login.php register.php
setup.sql
```

## Notes

Coursework, not production. Passwords are bcrypt. Sessions sit behind `includes/auth_check.php`. P&L uses lot multipliers for gold/silver in `includes/pnl.php`.

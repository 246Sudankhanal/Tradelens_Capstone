# TradeLens — 8-Week Team Development & GitHub Strategy

This document outlines the structured development plan for the TradeLens project, divided among the four team members for an 8-week period.

## Team Members & Responsibilities

| Member | Focus Area | Functional Requirements |
| :--- | :--- | :--- |
| **Rajan Shrestha** | User Systems & Documentation | Auth (FR-01), Profile (FR-02), Documentation (FR-12) |
| **Sudan Khanal** | Core Trade Logic & Validation | CRUD (FR-03), P&L Logic (FR-04), Live Preview (FR-11) |
| **Jagdish Pyakurel** | Analytics & Visual Identity | Stats (FR-05), Filters (FR-06), Dashboard (FR-07) |
| **Kiran Poudel** | AI Integration & Data Processing | Emotions (FR-08), Bulk Import (FR-09), AI Assistant (FR-10) |

---

## 8-Week Roadmap (Daily GitHub Push Strategy)

To make the project look structural on GitHub, each member should push their progress daily according to this schedule.

### Week 1: Project Foundation & Authentication
- **Rajan:** Initialize GitHub Repo, setup `config/db.php`, `setup.sql`, and implement Register/Login UI.
- **Sudan:** Design the Trade Entry Modal and initial `api/trades.php` POST handler.
- **Jagdish:** Create the main Dark Theme (`style.css`) and Shared Layout (`header.php`, `footer.php`).
- **Kiran:** Setup the `trades` table for emotion tracking and research AI API (Gemini/Ollama).

### Week 2: Core Trading & Profile Management
- **Rajan:** Implement Profile settings and Password change logic (`api/auth.php`).
- **Sudan:** Implement core P&L calculation formulas and Live P&L Preview in the modal.
- **Jagdish:** Build the Trade History table UI and basic GET endpoint in `api/trades.php`.
- **Kiran:** Start the Bulk Import engine — research CSV/Excel parsing libraries in PHP.

### Week 3: Advanced Journaling & Bulk Imports
- **Rajan:** Create downloadable CSV/Excel templates for users.
- **Sudan:** Implement Update (Edit) and Delete functionality for trades.
- **Jagdish:** Add Search (by asset) and Filter (by type/date) to the Trade History page.
- **Kiran:** Complete the Bulk Data Import Engine for CSV files.

### Week 4: Dashboard & Performance Metrics
- **Rajan:** Implement security hardening (CSRF protection, session timeouts).
- **Sudan:** Add robust server-side validation for all trade inputs.
- **Jagdish:** Implement KPI Metric Cards (Win Rate, Total P&L, Best/Worst Trade).
- **Kiran:** Add support for Excel (.xlsx) file imports and error reporting.

### Week 5: Data Visualization & AI Prototype
- **Rajan:** Draft the technical `DOCUMENTATION.md`.
- **Sudan:** Refactor API endpoints to ensure consistent JSON responses.
- **Jagdish:** Integrate Chart.js and build the Cumulative P&L line chart.
- **Kiran:** Build the AI Chatbot Widget UI (Floating bubble + Chat window).

### Week 6: AI Integration & Behavioural Analysis
- **Rajan:** Finalize User Management use cases and sequence diagrams.
- **Sudan:** Perform database query optimization (adding indexes).
- **Jagdish:** Build the Monthly P&L bar chart and recent trades list.
- **Kiran:** Connect the Chatbot to the backend API (`api/chat.php`) and AI Provider.

### Week 7: Psychology & Polish
- **Rajan:** Conduct security audit and final documentation review.
- **Sudan:** Finalize the P&L logic for all edge cases (Long vs Short trades).
- **Jagdish:** Polish the UI for mobile responsiveness.
- **Kiran:** Implement AI "Psychology Feedback" based on user's emotion data.

### Week 8: Validation, Testing & Final Delivery
- **All Team Members:** Perform cross-browser testing.
- **All Team Members:** Bug fixing and UI/UX fine-tuning.
- **Rajan:** Finalize the Project Proposal and Presentation.
- **Final Push:** Merge all branches into `main` for final submission.

---

## GitHub Folder Structure Recommendation

For a structural look, you can follow this folder organization in your repository:

```text
TradeLens/
├── api/                # Backend PHP Endpoints (Sudan & Kiran)
├── config/             # DB & AI Config (Rajan)
├── css/                # Visual Styles (Jagdish)
├── includes/           # Shared Layouts & UI Components (Jagdish & Rajan)
├── js/                 # Client-side logic (All)
├── docs/               # Screenshots, Use Cases, Resources (Rajan)
├── index.php           # Login (Rajan)
├── dashboard.php       # Analytics UI (Jagdish)
├── trades.php          # Journal UI (Sudan)
└── setup.sql           # Database Script (Rajan)
```

---

## Daily Workflow
1. **Pull:** Start the day by pulling the latest changes: `git pull origin main`.
2. **Branch:** Work on your specific feature branch (e.g., `feature/sudan-crud`).
3. **Commit:** Make small, frequent commits: `git commit -m "Added P&L logic to trade modal"`.
4. **Push:** Push your branch daily to show progress: `git push origin feature/...`.
5. **Merge:** Create a Pull Request (PR) at the end of each week to merge into `main`.

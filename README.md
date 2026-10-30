# SmartExam - Smart Online Examination & AI Assessment System

A modern, full-featured web-based online examination and assessment platform powered by **Groq AI LPU Engine**, PHP, and MySQL.

Live Demo: [https://smartexam.infinityfree.me/](https://smartexam.infinityfree.me/)

---

## Key Features

- **Groq AI Exam Generator**: Dynamic, high-speed question generation using state-of-the-art LLMs (openai/gpt-oss-120b) via Groq LPU API.
- **Zero-Failure Hybrid Resilience**: Built-in intelligent procedural fallback question engine ensures 100% uptime for exam generation regardless of external API rate limits.
- **Real-time Assessment**: Live interactive examination interface with per-question navigation, auto-saving, and countdown timer with automatic submission upon expiry.
- **Instant Automated Grading**: Automated scoring, percentage computation, grade assignment, and question-by-question explanations.
- **Group Study Rooms**: Collaborative study rooms allowing students to create private rooms, invite peers with room codes, and take group exams together.
- **Student Performance Analytics**: Visual performance breakdown, score history, and real-time global leaderboard.
- **Comprehensive Admin Control**:
  - Exam management (create, publish, edit, toggle status).
  - Question bank authoring (MCQs & True/False with explanations and marks).
  - Student attempt audit trails and comprehensive grade ledgers.
  - User and study room management.
- **Cosmic Futuristic UI**: Modern, responsive dark-mode glassmorphic interface styled with custom CSS variables and smooth transitions.

---

## Tech Stack

- **Backend**: PHP 8.x (Vanilla, Object-Oriented Database Operations with Prepared Statements)
- **Database**: MySQL 8.x / MariaDB (Relational schema with foreign keys and cascade integrity)
- **AI Engine**: Groq Cloud API (LPU inference engine with llama/gpt-oss models)
- **Frontend**: HTML5, CSS3 (Custom Design System), Modern Vanilla JavaScript
- **Email & Auth**: PHPMailer (SMTP OTP verification support)

---

## Default Demo Credentials

- **Admin Account**:
  - Email: `smartexam0167@gmail.com`
  - Password: `admin123`
- **Student Account**:
  - Email: `student@smartexam.com`
  - Password: `student123`

---

## Database Setup

1. Create a MySQL database (e.g. `smartexam_db`).
2. Import the schema file: `smartexam_db.sql`.
3. Update database connection settings in `config/database.php`.
4. Configure your Groq AI key in `config/ai_config.php`.

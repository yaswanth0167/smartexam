<?php

// ==========================================
// AI ENGINE CONFIGURATION (GROQ & OPENROUTER)
// ==========================================

define(
    "AI_PROVIDER",
    "groq"
);

// Load local secret credentials if available
if (file_exists(__DIR__ . "/ai_config.local.php")) {
    require_once __DIR__ . "/ai_config.local.php";
}

// GROQ AI CONFIGURATION
if (!defined("GROQ_API_KEY")) {
    define(
        "GROQ_API_KEY",
        getenv("GROQ_API_KEY") ?: "YOUR_GROQ_API_KEY_HERE"
    );
}

define(
    "GROQ_MODEL",
    "openai/gpt-oss-120b"
);

define(
    "GROQ_API_URL",
    "https://api.groq.com/openai/v1/chat/completions"
);

// OPENROUTER FALLBACK / COMPATIBILITY
if (!defined("OPENROUTER_API_KEY")) {
    define(
        "OPENROUTER_API_KEY",
        GROQ_API_KEY
    );
}

define(
    "OPENROUTER_MODEL",
    GROQ_MODEL
);

define(
    "OPENROUTER_API_URL",
    GROQ_API_URL
);

?>
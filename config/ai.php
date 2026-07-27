<?php
// ============================================================
// TradeLens AI Configuration — Ollama (100% Free, Local)
// ============================================================
// 1. Download Ollama: https://ollama.com
// 2. Open Terminal and run:  ollama pull llama3.2
// 3. Ollama runs automatically at localhost:11434
// No API key needed — works completely offline!
// ============================================================

define('AI_PROVIDER',   'ollama');
define('OLLAMA_HOST',   'http://localhost:11434');
define('OLLAMA_MODEL',  'llama3.2');   // change to any model you've pulled

// Other free models you can use (run: ollama pull <model>):
//   llama3.2:1b   — very fast, lightweight
//   mistral       — great quality
//   gemma3        — Google's open model
//   qwen2.5       — fast and smart
//   phi4          — small but capable

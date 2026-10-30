<?php

/**
 * Procedural AI Fallback Generator
 * Generates valid MCQ or True/False questions when AI API is unavailable or rate-limited.
 */
function get_procedural_ai_questions($subject, $topic, $count, $difficulty, $question_type) {
    $sub = htmlspecialchars(trim($subject));
    $top = htmlspecialchars(trim($topic));
    $diff = strtolower(trim($difficulty));

    $templates_mcq = [
        [
            'q' => "What is a primary principle of {$top} in {$sub}?",
            'a' => "Ensures modular structure and predictable execution",
            'b' => "Completely disables error handling and logging",
            'c' => "Restricts application memory to single byte blocks",
            'd' => "Forces all transactions to be public and unencrypted",
            'ans' => 'A',
            'exp' => "In {$sub}, {$top} promotes clean modularity, maintainability, and reliable operational state."
        ],
        [
            'q' => "Which of the following is considered best practice when working with {$top} in {$sub}?",
            'a' => "Handling edge cases, validating input, and optimizing resource bounds",
            'b' => "Hardcoding sensitive secrets and credentials in the core code",
            'c' => "Ignoring boundary conditions and unexpected input formats",
            'd' => "Removing unit tests to reduce build and deployment time",
            'ans' => 'A',
            'exp' => "Robust solutions in {$sub} require thorough validation, boundary checks, and resource awareness."
        ],
        [
            'q' => "In {$sub}, how does proper implementation of {$top} enhance performance?",
            'a' => "By minimizing unnecessary overhead and preventing algorithmic bottlenecks",
            'b' => "By converting all multithreaded operations into blocking loops",
            'c' => "By bypassing the hardware cache and CPU instruction pipeline",
            'd' => "By increasing infinite recursion depth without an exit condition",
            'ans' => 'A',
            'exp' => "Optimized {$top} designs reduce computational complexity and prevent system bottlenecks."
        ],
        [
            'q' => "What is the most likely consequence of misconfiguring {$top} in {$sub}?",
            'a' => "Runtime anomalies, data inconsistency, or unexpected termination",
            'b' => "Instantaneous automatic bug resolution across all modules",
            'c' => "Automatic generation of complete documentation and unit tests",
            'd' => "Permanent increase in hardware processor frequency",
            'ans' => 'A',
            'exp' => "Configuration errors in {$top} usually lead to unexpected runtime state or system crashes."
        ],
        [
            'q' => "Which layer or component directly collaborates with {$top} during {$sub} execution?",
            'a' => "The core logic layer and designated data processing interfaces",
            'b' => "The external analog cooling fans and power adapters",
            'c' => "The display refresh rate synchronization crystal",
            'd' => "Unrelated operating system background wallpaper services",
            'ans' => 'A',
            'exp' => "Execution pipelines interface through the core logic and runtime handlers in {$sub}."
        ],
        [
            'q' => "Why is understanding {$top} critical for scalable architecture in {$sub}?",
            'a' => "It provides decoupled data structures and efficient flow control",
            'b' => "It guarantees zero power consumption on mobile devices",
            'c' => "It forces every operation to run synchronously on a single core",
            'd' => "It completely removes the requirement for database storage",
            'ans' => 'A',
            'exp' => "Scalability relies on decoupled components and efficient flow control provided by {$top}."
        ]
    ];

    $templates_tf = [
        [
            'q' => "In {$sub}, gaining proficiency in {$top} is essential for building robust software.",
            'ans' => 'A',
            'exp' => "Mastering {$top} forms the foundation for dependable application design in {$sub}."
        ],
        [
            'q' => "Applying {$top} in {$sub} completely eliminates the necessity for testing and debugging.",
            'ans' => 'B',
            'exp' => "Testing and debugging remain fundamental requirements regardless of the methodologies used."
        ],
        [
            'q' => "Following standard architectural patterns for {$top} improves team collaboration in {$sub}.",
            'ans' => 'A',
            'exp' => "Standard patterns make code readable, modular, and easier for engineering teams to maintain."
        ],
        [
            'q' => "{$top} in {$sub} is completely deprecated and has zero relevance in modern computing.",
            'ans' => 'B',
            'exp' => "{$top} is widely utilized and remains relevant in modern systems."
        ],
        [
            'q' => "Proper documentation of {$top} workflows simplifies onboarding and long-term maintenance in {$sub}.",
            'ans' => 'A',
            'exp' => "Clear documentation significantly improves maintainability and team efficiency."
        ]
    ];

    $keys = ['A', 'B', 'C', 'D'];
    $questions = [];

    for ($i = 0; $i < $count; $i++) {
        if ($question_type === 'true_false') {
            $t = $templates_tf[$i % count($templates_tf)];
            $questions[] = [
                'question' => $t['q'],
                'option_a' => 'True',
                'option_b' => 'False',
                'option_c' => '',
                'option_d' => '',
                'correct_answer' => $t['ans'],
                'explanation' => $t['exp'],
                'marks' => 1,
                'difficulty' => $diff
            ];
        } else {
            $t = $templates_mcq[$i % count($templates_mcq)];
            $opts = [$t['a'], $t['b'], $t['c'], $t['d']];

            // Rotate options so correct answer is distributed across A, B, C, D
            $shift = $i % 4;
            $rotated = [];
            for ($k = 0; $k < 4; $k++) {
                $rotated[] = $opts[($k + (4 - $shift)) % 4];
            }
            $correct_key = $keys[$shift];

            $questions[] = [
                'question' => $t['q'],
                'option_a' => $rotated[0],
                'option_b' => $rotated[1],
                'option_c' => $rotated[2],
                'option_d' => $rotated[3],
                'correct_answer' => $correct_key,
                'explanation' => $t['exp'],
                'marks' => 1,
                'difficulty' => $diff
            ];
        }
    }

    return $questions;
}
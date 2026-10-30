<?php
/**
 * Generate the test documentation page: doc/testing/test_docs.html
 *
 * Reads the PHPUnit and Playwright test sources without running them, so the
 * documentation describes the tests independently from their results. The page
 * is committed: it carries no generation date, so it only changes with the tests.
 * The feature list comes from the "Test Features" table of
 * doc/AI_INSTRUCTIONS.md, which is its single source.
 *
 * Usage: php bin/gen_test_docs.php
 */

const TD_FEATURES_FILE = 'doc/AI_INSTRUCTIONS.md';
const TD_OUTPUT_FILE = 'doc/testing/test_docs.html';
const TD_TYPES = array('Unitaire', 'Intégration', 'E2E');
const TD_PHP_SUITES = array(
    'unit' => 'Unitaire',
    'integration' => 'Intégration',
    'enhanced' => 'Intégration',
    'mysql' => 'Intégration',
);

/**
 * Read the ordered feature list from the table between the test-features
 * markers. Each row gives a feature name and the file keywords (regular
 * expressions) that select its tests.
 * @param string $markdown
 * @return array[] Each entry: ['name' => string, 'keywords' => string[]]
 */
function td_load_features($markdown) {
    if (!preg_match('/<!-- test-features:start -->(.*?)<!-- test-features:end -->/s', $markdown, $block)) {
        return array();
    }

    $features = array();
    foreach (explode("\n", $block[1]) as $line) {
        if (!preg_match('/^\|\s*([^|]+?)\s*\|\s*(`.*`)\s*\|\s*$/', $line, $row)) {
            continue;
        }
        preg_match_all('/`([^`]+)`/', $row[2], $keywords);
        $features[] = array('name' => $row[1], 'keywords' => $keywords[1]);
    }
    return $features;
}

/**
 * First feature whose keywords match the test file path.
 * @param string $path Path relative to the test directory
 * @param array[] $features Result of td_load_features()
 * @return string|null
 */
function td_classify($path, $features) {
    foreach ($features as $feature) {
        foreach ($feature['keywords'] as $keyword) {
            if (preg_match('/' . str_replace('/', '\/', $keyword) . '/i', $path)) {
                return $feature['name'];
            }
        }
    }
    return null;
}

/**
 * Extract the feature and the Gherkin scenario of a comment block.
 * @param string $comment Raw comment, delimiters included
 * @return array ['feature' => string|null, 'scenario' => string|null]
 */
function td_parse_description($comment) {
    $feature = null;
    $scenario = array();
    $in_scenario = false;

    foreach (explode("\n", $comment) as $line) {
        $line = trim(preg_replace('#^\s*(/\*+|\*+/|\*)#', '', preg_replace('#\*+/\s*$#', '', $line)));
        if (preg_match('/^@feature\s+(.+)$/', $line, $match)) {
            $feature = trim($match[1]);
            $in_scenario = false;
        } elseif (preg_match('/^(Scénario|Scenario)\s*:/iu', $line)) {
            $scenario[] = $line;
            $in_scenario = true;
        } elseif ($in_scenario && strpos($line, '@') === 0) {
            $in_scenario = false;
        } elseif ($in_scenario && $line !== '') {
            $scenario[] = $line;
        }
    }

    return array(
        'feature' => $feature,
        'scenario' => $scenario ? implode("\n", $scenario) : null,
    );
}

/**
 * Feature declared once for a whole file, in a comment attached to no test.
 * @param string[] $comments Every comment of the file
 * @param string[] $attached Comments attached to a test
 * @return string|null
 */
function td_file_feature($comments, $attached) {
    foreach (array_diff($comments, $attached) as $comment) {
        $description = td_parse_description($comment);
        if ($description['feature'] !== null) {
            return $description['feature'];
        }
    }
    return null;
}

/**
 * List the test methods of a PHP source.
 * @param string $source
 * @return array[] Each entry: name, line, feature, scenario
 */
function td_parse_php($source) {
    $tokens = token_get_all($source);
    $skipped = array(T_WHITESPACE, T_PUBLIC, T_FINAL, T_ABSTRACT);
    $comments = array();
    $attached = array();
    $tests = array();

    foreach ($tokens as $index => $token) {
        if (!is_array($token)) {
            continue;
        }
        if ($token[0] === T_DOC_COMMENT) {
            $comments[] = $token[1];
        }
        if ($token[0] !== T_FUNCTION) {
            continue;
        }

        $next = $index + 1;
        while (isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) {
            $next++;
        }
        if (!isset($tokens[$next]) || !is_array($tokens[$next]) || $tokens[$next][0] !== T_STRING) {
            continue;
        }
        $name = $tokens[$next][1];

        $comment = '';
        $previous = $index - 1;
        while ($previous >= 0 && is_array($tokens[$previous]) && in_array($tokens[$previous][0], $skipped, true)) {
            $previous--;
        }
        if ($previous >= 0 && is_array($tokens[$previous]) && $tokens[$previous][0] === T_DOC_COMMENT) {
            $comment = $tokens[$previous][1];
        } elseif ($previous >= 0 && is_array($tokens[$previous])
            && in_array($tokens[$previous][0], array(T_PROTECTED, T_PRIVATE, T_STATIC), true)) {
            continue;
        }

        if (stripos($name, 'test') !== 0 && !preg_match('/@test\b/', $comment)) {
            continue;
        }

        $attached[] = $comment;
        $tests[] = array_merge(
            array('name' => $name, 'line' => $token[2]),
            td_parse_description($comment)
        );
    }

    return td_apply_file_feature($tests, td_file_feature($comments, $attached));
}

/**
 * List the tests of a Playwright source. A test defined in a loop counts once,
 * and test.skip('reason') inside a test body is not a test.
 * @param string $source
 * @return array[] Each entry: name, line, feature, scenario
 */
function td_parse_js($source) {
    preg_match_all('#/\*.*?\*/#s', $source, $comments);
    preg_match_all(
        '/^[ \t]*test(?:\.(?:only|skip|fixme|fail|slow))?\(\s*([\'"`])((?:\\\\.|(?!\1).)*)\1\s*,/ms',
        $source,
        $matches,
        PREG_OFFSET_CAPTURE
    );

    $attached = array();
    $tests = array();
    foreach ($matches[0] as $index => $match) {
        $offset = $match[1];
        $comment = '';
        $before = rtrim(substr($source, 0, $offset));
        if (substr($before, -2) === '*/') {
            $comment = substr($before, strrpos($before, '/*'));
        }

        $attached[] = $comment;
        $tests[] = array_merge(
            array(
                'name' => stripslashes($matches[2][$index][0]),
                'line' => substr_count($source, "\n", 0, $offset + strlen($match[0]) - strlen(ltrim($match[0]))) + 1,
            ),
            td_parse_description($comment)
        );
    }

    return td_apply_file_feature($tests, td_file_feature($comments[0], $attached));
}

/**
 * @param array[] $tests
 * @param string|null $file_feature
 * @return array[]
 */
function td_apply_file_feature($tests, $file_feature) {
    foreach ($tests as &$test) {
        if ($test['feature'] === null) {
            $test['feature'] = $file_feature;
        }
    }
    unset($test);
    return $tests;
}

/**
 * Readable title for a test method without scenario.
 * testResolveTargets_ExcludesMember becomes "Resolve targets - excludes member".
 * @param string $name
 * @return string
 */
function td_humanize($name) {
    if (strpos($name, ' ') !== false) {
        return $name;
    }
    $words = preg_replace('/^test_?/i', '', $name);
    $words = preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', ' ', $words);
    $words = strtolower(trim(str_replace('_', ' - ', $words)));
    return $words === '' ? $name : ucfirst($words);
}

/**
 * Collect every test of the repository with its type and feature.
 * @param string $root Repository root
 * @param array[] $features Result of td_load_features()
 * @return array[]
 */
function td_collect($root, $features) {
    $sources = array();
    foreach (TD_PHP_SUITES as $suite => $type) {
        foreach (td_files("$root/application/tests/$suite", '/\.php$/') as $file) {
            if (strpos(basename($file), 'bootstrap') === false) {
                $sources[] = array($file, 'td_parse_php', $type, $suite, "$root/application/tests/");
            }
        }
    }
    foreach (td_files("$root/playwright/tests", '/\.spec\.(js|ts)$/') as $file) {
        $sources[] = array($file, 'td_parse_js', 'E2E', 'playwright', "$root/playwright/tests/");
    }

    $all = array();
    foreach ($sources as $source) {
        list($file, $parser, $type, $suite, $base) = $source;
        $relative = substr($file, strlen($base));
        foreach ($parser(file_get_contents($file)) as $test) {
            $all[] = array(
                'feature' => $test['feature'] ?? td_classify($relative, $features),
                'type' => $type,
                'suite' => $suite,
                'name' => $test['name'],
                'title' => td_humanize($test['name']),
                'file' => substr($file, strlen($root) + 1),
                'line' => $test['line'],
                'scenario' => $test['scenario'],
            );
        }
    }
    return $all;
}

/**
 * @param string $directory
 * @param string $pattern
 * @return string[] Sorted file paths
 */
function td_files($directory, $pattern) {
    $files = array();
    if (!is_dir($directory)) {
        return $files;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (preg_match($pattern, $file->getPathname())) {
            $files[] = $file->getPathname();
        }
    }
    sort($files);
    return $files;
}

/**
 * Features declared in tests but missing from the list.
 * @param array[] $tests
 * @param array[] $features
 * @return string[]
 */
function td_unknown_features($tests, $features) {
    $known = array_column($features, 'name');
    return array_values(array_unique(array_diff(array_column($tests, 'feature'), $known)));
}

/**
 * Fragments of a scenario that look like code rather than behavior: function
 * calls, variables, snake_case or camelCase names, selectors, file names.
 * @param string|null $scenario
 * @return string[]
 */
function td_code_traces($scenario) {
    $pattern = '/\b\w+\(\)|\$\w+|\b[a-z]+_[a-z_]+\b|\b[a-z]+[A-Z]\w*|(?<!\S)#[a-zA-Z][\w-]*|\b[\w-]+\.(?:php|js|ts)\b/';
    preg_match_all($pattern, (string) $scenario, $traces);
    return array_values(array_unique($traces[0]));
}

/**
 * @param array[] $tests
 * @param array[] $features
 * @return string Self-contained HTML page
 */
function td_render_html($tests, $features) {
    $data = json_encode(array(
        'types' => TD_TYPES,
        'features' => array_merge(array_column($features, 'name'), td_unknown_features($tests, $features)),
        'tests' => $tests,
    ), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP);

    return str_replace('{{DATA}}', $data, file_get_contents(__DIR__ . '/gen_test_docs_template.html'));
}

function td_main() {
    $root = dirname(__DIR__);
    $features = td_load_features(file_get_contents($root . '/' . TD_FEATURES_FILE));
    if (empty($features)) {
        fwrite(STDERR, "Liste des features introuvable dans " . TD_FEATURES_FILE . "\n");
        return 1;
    }

    $tests = td_collect($root, $features);

    $output = $root . '/' . TD_OUTPUT_FILE;
    if (!is_dir(dirname($output))) {
        mkdir(dirname($output), 0775, true);
    }
    file_put_contents($output, td_render_html($tests, $features));

    $described = count(array_filter(array_column($tests, 'scenario')));
    echo "Documentation des tests : $output\n";
    echo count($tests) . " tests, $described avec scénario, " . count($features) . " features\n";
    foreach (td_unknown_features($tests, $features) as $feature) {
        echo "Attention : feature inconnue, à ajouter à la liste : $feature\n";
    }
    foreach ($tests as $test) {
        $traces = td_code_traces($test['scenario']);
        if ($traces) {
            echo "Attention : scénario à revoir, {$test['file']}:{$test['line']} : " . implode(', ', $traces) . "\n";
        }
    }
    return 0;
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    exit(td_main());
}

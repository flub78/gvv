<?php
// Différences ligne à ligne — paramètre : $diff (Newsletter_files::diff_lines)
// Les lignes inchangées éloignées d'une modification sont repliées.
$context_lines = 2;
$count = count($diff);
$show = array_fill(0, $count, FALSE);
foreach ($diff as $i => $d) {
    if ($d['op'] !== ' ') {
        for ($k = max(0, $i - $context_lines); $k <= min($count - 1, $i + $context_lines); $k++) $show[$k] = TRUE;
    }
}
$changes = count(array_filter($diff, function ($d) { return $d['op'] !== ' '; }));
?>
<?php if ($changes === 0): ?>
<p class="text-muted"><?= $this->lang->line('nl_diff_none') ?></p>
<?php else: ?>
<pre class="border rounded p-2 small" style="white-space: pre-wrap; max-height: 70vh; overflow: auto;"><?php
$skipped = FALSE;
foreach ($diff as $i => $d) {
    if (!$show[$i]) {
        if (!$skipped) { echo '<span class="text-muted">…</span>' . "\n"; $skipped = TRUE; }
        continue;
    }
    $skipped = FALSE;
    $line = htmlspecialchars($d['line']);
    if ($d['op'] === '+')      echo '<span class="bg-success-subtle d-block">+ ' . $line . '</span>';
    elseif ($d['op'] === '-')  echo '<span class="bg-danger-subtle d-block text-decoration-line-through">- ' . $line . '</span>';
    else                       echo '  ' . $line . "\n";
}
?></pre>
<?php endif; ?>

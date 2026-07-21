<?php require_once dirname(__DIR__) . '/includes/auth.php'; require_admin(); $pdo=db(); include '_header.php'; ?>
<h1>Dashboard</h1><p class="muted">Signup-based follow-up sequences only. This module intentionally has no bulk import or eblast tools.</p><div class="grid">
<?php foreach(['clients','email_sequences','subscribers','email_send_log'] as $t): $count=$pdo->query("SELECT COUNT(*) c FROM $t")->fetch()['c']; ?><div class="card"><h2><?=e(ucwords(str_replace('_',' ',$t)))?></h2><strong><?=e($count)?></strong></div><?php endforeach; ?>
</div><?php include '_footer.php'; ?>

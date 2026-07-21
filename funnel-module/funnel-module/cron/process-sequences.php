<?php
// Signup-based follow-up sequence processor. This is not a bulk eblast tool.
require_once dirname(__DIR__) . '/includes/db.php'; require_once dirname(__DIR__) . '/includes/mailer.php';
if (($_GET['token'] ?? '') !== config('cron_secret_token')) { http_response_code(403); echo "Forbidden\n"; exit; }
$limit = max(1, min(100, (int)($_GET['limit'] ?? config('batch_limit', 25)))); $pdo=db(); $sent=0;
$sql='SELECT sub.*,seq.status sequence_status,c.* FROM subscribers sub JOIN email_sequences seq ON seq.id=sub.sequence_id JOIN clients c ON c.id=sub.client_id WHERE sub.status="active" AND seq.status="active" ORDER BY sub.signup_date ASC LIMIT 500';
foreach($pdo->query($sql) as $row){ if($sent >= $limit) break; $day=max(1, (int)floor((time()-strtotime($row['signup_date']))/86400)+1);
 $chk=$pdo->prepare('SELECT id FROM email_send_log WHERE subscriber_id=? AND sequence_id=? AND day_number=? AND sent_status="success"'); $chk->execute([$row['id'],$row['sequence_id'],$day]); if($chk->fetch()) continue;
 $tpl=$pdo->prepare('SELECT * FROM email_templates WHERE sequence_id=? AND day_number=? AND status="active"'); $tpl->execute([$row['sequence_id'],$day]); $template=$tpl->fetch(); if(!$template) continue;
 $res=send_funnel_email($row,$row,$template); $pdo->prepare('INSERT IGNORE INTO email_send_log (subscriber_id,sequence_id,template_id,day_number,recipient_email,subject,sent_status,error_message) VALUES (?,?,?,?,?,?,?,?)')->execute([$row['id'],$row['sequence_id'],$template['id'],$day,$row['email'],$res['subject']??$template['subject'],$res['success']?'success':'failed',$res['error']]); $sent++; }
echo "Processed sends: $sent\n";

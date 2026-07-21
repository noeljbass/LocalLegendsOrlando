<?php
require_once dirname(__DIR__) . '/includes/db.php'; require_once dirname(__DIR__) . '/includes/mailer.php';
header('Content-Type: application/json'); require_post();
if (!empty($_POST['website'] ?? '')) { echo json_encode(['success'=>true,'message'=>'Thanks.']); exit; } // honeypot
$name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $sequenceId=(int)($_POST['sequence_id']??0);
if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || $sequenceId<1) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Valid name, email, and sequence are required.']); exit; }
$pdo=db();
$stmt=$pdo->prepare('SELECT s.*,c.* FROM email_sequences s JOIN clients c ON c.id=s.client_id WHERE s.id=? AND s.status="active"'); $stmt->execute([$sequenceId]); $seq=$stmt->fetch();
if(!$seq){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'Sequence unavailable.']); exit; }
$stmt=$pdo->prepare('SELECT id FROM subscribers WHERE sequence_id=? AND email=? AND status="active"'); $stmt->execute([$sequenceId,$email]); if($stmt->fetch()){ echo json_encode(['success'=>false,'message'=>'This email is already subscribed.']); exit; }
$token=bin2hex(random_bytes(32));
$pdo->prepare('INSERT INTO subscribers (client_id,sequence_id,name,email,unsubscribe_token,signup_ip) VALUES (?,?,?,?,?,?)')->execute([$seq['client_id'],$sequenceId,$name,$email,$token,client_ip()]); $subscriberId=$pdo->lastInsertId();
$subscriber=['id'=>$subscriberId,'name'=>$name,'email'=>$email,'unsubscribe_token'=>$token];
$stmt=$pdo->prepare('SELECT * FROM email_templates WHERE sequence_id=? AND day_number=1 AND status="active"'); $stmt->execute([$sequenceId]); $template=$stmt->fetch();
if($template){ $res=send_funnel_email($subscriber,$seq,$template); $pdo->prepare('INSERT IGNORE INTO email_send_log (subscriber_id,sequence_id,template_id,day_number,recipient_email,subject,sent_status,error_message) VALUES (?,?,?,?,?,?,?,?)')->execute([$subscriberId,$sequenceId,$template['id'],1,$email,$res['subject']??$template['subject'],$res['success']?'success':'failed',$res['error']]); }
echo json_encode(['success'=>true,'message'=>'Subscription received.']);

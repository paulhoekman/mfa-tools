<?php
header("Content-type: application/json");
$project = (int)$_POST['project'];
require_once 'functions.php';
require_once 'functions.omat.php';

$contact = (int)$_POST['contact'];
$source = (int)$_POST['source'];

if ($_POST['action'] == 'addcontact') {
  // If this contact already exists, then we just add a lead, but we 
  // don't repeat adding the same contact again
  $name = html(trim($_POST['name']));  
  $check = $db->record("SELECT * FROM mfa_contacts WHERE dataset = $project AND name = '$name' LIMIT 1");
  if (!$check->id) {
    $post = array(
      'name' => html(trim($_POST['name'])),
      'organization' => (int)$_POST['organization'],
      'belongs_to' => (int)$_POST['works_for_referral_organization'] ? $contact : NULL,
      'dataset' => $project,
      'status' => 1,
    );
    $db->insert("mfa_contacts",$post);
    $id = $db->lastInsertId();
  } else {
    $id = $check->id;
  }
  $post = array(
    'to_contact' => $id,
  );
  if ($contact) {
    $post['from_contact'] = $contact;
  } else {
    $post['from_source'] = $source;
  }
  $db->insert("mfa_leads",$post);
  $icon = $_POST['organization'] ? 'building-o' : 'user';
  $data['response'] = 'OK';
  $data['message'] = 
    "<a class='list-group-item active' href='omat/{$project}/viewcontact/{$id}'>
      <i class='fa fa-{$icon}'></i> {$_POST['name']}</a>";
}
if ($_POST['action'] == 'addsource') {
  $name = html(trim($_POST['name']));  
  $check = $db->record("SELECT * FROM mfa_sources WHERE dataset = $project AND name = '$name' LIMIT 1");
  if (!$check->id) {
    $post = array(
      'name' => html(trim($_POST['name'])),
      'dataset' => $project,
      'status' => 1,
    );
    $db->insert("mfa_sources",$post);
    $id = $db->lastInsertId();
  } else {
    $id = $check->id;
  }
  $post = array(
    'to_source' => $id,
  );
  if ($contact) {
    $post['from_contact'] = $contact;
  } else {
    $post['from_source'] = $source;
  }
  $db->insert("mfa_leads",$post);
  $data['response'] = 'OK';
  $data['message'] = 
    "<a class='list-group-item active' href='omat/{$project}/viewsource/{$id}'>{$_POST['name']}</a>";
}
if ($_POST['action'] == 'addactivity') {
  $explode = explode(":", $_POST['time']);
  if ($explode[1]) {
    $time = $explode[0]*60 + $explode[1];
  } else { 
    $time = (int)$_POST['time'];
  }
  if ($_POST['timer']) {
    $post = array(
      'activity' => (int)$_POST['type'],
      'start' => date("Y-m-d H:i:s"),
    );
    $icon = '<i class="fa fa-clock-o"></i>';
    $min = "<em>ongoing</em>";
  } else {
    $post = array(
      'activity' => (int)$_POST['type'],
      'time' => $time,
      'end' => date("Y-m-d H:i:s"),
    );
    $min = "$time min";
  }
  if ($contact) {
    $post['contact'] = $contact;
  } else {
    $post['source'] = $source;
  }
  $type = (int)$_POST['type'];
  $getname = $db->record("SELECT * FROM mfa_activities WHERE dataset = $project AND id = $type");
  $db->insert("mfa_activities_log",$post);
  $id = $db->lastInsertId();
  $data['response'] = 'OK';
  $data['message'] = 
    "<a class='list-group-item active' href='omat/{$project}/viewactivity/{$id}'>
    $icon
    {$getname->name} ($min)</a>";
} elseif ($_POST['specialty']) {
  $specialty = (int)$_POST['specialty'];
  $id = (int)$_POST['id'];
  $db->query("UPDATE mfa_sources SET specialty = $specialty WHERE id = $id AND dataset = $project");
  $data['response'] = 'OK';
} elseif ($_POST['belongs_to']) {
  $belongs_to = (int)$_POST['belongs_to'];
  $id = (int)$_POST['id'];
  if ($_POST['contact']) {
    $db->query("UPDATE mfa_contacts SET belongs_to = $belongs_to WHERE id = $id AND dataset = $project");
  } elseif ($_POST['source']) {
    $db->query("UPDATE mfa_sources SET belongs_to = $belongs_to WHERE id = $id AND dataset = $project");
  }
  $data['response'] = 'OK';
}
if (!$data) {
  $data['reponse'] = 'Fail';
}
echo json_encode($data);
?>

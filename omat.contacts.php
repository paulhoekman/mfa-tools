<?php
require_once 'functions.php';
require_once 'functions.omat.php';
$section = 6;
$load_menu = 1;
$sub_page = 2;

$id = $project;

$type = (int)$_GET['type'];
$status = (int)$_GET['status'];

$sql = $type ? " AND c.type = $type" : false;
if ($status) {
  $sql .= " AND c.status = $status";
  $status_name = $db->record("SELECT * FROM mfa_status_options WHERE id = $status");
  $type_sql = "AND mfa_contacts.status = $status";
}

if ($_GET['flag']) {
  $flag = (int)$_GET['flag'];
  $sql .= " AND EXISTS (SELECT * FROM mfa_contacts_flags WHERE contact = c.id AND flag = $flag)";
  $type_sql .= " AND EXISTS (SELECT * FROM mfa_contacts_flags WHERE contact = mfa_contacts.id AND flag = $flag)";
}

if ($_GET['random-contact']) {
  $contact = $db->record("SELECT * FROM mfa_contacts WHERE dataset = $project AND status = 1 ORDER BY RAND() LIMIT 1");
  if ($contact->id) {
    header("Location: " . URL . "omat/$project/viewcontact/{$contact->id}");
    exit();
  }
}

$list = $db->query("SELECT c.*, t.name AS type, o.status,
  (SELECT name FROM mfa_leads
    JOIN mfa_contacts ON mfa_leads.from_contact = mfa_contacts.id
    WHERE mfa_leads.to_contact = c.id ORDER BY mfa_leads.id DESC LIMIT 1) AS referral
FROM mfa_contacts c
  LEFT JOIN mfa_contacts_types t ON c.type = t.id
  JOIN mfa_status_options o ON c.status = o.id
WHERE c.dataset = $id $sql
  ORDER BY name
");

$types = $db->query("SELECT *,
  (SELECT COUNT(*) FROM mfa_contacts WHERE type = mfa_contacts_types.id $type_sql) as total
FROM mfa_contacts_types WHERE dataset = $id ORDER BY name");

foreach ($types as $row) {
  $overall_total += $row['total'];
}

$unclassified = $db->record("SELECT COUNT(*) AS total FROM mfa_contacts WHERE dataset = $id AND type IS NULL $type_sql");

if ($_GET['deleted']) {
  $print = "The contact has been deleted";
}

$status_options = $db->query("SELECT * FROM mfa_status_options ORDER BY id");
$flags = $db->query("SELECT * FROM mfa_special_flags ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php echo $header ?>
    <title>Contacts | <?php echo SITENAME ?></title>
    <style type="text/css">
    table {width:100%;table-layout: fixed;}
    th,td{ white-space:nowrap; overflow:hidden; text-overflow: ellipsis; }
    .right{float:right;margin-left:6px;}
    .table > tbody > tr > th{border-top:0}
    .row-name{width:auto}
    .row-employer{width:200px}
    .row-status,.row-added{width:130px}
    </style>
  </head>

  <body>

<?php require_once 'include.header.php'; ?>

  <a href="omat/<?php echo $id ?>/contact/0" class="btn btn-success right">Add Contact</a>

  <?php foreach ($flags as $row) { ?>
    <form method="get" action="omat.contacts.php">
      <button 
      type="submit" class="btn btn-<?php echo $_GET['flag'] == $row['id'] ? 'primary' : 'default' ?> right" 
      name="flag" value="<?php echo $_GET['flag'] == $row['id'] ? 0 : $row['id'] ?>"><?php echo $row['name'] ?></button>
      <input type="hidden" name="project" value="<?php echo $project ?>" />
      <input type="hidden" name="status" value="<?php echo $status ?>" />
      <input type="hidden" name="type" value="<?php echo $type ?>" />
    </form>
  <?php } ?>

  <div class="dropdown right">
    <button class="btn btn-<?php echo $status ? 'primary' : 'default'; ?> dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown">
      <?php echo $_GET['status'] ? "Status: <strong>" . $status_name->status . "</strong>" : 'Filter by Status'; ?>
      <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
      <li role="presentation"<?php if (!$_GET['status']) { echo ' class="active"'; } ?>>
        <a role="menuitem" tabindex="-1" href="omat.contacts.php?project=<?php echo $id ?>&amp;type=<?php echo $type ?>&amp;flag=<?php echo $flag ?>">
          <?php if (!$_GET['status']) { ?>
            <i class="fa fa-check"></i>
          <?php } ?>
          All
        </a>
      </li>
    <?php foreach ($status_options as $row) { ?>
      <li role="presentation"<?php if ($_GET['status'] == $row['id']) { echo ' class="active"'; } ?>>
        <a role="menuitem" tabindex="-1" href="omat.contacts.php?project=<?php echo $id ?>&amp;type=<?php echo $type ?>&amp;status=<?php echo $row['id'] ?>&amp;flag=<?php echo $flag ?>">
          <?php if ($row['id'] == $_GET['status']) { ?>
            <i class="fa fa-check"></i>
          <?php } ?>
          <?php echo $row['status'] ?>
        </a>
      </li>
    <?php } ?>
    </ul>
  </div>


  <h1>Contacts</h1>

  <ol class="breadcrumb">
    <li><a href="omat/<?php echo $project ?>/dashboard">Dashboard</a></li>
    <li class="active">Contacts</li>
  </ol>

  <?php if ($print) { echo "<div class=\"alert alert-success\">$print</div>"; } ?>

  <?php if (count($types)) { ?>
    <ul class="nav nav-tabs" role="tablist">
      <li class="<?php echo !$_GET['type'] ? 'active' : 'regular'; ?>"><a href="omat.contacts.php?project=<?php echo $id ?>&amp;status=<?php echo $status ?>&amp;flag=<?php echo $flag ?>">All (<?php echo $overall_total+$unclassified->total ?>)</a></li>
    <?php foreach ($types as $row) { ?>
      <li class="<?php if ($_GET['type'] == $row['id']) { echo 'active'; } elseif (!$row['total']) { echo 'disabled'; } else { echo 'regular'; } ?>">
        <a href="omat.contacts.php?project=<?php echo $id ?>&amp;status=<?php echo $status ?>&amp;type=<?php echo $row['id'] ?>&amp;flag=<?php echo $flag ?>">
          <?php echo $row['name'] ?> (<?php echo $row['total'] ?>)
        </a>
      </li>
    <?php } ?>
    </ul>
  <?php } else { ?>
    <div class="alert alert-info">A total of <?php echo count($list) ?> contacts where found.</div>
  <?php } ?>

  <?php if (count($list)) { ?>

    <table class="table table-striped">
      <tr>
        <th class="row-name">Name</th>
        <th class="row-employer">Employer</th>
        <th class="row-added">Added</th>
        <th class="row-status">Status</th>
      </tr>
    <?php foreach ($list as $row) { ?>
      <tr>
        <td class="long"><a href="omat/<?php echo $project ?>/viewcontact/<?php echo $row['id'] ?>"><?php echo $row['name'] ?></a></td>
        <td class="medium"><?php echo $row['works_for_referral_organization'] ? $row['referral'] : $row['employer']; ?></td>
        <td><?php echo format_date("M d, Y", $row['created']) ?></td>
        <td>
          <?php echo $row['status'] ?>
        </td>
      </tr>
    <?php } ?>
    </table>

  <?php } ?>

  <a href="omat/<?php echo $id ?>/contact/0" class="btn btn-success">Add Contact</a>
  <a href="omat/<?php echo $project ?>/contacts/random-contact" class="btn btn-success"><i class="fa fa-random"></i> Open random pending contact</a>

<?php require_once 'include.footer.php'; ?>

  </body>
</html>

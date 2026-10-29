<?php
require_once 'inc/config.php';
require_once 'inc/connect.php';

date_default_timezone_set('Europe/Dublin');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header('Location: login.php'); exit; }
if (!isset($_SESSION['regenerated'])) { session_regenerate_id(true); $_SESSION['regenerated'] = true; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zynex Root - Crew</title>

<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/webkul-micron@1.1.6/dist/css/micron.min.css">
<link rel="stylesheet" href="css/toastr.min.css">
<link rel="stylesheet" href="css/flag-icon.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/x-icon" href="img/favicon.ico">

<style>
:root{
  --primary:#6366f1; --primary-dark:#4f46e5; --secondary:#8b5cf6;
  --bg:#0f172a; --bg-2:#0b1220; --card:#0f172a; --border:#1f2937;
  --text:#fff; --text-2:#e5e7eb; --muted:#94a3b8;
  --shadow:0 12px 28px rgba(0,0,0,.35);
}
*{box-sizing:border-box}
body{font-family:'Inter',system-ui,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text);}

/* Header */
.modern-header{background:#0b1220;border-bottom:1px solid var(--border);padding:16px 0;position:sticky;top:0;z-index:10}
.header-container{padding:0 24px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.logo-section img{width:56px;height:56px;border-radius:14px}
.user-menu{display:flex;gap:12px;align-items:center;flex-wrap:nowrap}
.user-menu .nav-link-modern{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1rem;border:1px solid var(--border);border-radius:12px;background:var(--card);text-decoration:none;color:var(--text);font-weight:700;white-space:nowrap}
.user-menu .nav-link-modern:hover{background:var(--primary);border-color:var(--primary);color:#fff}

/* Layout */
.main-content{width:100%;padding:28px 24px}
.block{background:var(--card);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow);overflow:hidden;margin-bottom:24px}
.block:before{content:attr(data-title);display:block;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-weight:800;padding:14px 20px}
.block-body{padding:24px}

/* Forms */
.form-control{background:var(--bg-2);border:1px solid var(--border);color:#fff;border-radius:12px;padding:.9rem 1rem}
.form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.25) inset;background:#0d1526}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;border-radius:14px;padding:.9rem 1.3rem;font-weight:800}
.btn-primary:hover{filter:brightness(1.05);transform:translateY(-1px)}

/* Tables */
.table{color:var(--text-2)}
.table thead th{background:var(--bg-2);color:var(--text);font-weight:700;border-bottom:2px solid var(--border)}
.table-striped tbody tr:nth-of-type(odd){background:rgba(255,255,255,0.02)}
.table-striped tbody tr:hover{background:rgba(255,255,255,0.05)}

/* Responsive table for mobile */
@media (max-width:768px){
  .table thead{display:none}
  .table, .table tbody, .table tr, .table td{display:block;width:100%}
  .table tr{margin-bottom:15px;background:var(--bg-2);padding:12px;border-radius:10px}
  .table td{padding:6px 0;border:none}
  .table td::before{content:attr(data-label);font-weight:700;color:var(--muted);display:block;margin-bottom:4px}
}

/* Mobile nav wrap */
@media (max-width:992px){.user-menu{flex-wrap:wrap;justify-content:flex-end}}
</style>
</head>
<body>

<header class="modern-header">
  <div class="header-container">
    <div class="logo-section">
      <a href="index.php"><img src="logo.png" alt="logo"></a>
    </div>
    <div class="user-menu">
      <a class="nav-link-modern" href="index.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
      <a class="nav-link-modern" href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
      <a class="nav-link-modern" href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
  </div>
</header>


<div class="main-content">
  <div class="page-content">

    <div class="block" data-title="Add Crew">
      <div class="block-body">
        <form method="POST" action="add.php">
          <div class="form-group">
            <label class="form-control-label">Username</label>
            <input autocomplete="off" type="text" name="huser" placeholder="Username" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-control-label">Password</label>
            <input autocomplete="off" type="text" name="hpass" placeholder="Password" class="form-control">
          </div>
          <div class="form-group">
            <input type="submit" value="Add" class="btn btn-primary">
          </div>
        </form>
      </div>
    </div>

    <div class="block" data-title="Crews List">
      <div class="block-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>#</th><th>Username</th><th>Password</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $query = mysqli_query($conn,"SELECT * from handlers");
              if($query && mysqli_num_rows($query)>=1){
                $array = array_filter(mysqli_fetch_all($query,MYSQLI_ASSOC));
                foreach($array as $value){
                  echo "
                  <tr>
                    <td data-label='#'>{$value['id']}</td>
                    <td data-label='Username' data-micron='flicker' onclick='copyTextToClipboard(this)' style='cursor:pointer;'>{$value['username']}</td>
                    <td data-label='Password' data-micron='flicker' onclick='copyTextToClipboard(this)' style='cursor:pointer;'>{$value['password']}</td>
                    <td data-label='Action'><button type='button' onclick='remove_handler(\"{$value['id']}\");' title='Remove' class='btn btn-outline-light'><i class='fas fa-times'></i></button></td>
                  </tr>";
                }
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/toastr.min.js"></script>
<script src="https://unpkg.com/webkul-micron@1.1.6/dist/js/micron.min.js"></script>
<!-- GLOBAL TOASTR CONFIG (set ONCE, top-center) -->
<script>
toastr.options = {
  closeButton: true,
  progressBar: true,
  positionClass: "toast-top-center",
  preventDuplicates: true,
  newestOnTop: true,
  timeOut: 2200,
  extendedTimeOut: 1000,
  showDuration: 120,
  hideDuration: 120,
  showMethod: "fadeIn",
  hideMethod: "fadeOut"
};
</script>
<script>
function remove_handler(id){
  $.ajax({
    type:'POST',
    url:'inc/action.php?type=remove_handler',
    data:'userid='+id,
    success:function(data){
      var parsed=JSON.parse(data);
      if(parsed.status=='ok'){
        toastr["success"]("removed successfully");
        setTimeout(function(){location.reload()},2000);
      }else{
        toastr["error"]("failed to remove #"+id);
      }
    }
  })
}
</script>
<?php
if($_SESSION['add_handlers']=='success'){
  echo '<script>toastr["success"]("handler added successfully");</script>'; $_SESSION['add_handlers']='';
}elseif($_SESSION['add_handlers']=='error'){
  echo '<script>toastr["error"]("handler already exist")</script>'; $_SESSION['add_handlers']='';
}
?>
</body>
</html>

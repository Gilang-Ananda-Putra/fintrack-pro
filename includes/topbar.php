<?php
declare(strict_types=1);
if (!defined('APP_NAME') || !defined('BASE_URL')) require_once __DIR__ . '/../config/app.php';
$displayName = trim((string)($_SESSION['name'] ?? $_SESSION['username'] ?? 'User')); $avatar = $displayName !== '' ? strtoupper(mb_substr($displayName,0,1)) : 'U';
?>
<header class="bg-surface-container-lowest shadow-sm flex justify-between items-center w-full h-16 px-4 sm:px-8 sticky top-0 z-40 border-b border-outline-variant/20">
<div class="flex items-center gap-3"><button id="sidebar-toggle" type="button" class="md:hidden inline-flex h-10 w-10 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface"><span class="material-symbols-outlined">menu</span></button><span class="font-black text-on-surface text-lg">FinTrack Pro</span></div>
<div class="flex items-center gap-3"><button class="p-2 rounded-full text-on-surface-variant hover:bg-surface"><span class="material-symbols-outlined">notifications</span></button><div class="hidden sm:block text-right"><p class="text-sm font-semibold"><?= htmlspecialchars($displayName,ENT_QUOTES,'UTF-8');?></p><p class="text-xs text-on-surface-variant">Premium Member</p></div><div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-semibold"><?= htmlspecialchars($avatar,ENT_QUOTES,'UTF-8');?></div></div>
</header>
<script>const s=document.getElementById('app-sidebar'),b=document.getElementById('sidebar-backdrop'),t=document.getElementById('sidebar-toggle');if(s&&b&&t){const c=()=>{s.classList.add('-translate-x-full');b.classList.add('hidden')};t.onclick=()=>{s.classList.toggle('-translate-x-full');b.classList.toggle('hidden')};b.onclick=c;}</script>

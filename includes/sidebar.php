<?php
declare(strict_types=1);
if (!defined('BASE_URL')) require_once __DIR__ . '/../config/app.php';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$menuItems = [
['label'=>'Dashboard','icon'=>'dashboard','href'=>rtrim(BASE_URL,'/').'/dashboard/index.php','match'=>'/dashboard/'],
['label'=>'Transactions','icon'=>'receipt_long','href'=>rtrim(BASE_URL,'/').'/transactions/index.php','match'=>'/transactions/'],
['label'=>'Categories','icon'=>'category','href'=>rtrim(BASE_URL,'/').'/categories/index.php','match'=>'/categories/'],
['label'=>'Reports','icon'=>'assessment','href'=>rtrim(BASE_URL,'/').'/reports/index.php','match'=>'/reports/'],
['label'=>'Settings','icon'=>'settings','href'=>rtrim(BASE_URL,'/').'/settings/index.php','match'=>'/settings/'],
];
?>
<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-50 w-[280px] -translate-x-full border-r border-outline-variant bg-surface-container-lowest transition-transform duration-300 md:translate-x-0">
<div class="flex h-full flex-col">
<div class="p-6 border-b border-outline-variant/40 flex items-center gap-3"><div class="w-12 h-12 rounded-lg bg-primary-container flex items-center justify-center"><span class="material-symbols-outlined text-white">account_balance_wallet</span></div><div><h2 class="font-bold text-xl text-primary">FinTrack Pro</h2><p class="text-xs text-on-surface-variant">Premium Account</p></div></div>
<nav class="flex-1 overflow-y-auto p-3 space-y-1"><?php foreach($menuItems as $item): $active=str_contains($currentPath,$item['match']); ?><a href="<?= htmlspecialchars($item['href'],ENT_QUOTES,'UTF-8');?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= $active ? 'bg-surface text-primary font-semibold border-r-2 border-primary' : 'text-on-surface-variant hover:bg-surface'; ?>"><span class="material-symbols-outlined <?= $active?'icon-fill':''; ?>"><?= htmlspecialchars($item['icon'],ENT_QUOTES,'UTF-8');?></span><span><?= htmlspecialchars($item['label'],ENT_QUOTES,'UTF-8');?></span></a><?php endforeach; ?></nav>
<div class="p-3 border-t border-outline-variant/40"><a href="<?= htmlspecialchars(rtrim(BASE_URL,'/').'/auth/logout.php',ENT_QUOTES,'UTF-8');?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-600 hover:bg-rose-50"><span class="material-symbols-outlined">logout</span>Sign Out</a></div>
</div></aside><div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/40 md:hidden"></div>

<?php
declare(strict_types=1);

require_once __DIR__ . '/account-menu.php';

function render_public_header(string $active = ''): void
{
    $links = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => public_dashboard_href(), 'external' => false],
        ['key' => 'resume', 'label' => 'Resume', 'href' => root_url('resume.php'), 'external' => false],
        ['key' => 'ev-storage', 'label' => 'EV Storage', 'href' => root_url('ev-storage/index.php'), 'external' => true],
    ];
    ?>
<a class="skip-link" href="#mainContent">Skip to main content</a>
<header class="site-header" id="siteHeader">
    <a class="wordmark" href="<?= h(root_url('index.php#top')) ?>" aria-label="Gruber Companies home">
        <img class="header-brand-logo" src="<?= h(root_url('assets/images/gruber-main.png')) ?>" alt="Gruber">
    </a>
    <nav aria-label="Primary navigation">
        <?php foreach ($links as $link): ?>
            <a href="<?= h($link['href']) ?>"<?= $active === $link['key'] ? ' aria-current="page"' : '' ?><?= $link['external'] ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php render_public_account_dropdown(); ?>
    <button class="mobile-menu-toggle" type="button" aria-label="Open navigation" aria-controls="mobileNav" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</header>
<aside class="mobile-nav-drawer" id="mobileNav" aria-hidden="true" aria-label="Mobile navigation" tabindex="-1">
    <div class="mobile-nav-head">
        <img src="<?= h(root_url('assets/images/gruber-main.png')) ?>" alt="Gruber">
        <button type="button" data-mobile-menu-close aria-label="Close navigation">×</button>
    </div>
    <nav class="mobile-nav-links" aria-label="Mobile primary navigation">
        <?php foreach ($links as $link): ?>
            <a href="<?= h($link['href']) ?>"<?= $active === $link['key'] ? ' aria-current="page"' : '' ?><?= $link['external'] ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= h($link['label']) ?> <span aria-hidden="true">→</span></a>
        <?php endforeach; ?>
    </nav>
    <?php render_public_mobile_account_links(); ?>
    <div class="mobile-nav-contact">
        <span>Program contact</span>
        <strong>David Evans</strong>
        <a href="tel:+14802697433">(480) 269-7433</a>
    </div>
</aside>
<button class="mobile-nav-backdrop" type="button" data-mobile-menu-close aria-label="Close navigation" tabindex="-1"></button>
<?php
}

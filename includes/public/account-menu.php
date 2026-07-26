<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

function public_account_user(): ?array
{
    static $resolved = false;
    static $user = null;
    if ($resolved) return $user;
    $resolved = true;
    $user = current_user();
    return $user;
}

function public_account_is_logged_in(): bool
{
    return public_account_user() !== null;
}

function public_dashboard_href(): string
{
    return public_account_is_logged_in() ? app_url('dashboard.php') : root_url('demo.php');
}

function render_public_account_dropdown(): void
{
    $loggedIn = public_account_is_logged_in();
    ?>
    <div class="public-account-menu">
        <button class="public-account-button" type="button" aria-expanded="false" aria-controls="publicAccountDropdown">
            <span class="public-account-icon" aria-hidden="true">◎</span>
            <span>Account</span>
            <span class="public-account-chevron" aria-hidden="true">⌄</span>
        </button>
        <div class="public-account-dropdown" id="publicAccountDropdown" hidden>
            <div class="public-account-state">
                <span><?= $loggedIn ? 'Account access' : 'Guest access' ?></span>
                <strong><?= $loggedIn ? 'You are currently signed in' : 'You are currently logged out' ?></strong>
            </div>
            <?php if ($loggedIn): ?>
                <a href="<?= h(app_url('profile.php')) ?>"><strong>Profile settings</strong><small>Manage your account information</small></a>
                <a href="<?= h(root_url('demo.php')) ?>"><strong>Demo accounts</strong><small>Open the role-based demo account selector</small></a>
                <a href="<?= h(app_url('logout.php')) ?>"><strong>Sign out</strong><small>End your current session</small></a>
            <?php else: ?>
                <a href="<?= h(app_url('login.php')) ?>"><strong>Sign in</strong><small>Access your Gruber dashboard</small></a>
                <a href="<?= h(root_url('signup.php')) ?>"><strong>Create Account</strong><small>Request access to the system</small></a>
                <a href="<?= h(root_url('demo.php')) ?>"><strong>Demo accounts</strong><small>Explore the platform with a sample role</small></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function render_public_mobile_account_links(): void
{
    $loggedIn = public_account_is_logged_in();
    ?>
    <div class="mobile-nav-account">
        <span>Account</span>
        <?php if ($loggedIn): ?>
            <a href="<?= h(app_url('profile.php')) ?>">Profile settings</a>
            <a href="<?= h(root_url('demo.php')) ?>">Demo accounts</a>
            <a href="<?= h(app_url('logout.php')) ?>">Sign out</a>
        <?php else: ?>
            <a href="<?= h(app_url('login.php')) ?>">Sign in</a>
            <a href="<?= h(root_url('signup.php')) ?>">Create Account</a>
            <a href="<?= h(root_url('demo.php')) ?>">Demo accounts</a>
        <?php endif; ?>
    </div>
    <?php
}

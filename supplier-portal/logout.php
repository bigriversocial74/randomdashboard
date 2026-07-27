<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/supplier_portal.php';
supplier_portal_logout();session_regenerate_id(true);redirect_to(root_url('supplier-portal/login.php'));

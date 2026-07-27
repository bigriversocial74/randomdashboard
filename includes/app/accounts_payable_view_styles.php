<?php
declare(strict_types=1);
function accounts_payable_styles():string
{
    return '<style>
.ap-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0}.ap-tabs a{padding:10px 14px;border:1px solid var(--border);border-radius:999px;text-decoration:none}.ap-tabs a.active{background:#101828;color:#fff;border-color:#101828}
.ap-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(300px,.8fr);gap:18px}.ap-stack{display:grid;gap:18px}.ap-card{border:1px solid var(--border);border-radius:16px;padding:16px;background:var(--surface)}
.ap-card header{display:flex;justify-content:space-between;gap:12px}.ap-card dl{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:14px 0}.ap-card dt{font-size:12px;color:var(--muted)}.ap-card dd{margin:3px 0 0;font-weight:700}
.ap-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.ap-form label{display:grid;gap:6px}.ap-form .span-2{grid-column:1/-1}.ap-form textarea{min-height:92px}.ap-note{padding:14px;border-left:4px solid #d97706;background:#fff7ed;border-radius:10px}.ap-note strong{display:block;margin-bottom:4px}
@media(max-width:980px){.ap-grid{grid-template-columns:1fr}.ap-form{grid-template-columns:1fr}.ap-form .span-2{grid-column:auto}.ap-card dl{grid-template-columns:1fr}}
</style>';
}

<?php

/**
 * Menu permissions shown in the sidebar (AppLayout) and on Roles & Permissions.
 * Keys must stay stable; labels are Indonesian for UI.
 */
return [
    ['name' => 'menu.dashboard', 'label' => 'Dashboard', 'group' => 'Utama'],

    ['name' => 'menu.erp.accounting', 'label' => 'Accounting', 'group' => 'Modul ERP'],
    ['name' => 'menu.erp.sales', 'label' => 'Sales', 'group' => 'Modul ERP'],
    ['name' => 'menu.erp.purchasing', 'label' => 'Purchasing', 'group' => 'Modul ERP'],
    ['name' => 'menu.erp.inventory', 'label' => 'Inventory', 'group' => 'Modul ERP'],
    ['name' => 'menu.erp.projects', 'label' => 'Projects', 'group' => 'Modul ERP'],
    ['name' => 'menu.erp.hr', 'label' => 'HR', 'group' => 'Modul ERP'],
    ['name' => 'menu.erp.crm', 'label' => 'CRM', 'group' => 'Modul ERP'],
    ['name' => 'menu.erp.calendar', 'label' => 'Calendar', 'group' => 'Modul ERP'],
    ['name' => 'menu.erp.reporting', 'label' => 'Reporting', 'group' => 'Modul ERP'],

    ['name' => 'menu.cms.dashboard', 'label' => 'Dashboard CMS', 'group' => 'Website CMS'],
    ['name' => 'menu.cms.sites', 'label' => 'Landing sites', 'group' => 'Website CMS'],
    ['name' => 'menu.cms.media', 'label' => 'Media library', 'group' => 'Website CMS'],

    ['name' => 'menu.personal', 'label' => 'Beranda (Personal)', 'group' => 'Personal'],

    ['name' => 'menu.administration.users', 'label' => 'User', 'group' => 'Administrasi'],
    ['name' => 'menu.administration.roles', 'label' => 'Roles & permission', 'group' => 'Administrasi'],
    ['name' => 'menu.administration.erp_settings', 'label' => 'Pengaturan ERP', 'group' => 'Administrasi'],
];

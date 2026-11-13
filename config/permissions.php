<?php

/**
 * What a role can be allowed to do. The list is fixed here because each permission is checked
 * somewhere in the code (a route, a menu entry or a button); which role has which is edited in
 * Administración → Roles. Administrators always have all of them; users and roles stay for them only.
 */
return [
    'vendors.all' => 'Ver todos los proveedores',
    'documents.details' => 'Ver detalle de documentos',
    'labels.download' => 'Descargar etiquetas',
    'delivery.download' => 'Descargar instrucción de entrega',
    'versions.view' => 'Ver versiones anteriores',
    'versions.download' => 'Descargar versiones anteriores',
    'unreceived.view' => 'Ver partes no recibidas',
    'unreceived.download' => 'Descargar partes no recibidas',
    // Administración: one per menu entry
    'sync-runs.view' => 'Menú: Sincronizaciones',
    'activity.view' => 'Menú: Actividad de proveedores',
    'infor.vendors' => 'Menú Infor: Proveedores',
    'infor.parts' => 'Menú Infor: Números de parte',
    'infor.labels' => 'Menú Infor: Etiquetas',
    'infor.delivery' => 'Menú Infor: Instrucción de entrega',
    'infor.calendar' => 'Menú Infor: Calendario laboral',
];

<?php

/**
 * What a role can be allowed to do. The list is fixed here because each permission is checked
 * somewhere in the code (routes and buttons); which role has which is edited in Administración → Roles.
 * Administrators always have all of them.
 */
return [
    'vendors.all' => 'Ver todos los proveedores',
    'documents.details' => 'Ver el detalle de etiquetas e instrucción de entrega',
    'labels.download' => 'Descargar etiquetas (PDF)',
    'delivery.download' => 'Descargar instrucción de entrega (PDF y Excel)',
    'versions.view' => 'Ver y descargar versiones anteriores de los documentos',
    'unreceived.view' => 'Ver partes no recibidas',
    'unreceived.download' => 'Descargar reportes de partes no recibidas',
    // Administración: users and roles stay for administrators only
    'activity.view' => 'Ver la actividad de proveedores (Administración)',
    'sync-runs.view' => 'Ver las sincronizaciones (Administración)',
    'infor-data.view' => 'Ver los datos de Infor: proveedores, números de parte, etiquetas, instrucción de entrega y calendario laboral (Administración)',
];

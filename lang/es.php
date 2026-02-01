<?php
/**
 * Traducciones al español
 */

return [
    // Navegación
    'nav' => [
        'dashboard' => 'Panel de control',
        'books' => 'Libros',
        'types' => 'Tipos',
        'categories' => 'Categorías',
        'assignments' => 'Asignaciones',
        'import' => 'Importar Corán',
        'history' => 'Historial',
        'backup' => 'Copia de seguridad',
        'settings' => 'Configuración',
        'logout' => 'Cerrar sesión',
    ],

    // Autenticación
    'auth' => [
        'login' => 'Iniciar sesión',
        'password' => 'Contraseña',
        'wrong_password' => 'Contraseña incorrecta',
        'administration' => 'Administración',
    ],

    // Acciones comunes
    'actions' => [
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'delete' => 'Eliminar',
        'edit' => 'Editar',
        'add' => 'Agregar',
        'create' => 'Crear',
        'update' => 'Actualizar',
        'search' => 'Buscar',
        'filter' => 'Filtrar',
        'export' => 'Exportar',
        'import' => 'Importar',
        'close' => 'Cerrar',
        'confirm' => 'Confirmar',
        'back' => 'Atrás',
        'next' => 'Siguiente',
        'previous' => 'Anterior',
        'start' => 'Iniciar',
        'download' => 'Descargar',
    ],

    // Etiquetas comunes
    'labels' => [
        'name' => 'Nombre',
        'description' => 'Descripción',
        'icon' => 'Icono',
        'color' => 'Color',
        'order' => 'Orden',
        'status' => 'Estado',
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'type' => 'Tipo',
        'category' => 'Categoría',
        'language' => 'Idioma',
        'date' => 'Fecha',
        'actions' => 'Acciones',
        'total' => 'Total',
        'yes' => 'Sí',
        'no' => 'No',
        'book' => 'Libro',
    ],

    // Mensajes
    'messages' => [
        'success' => 'Operación exitosa',
        'error' => 'Ocurrió un error',
        'confirm_delete' => '¿Está seguro de que desea eliminar este elemento?',
        'no_results' => 'No se encontraron resultados',
        'loading' => 'Cargando...',
        'saving' => 'Guardando...',
        'saved' => 'Guardado',
        'deleted' => 'Eliminado',
        'required_field' => 'Este campo es obligatorio',
    ],

    // Panel de control
    'dashboard' => [
        'title' => 'Panel de control',
        'welcome' => 'Bienvenido a Sadaa',
        'stats' => [
            'types' => 'Tipos',
            'categories' => 'Categorías',
            'surahs' => 'Suras',
            'ayahs' => 'Aleyas',
            'books' => 'Libros',
            'languages' => 'Idiomas',
        ],
        'recent_imports' => 'Importaciones recientes',
        'quick_actions' => 'Acciones rápidas',
    ],

    // Libros
    'books' => [
        'title' => 'Gestión de libros',
        'add' => 'Agregar libro',
        'edit' => 'Editar libro',
        'book_title' => 'Título del libro',
        'author' => 'Autor',
        'chapters' => 'Capítulos',
        'verses' => 'Versículos',
    ],

    // Tipos
    'types' => [
        'title' => 'Gestión de tipos',
        'add' => 'Agregar tipo',
        'edit' => 'Editar tipo',
        'no_types' => 'No hay tipos disponibles',
    ],

    // Categorías
    'categories' => [
        'title' => 'Gestión de categorías',
        'add' => 'Agregar categoría',
        'edit' => 'Editar categoría',
        'select_type' => 'Seleccione un tipo',
        'no_categories' => 'No hay categorías disponibles',
    ],

    // Asignaciones
    'assignments' => [
        'title' => 'Asignaciones de versículos',
        'assign' => 'Asignar',
        'unassign' => 'Desasignar',
        'select_surah' => 'Seleccione una sura',
        'select_category' => 'Seleccione una categoría',
        'assigned_verses' => 'Versículos asignados',
    ],

    // Importación
    'import' => [
        'title' => 'Importar Corán',
        'select_languages' => 'Seleccione idiomas',
        'import_all' => 'Importar todo',
        'import_selected' => 'Importar seleccionados',
        'progress' => 'Progreso',
        'importing' => 'Importando...',
        'complete' => 'Importación completada',
        'failed' => 'Importación fallida',
    ],

    // Historial
    'history' => [
        'title' => 'Historial de importaciones',
        'date' => 'Fecha',
        'type' => 'Tipo',
        'status' => 'Estado',
        'details' => 'Detalles',
    ],

    // Configuración
    'settings' => [
        'title' => 'Configuración',
        'general' => 'General',
        'languages' => 'Idiomas',
        'app_name' => 'Nombre de la aplicación',
        'app_tagline' => 'Eslogan',
        'manage_languages' => 'Gestionar idiomas',
        'add_language' => 'Agregar idioma',
        'language_code' => 'Código de idioma',
        'language_name' => 'Nombre del idioma',
        'rtl' => 'De derecha a izquierda (RTL)',
        'source_language' => 'Idioma de origen',
        'quran_edition' => 'Edición del Corán',
    ],

    // Copia de seguridad y restauración
    'backup' => [
        'title' => 'Copia de seguridad y restauración',
        'export' => 'Exportar base de datos',
        'import' => 'Restaurar base de datos',
        'export_desc' => 'Descargue una copia completa de su base de datos.',
        'import_desc' => 'Restaure su base de datos desde un archivo de respaldo.',
        'format' => 'Formato',
        'tables_included' => 'Tablas incluidas',
        'download' => 'Descargar copia de seguridad',
        'restore' => 'Restaurar',
        'select_file' => 'Seleccionar archivo',
        'accepted_formats' => 'Formatos aceptados',
        'import_warning' => '¡Advertencia: Esta acción reemplazará todos los datos existentes!',
        'confirm_import' => '¿Está seguro de que desea restaurar? Todos los datos actuales serán reemplazados.',
        'confirm_delete' => '¿Está seguro de que desea eliminar esta copia de seguridad?',
        'export_success' => 'Exportación exitosa',
        'export_error' => 'Error en la exportación',
        'import_success' => 'Restauración exitosa',
        'import_error' => 'Error en la restauración',
        'upload_error' => 'Error al cargar el archivo',
        'invalid_format' => 'Formato de archivo no válido',
        'invalid_json' => 'Archivo JSON no válido o corrupto',
        'recent_backups' => 'Copias de seguridad recientes',
        'filename' => 'Nombre del archivo',
        'size' => 'Tamaño',
        'date' => 'Fecha',
    ],

    // Interfaz pública
    'public' => [
        'tagline' => 'Eco de sabiduría para el alma',
        'select_intention' => 'Seleccione una intención...',
        'no_category' => 'No hay categoría disponible',
        'change_theme' => 'Cambiar tema',
        'verse' => 'Versículo',
        'surah' => 'Sura',
        'play' => 'Reproducir',
        'pause' => 'Pausar',
        'next_verse' => 'Siguiente versículo',
        'previous_verse' => 'Versículo anterior',
        'share' => 'Compartir',
        'share_verse' => 'Compartir versículo',
        'copy' => 'Copiar',
        'copied' => '¡Copiado!',
        'quran' => 'El Sagrado Corán',
        'read_quran' => 'Leer el Corán',
        'decrease_font' => 'Reducir tamaño de letra',
        'increase_font' => 'Aumentar tamaño de letra',
        'prev_surah' => 'Sura anterior',
        'next_surah' => 'Sura siguiente',
        'prev_page' => 'Página anterior',
        'next_page' => 'Página siguiente',
        'previous' => 'Anterior',
        'next' => 'Siguiente',
        'meccan' => 'Mecana',
        'medinan' => 'Medinense',
        'verses' => 'versículos',
    ],

    // Traducciones JavaScript (para frontend)
    'js' => [
        'select_intention' => 'Seleccione una intención...',
        'no_category' => 'No hay categoría disponible',
        'loading' => 'Cargando...',
        'error' => 'Ocurrió un error',
        'copied' => '¡Copiado!',
        'play' => 'Reproducir',
        'pause' => 'Pausar',
        'confirm_delete' => '¿Está seguro de que desea eliminar?',
        'meccan' => 'Mecki',
        'medinan' => 'Medini',
        'verses' => 'versículos',
        'share_format' => 'Formato',
        'share_theme' => 'Tema',
        'theme_dark' => 'Oscuro',
        'theme_light' => 'Claro',
        'story' => 'Story',
        'square' => 'Cuadrado',
    ],
];
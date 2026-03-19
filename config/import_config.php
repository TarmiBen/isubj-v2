<?php
// Configuración para importaciones grandes
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 3600); // 1 hora
ini_set('max_input_time', 3600);
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');

// Configuración para MySQL
ini_set('mysql.connect_timeout', 300);
ini_set('default_socket_timeout', 300);

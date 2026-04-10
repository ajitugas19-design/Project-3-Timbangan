<?php
spl_autoload_register(function ($class) {
    // Simple namespace autoloader for Dompdf
    if (strpos($class, 'Dompdf\\') === 0) {
        $file = __DIR__ . '/dompdf/src/' . str_replace('\\', '/', substr($class, 7)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});
?>

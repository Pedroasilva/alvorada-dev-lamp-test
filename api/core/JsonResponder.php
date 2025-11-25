<?php
class JsonResponder
{
    public static function send(array $data, int $status = 200): void
    {
        // Limpa qualquer saída acidental (warnings HTML) antes de enviar JSON
        if (ob_get_level() === 0) {
            ob_start();
        }
        if (ob_get_length()) {
            ob_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public static function success(array $data, int $status = 200): void
    {
        self::send(['success' => true] + $data, $status);
    }

    public static function error(string $message, int $status = 400): void
    {
        self::send(['success' => false, 'error' => $message], $status);
    }
}

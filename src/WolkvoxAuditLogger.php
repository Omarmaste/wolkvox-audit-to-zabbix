<?php

class WolkvoxAuditLogger
{
    private $apiUrl;
    private $pointerFile;
    private $zabbixServer;
    private $zabbixHost;
    private $zabbixKey;
    private $token;
    private $server;

    public function __construct()
    {
        $this->loadEnv();

        $this->server = getenv('WOLKVOX_SERVER');
        $this->token = getenv('WOLKVOX_TOKEN');
        $this->zabbixServer = getenv('ZABBIX_SERVER');
        $this->zabbixHost = getenv('ZABBIX_HOST');
        $this->zabbixKey = getenv('ZABBIX_KEY');
        $this->pointerFile = __DIR__ . '/../wolkvox_last_timestamp.txt';

        if (!$this->token || !$this->server) {
            exit("Faltan variables de entorno necesarias (WOLKVOX_TOKEN o WOLKVOX_SERVER).\n");
        }

        $start = date('Ymd000000');
        $end = date('Ymd235959');
        echo "Consultando datos desde $start hasta $end\n";

        $this->apiUrl = "https://wv{$this->server}.wolkvox.com/api/v2/information.php?api=audit_log&date_ini=$start&date_end=$end";
    }

    private function loadEnv()
    {
        $envPath = __DIR__ . '/../.env';
        if (!file_exists($envPath)) {
            echo "Archivo .env no encontrado. Se esperan las variables de entorno exportadas.\n";
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') === false) continue;

            putenv(trim($line));
        }
    }

    public function run()
    {
        $lastCount = $this->getLastCount();
        $data = $this->fetchData();

        if (!$data || !isset($data['data']) || empty($data['data'])) {
            exit("No se recibieron datos desde la API.\n");
        }

        $newCount = count($data['data']);
        echo "Registros encontrados: $newCount (anteriores: $lastCount)\n";

        if ($newCount <= $lastCount) {
            exit("No hay datos nuevos.\n");
        }

        // Solo enviar los registros nuevos (de la parte superior)
        $diff = $newCount - $lastCount;
        $newItems = array_slice($data['data'], 0, $diff);

        foreach ($newItems as $item) {
            if (strpos($item['action'], 'ADMIN:') !== false) {
                $this->sendToZabbix($item);
            }
        }

        $this->updatePointer($newCount);
    }

    private function fetchData()
    {
        $headers = [
            "wolkvox_server: {$this->server}",
            "wolkvox-token: {$this->token}",
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function getLastCount()
    {
        return file_exists($this->pointerFile) ? (int)trim(file_get_contents($this->pointerFile)) : 0;
    }

    private function updatePointer($count)
    {
        file_put_contents($this->pointerFile, $count);
    }

    private function sendToZabbix($item)
    {
        $action = isset($item['action']) ? $item['action'] : '';
        $workstation = isset($item['workstation']) ? $item['workstation'] : '';
        $ip = isset($item['ip']) ? $item['ip'] : '';
        $user = isset($item['user']) ? $item['user'] : '';
        $message = "$action | $workstation | $ip | $user";

        $cmd = "/usr/bin/zabbix_sender -z {$this->zabbixServer} -s {$this->zabbixHost} -k {$this->zabbixKey} -o \"$message\"";
        shell_exec($cmd);
        echo "Enviado: $message\n";
    }
}

// Ejecutar el logger
$logger = new WolkvoxAuditLogger();
$logger->run();

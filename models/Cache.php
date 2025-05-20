<?php
class Cache {
    private $cacheDir;
    private $cacheDuration = 300; // 5 minutes default

    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../cache';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function set($key, $data, $duration = null) {
        $cacheFile = $this->getCacheFilePath($key);
        $cacheData = [
            'expires' => time() + ($duration ?? $this->cacheDuration),
            'data' => $data
        ];
        file_put_contents($cacheFile, serialize($cacheData));
    }

    public function get($key) {
        $cacheFile = $this->getCacheFilePath($key);
        if (!file_exists($cacheFile)) {
            return null;
        }

        $cacheData = unserialize(file_get_contents($cacheFile));
        if ($cacheData['expires'] < time()) {
            unlink($cacheFile);
            return null;
        }

        return $cacheData['data'];
    }

    public function delete($key) {
        $cacheFile = $this->getCacheFilePath($key);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public function clear() {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function getCacheFilePath($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
?>

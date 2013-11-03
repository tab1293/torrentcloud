<?php
/**
 * Magnet links library for PHP
 *
 * @link http://stackoverflow.com/questions/6634059/magnet-links-library-for-php
 */
/**
 * MagnetUri
 * 
 * Parser and validator for MagnetUris
 * 
 * Supports the following parameters:
 * 
 * @@support-params-start
 * dn (Display Name) - Filename
 * xl (eXact Length) - Size in bytes
 * xt (eXact Topic) - URN containing file hash
 * as (Acceptable Source) - Web link to the file online
 * xs (eXact Source) - P2P link.
 * kt (Keyword Topic) - Key words for search
 * mt (Manifest Topic) - link to the metafile that contains a list of magneto (MAGMA - MAGnet MAnifest)
 * tr (address TRacker) - Tracker URL for BitTorrent downloads
 * @@support-params-end
 */
class MagnetUri {
    private $def;
    private $uri;
    private $data;
    private $valid=false;
    private function initDefFromLines(array $lines) {
        $state = 0;
        foreach($lines as $line) {
            if ($state) {
              if ($line === ' * @@support-params-end') break;
              $line = ltrim($line, '* ');
              list($mix, $desc) = explode(' - ', $line);
              list($key, $name) = explode(' ', $mix, 2);
              $name = trim($name, '()');
              $this->def['keys'][$key] = $name;
              $norm = strtolower(str_replace(' ', '', $name));
              $this->def['names'][$norm] = $key;
              
            }
            if ($line === ' * @@support-params-start') $state = 1;
        }
        if (!$state || null === $this->def) {
            throw new Exception('Supported Params are undefined.');
        }
    }
    private function init() {
        $refl = new ReflectionClass($this);
        $this->initDefFromLines(explode("\n", str_replace("\r", '', $refl->getDocComment())));
    }
    private function getKey($param) {
        $param = strtolower($param);
        $key = false;
        if (isset($this->def['keys'][$param]))
            $key = $param;
        elseif (isset($this->def['names'][$param]))
            $key = $this->def['names'][$param];
        return $key;
    }
    public function __isset($name) {
        return false !== $this->getKey($name);
    }
    public function __get($name) {
        if ($name === 'valid') {
            return $this->valid;
        }
        if (false === $key = $this->getKey($name)) {
            $trace = debug_backtrace();
            trigger_error(
                'Undefined property ' . $name .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'],
                E_USER_NOTICE)
                ;                
            return null;
        }
        return isset($this->data[$key])?$this->data[$key]:'';
    }
    public function setUri($uri) {
        $this->uri = $uri;
        $this->data = array();
        $sheme = parse_url($uri, PHP_URL_SCHEME);
        # invalid URI scheme
        if ($sheme !== 'magnet') return $this->valid = false;
        $query = parse_url($uri, PHP_URL_QUERY);
        if ($query === false) return $this->valid = false;
        parse_str($query, $data);
        if (null == $data) return $this->valid = false;
        $this->data = $data;
        return $this->valid = true;
    }
    public function isValid() {
        return $this->valid;
    }
    public function getRawData() {
        return $this->data;
    }
    public function __construct($uri) {
        $this->init();
        $this->setUri($uri);
    }
    public function __toString() {
        ob_start();
        printf("Magnet URI: %s (%s)\n\n", $this->uri, $this->valid?'valid':'invalid');
        $l = max(array_map('strlen', $this->def['keys']));
        foreach($this->def['keys'] as $key => $name) {
            printf("  %'.-{$l}.{$l}s (%s): %s\n", $name.' ', $key, $this->$key);
        }
        return ob_get_clean();
    }
}
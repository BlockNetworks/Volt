<?php
namespace httpserver;

use Handlebars\Handlebars;

class ClientTask extends \Threaded{
    private $clientSocket;
    private $loader;
    private $logger;
    private $basePath;
    private $config;
    public function __construct($clientSocket, \ClassLoader $loader, \Logger $logger, $path, $config){
        $this->clientSocket = $clientSocket;
        $this->h = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n";
        $this->loader = clone $loader;
        $this->logger = $logger;
        $this->basePath = $path;
        $this->config = $config;
    }
    public function run(){
        $this->loader->register(true);
        $engine = new Handlebars([
            "loader" => new TemplateLoader($this, $this->basePath)
        ]);
        $buf = '';
        $headers = [];
        while ($message = socket_read($this->clientSocket, 2048, PHP_NORMAL_READ)) {
            $buf .= $message;
            if ($message == "\r") break;
            if ($message == "\n") continue;
            list($key, $val) = explode(' ', $message, 2);
            $headers[$key] = $val;
        }
        //$html .= socket_read($soc, 1+$length);
        if($buf == ""){
            return;
        }
        if (!$buf = trim($buf)) {
            return;
        }
        socket_getpeername($this->clientSocket, $ip);
        $msg = $this->h;
        $url = explode(" ", reset($headers))[0];
        $query = parse_url("http://e.co" . $url , PHP_URL_QUERY);
        $path = $this->sanitizePath($url);
        $verb = key($headers);
        switch(strtoupper($verb)){
            case 'GET':
                //$msg .= $this->getFile($path);
                $msg .= $engine->render($path, ["_request" => ["query" => $query, "path" => $path, "ip" => $ip]]);
                break;
            case 'POST':

                break;
        }
        socket_write($this->clientSocket, $msg);
        $this->close();
    }
    public function sanitizePath($path){
        $path = parse_url("http://e.co" . $path, PHP_URL_PATH); //Parse url won't work on relative URLs
        return str_replace("/.", "", str_replace("/..", "", $path));
    }
    public function getFile($path){
        if(substr($path, strlen($path)-1, 1) === "/") return $this->getFile($path . $this->getConfig()->get("special-pages")["index"]);
        elseif(!is_file($this->basePath . $path)){
            if(is_file($this->basePath . $this->getConfig()->get("special-pages")["404"])){
                return $this->getFile($this->getConfig()->get("special-pages")["404"]);
            }
            else{
                return "404! Page not found.";
            }
        }
        //elseif (substr($page, -4) == "html") socket_write($con, $this->h . $this->replace(file_get_contents($this->path . $page)));
        else return file_get_contents($this->basePath . $path);
    }
    /**
     * @return \Logger
     */
    public function getLogger(){
        return $this->logger;
    }

    /**
     * @return mixed
     */
    public function getConfig(){
        return $this->config;
    }

    public function close(){
        @socket_shutdown($this->clientSocket);
        socket_close($this->clientSocket);

        $this->clientSocket = false;
    }
}
<?php

namespace Vtiful\Request;

use \Swoole\Http\Request as SwooleRequest;
use \Illuminate\Http\Request as IlluminateRequest;
use \Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class RequestFactory
 *
 * @package Vtiful\Request
 */
class RequestFactory
{
    static $headerMap = [
        'x-real-ip'       => 'REMOTE_ADDR',
        'x-real-port'     => 'REMOTE_PORT',
        'server-protocol' => 'SERVER_PROTOCOL',
        'server-name'     => 'SERVER_NAME',
        'server-addr'     => 'SERVER_ADDR',
        'server-port'     => 'SERVER_PORT',
        'scheme'          => 'REQUEST_SCHEME',
    ];

    /**
     * @var array
     */
    protected $get;

    /**
     * @var array
     */
    protected $post;

    /**
     * @var array
     */
    protected $files;

    /**
     * @var array
     */
    protected $header;

    /**
     * @var array
     */
    protected $server;

    /**
     * @var array
     */
    protected $cookie;

    /**
     * @var SwooleRequest
     */
    protected $request;

    /**
     * Request constructor.
     *
     * @param SwooleRequest $request
     */
    public function __construct(SwooleRequest $request)
    {
        $this->get     = $request->get ?? [];
        $this->post    = $request->post ?? [];
        $this->cookie  = $request->cookie ?? [];
        $this->server  = $request->server ?? [];
        $this->header  = $request->header ?? [];
        $this->files   = $request->files ?? [];
        $this->request = $request;

        foreach ($this->header as $key => $value) {
            $this->server[$this->serverTransformation($key)] = $value;
        }

        $this->server = array_change_key_case($this->server, CASE_UPPER);

        $this->serverParametersTransformation();
    }

    /**
     * Request to IlluminateRequest
     *
     * @return IlluminateRequest
     */
    public function illuminateRequest()
    {
        IlluminateRequest::enableHttpMethodParameterOverride();

        $request = new \Symfony\Component\HttpFoundation\Request(
            $this->get,
            $this->post,
            [],
            $this->cookie,
            $this->files,
            $this->server,
            $this->request->rawContent()
        );

        $illuminateRequest = IlluminateRequest::createFromBase($request);

        if (0 === strpos($illuminateRequest->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($illuminateRequest->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])
        ) {
            parse_str($illuminateRequest->getContent(), $data);
            $illuminateRequest->request = new ParameterBag($data);
        }

        return $illuminateRequest;
    }

    /**
     * Server Transformation
     *
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    protected function serverTransformation(string $name): string
    {
        $newName = self::$headerMap[$name] ?? NULL;

        if ($newName === NULL) {
            return implode('_', ['http', str_replace('-', '_', $name)]);
        }

        return $newName;
    }

    /**
     * Server Parameters Transformation
     *
     * @return void
     */
    protected function serverParametersTransformation(): void
    {
        if (isset($this->server['REQUEST_SCHEME']) && $this->server['REQUEST_SCHEME'] === 'https') {
            $this->server['HTTPS'] = 'on';
        }

        if (strpos($this->server['REQUEST_URI'], '?') === false &&
            isset($this->server['QUERY_STRING']) &&
            strlen($this->server['QUERY_STRING']) > 0
        ) {
            $this->server['REQUEST_URI'] .= '?' . $this->server['QUERY_STRING'];
        }

        if (!isset($this->server['argv'])) {
            $this->server['argv'] = isset($GLOBALS['argv']) ? $GLOBALS['argv'] : [];
            $this->server['argc'] = isset($GLOBALS['argc']) ? $GLOBALS['argc'] : 0;
        }
    }
}
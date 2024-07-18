<?php

namespace AnserGateway\Filters;

use Exception;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use AnserGateway\Filters\Exception\FilterException;

class Filters
{
    // protected $filterPath = PROJECT_CONFIG . 'Filters.php';

    /**
     * The original config class
     *
     */
    protected $config;

    /**
     * The active IncomingRequest or CLIRequest
     *
     * @var Request
     */
    protected $request;

    /**
     * The active Response instance
     *
     * @var Response
     */
    protected $response;

    /**
     * Whether we've done initial processing
     * on the filter lists.
     *
     * @var bool
     */
    protected $initialized = false;

    /**
     * The processed filters that will
     * be used to check against.
     *
     * @var array
     */
    protected $filters = [
        'before' => [],
        'after'  => [],
    ];

    /**
     * The collection of filters' class names that will
     * be used to execute in each position.
     *
     * @var array
     */
    protected $filtersClass = [
        'before' => [],
        'after'  => [],
    ];

    /**
     * Any arguments to be passed to filters.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Any arguments to be passed to filtersClass.
     *
     * @var array
     */
    protected $argumentsClass = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = &$request;
        $this->setResponse($response);
        $this->config = new \Config\Filters();
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function run(string $uri, string $position = 'before')
    {
        $this->initialize($uri);

        foreach ($this->filtersClass[$position] as $className) {
            $class = new $className();
            
            if (! $class instanceof FilterInterface) {
                throw FilterException::forIncorrectInterface(get_class($class));
            }

            if ($position === 'before') {
                
                $result = $class->before($this->request, $this->argumentsClass[$className] ?? null);
                
                if ($result instanceof Request) {
                    $this->request = $result;

                    continue;
                }

                // If the response object was sent back,
                // then send it and quit.
                if ($result instanceof Response) {
                    // short circuit - bypass any other filters
                    return $result;
                }
                // Ignore an empty result
                if (empty($result)) {
                    continue;
                }

                return $result;
            }

            if ($position === 'after') {
                $result = $class->after($this->request, $this->response, $this->argumentsClass[$className] ?? null);

                if ($result instanceof Response) {
                    $this->response = $result;

                    continue;
                }
            }
        }

        return $position === 'before' ? $this->request : $this->response;
    }

    /**
     * 參考CodeIgniter Filter
     * 將路由相關的filter進行規則判斷，例如某些路由不需要套用某個filter
     *
     * @return Filters
     */
    public function initialize(?string $uri = null)
    {
        if ($this->initialized === true) {
            return $this;
        }

        $this->processGlobals($uri);

        // 預設jsonResponse最後執行
        if (in_array('jsonResponse', $this->filters['after'], true)
            && ($count = count($this->filters['after'])) > 1
            && $this->filters['after'][$count - 1] !== 'jsonResponse'
        ) {
            array_splice($this->filters['after'], array_search('jsonResponse', $this->filters['after'], true), 1);
            $this->filters['after'][] = 'jsonResponse';
        }

        $this->processAliasesToClass('before');
        $this->processAliasesToClass('after');

        $this->initialized = true;

        return $this;
    }

    /**
     * Restores instance to its pre-initialized state.
     * Most useful for testing so the service can be
     * re-initialized to a different path.
     */
    public function reset(): self
    {
        $this->initialized = false;

        $this->arguments = $this->argumentsClass = [];

        $this->filters = $this->filtersClass = [
            'before' => [],
            'after'  => [],
        ];

        return $this;
    }

    /**
     * Returns the arguments for a specified key, or all.
     *
     * @return mixed
     */
    public function getArguments(?string $key = null)
    {
        return $key === null ? $this->arguments : $this->arguments[$key];
    }

    /**
     * Returns the processed filters array.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Returns the filtersClass array.
     */
    public function getFiltersClass(): array
    {
        return $this->filtersClass;
    }

    /**
     * Ensures that a specific filter is on and enabled for the current request.
     *
     * Filters can have "arguments". This is done by placing a colon immediately
     * after the filter name, followed by a comma-separated list of arguments that
     * are passed to the filter when executed.
     *
     * @return Filters
     *
     * @deprecated Use enableFilters(). This method will be private.
     */
    public function enableFilter(string $name, string $when = 'before')
    {

        // 設定filter後夾帶的參數
        if (strpos($name, ':') !== false) {
            [$name, $params] = explode(':', $name);

            $params = explode(',', $params);
            array_walk($params, static function (&$item) {
                $item = trim($item);
            });

            $this->arguments[$name] = $params;
        }

        // 判斷別名
        if (class_exists($name)) {
            $this->config->aliases[$name] = $name;
        } elseif (! array_key_exists($name, $this->config->aliases)) {
            throw FilterException::forNoAlias($name);
        }


        //取得別名對應的class
        $classNames = (array) $this->config->aliases[$name];

        foreach ($classNames as $className) {
            $this->argumentsClass[$className] = $this->arguments[$name] ?? null;
        }

        // 將filter的別名與class放入對應的執行時期待用
        if (! isset($this->filters[$when][$name])) {
            $this->filters[$when][]    = $name;
            $this->filtersClass[$when] = array_merge($this->filtersClass[$when], $classNames);
        }

        return $this;
    }

    /**
     * Ensures that specific filters are on and enabled for the current request.
     *
     * Filters can have "arguments". This is done by placing a colon immediately
     * after the filter name, followed by a comma-separated list of arguments that
     * are passed to the filter when executed.
     *
     * @return Filters
     */
    public function enableFilters(array $names, string $when = 'before')
    {
        foreach ($names as $filter) {
            $this->enableFilter($filter, $when);
        }

        return $this;
    }

     /**
     * Add any applicable (not excluded) global filter settings to the mix.
     *
     * @param string $uri
     */
    protected function processGlobals(?string $uri = null)
    {
        if (! isset($this->config->globals) || ! is_array($this->config->globals)) {
            return;
        }

        $uri = strtolower(trim($uri ?? '', '/ '));

        // // Add any global filters, unless they are excluded for this URI
        $sets = ['before', 'after'];

        foreach ($sets as $set) {
            if (isset($this->config->globals[$set])) {
                // look at each alias in the group
                foreach ($this->config->globals[$set] as $alias => $rules) {
                    $keep = true;
                    if (is_array($rules)) {
                        // see if it should be excluded
                        if (isset($rules['except'])) {
                            // grab the exclusion rules
                            $check = $rules['except'];
                            if ($this->pathApplies($uri, $check)) {
                                $keep = false;
                            }
                        }
                    } else {
                        $alias = $rules; // simple name of filter to apply
                    }

                    if ($keep) {
                        $this->filters[$set][] = $alias;
                    }
                }
            }
        }

    }

    /**
     * Maps filter aliases to the equivalent filter classes
     *
     * @throws FilterException
     */
    protected function processAliasesToClass(string $position)
    {
        foreach ($this->filters[$position] as $alias => $rules) {
            if (is_numeric($alias) && is_string($rules)) {
                $alias = $rules;
            }

            if (! array_key_exists($alias, $this->config->aliases)) {
                throw FilterException::forNoAlias($alias);
            }

            if (is_array($this->config->aliases[$alias])) {
                $this->filtersClass[$position] = array_merge($this->filtersClass[$position], $this->config->aliases[$alias]);
            } else {
                $this->filtersClass[$position][] = $this->config->aliases[$alias];
            }
        }

        // when using enableFilter() we already write the class name in ->filtersClass as well as the
        // alias in ->filters. This leads to duplicates when using route filters.
        // Since some filters like rate limiters rely on being executed once a request we filter em here.
        $this->filtersClass[$position] = array_values(array_unique($this->filtersClass[$position]));
        // var_dump($this->filtersClass);
    }

    /**
     * Check paths for match for URI
     *
     * @param string       $uri   URI to test against
     * @param array|string $paths The path patterns to test
     *
     * @return bool True if any of the paths apply to the URI
     */
    private function pathApplies(string $uri, $paths)
    {
        // empty path matches all
        if (empty($paths)) {
            return true;
        }

        // make sure the paths are iterable
        if (is_string($paths)) {
            $paths = [$paths];
        }
        
        // treat each paths as pseudo-regex
        foreach ($paths as $path) {
            // need to escape path separators
            $path = str_replace('/', '\/', trim($path, '/ '));
            // need to make pseudo wildcard real
            $path = strtolower(str_replace('*', '.*', $path));
            // Does this rule apply here?
            if (preg_match('#^' . $path . '$#', $uri, $match) === 1) {
                return true;
            }
        }

        return false;
    }
}
